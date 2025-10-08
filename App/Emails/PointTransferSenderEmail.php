<?php

namespace Wlps\App\Emails;

use Wlr\App\Helpers\Rewards;

defined( "ABSPATH" ) or die();
require_once plugin_dir_path( WC_PLUGIN_FILE ) . 'includes/emails/class-wc-email.php';

class PointTransferSenderEmail extends \WC_Email {

	public function __construct() {
		$this->id             = 'wlps_point_transfer_sender_email';
		$this->customer_email = true;
		$this->title          = __( 'Point Transfer (Sender)', 'wp-loyalty-point-sharing' );
		$this->description    = __( 'This email is sent to the sender asking them to confirm the point transfer.', 'wp-loyalty-point-sharing' );

		$this->template_html  = 'emails/point-transfer-sender.php';
		$this->template_plain = 'emails/plain/point-transfer-sender.php';
		$this->template_base  = WLPS_PLUGIN_PATH . 'templates/';

		$this->placeholders = apply_filters( $this->id . "_short_codes_list", [
			'{site_name}'      => get_bloginfo( 'name' ),
			'{wlr_shop_url}'   => 'https://example.com',
			'{sender_name}'    => '',
			'{recipient_name}' => '',
			'{points_amount}'  => '',
			'{points_label}'   => __( 'points', 'wp-loyalty-point-sharing' ),
			'{confirm_link}'   => '',
		] );
		add_action( 'wlr_send_point_transfer_sender_email', [ $this, 'trigger' ], 10, 5 );
		parent::__construct();

		$this->heading    = $this->get_option( 'heading', $this->get_default_heading() );
		$this->subject    = $this->get_option( 'subject', $this->get_default_subject() );
		$this->email_type = $this->get_option( 'email_type', 'html' );
		$this->enabled    = $this->get_option( 'enabled', 'yes' );
	}

	public function get_default_heading() {
		return __( 'Confirm Your Point Transfer', 'wp-loyalty-point-sharing' );
	}

	public function get_default_subject() {
		return __( 'Confirm sending {points_amount} {points_label} to {recipient_name}', 'wp-loyalty-point-sharing' );
	}

	public function get_subject() {
		$subject = $this->get_option( 'subject', $this->get_default_subject() );

		return apply_filters( 'woocommerce_email_subject_' . $this->id, $this->format_string( $subject ), $this->object, $this );
	}

	/**
	 * Trigger this email
	 */
	public function trigger( $sender_email, $sender_name, $recipient_name, $points_amount, $confirm_link ) {
		if ( ! class_exists( '\Wlps\App\Models\PointTransfers' ) ) {
			return;
		}
		// Set placeholders
		$this->placeholders['{sender_name}']    = $sender_name;
		$this->placeholders['{recipient_name}'] = $recipient_name;
		$this->placeholders['{points_amount}']  = $points_amount;
		$this->placeholders['{confirm_link}']   = $confirm_link;

		$this->recipient = $sender_email;

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$created_at = strtotime( gmdate( "Y-m-d H:i:s" ) );
		$log_data   = [
			'user_email'          => $sender_email,
			'action_type'         => 'point_transfer',
			'points'              => (int) $points_amount,
			'action_process_type' => 'email_notification',
			'created_at'          => $created_at,
			'note'                => sprintf( __( 'Sending point transfer (%1$s) email failed', 'wp-loyalty-point-sharing' ), $points_amount ),
			'customer_note'       => sprintf( __( 'Sending point transfer (%1$s) email failed', 'wp-loyalty-point-sharing' ), $points_amount ),
		];


		if ( $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() ) ) {

			$log_data['note']          = sprintf( __( 'Point transfer email sent successfully', 'wp-loyalty-point-sharing' ) );
			$log_data['customer_note'] = sprintf( __( 'Point transfer email sent successfully', 'wp-loyalty-point-sharing' ) );
		}

		$reward_helper = Rewards::getInstance();
		$reward_helper->add_note( $log_data );
	}


	public function get_content_html() {
		return $this->format_string( wc_get_template_html( $this->template_html, [
			'email_heading'      => $this->get_heading(),
			'additional_content' => $this->get_additional_content(),
			'sent_to_admin'      => false,
			'plain_text'         => false,
			'email'              => $this,
			'placeholders'       => $this->placeholders
		], '', $this->template_base ) );
	}

	public function get_content_plain() {
		return $this->format_string( wc_get_template_html( $this->template_html, [
			'email_heading'      => $this->get_heading(),
			'additional_content' => $this->get_additional_content(),
			'sent_to_admin'      => false,
			'plain_text'         => true,
			'email'              => $this,
			'placeholder'        => $this->placeholders,
		], '', $this->template_base ) );
	}
}
