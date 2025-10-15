<?php

namespace Wlps\App\Controller;

use Wlps\App\Helpers\WlpsUtil;
use Wlr\App\Helpers\Base;
use Wlr\App\Models\Users;

defined( 'ABSPATH' ) or die();

class Common {
	/**
	 * Enqueues frontend scripts and styles for the point transfer modal,
	 * and passes localized data (like AJAX URLs and user points) to JavaScript.
	 *
	 * @return void
	 */
	public static function addFrontendAssets() {
		$base_helper  = new Base();
		$settings     = get_option( 'wlps_settings', [] );
		$max_transfer = isset( $settings['max_transfer_points'] ) ? (int) $settings['max_transfer_points'] : 0;
		$user_email   = WlpsUtil::getLoginUserEmail();
		$user_points  = $base_helper->getUserPoint( $user_email );

		// JS
		wp_enqueue_script( WLPS_PLUGIN_SLUG . '-frontend', WLPS_PLUGIN_URL . 'Assets/Site/Js/transfer-modal.js', array( 'jquery' ), WLPS_PLUGIN_VERSION . '&t=', true );
		$suffix = '.min';
		wp_enqueue_style( WLR_PLUGIN_SLUG . '-alertify', WLR_PLUGIN_URL . 'Assets/Admin/Css/alertify' . $suffix . '.css', array(), WLR_PLUGIN_VERSION );
		wp_enqueue_style( WLPS_PLUGIN_SLUG . '-frontend', WLPS_PLUGIN_URL . 'Assets/Site/Css/transfer-modal.css', array(), WLPS_PLUGIN_VERSION );
		wp_enqueue_script( WLR_PLUGIN_SLUG . '-alertify', WLR_PLUGIN_URL . 'Assets/Admin/Js/alertify' . $suffix . '.js', array(), WLR_PLUGIN_VERSION . '&t=' . strtotime( gmdate( "Y-m-d H:i:s" ) ), true );
		$localize = array(
			'home_url'                   => get_home_url(),
			'admin_url'                  => admin_url(),
			'ajax_url'                   => admin_url( 'admin-ajax.php' ),
			'saving_button_label'        => __( "Saving...", "wp-loyalty-point-sharing" ),
			'saved_button_label'         => __( "Save Changes", "wp-loyalty-point-sharing" ),
			'max_transfer_points'        => $max_transfer,
			'available_user_points'      => $user_points,
			'wlps_transfer_points_nonce' => WlpsUtil::create_nonce( "wlps-transfer-points-nonce" )
		);
		wp_localize_script( WLPS_PLUGIN_SLUG . '-frontend', 'wlps_frontend_data', $localize );
	}

	/**
	 * Adds an extra action to the provided action list.
	 *
	 * This method adds an extra action key-value pair to the given action list array.
	 * If the provided action list is empty or not an array, the original list is returned unchanged.
	 * The added action key is 'migration_to_wployalty' with the corresponding label retrieved from the translation function.
	 *
	 * @param array $action_list The array of actions to which the extra action will be added.
	 *
	 * @return array The updated action list with the extra action added.
	 */
	public static function addExtraAction( $action_list ) {
		if ( empty( $action_list ) || ! is_array( $action_list ) ) {
			return $action_list;
		}
		$action_list['share_point_debit']  = __( 'Points Shared with another user', 'wp-loyalty-point-sharing' );
		$action_list['share_point_credit'] = __( 'Points received from another user', 'wp-loyalty-point-sharing' );

		return $action_list;
	}

	public static function renderSharePointModal() {
		$settings               = get_option( 'wlps_settings', [] );
		$is_share_point_enabled = ! empty( $settings['enable_share_point'] );

		if ( $is_share_point_enabled ) {

			$file_path = get_theme_file_path( 'wp-loyalty-point-sharing/Site/share-points-modal.php' );

			if ( ! file_exists( $file_path ) ) {

				$file_path = WLPS_VIEW_PATH . '/Site/share-points-modal.php';
				error_log( $file_path );
			}

			if ( file_exists( $file_path ) ) {
				include $file_path;
			}
		}
	}

	public static function getLoyaltyUser( $email ) {
		if ( empty( $email ) ) {
			return false;
		}
		$user_model = new Users();

		return $user_model->getQueryData( [
			'user_email' => [
				'operator' => '=',
				'value'    => $email
			]
		], '*', [], false, true );
	}

	public static function addEmailId( $emailIds ) {
		$wlpsEmailIds = [ 'wlps_point_transfer_receiver_email', 'wlps_point_transfer_sender_email' ];

		return array_merge( $emailIds, $wlpsEmailIds );
	}

}