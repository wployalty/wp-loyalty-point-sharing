<?php

namespace Wlps\App\Controller;

class WlpsEmailManager {
	public static function init() {
		add_filter( "woocommerce_email_classes", [ self::class, 'addEmailClass' ] );
	}

	public static function addEmailClass( $emails ) {
		require_once plugin_dir_path( WC_PLUGIN_FILE ) . 'includes/emails/class-wc-email.php';
		if ( class_exists( 'Wlps\App\Emails\PointTransferSenderEmail' ) ) {
			$emails['WlpsSenderEmail'] = new \Wlps\App\Emails\PointTransferSenderEmail();
		}
		if ( class_exists( 'Wlps\App\Emails\PointTransferReceiverEmail' ) ) {
			$emails['WlpsReceiverEmail'] = new \Wlps\App\Emails\PointTransferReceiverEmail();
		}

		return $emails;
	}
}