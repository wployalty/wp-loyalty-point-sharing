<?php

namespace Wlps\App;

use Wlps\App\Controller\Common;
use Wlps\App\Controller\Site\WlpsEmailManager;
use Wlps\App\Models\PointTransfers;

class Router {
	private static $controller;

	public static function init() {
		self::$controller = empty( self::$controller ) ? new Common() : self::$controller;
		WlpsEmailManager::init();


		if ( is_admin() ) {
			add_action( 'admin_menu', array( self::$controller, 'addMenu' ), 11 );
			add_action( 'admin_enqueue_scripts', array( self::$controller, 'addAssets' ) );
			add_action( 'wp_ajax_wlps_save_settings', array( self::$controller, 'saveSettings' ) );
		}
		add_action( 'wlps_share_point_modal', [
			\Wlps\App\Controller\Common::class,
			'wlps_render_share_points_modal'
		] );
		add_action( 'wp_enqueue_scripts', array( self::$controller, 'addFrontendAssets' ) );
		// safe to use WC_Email now
		add_action( 'wp_ajax_wlps_transfer_points', [ \Wlps\App\Controller\Common::class, 'transferPoints' ] );
		add_action( 'init', [ Common::class, 'handleConfirmTransfer' ] );
	}

}