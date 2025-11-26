<?php
defined( 'ABSPATH' ) || exit;

/**
 * Plain Text Point Received Confirmation Email
 *
 * Uses shortcodes for dynamic content.
 */

echo esc_html__("Hi {wlr_recipient_name}",'wp-loyalty-point-sharing')."\n\n";

echo esc_html__( "You have received {wlr_transfer_points} {wlr_points_label} from {wlr_sender_name}",'wp-loyalty-point-sharing')."\n\n";

echo esc_html__( "Check your updated points balance here: {wlr_account_link}", 'wp-loyalty-point-sharing') ."\n\n";

echo esc_html__("Thank you", 'wp-loyalty-point-sharing') ."\n";
echo esc_html__("{site_title}", 'wp-loyalty-point-sharing') ."\n";
