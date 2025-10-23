<?php
/**
 * Point Transfer Receiver Email (HTML)
 *
 * This file is rendered via WooCommerce email system.
 */
defined( 'ABSPATH' ) || exit;

// WooCommerce email header
do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>Hi {wlr_recipient_name},</p>

<p>
    Great news! <strong>{wlr_sender_name}</strong>
    has sent you
    <strong>{wlr_points} {wlr_points_label}</strong>.
</p>

<p>You can view and use your points by visiting your account:</p>

<p>
    <a href="{wlr_account_link}" class="button">
        View My Points
    </a>
</p>

<p>
    Thank you,<br>
    {site_name}
</p>

<?php
// WooCommerce email footer
do_action( 'woocommerce_email_footer', $email );
?>
