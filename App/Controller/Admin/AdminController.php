<?php

namespace Wlps\App\Controller\Admin;

use Wlps\App\Helpers\Input;
use Wlps\App\Helpers\Pagination;
use Wlps\App\Helpers\Validation;
use Wlps\App\Helpers\WlpsUtil;

class AdminController {
	/**
	 * Register the admin menu page for the "WPLoyalty: Point Sharing" feature.
	 *
	 * This method adds a new menu item to the WordPress admin sidebar for managing
	 * the Point Sharing functionality. The menu is only visible to users with
	 * administrative privileges (checked via `WlpsUtil::hasAdminPrivilege()`).
	 *
	 * It uses the `add_menu_page()` function to create the top-level menu item,
	 * which links to the main admin page rendered by the `renderMainPage()` method.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 * @hooked admin_menu
	 *
	 */
	public static function addMenu() {
		if ( WlpsUtil::hasAdminPrivilege() ) {
			add_menu_page( __( 'WPLoyalty: Point Sharing', 'wp-loyalty-point-sharing' ), __( 'WPLoyalty: Point Sharing', 'wp-loyalty-point-sharing' ), 'manage_woocommerce', WLPS_PLUGIN_SLUG, [
				self::class,
				'renderMainPage'
			], 'dashicons-megaphone', 58 );
		}
	}

	/**
	 * To hide menu.
	 *
	 * @return void
	 */
	public static function hideMenu() {
		?>
        <style>
            #toplevel_page_wp-loyalty-point-sharing {
                display: none !important;
            }
        </style>
		<?php
	}


	/**
	 * Render the main admin page for the WPLoyalty Point Sharing module.
	 *
	 * This method serves as the primary entry point for the plugin’s admin interface.
	 * It determines which view (tab) to render based on the `view` query parameter —
	 * such as the Point Sharing activity view or the Settings page.
	 *
	 * It performs an admin privilege check using `WlpsUtil::hasAdminPrivilege()` to
	 * ensure only authorized users can access this page. If unauthorized, the request
	 * terminates with a permission error message.
	 *
	 * The method also supports theme-based template overrides:
	 * - Custom theme path: `yourtheme/wp-loyalty-point-sharing/Admin/main.php`
	 * - Default plugin fallback: `WLPS_VIEW_PATH/Admin/main.php`
	 *
	 * @return void Outputs the rendered admin page HTML.
	 * @since 1.0.0
	 *
	 */

	public static function renderMainPage() {
		if ( ! WlpsUtil::hasAdminPrivilege() ) {
			wp_die( esc_html__( "You don't have permission to access this page.", 'wp-loyalty-point-sharing' ) );
		}

		$view = Input::get( 'view', 'point_sharing', 'query' );

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

	/**
	 * Retrieve and render the Point Sharing activity page for the admin panel.
	 *
	 * This method handles:
	 *  - Fetching point transfer records from the custom database table (`wlr_point_transfers`).
	 *  - Filtering by status (`all`, `pending`, `completed`, `expired`, `failed`).
	 *  - Searching by sender or recipient email.
	 *  - Sorting by any valid column (e.g., ID, date, etc.).
	 *  - Handling pagination.
	 *  - Rendering the appropriate admin view template.
	 *
	 * @return string The rendered HTML content of the activity page.
	 * @global wpdb $wpdb WordPress database access abstraction object.
	 *
	 * @since 1.0.0
	 *
	 */

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

		$table_name = esc_sql( $wpdb->prefix . 'wlr_point_transfers' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$total_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE $where" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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

	/**
	 * Retrieve and render the Point Sharing settings page for the admin panel.
	 *
	 * This method prepares data and paths required to display the settings page of the
	 * "WPLoyalty: Point Sharing" plugin. It fetches stored plugin settings, constructs URLs
	 * for managing related WooCommerce emails, and loads the appropriate admin template file.
	 *
	 * @return string The rendered HTML output of the settings page.
	 * @since 1.0.0
	 *
	 */

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

	/**
	 * Save Settings to wp_options table
	 *
	 * This method saves the settings data of "Wp-loyalty-point-sharing" fields to wp_options under
	 * wlps_settings key that will manage the wp-loyalty-point-sharing restrictions
	 *
	 * return @void
	 */

	public static function saveSettings() {
		// Check admin privilege & nonce
		$wlps_nonce = (string) Input::get( 'wlps_nonce', '', 'post' );
		if ( ! WlpsUtil::hasAdminPrivilege() || ! WlpsUtil::verify_nonce( $wlps_nonce, 'wlps-setting-nonce' ) ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Settings not saved!', 'wp-loyalty-point-sharing' ),
			] );
		}

		// Validate option key
		$key = (string) Input::get( 'option_key', '', 'post' );
		$key = Validation::validateInputAlpha( $key );
		if ( empty( $key ) ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Settings not saved!', 'wp-loyalty-point-sharing' ),
			] );
		}

		// Clean input data
		$data             = Input::post();
		$fields_to_remove = [ 'option_key', 'action', 'wlps_nonce' ];
		foreach ( $fields_to_remove as $field ) {
			unset( $data[ $field ] );
		}

		// Validate settings tab
		$validate_data = Validation::validateSettingsTab( $data ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( is_array( $validate_data ) && ! empty( $validate_data ) ) {
			$first_field_errors  = reset( $validate_data );
			$first_error_message = current( $first_field_errors );

			wp_send_json_error( [
				'message' => $first_error_message,
			] );
		}

		// Save settings
		$updated = update_option( $key, $data, true );

		if ( $updated !== false || get_option( $key ) === $data ) {
			do_action( 'wlps_after_save_settings', $data, $key );

			wp_send_json_success( [
				'message' => esc_html__( 'Settings saved successfully!', 'wp-loyalty-point-sharing' ),
			] );
		}

		wp_send_json_error( [
			'message' => esc_html__( 'Settings not saved!', 'wp-loyalty-point-sharing' ),
		] );
	}

	/**
	 * Enqueue admin assets for the WPLoyalty Point Sharing plugin.
	 *
	 * This method loads the required CSS and JavaScript files only on the
	 * plugin’s admin page. It also localizes data for use in JavaScript,
	 * such as URLs and translatable strings.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */

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
			'saving_button_label' => __( "Saving...", "wp-loyalty-point-sharing" ),
			'saved_button_label'  => __( "Save Changes", "wp-loyalty-point-sharing" ),
			'wlps_setting_nonce'  => WlpsUtil::create_nonce( 'wlps-setting-nonce' ),
		];
		wp_localize_script( WLPS_PLUGIN_SLUG . '-admin', 'wlps_localize_data', $localize );
	}
}