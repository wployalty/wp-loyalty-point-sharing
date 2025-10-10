<?php
defined( 'ABSPATH' ) || exit;

/**
 * Plain Text Point Received Confirmation Email
 *
 * Uses shortcodes for dynamic content.
 */

echo "Hi {wlr_recipient_name},\n\n";

echo "You have received {wlr_points} {wlr_points_label} from {wlr_sender_name}.\n\n";

echo "Check your updated points balance here: {wlr_account_url}\n\n";

echo "Thank you,\n";
echo "{site_name}\n";
