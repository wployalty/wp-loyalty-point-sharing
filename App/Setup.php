<?php

namespace Wlps\App;

use Wlps\App\Emails\Helpers\Plugin;
use Wlps\App\Models\PointTransfers;

defined( "ABSPATH" ) || exit;

class Setup {
	public static function init() {
		register_activation_hook( WLPS_PLUGIN_FILE, [ __CLASS__, 'activate' ] );
		register_deactivation_hook( WLPS_PLUGIN_FILE, [ __CLASS__, 'deactivate' ] );
		register_uninstall_hook( WLPS_PLUGIN_FILE, [ __CLASS__, 'uninstall' ] );

		add_action( 'plugins_loaded', [ __CLASS__, 'maybeRunMigration' ] );
		add_action( 'upgrader_process_complete', [ __CLASS__, 'maybeRunMigration' ] );
	}

	public static function activate() {
		Plugin::checkDependencies( true );
		self::maybeRunMigration();
	}

	public static function deactivate() {
		// silence is golden
	}

	public static function uninstall() {
		// silence is golden
	}

	/**
	 * Maybe run database migration.
	 */
	public static function maybeRunMigration() {
		$db_version = get_option( 'wlps_version', '0.0.1' );
		if ( version_compare( $db_version, WLPS_PLUGIN_VERSION, '<' ) ) {
			self::runMigration();
			update_option( 'wlps_version', WLPS_PLUGIN_VERSION );
		}
	}

	/**
	 * Run database migration.
	 */
	private static function runMigration() {
		$models = [
			new PointTransfers(),
		];
		foreach ( $models as $model ) {
			if ( is_a( $model, '\Wlr\App\Models\Base' ) ) {
				$model->create();
			}
		}
	}

}