<?php

namespace Wlps\App\Emails;

use Wlr\App\Emails\Traits\Common;
use Wlr\App\Helpers\Rewards;

defined( "ABSPATH" ) or die();


class PointTransferSenderEmail extends \WC_Email {
	use Common;

	private string $template_path;

	public function __construct() {
		$this->id             = 'wlps_point_transfer_sender_email';
		$this->customer_email = true;
		$this->title          = __( 'Point Transfer (Sender)', 'wp-loyalty-point-sharing' );
		$this->description    = __( 'This email is sent to the sender asking them to confirm the point transfer.', 'wp-loyalty-point-sharing' );

		$this->template_html  = 'emails/point-transfer-sender.php';
		$this->template_plain = 'emails/plain/point-transfer-sender.php';
		$this->template_base  = WLPS_PLUGIN_PATH . 'templates/';
		$this->template_path  = 'wp-loyalty-point-sharing/';
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
		$this->placeholders = apply_filters($this->id.'_short_codes_list' , [
			'{site_title}'          => get_bloginfo( 'name' ),
			'{site_address}'        => 'localhost',
			'{wlr_shop_url}'        => 'https://example.com',
			'{wlr_sender_email}'    => 'examplesender@mail.com',
			'{wlr_recipient_email}' => 'examplereciever@mail.com',
			'{wlr_sender_name}'     => 'tony',
			'{wlr_recipient_name}'  => 'alex',
			'{wlr_transfer_points}' => '10',
			'{wlr_points_label}'    => __( 'points', 'wp-loyalty-point-sharing' ),
			'{wlr_confirm_link}'    => '',
			'{wlr_referral_url}'    => 'http:example.com',
			'{wlr_user_points}'     => 0,
		] );
		add_action( 'wlps_send_point_transfer_sender_email', [ $this, 'trigger' ], 10, 2 );
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
		return __( 'Confirm sending {wlr_transfer_points} {wlr_points_label} to {wlr_recipient_name}', 'wp-loyalty-point-sharing' );
	}

	/**
	 * Trigger this email
	 */
	public function trigger( $transfer, $confirm_link ) {
		if ( ! class_exists( '\Wlps\App\Models\PointTransfers' ) ) {
			return;
		}
		$this->recipient = sanitize_email( $transfer->sender_email );

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$loyalUser       = $this->getLoyaltyUser( $transfer->sender_email );
		$ref_code        = ! empty( $loyalUser->refer_code ) ? $loyalUser->refer_code : '';
		$available_point = ! empty( $loyalUser->points ) ? $loyalUser->points : 0;
		$reward_helper   = Rewards::getInstance();
		$point_label     = $reward_helper->getPointLabel( $available_point );


		$this->placeholders = [
			'{site_title}'          => get_bloginfo( 'name' ),
			'{site_address}'        => wp_parse_url( home_url(), PHP_URL_HOST ),
			'{site_url}'            => home_url(),
			'{store_email}'         => get_option( 'admin_email' ),
			'{wlr_recipient_name}'  => $this->getUserDisplayName( $transfer->recipient_email ) ?: $transfer->recipient_email,
			'{wlr_sender_name}'     => $this->getUserDisplayName( $transfer->sender_email ),
			'{wlr_sender_email}'    => $transfer->sender_email ?? '',
			'{wlr_recipient_email}' => $transfer->recipient_email ?? '',
			'{wlr_transfer_points}' => $transfer->points,
			'{wlr_account_link}'    => get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ),
			'{wlr_referral_url}'    => $ref_code ? $reward_helper->getReferralUrl( $ref_code ) : '',
			'{wlr_shop_url}'        => get_permalink( wc_get_page_id( 'shop' ) ),
			'{wlr_store_name}'      => apply_filters( 'wlps_before_display_store_name', get_option( 'blogname' ) ),
			'{wlr_points_label}'    => $point_label,
			'{wlr_confirm_link}'    => $confirm_link,
			'{wlr_user_points}'     => $loyalUser->points ?? 0,
		];

		$created_at = strtotime( gmdate( "Y-m-d H:i:s" ) );
		$log_data   = [
			'user_email'          => $transfer->sender_email,
			'action_type'         => 'point_transfer',
			'points'              => (int) $transfer->points,
			'action_process_type' => 'email_notification',
			'created_at'          => $created_at,
			/* translators: %1$s: number of points being transferred */
			'note'                => sprintf( __( 'Sending point transfer (%1$s) email failed', 'wp-loyalty-point-sharing' ), $transfer->points ),
			/* translators: %1$s: number of points being transferred */
			'customer_note'       => sprintf( __( 'Sending point transfer (%1$s) email failed', 'wp-loyalty-point-sharing' ), $transfer->points ),
		];


		if ( $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() ) ) {

			$log_data['note']          = sprintf( __( 'Point transfer email sent successfully', 'wp-loyalty-point-sharing' ) );
			$log_data['action_type']   = sprintf( __( 'point_transfer_email', 'wp-loyalty-point-sharing' ) );
			$log_data['customer_note'] = sprintf( __( 'Point transfer email sent successfully', 'wp-loyalty-point-sharing' ) );
		}

		$reward_helper->add_note( $log_data );
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
			'{wlr_transfer_points}' => __( 'The number of points that are going to transfer', 'wp-loyalty-point-sharing' ),
			'{wlr_points_label}'    => __( 'The label for points (e.g., points, credits)', 'wp-loyalty-point-sharing' ),
			'{wlr_shop_url}'        => __( 'The URL to the shop page of the website', 'wp-loyalty-point-sharing' ),
			'{wlr_confirm_link}'    => __( 'The URL to confirm the transfer point sharing', 'wp-loyalty-point-sharing' ),
			'{wlr_recipient_name}'  => __( 'The Recipient who is receiving the points', 'wp-loyalty-point-sharing' ),
			'{wlr_sender_name}'     => __( 'The Sender who sends the points', 'wp-loyalty-point-sharing' ),
			'{wlr_sender_email}'    => __( 'The Sender Email who sends the points', 'wp-loyalty-point-sharing' ),
			'{wlr_recipient_email}' => __( 'The Recipient who receives the points', 'wp-loyalty-point-sharing' ),

			//loyalty common
			'{wlr_referral_url}'    => __( 'The referral URL for the customer to share with friends', 'wp-loyalty-point-sharing' ),
			'{wlr_user_points}'     => __( 'The current points balance of the customer', 'wp-loyalty-point-sharing' ),
			'{wlr_user_name}'       => __( 'The display name of the customer', 'wp-loyalty-point-sharing' ),
			'{wlr_store_name}'      => __( 'The name of the store or website', 'wp-loyalty-point-sharing' ),
			// common
			'{site_title}'          => __( 'The title of the website', 'wp-loyalty-point-sharing' ),
			'{site_address}'        => __( 'The address of the website', 'wp-loyalty-point-sharing' ),
			'{site_url}'            => __( 'The URL of the website', 'wp-loyalty-point-sharing' ),
			'{store_email}'         => __( 'The store\'s contact email address', 'wp-loyalty-point-sharing' )
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
		], $this->template_path, $this->template_base ) );
	}

	public function get_content_plain() {
		return $this->format_string( wc_get_template_html( $this->template_html, [
			'email_heading'      => $this->get_heading(),
			'additional_content' => $this->get_additional_content(),
			'sent_to_admin'      => false,
			'plain_text'         => true,
			'email'              => $this,
		], $this->template_path, $this->template_base ) );
	}
}
