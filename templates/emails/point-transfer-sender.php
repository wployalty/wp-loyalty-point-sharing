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

<p>Hi <?php echo esc_html( $placeholders['{sender_name}'] ?? '' ); ?>,</p>

<p>
    You are about to send
    <strong><?php echo intval( $placeholders['{points_amount}'] ?? 0 ); ?><?php echo esc_html( $placeholders['{points_label}'] ?? 'points' ); ?></strong>
    to <strong><?php echo esc_html( $placeholders['{recipient_name}'] ?? '' ); ?></strong>.
</p>

<p>Please confirm by clicking the button below:</p>

<p>
    <a href="<?php echo esc_url( $placeholders['{confirm_link}'] ?? '#' ); ?>" target="_blank" class="button">
        Confirm Transfer
    </a>
</p>

<p>
    Thank you,<br>
	<?php echo esc_html( $placeholders['{site_name}'] ?? get_bloginfo( 'name' ) ); ?>
</p>

<?php
// Include WooCommerce email footer
do_action( 'woocommerce_email_footer', $email );
?>
