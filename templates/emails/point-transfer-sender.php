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

<p>Hi <?php esc_html_e( '{wlr_sender_name}' ?? '' ); ?>,</p>

<p>
	<?php esc_html_e( 'You are about to send', 'wp-loyalty-point-sharing' ); ?>
    <strong><?php echo esc_html( '{wlr_points}' ); ?><?php echo esc_html( '{wlr_points_label}' ); ?></strong>
	<?php esc_html_e( 'to', 'wp-loyalty-point-sharing' ); ?>
    <strong><?php echo esc_html( '{wlr_recipient_name}' ); ?></strong>.
</p>

<p><?php esc_html_e( 'Please confirm by clicking the button below:', 'wp-loyalty-point-sharing' ); ?></p>

<p>
    <a href="{wlr_confirm_link}" target="_blank" class="button">
		<?php esc_html_e( 'Confirm Transfer', 'wp-loyalty-point-sharing' ); ?>
    </a>
</p>

<p>
	<?php esc_html_e( 'View your referral link:', 'wp-loyalty-point-sharing' ); ?>
    <a href="{wlr_referral_url}" target="_blank">{wlr_referral_url}</a>
</p>

<p>
    Thank you,<br>
	<?php echo esc_html( '{site_title}' ?? get_bloginfo( 'name' ) ); ?>
</p>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
// Include WooCommerce email footer
do_action( 'woocommerce_email_footer', $email );
?>
