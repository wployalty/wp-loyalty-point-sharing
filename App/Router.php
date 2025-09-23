<?php

namespace Wlps\App;

use Wlps\App\Controller\Common;

class Router {
	public static function init() {
		if ( is_admin() ) {
			add_action( 'admin_menu', [ Common::class, 'addMenu' ], 11 );
//			add_action( 'admin_footer', [ Common::class, 'hideMenu' ] );
			add_action( 'admin_enqueue_scripts', [ Common::class, 'addAssets' ] );

		}
	}
}