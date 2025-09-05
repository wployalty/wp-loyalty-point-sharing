<?php

namespace Wlps\App;
defined( "ABSPATH" ) || exit;

class Setup {
	public static function init() {
		register_activation_hook( WLPS_PLUGIN_FILE, [ __CLASS__, 'activate' ] );
		register_deactivation_hook( WLPS_PLUGIN_FILE, [ __CLASS__, 'deactivate' ] );
		register_uninstall_hook( WLPS_PLUGIN_FILE, [ __CLASS__, 'uninstall' ] );
	}

	public static function activate() {
		// silence is golden
	}

	public static function deactivate() {
		// silence is golden
	}

	public static function uninstall() {
		// silence is golden
	}
	
}