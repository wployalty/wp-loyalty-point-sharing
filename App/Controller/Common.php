<?php

namespace Wlps\App\Controller;

use Wlps\App\Emails\PointTransferReceiverEmail;
use Wlps\App\Emails\PointTransferSenderEmail;
use Wlps\App\Helpers\Input;
use Wlps\App\Helpers\WC;
use Wlps\App\Helpers\Validation;
use Wlps\App\Models\PointTransfers;
use Wlr\App\Helpers\Base;
use Wlr\App\Helpers\Pagination;
use Wlr\App\Helpers\Woocommerce;
use Wlr\App\Models\Users;

defined( 'ABSPATH' ) or die();

class Common {
	public static $input, $template, $woocommerce_helper, $base_helper;

	function __construct() {
		self::$input              = empty( self::$input ) ? new \Wlps\App\Helpers\Input() : self::$input;
		self::$woocommerce_helper = empty( self::$woocommerce_helper ) ? new Woocommerce() : self::$woocommerce_helper;
		self::$base_helper        = empty( self:: $base_helper ) ? new Base() : self::$base_helper;
	}

	public static function addMenu() {
		if ( WC::hasAdminPrivilege() ) {
			add_menu_page( __( 'WPLoyalty: Point Sharing', 'wp-loyalty-point-sharing' ), __( 'WPLoyalty: Point Sharing', 'wp-loyalty-point-sharing' ), 'manage_woocommerce', WLPS_PLUGIN_SLUG, [
				self::class,
				'renderMainPage'
			], 'dashicons-megaphone', 58 );
		}
	}

	public static function renderMainPage() {
		if ( ! WC::hasAdminPrivilege() ) {
			wp_die( __( "You don't have permission to access this page.", 'wp-loyalty-point-sharing' ) );
		}

		$view = Input::get( 'view', 'actions' );

		$params = [
			'current_view' => $view,
			'tab_content'  => null,
		];
		switch ( $view ) {
			default :
			case 'point_sharing':
				$params['tab_content'] = self::getActivityPage();
				break;

			case 'settings':
				$params['tab_content'] = self::getSettingsPage();
				break;

		}

		$file_path = WLPS_VIEW_PATH . '/Admin/main.php';
		WC::renderTemplate( $file_path, $params );
	}

	public static function addAssets() {
		$suffix = '.min';
		wp_enqueue_style(
			WLPS_PLUGIN_SLUG . '-admin-style',
			WLPS_PLUGIN_URL . 'Assets/Admin/Css/wlps-admin.css',
			array(),
			WLPS_PLUGIN_VERSION
		);
		wp_enqueue_script( WLPS_PLUGIN_SLUG . '-admin', WLPS_PLUGIN_URL . 'Assets/Admin/Js/wlps-admin.js', array( 'jquery' ), WLPS_PLUGIN_VERSION . '&t=' . strtotime( gmdate( "Y-m-d H:i:s" ) ), true );
		wp_enqueue_style( WLR_PLUGIN_SLUG . '-alertify', WLR_PLUGIN_URL . 'Assets/Admin/Css/alertify' . $suffix . '.css', array(), WLR_PLUGIN_VERSION );
		wp_enqueue_script( WLR_PLUGIN_SLUG . '-alertify', WLR_PLUGIN_URL . 'Assets/Admin/Js/alertify' . $suffix . '.js', array(), WLR_PLUGIN_VERSION . '&t=' . strtotime( gmdate( "Y-m-d H:i:s" ) ), true );
		$localize = array(
			'home_url'            => get_home_url(),
			'admin_url'           => admin_url(),
			'ajax_url'            => admin_url( 'admin-ajax.php' ),
			'saving_button_label' => __( "Saving...", "wp-loyalty-rules" ),
			'saved_button_label'  => __( "Save Changes", "wp-loyalty-rules" ),
		);
		wp_localize_script( WLPS_PLUGIN_SLUG . '-admin', 'wlps_localize_data', $localize );
	}

	public function addFrontendAssets() {
		$settings     = get_option( 'wlps_settings', [] );
		$max_transfer = isset( $settings['max_transfer_points'] ) ? (int) $settings['max_transfer_points'] : 0;
		$user_email   = self::$woocommerce_helper->get_login_user_email();
		$user_points  = self::$base_helper->getUserPoint( $user_email );

		// JS
		wp_enqueue_script( WLPS_PLUGIN_SLUG . '-frontend', WLPS_PLUGIN_URL . 'Assets/Site/Js/transfer-modal.js', array( 'jquery' ), WLPS_PLUGIN_VERSION . '&t=', true );
		$suffix = '.min';
		wp_enqueue_style( WLR_PLUGIN_SLUG . '-alertify', WLR_PLUGIN_URL . 'Assets/Admin/Css/alertify' . $suffix . '.css', array(), WLR_PLUGIN_VERSION );
		wp_enqueue_style( WLPS_PLUGIN_SLUG . '-frontend', WLPS_PLUGIN_URL . 'Assets/Site/Css/transfer-modal.css', array(), WLPS_PLUGIN_VERSION );
		wp_enqueue_script( WLR_PLUGIN_SLUG . '-alertify', WLR_PLUGIN_URL . 'Assets/Admin/Js/alertify' . $suffix . '.js', array(), WLR_PLUGIN_VERSION . '&t=' . strtotime( gmdate( "Y-m-d H:i:s" ) ), true );
		$localize = array(
			'home_url'              => get_home_url(),
			'admin_url'             => admin_url(),
			'ajax_url'              => admin_url( 'admin-ajax.php' ),
			'saving_button_label'   => __( "Saving...", "wp-loyalty-rules" ),
			'saved_button_label'    => __( "Save Changes", "wp-loyalty-rules" ),
			'max_transfer_points'   => $max_transfer,
			'available_user_points' => $user_points,
		);
		wp_localize_script( WLPS_PLUGIN_SLUG . '-frontend', 'wlps_frontend_data', $localize );
	}


	public static function getActivityPage() {
		global $wpdb;

		$search           = (string) ( $_POST['search'] ?? '' );
		$search           = sanitize_text_field( $search );
		$filter_order     = (string) ( $_POST['sort_order'] ?? 'id' );
		$filter_order_dir = (string) ( $_POST['sort_order_dir'] ?? 'ASC' );
		$point_sort       = (string) ( $_POST['point_sort'] ?? 'all' );
		$per_page         = (int) ( $_GET['per_page'] ?? 10 );
		$current_page     = (int) ( $_GET['page_number'] ?? 1 );
		$offset           = $per_page * ( $current_page - 1 );
		switch ( $point_sort ) {
			case 'pending':
				$where = $wpdb->prepare( "status = %s AND id > 0", array( 'pending' ) );
				break;
			case 'expired':
				$where = $wpdb->prepare( "status = %s AND id > 0", array( 'expired' ) );
				break;
			case 'completed':
				$where = $wpdb->prepare( "status = %s AND id > 0", array( 'completed' ) );
				break;
			case 'failed':
				$where = $wpdb->prepare( "status = %s AND (expire_date >= %s OR expire_date = 0) AND id > 0", array(
					'failed',
					strtotime( gmdate( "Y-m-d H:i:s" ) )
				) );
				break;
			case 'all':
			default:
				$where = "id > 0";
				break;
		}

		if ( ! empty( $search ) ) {
			$search_key = '%' . $search . '%';
			$where      .= $wpdb->prepare( " AND (sender_email LIKE %s OR recipient_email LIKE %s)", [
				$search_key,
				$search_key
			] );
		}

		// Sorting
		$order_by_sql = sanitize_sql_orderby( "$filter_order $filter_order_dir" );
		$order_by     = ! empty( $order_by_sql ) ? " ORDER BY $order_by_sql" : '';
		$limit_offset = $wpdb->prepare( " LIMIT %d OFFSET %d", [ $per_page, $offset ] );

		$table_name = $wpdb->prefix . 'wlr_point_transfers'; // <-- replace with your table

		// Fetch total count for pagination
		$total_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE $where" );

		// Fetch items for current page
		$items = $wpdb->get_results( "SELECT * FROM $table_name WHERE $where $order_by $limit_offset" );

		foreach ( $items as $item ) {
			$item->created_at = isset( $item->created_at ) && $item->created_at > 0 ? self::$woocommerce_helper->beforeDisplayDate( $item->created_at ) : '';
		}

		// Pagination parameters
		$params = [
			'totalRows'   => $total_count,
			'perPage'     => $per_page,
			'baseURL'     => admin_url( 'admin.php?' . http_build_query( [
					'page'       => WLPS_PLUGIN_SLUG,
					'view'       => 'point_sharing',
					'point_sort' => $point_sort,
					'search'     => $search
				] ) ),
			'currentPage' => $current_page,
		];

		$pagination = new Pagination( $params );

		$page_details = [
			'items'            => $items,
			'base_url'         => admin_url( 'admin.php?' . http_build_query( [
					'page' => WLPS_PLUGIN_SLUG,
					'view' => 'point_sharing'
				] ) ),
			'search'           => $search,
			'filter_order'     => $filter_order,
			'filter_order_dir' => $filter_order_dir,
			'pagination'       => $pagination,
			'per_page'         => $per_page,
			'page_number'      => $current_page,
			'app_url'          => admin_url( 'admin.php?page=' . WLPS_PLUGIN_SLUG ), // or your apps URL
			'point_sort'       => $point_sort,
			'filter_status'    => [
				'all'       => __( 'All', 'wp-loyalty-point-sharing' ),
				'pending'   => __( 'Pending', 'wp-loyalty-point-sharing' ),
				'completed' => __( 'Completed', 'wp-loyalty-point-sharing' ),
				'expired'   => __( 'Expired', 'wp-loyalty-point-sharing' )
			],
			'no_points_yet'    => WLPS_PLUGIN_URL . 'Assets/svg/no_points_yet.svg',
			'search_email'     => WLPS_PLUGIN_URL . 'Assets/svg/search.svg',
			'back'             => WLPS_PLUGIN_URL . 'Assets/svg/back.svg',
			'previous'         => WLPS_PLUGIN_URL . "Assets/svg/previous.svg",
			'wp_date_format'   => get_option( 'date_format', 'Y-m-d H:i:s' ),
		];

		$file_path = WLPS_VIEW_PATH . '/Admin/point-sharing.php';

		return WC::renderTemplate( $file_path, $page_details, false );
	}


	public static function getSettingsPage() {
		$options   = get_option( 'wlps_settings', array() );
		$args      = [
			'current_page'          => 'settings',
			'back_to_apps_url'      => admin_url( 'admin.php?' . http_build_query( [ 'page' => WLR_PLUGIN_SLUG ] ) ) . '#/apps',
			'wlps_setting_nonce'    => Woocommerce::create_nonce( 'wlps-setting-nonce' ),
			'back'                  => WLPS_PLUGIN_URL . "Assets/svg/back.svg",
			'previous'              => WLPS_PLUGIN_URL . "Assets/svg/previous.svg",
			'options'               => $options ?? [],
			'save'                  => WLPS_PLUGIN_URL . 'Assets/svg/save.svg',
			'manage_sender_email'   => admin_url( 'admin.php?' . http_build_query( [
					'page'    => 'wc-settings',
					'tab'     => 'email',
					'section' => 'WlpsSenderEmail'
				] ) ),
			'manage_receiver_email' => admin_url( 'admin.php?' . http_build_query( [
					'page'    => 'wc-settings',
					'tab'     => 'email',
					'section' => 'WlpsReceiverEmail'
				] ) ),
		];
		$file_path = get_theme_file_path( 'wp-loyalty-migration/Admin/settings.php' );
		if ( ! file_exists( $file_path ) ) {
			$file_path = WLPS_VIEW_PATH . '/Admin/settings.php';
		}

		return WC::renderTemplate( $file_path, $args, false );
	}

	/**
	 * Adds an extra action to the provided action list.
	 *
	 * This method adds an extra action key-value pair to the given action list array.
	 * If the provided action list is empty or not an array, the original list is returned unchanged.
	 * The added action key is 'migration_to_wployalty' with the corresponding label retrieved from the translation function.
	 *
	 * @param array $action_list The array of actions to which the extra action will be added.
	 *
	 * @return array The updated action list with the extra action added.
	 */
	public static function addExtraAction( $action_list ) {
		if ( empty( $action_list ) || ! is_array( $action_list ) ) {
			return $action_list;
		}
		$action_list['share_point_debit']  = __( 'Points Shared with another user', 'wp-loyalty-point-sharing' );
		$action_list['share_point_credit'] = __( 'Points received from another user', 'wp-loyalty-point-sharing' );

		return $action_list;
	}


	function saveSettings() {
		$response            = array();
		$validate_data_error = array();
		$wlps_nonce          = (string) self::$input->post( 'wlps_nonce' );
		if ( ! Woocommerce::hasAdminPrivilege() || ! Woocommerce::verify_nonce( $wlps_nonce, 'wlps-setting-nonce' ) ) {
			$response['error']   = true;
			$response['message'] = esc_html__( 'Settings not saved!', 'wp-loyalty-point-sharing' );
			wp_send_json( $response );
		}
		$key = (string) self::$input->post( 'option_key' );
		$key = Validation::validateInputAlpha( $key );
		if ( ! empty( $key ) ) {
			$data                  = self::$input->post();
			$need_to_remove_fields = array( 'option_key', 'action', 'wlps_nonce' );
			foreach ( $need_to_remove_fields as $field ) {
				unset( $data[ $field ] );
			}
			$validate_data = Validation::validateSettingsTab( $_REQUEST ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( is_array( $validate_data ) ) {
				$response['error'] = true;

				foreach ( $validate_data as $validate_key => $validate ) {
					$validate_data_error[ $validate_key ] = current( $validate );
				}
				$response['field_error'] = ( $validate_data_error );
				$response['message']     = __( 'Settings not saved!', 'wp-loyalty-rules' );
			}
			if ( ! isset( $response['error'] ) || ! $response['error'] ) {
				$data['email_template'] = isset( $_POST['email_template'] ) && ! empty( $_POST['email_template'] && trim( $_POST['email_template'] ) !== "" ) ? $_POST['email_template'] : $this->defaultEmailTemplate();
				update_option( $key, $data, true );
				do_action( 'wlps_after_save_settings', $data, $key );
				$response['error']   = false;
				$response['message'] = esc_html__( 'Settings saved successfully!', 'wp-loyalty-rules' );
			}
		} else {
			$response['error']   = true;
			$response['message'] = esc_html__( 'Settings not saved!', 'wp-loyalty-rules' );
		}
		wp_send_json( $response );
	}

	public static function wlps_render_share_points_modal() {
		$file = WLPS_VIEW_PATH . '/Site/share-points-modal.php';
		if ( file_exists( $file ) ) {
			include $file;
		}
	}

	public static function transferPoints() {
		$data = $_POST;

		$sender          = wp_get_current_user();
		$sender_email    = $sender->user_email;
		$sender_name     = $sender->display_name;
		$recipient_email = $data['transfer_email'];
		$transfer_points = intval( $data['transfer_points'] ?? 0 );

		$validation_result = Validation::validateTransferPointsInput( $recipient_email, $transfer_points );

		if ( is_array( $validation_result ) && ! empty( $validation_result ) ) {
			$field_errors = [];
			foreach ( $validation_result as $key => $errors ) {
				$field_errors[ $key ] = implode( ', ', $errors );
			}

			wp_send_json_error( [
				'field_error' => $field_errors,
				'message'     => implode( ' ', $field_errors )
			] );
		}
		self::validateTransferRequest( $sender, $recipient_email, $transfer_points );

		$timestamp    = strtotime( gmdate( "Y-m-d H:i:s" ) );
		$token        = wp_generate_password( 32, false );
		$confirm_link = add_query_arg( [
			'wlps_action' => 'confirm_transfer',
			'token'       => $token,
		], site_url() );

		$pointTransfers = new PointTransfers();
		$pointTransfers->saveData( [
			'sender_email'    => $sender_email,
			'recipient_email' => $recipient_email,
			'points'          => $transfer_points,
			'status'          => 'pending',
			'notes'           => sprintf(
				__( 'Transfer initiated — waiting for confirmation by %s.', 'wp-loyalty-point-sharing' ),
				$recipient_email
			),
			'token'           => $token,
			'created_at'      => $timestamp,
			'updated_at'      => $timestamp,
		] );

		\WC_Emails::instance();
		do_action( "wlr_send_point_transfer_sender_email", $sender_email, $sender_name, $recipient_email, $transfer_points, $confirm_link );

		wp_send_json_success( [ 'message' => __( 'Confirmation email sent. Please check your inbox.', 'wp-loyalty-point-sharing' ) ] );
	}


	/**
	 * Handle confirmation link from email
	 */
	public static function handleConfirmTransfer() {
		if ( ! isset( $_GET['wlps_action'] ) || $_GET['wlps_action'] !== 'confirm_transfer' ) {
			return;
		}

		$token         = sanitize_text_field( $_GET['token'] ?? '' );
		$redirect_url  = wc_get_account_endpoint_url( 'loyalty_reward' );
		$transferModel = new PointTransfers();
		$transfer      = $transferModel->findByToken( $token );

		self::validateTransferToken( $transfer, $token, $redirect_url );

		self::validateAuthorizedSender( $transfer, $redirect_url );

		self::validateSenderAndRecipient( $transfer, $transferModel, $redirect_url );

		self::performPointTransfer( $transfer );

		$transferModel->updateRow( [
			'status'     => 'completed',
			'notes'      => sprintf( __( 'Transfer successful: %d points sent to %s.', 'wp-loyalty-point-sharing' ), $transfer->points, $transfer->recipient_email ),
			'updated_at' => strtotime( gmdate( "Y-m-d H:i:s" ) ),
		], [ 'id' => $transfer->id ] );

		wc_add_notice( __( 'Points transfer successful!', 'wp-loyalty-point-sharing' ), 'success' );
		Common::sendRecieverEmail( $transfer );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	private static function validateTransferRequest( $sender, $recipient_email, $transfer_points ) {
//		if ( empty( $recipient_email ) || empty( $transfer_points ) ) {
//			wp_send_json_error( [ 'message' => __( 'Email and points are required.', 'wp-loyalty-point-sharing' ) ] );
//		}

		if ( $recipient_email === $sender->user_email ) {
			wp_send_json_error( [ 'message' => __( 'Recipient email and logged in email must not be same.', 'wp-loyalty-point-sharing' ) ] );
		}

//		if ( ! is_email( $recipient_email ) ) {
//			wp_send_json_error( [ 'message' => __( 'Please enter a valid email address.', 'wp-loyalty-point-sharing' ) ] );
//		}

//		if ( $transfer_points <= 0 ) {
//			wp_send_json_error( [ 'message' => __( 'Points must be greater than 0.', 'wp-loyalty-point-sharing' ) ] );
//		}


		$recipient = Common::getLoyaltyUser( $recipient_email );
		if ( is_object( $recipient ) && $recipient->is_allow_send_email < 0 ) {
			wp_send_json_error( [ 'message' => __( 'Recipient does not allow receiving point transfer emails.', 'wp-loyalty-point-sharing' ) ] );
		}

		$sender = Common::getLoyaltyUser( $sender->user_email );
		if ( is_object( $sender ) && $sender->is_allow_send_email < 0 ) {
			wp_send_json_error( [ 'message' => __( 'Please Turn on Email Opt-in to transfer points.', 'wp-loyalty-point-sharing' ) ] );
		}

		if ( self::$woocommerce_helper->isBannedUser( $recipient_email ) ) {
			wp_send_json_error( [ 'message' => __( 'The recipient user is banned cannot transfer points.', 'wp-loyalty-point-sharing' ) ] );
		}

		$user_points = self::$base_helper->getPointBalanceByEmail( $sender->user_email );
		if ( $user_points < $transfer_points ) {
			wp_send_json_error( [ 'message' => __( 'Not enough Points.', 'wp-loyalty-point-sharing' ) ] );
		}
	}


	private static function validateTransferToken( $transfer, $token, $redirect_url ) {
		if ( empty( $token ) || ! $transfer ) {
			wc_add_notice( __( 'Invalid or expired transfer link.', 'wp-loyalty-point-sharing' ), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		if ( $transfer->status !== 'pending' ) {
			wc_add_notice( __( 'This link has already been used.', 'wp-loyalty-point-sharing' ), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		if ( strtotime( gmdate( "Y-m-d H:i:s" ) ) > intval( $transfer->created_at ) + 15 * MINUTE_IN_SECONDS ) {
			$pointTransfers = new PointTransfers();
			$pointTransfers->updateRow( [
				'status'     => 'expired',
				'notes'      => __( 'Transfer failed due to expired confirmation link.', 'wp-loyalty-point-sharing' ),
				'updated_at' => strtotime( gmdate( "Y-m-d H:i:s" ) ),
			], [ 'id' => $transfer->id ] );

			wc_add_notice( __( 'This transfer link has expired.', 'wp-loyalty-point-sharing' ), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}


	private static function validateAuthorizedSender( $transfer, $redirect_url ) {
		$current_user = wp_get_current_user();
		if ( ! $current_user->exists() ) {
			wc_add_notice( __( 'Please log in to confirm your point transfer.', 'wp-loyalty-point-sharing' ), 'error' );
			wp_safe_redirect( wp_login_url( $_SERVER['REQUEST_URI'] ) );
			exit;
		}

		if ( $current_user->user_email !== $transfer->sender_email ) {
			$pointTransfers = new PointTransfers();
			$pointTransfers->updateRow( [
				'status'     => 'failed',
				'notes'      => __( 'Transfer failed: unauthorized user tried to confirm.', 'wp-loyalty-point-sharing' ),
				'updated_at' => strtotime( gmdate( "Y-m-d H:i:s" ) ),
			], [ 'id' => $transfer->id ] );

			wc_add_notice( __( 'You are not authorized to confirm this transfer.', 'wp-loyalty-point-sharing' ), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}


	private static function validateSenderAndRecipient( $transfer, $transferModel, $redirect_url ) {
		if ( self::$woocommerce_helper->isBannedUser( $transfer->recipient_email ) ) {
			$transferModel->updateRow( [
				'status'     => 'failed',
				'notes'      => __( 'Transfer failed: recipient account is banned.', 'wp-loyalty-point-sharing' ),
				'updated_at' => strtotime( gmdate( "Y-m-d H:i:s" ) ),
			], [ 'id' => $transfer->id ] );

			wc_add_notice( __( 'This user is banned due to security concerns.', 'wp-loyalty-point-sharing' ), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		$user_points = self::$base_helper->getPointBalanceByEmail( $transfer->sender_email );
		if ( $user_points < $transfer->points ) {
			$transferModel->updateRow( [
				'status'     => 'failed',
				'notes'      => __( 'Transfer failed: Not Enough Points.', 'wp-loyalty-point-sharing' ),
				'updated_at' => strtotime( gmdate( "Y-m-d H:i:s" ) ),
			], [ 'id' => $transfer->id ] );

			wc_add_notice( __( 'Not enough points available for this transfer.', 'wp-loyalty-point-sharing' ), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}


	private static function performPointTransfer( $transfer ) {
		$sender_email    = $transfer->sender_email;
		$recipient_email = $transfer->recipient_email;
		$points          = intval( $transfer->points );

		// Debit
		self::$base_helper->addExtraPointAction(
			'share_point_debit',
			$points,
			[
				'user_email'          => $sender_email,
				'action_type'         => 'share_point_debit',
				'action_process_type' => 'reduce_point',
				'note'                => sprintf( __( '%s Transferred %d points to %s', 'wp-loyalty-point-sharing' ), $sender_email, $points, $recipient_email ),
				'customer_note'       => sprintf( __( '%s Sent %d points to %s', 'wp-loyalty-point-sharing' ), $sender_email, $points, $recipient_email ),
			],
			'debit',
			true
		);

		// Credit
		self::$base_helper->addExtraPointAction(
			'share_point_credit',
			$points,
			[
				'user_email'          => $recipient_email,
				'action_type'         => 'share_point_credit',
				'action_process_type' => 'add_point',
				'note'                => sprintf( __( '%s Received %d points from %s', 'wp-loyalty-point-sharing' ), $recipient_email, $points, $sender_email ),
				'customer_note'       => sprintf( __( '%s Received %d points from %s', 'wp-loyalty-point-sharing' ), $recipient_email, $points, $sender_email ),
			],
			'credit',
			true
		);
	}


	public static function sendRecieverEmail( $transfer ) {
		if ( empty( $transfer->recipient_email ) ) {
			return;
		}
		$receiverAccountLink = wc_get_account_endpoint_url( 'loyalty_reward' );
		$receiver            = get_user_by( 'email', $transfer->recipient_email );
		$sender              = wp_get_current_user();
		\WC_Emails::instance();
		do_action( "wlr_send_point_transfer_reciever_email", $transfer->recipient_email,
			$receiver->display_name ?? " ",
			$sender->display_name,
			$transfer->points,
			$receiverAccountLink );
	}


	public function defaultEmailTemplate() {
		return '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                            <tbody>
                                            <tr>
                                                <td style="word-wrap: break-word;font-size: 0px;padding: 0px;" align="left">
                                                    <div style="cursor:auto;font-family: Arial;font-size:16px;line-height:24px;text-align:left;">
                                                        <h3 style="display: block;margin: 0 0 40px 0; color: #333;">' . esc_attr__( '{wlr_expiry_points} {wlr_points_label} are about to expire', 'wp-loyalty-rules' ) . '</h3>
                                                        <p style="display: block;margin: 0 0 40px 0; color: #333;">' . esc_attr__( 'Redeem your hard earned {wlr_points_label} before they expire on {wlr_expiry_date}', 'wp-loyalty-rules' ) . '</p>
                                                        <a href="{wlr_shop_url}" target="_blank"> ' . esc_attr__( 'Shop & Redeem Now', 'wp-loyalty-rules' ) . '</a>
                                                    </div>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>';
	}

	public static function getLoyaltyUser( $email ) {
		if ( empty( $email ) ) {
			return false;
		}
		$user_model = new Users();

		return $user_model->getQueryData( [
			'user_email' => [
				'operator' => '=',
				'value'    => $email
			]
		], '*', [], false, true );
	}

}