<?php

namespace Wlps\App\Controller;

use Wlps\App\Emails\PointTransferSenderEmail;
use Wlps\App\Emails\PointTransferReceiverEmail;

class WlpsEmailManager {
	public static function init() {
		add_filter( "woocommerce_email_classes", [ self::class, 'addEmailClass' ] );
		add_filter( 'woocommerce_template_directory', [ self::class, 'addTemplateDirectory' ], 10, 2 );
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

	public static function addTemplateDirectory( $template_dir, $template ) {
		$my_templates = [
			'emails/point-transfer-sender.php',
			'emails/point-transfer-receiver.php',
			'emails/plain/point-transfer-sender.php',
			'emails/plain/point-transfer-receiver.php'
		];

		if ( in_array( $template, $my_templates ) ) {
			return 'wployalty';
		}

		return $template_dir;
	}
}