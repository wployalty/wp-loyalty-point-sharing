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

<p>Hi <?php echo esc_html( $placeholders['{recipient_name}'] ?? '' ); ?>,</p>

<p>
    Great news! <strong><?php echo esc_html( $placeholders['{sender_name}'] ?? '' ); ?></strong>
    has sent you
    <strong><?php echo intval( $placeholders['{points_amount}'] ?? 0 ); ?><?php echo esc_html( $placeholders['{points_label}'] ?? 'points' ); ?></strong>.
</p>

<p>You can view and use your points by visiting your account:</p>

<p>
    <a href="<?php echo esc_url( $placeholders['{account_link}'] ?? '#' ); ?>" class="button">
        View My Points
    </a>
</p>

<p>
    Thank you,<br>
	<?php echo esc_html( $placeholders['{site_name}'] ?? get_bloginfo( 'name' ) ); ?>
</p>

<?php
// WooCommerce email footer
do_action( 'woocommerce_email_footer', $email );
?>
