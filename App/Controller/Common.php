<?php

namespace Wlps\App\Controller;

use Wlps\App\Emails\PointTransferSenderEmail;
use Wlps\App\Helpers\Input;
use Wlps\App\Helpers\WC;
use Wlps\App\Helpers\Validation;
use Wlps\App\Models\PointTransfers;
use Wlr\App\Helpers\Base;
use Wlr\App\Helpers\Woocommerce;

defined( 'ABSPATH' ) or die();

class Common {
	public static $input, $template;

	function __construct() {
		self::$input = empty( self::$input ) ? new \Wlps\App\Helpers\Input() : self::$input;
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

		// Now render wrapper template with everything
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
		wp_enqueue_script( WLPS_PLUGIN_SLUG . '-admin', WLPS_PLUGIN_URL . 'Assets/Admin/Js/wlps-admin.js', array( 'jquery' ), WLPS_PLUGIN_VERSION . '&t=' . time(), true );
		wp_enqueue_style( WLR_PLUGIN_SLUG . '-alertify', WLR_PLUGIN_URL . 'Assets/Admin/Css/alertify' . $suffix . '.css', array(), WLR_PLUGIN_VERSION );
		wp_enqueue_script( WLR_PLUGIN_SLUG . '-alertify', WLR_PLUGIN_URL . 'Assets/Admin/Js/alertify' . $suffix . '.js', array(), WLR_PLUGIN_VERSION . '&t=' . time(), true );
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
		$woo          = Woocommerce::getInstance();
		$user_email   = $woo->get_login_user_email();
		$base         = new Base();
		$user_points  = $base->getUserPoint( $user_email );

		// JS
		wp_enqueue_script( WLPS_PLUGIN_SLUG . '-frontend', WLPS_PLUGIN_URL . 'Assets/Site/Js/transfer-modal.js', array( 'jquery' ), WLPS_PLUGIN_VERSION . '&t=', true );
		$suffix = '.min';
		wp_enqueue_style( WLR_PLUGIN_SLUG . '-alertify', WLR_PLUGIN_URL . 'Assets/Admin/Css/alertify' . $suffix . '.css', array(), WLR_PLUGIN_VERSION );
		wp_enqueue_style( WLPS_PLUGIN_SLUG . '-frontend', WLPS_PLUGIN_URL . 'Assets/Site/Css/transfer-modal.css', array(), WLPS_PLUGIN_VERSION );
		wp_enqueue_script( WLR_PLUGIN_SLUG . '-alertify', WLR_PLUGIN_URL . 'Assets/Admin/Js/alertify' . $suffix . '.js', array(), WLR_PLUGIN_VERSION . '&t=' . time(), true );
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
		$args        = [
			'current_page'     => 'actions',
			'back_to_apps_url' => admin_url( 'admin.php?' . http_build_query( [ 'page' => WLR_PLUGIN_SLUG ] ) ) . '#/apps',
			'previous'         => WLPS_PLUGIN_URL . "Assets/svg/previous.svg",
			'back'             => WLPS_PLUGIN_URL . "Assets/svg/back.svg",
			'search_email'     => WLPS_PLUGIN_URL . "Assets/svg/search.svg",
		];
		$sample_file = WLPS_VIEW_PATH . '/Admin/sample-data.php';
		if ( file_exists( $sample_file ) ) {
			$args['items'] = include $sample_file;
		} else {
			$args['items'] = [];
		}

		$file_path = get_theme_file_path( 'wp-loyalty-point-sharing/Admin/point-sharing.php' );
		if ( ! file_exists( $file_path ) ) {
			$file_path = WLPS_VIEW_PATH . '/Admin/point-sharing.php';
		}

		return WC::renderTemplate( $file_path, $args, false );
	}


	public static function getSettingsPage() {
		$options   = get_option( 'wlps_settings', array() );
		$args      = [
			'current_page'          => 'settings',
			'back_to_apps_url'      => admin_url( 'admin.php?' . http_build_query( [ 'page' => WLR_PLUGIN_SLUG ] ) ) . '#/apps',
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

	function saveSettings() {
		$response            = array();
		$validate_data_error = array();
		$wlps_nonce          = (string) self::$input->post( 'wlps_nonce' );
//		if ( ! Woocommerce::hasAdminPrivilege() || ! Woocommerce::verify_nonce( $wlps_nonce, 'wlps-setting-nonce' ) ) {
//			$response['error']   = true;
//			$response['message'] = esc_html__( 'Settings not saved!', 'wp-loyalty-rules' );
//			wp_send_json( $response );
//		}
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
//				$expire_points               = new ExpirePoints();
//				$data['enable_expire_email'] = isset( $data['enable_expire_email'] ) && $data['enable_expire_email'] > 0 ? $data['enable_expire_email'] : 0;
				//phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
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

	/**
	 * Handle request to initiate a transfer.
	 */
	public static function transferPoints() {
		$data = $_POST;

		$sender       = wp_get_current_user();
		$sender_id    = $sender->ID;
		$sender_email = $sender->user_email;
		$sender_name  = $sender->display_name;

		$recipient_email = isset( $data['transfer_email'] ) ? sanitize_email( $data['transfer_email'] ) : '';
		$transfer_points = isset( $data['transfer_points'] ) ? intval( $data['transfer_points'] ) : 0;

		if ( empty( $recipient_email ) || empty( $transfer_points ) ) {
			wp_send_json_error( [ 'message' => __( 'Email and points are required.', 'wp-loyalty-point-sharing' ) ] );
		}
		if ( ! is_email( $recipient_email ) ) {
			wp_send_json_error( [ 'message' => __( 'Please enter a valid email address.', 'wp-loyalty-point-sharing' ) ] );
		}
		if ( $transfer_points <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Points must be greater than 0.', 'wp-loyalty-point-sharing' ) ] );
		}

		$timestamp = time();
		$token     = wp_generate_password( 32, false ); // long random token

		$transferModel = new PointTransfers();
		$transferModel->saveData( [
			'sender_email'    => $sender_email,
			'recipient_email' => $recipient_email,
			'points'          => $transfer_points,
			'status'          => 'pending',
			'token'           => $token,
			'created_at'      => $timestamp,
			'updated_at'      => $timestamp,
		] );

		$confirm_link = add_query_arg( [
			'wlps_action' => 'confirm_transfer',
			'token'       => $token,
		], site_url() );


		// Send email
		$email = new PointTransferSenderEmail();
		$email->trigger(
			$sender_email,
			$sender_name,
			$recipient_email,
			$transfer_points,
			$confirm_link
		);

		wp_send_json_success( [ 'message' => __( 'Confirmation email sent. Please check your inbox.', 'wp-loyalty-point-sharing' ) ] );
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

	/**
	 * Handle confirmation link from email
	 */
	public static function handleConfirmTransfer() {
		if ( ! isset( $_GET['wlps_action'] ) || $_GET['wlps_action'] !== 'confirm_transfer' ) {
			return;
		}

		$token        = sanitize_text_field( $_GET['token'] ?? '' );
		$redirect_url = wc_get_account_endpoint_url( 'loyalty_reward' );

		if ( empty( $token ) ) {
			wc_add_notice( __( 'Invalid transfer link.', 'wp-loyalty-point-sharing' ), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		$transferModel = new PointTransfers();
		$transfer      = $transferModel->findByToken( $token );

		if ( ! $transfer ) {
			wc_add_notice( __( 'Transfer not found.', 'wp-loyalty-point-sharing' ), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		// Check expiry (5–15 min)
		if ( time() > intval( $transfer->created_at ) + 5 * MINUTE_IN_SECONDS ) {
			$transferModel->updateRow( [ 'status' => 'expired', 'updated_at' => time() ], [ 'id' => $transfer->id ] );
			wc_add_notice( __( 'This transfer link has expired.', 'wp-loyalty-point-sharing' ), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		// Optional: restrict to sender if logged in
		$current_user = wp_get_current_user();
		if ( $current_user->ID && $current_user->user_email !== $transfer->sender_email ) {
			wc_add_notice( __( 'You are not authorized to confirm this transfer.', 'wp-loyalty-point-sharing' ), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		// Check sender points
		$base        = new Base();
		$user_points = $base->getUserPoint( $transfer->sender_email );
		if ( $user_points < $transfer->points ) {
			wc_add_notice( __( 'Not enough points available for this transfer.', 'wp-loyalty-point-sharing' ), 'error' );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		// Perform transfer
		do_action( 'wlps_process_transfer', $transfer );

		// Update status
		$transferModel->updateRow( [ 'status' => 'completed', 'updated_at' => time() ], [ 'id' => $transfer->id ] );

		wc_add_notice( __( 'Points transfer successful!', 'wp-loyalty-point-sharing' ), 'success' );
		wp_safe_redirect( $redirect_url );
		exit;
	}

}