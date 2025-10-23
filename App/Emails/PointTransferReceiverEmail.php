<?php

namespace Wlps\App\Emails;

use WC_Email;
use Wlr\App\Emails\Traits\Common;
use Wlr\App\Helpers\Rewards;

defined( "ABSPATH" ) or die();
require_once plugin_dir_path( WC_PLUGIN_FILE ) . 'includes/emails/class-wc-email.php';

class PointTransferReceiverEmail extends WC_Email {
	use Common;

	public function __construct() {
		$this->id             = 'wlps_point_transfer_receiver_email';
		$this->customer_email = true;
		$this->title          = __( 'Point Transfer (Receiver)', 'wp-loyalty-point-sharing' );
		$this->description    = __( 'This email is sent to the recipient when they receive loyalty points.', 'wp-loyalty-point-sharing' );

		$this->template_html  = 'emails/point-transfer-receiver.php';
		$this->template_plain = 'emails/plain/point-transfer-receiver.php';
		$this->template_base  = WLPS_PLUGIN_PATH . 'templates/';

		$this->placeholders = apply_filters( $this->id . "_short_codes_list", [
			'{site_name}'          => get_bloginfo( 'name' ),
			'{wlr_shop_url}'       => 'https://example.com',
			'{wlr_store_name}'     => 'shop',
			'{wlr_sender_name}'    => 'Thomas',
			'{wlr_recipient_name}' => 'Judy',
			'{wlr_points}'         => '',
			'{wlr_points_label}'   => __( 'points', 'wp-loyalty-point-sharing' ),
			'{wlr_account_link}'   => 'https://example.com',
			'{wlr_referral_url}'   => 'https://example.com',
			'{wlr_user_point}'     => 0,
		] );
		add_action( 'wlr_send_point_transfer_reciever_email', [ $this, 'trigger' ], 10, 3 );
		parent::__construct();

		$this->heading    = $this->get_option( 'heading', $this->get_default_heading() );
		$this->subject    = $this->get_option( 'subject', $this->get_default_subject() );
		$this->email_type = $this->get_option( 'email_type', 'html' );
		$this->enabled    = $this->get_option( 'enabled', 'yes' );
	}

	public function get_default_heading() {
		return __( 'You’ve received loyalty points!', 'wp-loyalty-point-sharing' );
	}

	public function get_default_subject() {
		return __( '{wlr_sender_name} sent you {wlr_points} {wlr_points_label}', 'wp-loyalty-point-sharing' );
	}

	public function get_subject() {
		$subject = $this->get_option( 'subject', $this->get_default_subject() );

		return apply_filters( 'woocommerce_email_subject_' . $this->id, $this->format_string( $subject ), $this->object, $this );
	}

	/**
	 * Trigger this email
	 */
	public function trigger( $recipient_email, $sender_email, $points_amount ) {
		if ( ! class_exists( 'Wlps\App\Models\PointTransfers' ) ) {
			return;
		}
		$this->recipient = sanitize_email( $recipient_email );
		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}
		$loyalUser       = $this->getLoyaltyUser( $recipient_email );
		$ref_code        = ! empty( $loyal_user->refer_code ) ? $loyal_user->refer_code : '';
		$available_point = ! empty( $loyalUser->points ) ? $loyalUser->points : 0;
		$isAllowEmail    = intval( $loyalUser->is_allow_send_email ?? 0 );
		$reward_helper   = Rewards::getInstance();
		$point_label     = $reward_helper->getPointLabel( $available_point );

		$this->placeholders = [
			'{site_name}'          => get_bloginfo( 'name' ),
			'{site_address}'       => get_bloginfo( 'description' ),
			'{site_url}'           => home_url(),
			'{store_email}'        => get_option( 'admin_email' ),
			'{wlr_recipient_name}' => $this->getUserDisplayName( $recipient_email ) ?: $recipient_email,
			'{wlr_referral_url}'   => $ref_code ? $reward_helper->getReferralUrl( $ref_code ) : '',
			'{wlr_sender_name}'    => $this->getUserDisplayName( $sender_email ),
			'{wlr_store_name}'     => apply_filters( 'wlr_before_display_store_name', get_option( 'blogname' ) ),
			'{wlr_points}'         => $points_amount,
			'{wlr_account_link}'   => get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ),
			'{wlr_points_label}'   => $point_label,
			'{wlr_shop_url}'       => get_permalink( wc_get_page_id( 'shop' ) ),
			'{wlr_user_point}'     => $loyal_user->points ?? 0
		];

		$created_at = strtotime( gmdate( "Y-m-d H:i:s" ) );
		$log_data   = [
			'action_type'         => 'point_transfer',
			'points'              => (int) $points_amount,
			'action_process_type' => 'email_notification',
			'created_at'          => $created_at,
			'note'                => '',
			'customer_note'       => '',
		];

		if ( $isAllowEmail < 1 ) {
			/* translators: %1$s: email not sent */
			$log_data['note']          = sprintf( __( 'Email not sent — recipient (%1$s) opted out of notifications', 'wp-loyalty-point-sharing' ), $recipient_email );
			$log_data['customer_note'] = $log_data['note'];
			Rewards::getInstance()->add_note( $log_data );

			return;
		}

		$sent             = $this->send(
			$this->get_recipient(),
			$this->get_subject(),
			$this->get_content(),
			$this->get_headers(),
			$this->get_attachments()
		);
		$log_data['note'] = $sent
			? sprintf(
			/* translators: 1: recipient email address */
				__( 'Point transfer email sent successfully to %1$s', 'wp-loyalty-point-sharing' ),
				$recipient_email
			)
			: sprintf(
			/* translators: 1: points amount, 2: recipient email address */
				__( 'Sending point transfer (%1$s) email failed to %2$s', 'wp-loyalty-point-sharing' ),
				$points_amount,
				$recipient_email
			);

		$log_data['customer_note'] = $log_data['note'];

		Rewards::getInstance()->add_note( $log_data );
	}

	public function getShortCodesList() {
		$short_codes = [];
		foreach ( $this->placeholders as $short_code => $default_value ) {
			$short_codes[] = [
				'short_code'    => $short_code,
				'description'   => $this->getShortCodeDescription( $short_code ),
				'default_value' => $default_value
			];
		}

		return $short_codes;
	}

	protected function getShortCodeDescription( $short_code ) {
		$short_code_descriptions = [
			'{wlr_points}'         => __( 'The number of points that are going to transfer', 'wp-loyalty-point-sharing' ),
			'{wlr_recipient_name}' => __( 'The Recipient who is recieved the points', 'wp-loyalty-point-sharing' ),
			'{wlr_sender_name}'    => __( 'The Sender who sends the points', 'wp-loyalty-point-sharing' ),
			'{wlr_points_label}'   => __( 'The label for points (e.g., points, credits)', 'wp-loyalty-point-sharing' ),
			'{wlr_shop_url}'       => __( 'The URL to the shop page of the website', 'wp-loyalty-point-sharing' ),
			'{wlr_account_link}'   => __( 'The URL to access the account page', 'wp-loyalty-point-sharing' ),

			//loyalty common
			'{wlr_referral_url}'   => __( 'The referral URL for the customer to share with friends', 'wp-loyalty-point-sharing' ),
			'{wlr_user_point}'     => __( 'The current points balance of the customer', 'wp-loyalty-point-sharing' ),
			'{wlr_user_name}'      => __( 'The display name of the customer', 'wp-loyalty-point-sharing' ),
			'{wlr_store_name}'     => __( 'The name of the store or website', 'wp-loyalty-point-sharing' ),
			// common
			'{site_name}'          => __( 'The title of the website', 'wp-loyalty-point-sharing' ),
			'{site_address}'       => __( 'The address of the website', 'wp-loyalty-point-sharing' ),
			'{site_url}'           => __( 'The URL of the website', 'wp-loyalty-point-sharing' ),
			'{store_email}'        => __( 'The store\'s contact email address', 'wp-loyalty-point-sharing' )
		];

		return in_array( $short_code, array_keys( $short_code_descriptions ) ) ? $short_code_descriptions[ $short_code ] : '';
	}

	public function get_content_html() {
		return $this->format_string( wc_get_template_html( $this->template_html, [
			'email_heading'      => $this->get_heading(),
			'additional_content' => $this->get_additional_content(),
			'sent_to_admin'      => false,
			'plain_text'         => false,
			'email'              => $this,
		], '', $this->template_base ) );
	}

	public function get_content_plain() {
		return $this->format_string( wc_get_template_html( $this->template_html, [
			'email_heading'      => $this->get_heading(),
			'additional_content' => $this->get_additional_content(),
			'sent_to_admin'      => false,
			'plain_text'         => true,
			'email'              => $this,
		], '', $this->template_base ) );
	}
}
