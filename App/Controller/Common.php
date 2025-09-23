<?php

namespace Wlps\App\Controller;

use Wlps\App\Helper\Input;
use Wlps\App\Helper\WC;
use Wlr\App\Helpers\Util;

defined( 'ABSPATH' ) or die();

class Common {
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
		wp_enqueue_style(
			WLPS_PLUGIN_SLUG . '-admin-style',
			WLPS_PLUGIN_URL . 'Assets/Admin/Css/wlps-admin.css',
			array(),
			WLPS_PLUGIN_VERSION
		);
	}

	public static function getActivityPage() {
		$args        = [
			'current_page'     => 'actions',
			'back_to_apps_url' => admin_url( 'admin.php?' . http_build_query( [ 'page' => WLR_PLUGIN_SLUG ] ) ) . '#/apps',
			'previous'         => WLRMG_PLUGIN_URL . "Assets/svg/previous.svg",
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
		$args      = [
			'current_page'          => 'settings',
			'back_to_apps_url'      => admin_url( 'admin.php?' . http_build_query( [ 'page' => WLR_PLUGIN_SLUG ] ) ) . '#/apps',
			'back'                  => WLPS_PLUGIN_URL . "Assets/svg/back.svg",
			'previous'              => WLPS_PLUGIN_URL . "Assets/svg/previous.svg",
			'option_settings'       => get_option( 'wlrps_settings', [] ),
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

}