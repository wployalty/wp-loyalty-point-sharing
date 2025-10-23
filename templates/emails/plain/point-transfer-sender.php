<?php
defined( 'ABSPATH' ) || exit;

/**
 * Plain Text Point Transfer Confirmation Email
 *
 * Uses shortcodes for dynamic content.
 */

echo "Hi {wlr_sender_name},\n\n";

echo "You have successfully shared {wlr_points} {wlr_points_label} with {wlr_recipient_name}.\n\n";

echo "Check your updated points balance here: {wlr_account_url}\n\n";

echo "Thank you,\n";
echo "{site_title}\n";
