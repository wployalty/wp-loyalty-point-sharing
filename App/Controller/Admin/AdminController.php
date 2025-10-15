<?php

namespace Wlps\App\Controller\Admin;

use Wlps\App\Helpers\Input;
use Wlps\App\Helpers\Pagination;
use Wlps\App\Helpers\Validation;
use Wlps\App\Helpers\WlpsUtil;

class AdminController {
	public static function addMenu() {
		if ( WlpsUtil::hasAdminPrivilege() ) {
			add_menu_page( __( 'WPLoyalty: Point Sharing', 'wp-loyalty-point-sharing' ), __( 'WPLoyalty: Point Sharing', 'wp-loyalty-point-sharing' ), 'manage_woocommerce', WLPS_PLUGIN_SLUG, [
				self::class,
				'renderMainPage'
			], 'dashicons-megaphone', 58 );
		}
	}

	public static function renderMainPage() {
		if ( ! WlpsUtil::hasAdminPrivilege() ) {
			wp_die( __( "You don't have permission to access this page.", 'wp-loyalty-point-sharing' ) );
		}

		$view = Input::get( 'view', 'point_sharing' );

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

		$file_path = get_theme_file_path( 'wp-loyalty-point-sharing/Admin/main.php' );
		if ( ! file_exists( $file_path ) ) {
			$file_path = WLPS_VIEW_PATH . '/Admin/main.php';
		}

		return WlpsUtil::renderTemplate( $file_path, $params );
	}

	public static function getActivityPage() {

		global $wpdb;

		$search           = (string) Input::get( 'search', '' );
		$search           = sanitize_text_field( $search );
		$filter_order     = (string) Input::get( 'sort_order', 'id' );
		$filter_order_dir = (string) Input::get( 'sort_order_dir', 'ASC' );
		$status_sort      = (string) Input::get( 'status_sort', 'all' );
		$per_page         = (int) Input::get( 'per_page', 10 );
		$current_page     = (int) Input::get( 'page_number', 1 );
		$offset           = $per_page * ( $current_page - 1 );
		switch ( $status_sort ) {
			case 'pending':
				$where = $wpdb->prepare( "status = %s AND id > 0", [ 'pending' ] );
				break;
			case 'expired':
				$where = $wpdb->prepare( "status = %s AND id > 0", [ 'expired' ] );
				break;
			case 'completed':
				$where = $wpdb->prepare( "status = %s AND id > 0", [ 'completed' ] );
				break;
			case 'failed':
				$where = $wpdb->prepare( "status = %s AND id > 0", [ 'failed' ] );
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

		$table_name = $wpdb->prefix . 'wlr_point_transfers';

		// Fetch total count for pagination
		$total_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE $where" );

		// Fetch items for current page
		$items = $wpdb->get_results( "SELECT * FROM $table_name WHERE $where $order_by $limit_offset" );

		foreach ( $items as $item ) {
			$item->created_at = isset( $item->created_at ) && $item->created_at > 0 ? WlpsUtil::beforeDisplayDate( $item->created_at ) : '';
		}

		// Pagination parameters
		$params = [
			'totalRows'   => $total_count,
			'perPage'     => $per_page,
			'baseURL'     => admin_url( 'admin.php?' . http_build_query( [
					'page'        => WLPS_PLUGIN_SLUG,
					'view'        => 'point_sharing',
					'status_sort' => $status_sort,
					'search'      => $search
				] ) ),
			'currentPage' => $current_page,
		];

		$pagination = new Pagination( $params );

		$page_details = [
			'items'            => $items,
			'base_url'         => admin_url( 'admin.php?' . http_build_query( [
					'page' => WLPS_PLUGIN_SLUG,
					'view' => 'point_sharing',
				] ) ),
			'search'           => $search,
			'filter_order'     => $filter_order,
			'filter_order_dir' => $filter_order_dir,
			'pagination'       => $pagination,
			'per_page'         => $per_page,
			'page_number'      => $current_page,
			'app_url'          => admin_url( 'admin.php?' . http_build_query( [ 'page' => WLR_PLUGIN_SLUG ] ) ) . '#/apps',
			'status_sort'      => $status_sort,
			'filter_status'    => [
				'all'       => __( 'All', 'wp-loyalty-point-sharing' ),
				'pending'   => __( 'Pending', 'wp-loyalty-point-sharing' ),
				'completed' => __( 'Completed', 'wp-loyalty-point-sharing' ),
				'expired'   => __( 'Expired', 'wp-loyalty-point-sharing' ),
				'failed'    => __( 'Failed', 'wp-loyalty-point-sharing' )
			],
			'no_points_yet'    => WLPS_PLUGIN_URL . 'Assets/svg/no_points_yet.svg',
			'search_email'     => WLPS_PLUGIN_URL . 'Assets/svg/search.svg',
			'back'             => WLPS_PLUGIN_URL . 'Assets/svg/back.svg',
			'previous'         => WLPS_PLUGIN_URL . "Assets/svg/previous.svg",
			'wp_date_format'   => get_option( 'date_format', 'Y-m-d H:i:s' ),
		];

		$file_path = get_theme_file_path( 'wp-loyalty-point-sharing/Admin/point-sharing.php' );
		if ( ! file_exists( $file_path ) ) {
			$file_path = WLPS_VIEW_PATH . '/Admin/point-sharing.php';
		}

		return WlpsUtil::renderTemplate( $file_path, $page_details, false );
	}

	public static function getSettingsPage() {
		$options   = get_option( 'wlps_settings', [] );
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
		$file_path = get_theme_file_path( 'wp-loyalty-point-sharing/Admin/settings.php' );
		if ( ! file_exists( $file_path ) ) {
			$file_path = WLPS_VIEW_PATH . '/Admin/settings.php';
		}


		return WlpsUtil::renderTemplate( $file_path, $args, false );
	}

	public static function saveSettings() {
		$input               = new Input();
		$response            = [];
		$validate_data_error = [];
		$wlps_nonce          = (string) $input->post( 'wlps_nonce' );
		if ( ! WlpsUtil::hasAdminPrivilege() || ! WlpsUtil::verify_nonce( $wlps_nonce, 'wlps-setting-nonce' ) ) {
			$response['error']   = true;
			$response['message'] = esc_html__( 'Settings not saved!', 'wp-loyalty-point-sharing' );
			wp_send_json( $response );
		}
		$key = (string) $input->post( 'option_key' );
		$key = Validation::validateInputAlpha( $key );
		if ( ! empty( $key ) ) {
			$data                  = $input->post();
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

	public static function addAssets() {
		if ( Input::get( 'page' ) != WLPS_PLUGIN_SLUG ) {
			return;
		}

		$suffix = '.min';
		wp_enqueue_style(
			WLPS_PLUGIN_SLUG . '-admin-style',
			WLPS_PLUGIN_URL . 'Assets/Admin/Css/wlps-admin.css',
			[],
			WLPS_PLUGIN_VERSION
		);
		wp_enqueue_script( WLPS_PLUGIN_SLUG . '-admin', WLPS_PLUGIN_URL . 'Assets/Admin/Js/wlps-admin.js', array( 'jquery' ), WLPS_PLUGIN_VERSION . '&t=' . strtotime( gmdate( "Y-m-d H:i:s" ) ), true );
		wp_enqueue_style( WLR_PLUGIN_SLUG . '-alertify', WLR_PLUGIN_URL . 'Assets/Admin/Css/alertify' . $suffix . '.css', [], WLR_PLUGIN_VERSION );
		wp_enqueue_script( WLR_PLUGIN_SLUG . '-alertify', WLR_PLUGIN_URL . 'Assets/Admin/Js/alertify' . $suffix . '.js', [], WLR_PLUGIN_VERSION . '&t=' . strtotime( gmdate( "Y-m-d H:i:s" ) ), true );
		$localize = [
			'home_url'            => get_home_url(),
			'admin_url'           => admin_url(),
			'ajax_url'            => admin_url( 'admin-ajax.php' ),
			'saving_button_label' => __( "Saving...", "wp-loyalty-rules" ),
			'saved_button_label'  => __( "Save Changes", "wp-loyalty-rules" ),
			'wlps_setting_nonce'  => WlpsUtil::create_nonce( 'wlps-setting-nonce' ),
		];
		wp_localize_script( WLPS_PLUGIN_SLUG . '-admin', 'wlps_localize_data', $localize );
	}
}