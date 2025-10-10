<?php

namespace Wlps\App\Controller;

use Wlps\App\Emails\PointTransferSenderEmail;
use Wlps\App\Emails\PointTransferReceiverEmail;

class WlpsEmailManager {
	public static function init() {
		add_filter( "woocommerce_email_classes", [ self::class, 'addEmailClass' ] );
	}

	public static function addEmailClass( $emails ) {
		if ( class_exists( 'Wlps\App\Emails\PointTransferSenderEmail' ) ) {
			$emails['WlpsSenderEmail'] = new PointTransferSenderEmail();
		}
		if ( class_exists( 'Wlps\App\Emails\PointTransferReceiverEmail' ) ) {
			$emails['WlpsReceiverEmail'] = new PointTransferReceiverEmail();
		}

		return $emails;
	}
}