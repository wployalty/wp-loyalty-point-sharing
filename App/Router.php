<?php

namespace Wlps\App;

use Wlps\App\Controller\Common;
use Wlps\App\Controller\Site\WlpsEmailManager;

class Router {
	public static function init() {
		WlpsEmailManager::init();
		if ( is_admin() ) {
			add_action( 'admin_menu', [ Common::class, 'addMenu' ], 11 );
//			add_action( 'admin_footer', [ Common::class, 'hideMenu' ] );
			add_action( 'admin_enqueue_scripts', [ Common::class, 'addAssets' ] );

		}
	}
}