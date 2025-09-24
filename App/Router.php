<?php

namespace Wlps\App;

use Wlps\App\Controller\Common;
use Wlps\App\Controller\Site\WlpsEmailManager;

class Router {
	private static $controller;

	public static function init() {
		self::$controller = empty( self::$controller ) ? new Common() : self::$controller;
		WlpsEmailManager::init();
		if ( is_admin() ) {
			add_action( 'admin_menu', array( self::$controller, 'addMenu' ), 11 );
//			add_action( 'admin_footer', [ Common::class, 'hideMenu' ] );
			add_action( 'admin_enqueue_scripts', array( self::$controller, 'addAssets' ) );
			add_action( 'wp_ajax_wlps_save_settings', array( self::$controller, 'saveSettings' ) );

		}
	}
}