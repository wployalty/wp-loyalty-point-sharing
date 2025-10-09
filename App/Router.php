<?php

namespace Wlps\App;

use Wlps\App\Controller\Admin\AdminController;
use Wlps\App\Controller\Common;
use Wlps\App\Controller\PointTransferController;
use Wlps\App\Controller\Site\WlpsEmailManager;
use Wlps\App\Models\PointTransfers;

class Router {
	public static function init() {
		WlpsEmailManager::init();

		if ( is_admin() ) {
			add_action( 'admin_menu', [ AdminController::class, 'addMenu' ], 10 );
			add_action( "admin_enqueue_scripts", [ AdminController::class, 'addAssets' ] );
			if ( wp_doing_ajax() ) {
				add_action( "wp_ajax_wlps_save_settings", [ AdminController::class, 'saveSettings' ] );
			}
		}
		add_action( "wlps_share_point_modal", [ Common::class, 'renderSharePointModal' ] );
		add_action( "wp_enqueue_scripts", [ Common::class, 'addFrontendAssets' ] );
		add_filter( "wlr_extra_action_list", [ Common::class, 'addExtraAction' ], 10, 1 );
		add_action( "wp_ajax_wlps_transfer_points", [ PointTransferController::class, 'transferPoints' ], 10 );
		add_action( "init", [ PointTransferController::class, 'handleConfirmTransfer' ] );

	}

}