<?php
defined( 'ABSPATH' ) || exit;

/**
 * Plain Text Point Transfer Confirmation Email
 */

echo esc_html__( "Hi {wlr_sender_name},", 'wp-loyalty-point-sharing' ) . "\n\n";

echo esc_html__( "You are about to send {wlr_transfer_points} {wlr_points_label} to {wlr_recipient_name}.", 'wp-loyalty-point-sharing' ) . "\n\n";

echo esc_html__( "Please confirm your point transfer using the link below:", 'wp-loyalty-point-sharing' ) . "\n";
echo "{wlr_confirm_link}\n\n";

echo esc_html__( "You can also view your referral link:", 'wp-loyalty-point-sharing' ) . "\n";
echo "{wlr_referral_url}\n\n";

echo esc_html__( "Thank you,", 'wp-loyalty-point-sharing' ) . "\n";
echo "{site_title}\n";
