<?php
defined( 'ABSPATH' ) || exit;

/**
 * Custom Point Transfer Confirmation Email (HTML)
 *
 * Rendered via WooCommerce email system with default WooCommerce styles.
 */

// Include WooCommerce email header
do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>Hi <?php echo esc_html( '{wlr_sender_name}' ?? '' ); ?>,</p>

<p>
    You are about to send
    <strong>{wlr_points} {wlr_points_label}</strong>
    to <strong>{wlr_recipient_name}</strong>.
</p>

<p>Please confirm by clicking the button below:</p>

<p>
    <a href="{wlr_confirm_link}" target="_blank" class="button">
        Confirm Transfer
    </a>
</p>

<p>
    View your referral link:
    <a href="{wlr_referral_url}" target="_blank">{wlr_referral_url}</a>
</p>

<p>
    Thank you,<br>
	<?php echo esc_html( '{site_name}' ?? get_bloginfo( 'name' ) ); ?>
</p>

<?php
// Include WooCommerce email footer
do_action( 'woocommerce_email_footer', $email );
?>
