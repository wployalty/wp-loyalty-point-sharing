<?php
defined( 'ABSPATH' ) || exit;

/**
 * Custom Point Transfer Confirmation Email (HTML)
 *
 * Rendered via WooCommerce email system with default WooCommerce styles.
 */

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p><?php esc_html_e( "Hi {wlr_sender_name}", 'wp-loyalty-point-sharing' ); ?>,</p>

<p>
	<?php esc_html_e( 'You are about to send {wlr_transfer_points} {wlr_points_label} to {wlr_recipient_name}', 'wp-loyalty-point-sharing' ); ?>
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
	<?php echo esc_html__( 'Thank you,', 'wp-loyalty-point-sharing' ); ?><br>
	<?php echo esc_html( '{site_title}' ?? get_bloginfo( 'name' ) ); ?>
</p>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
do_action( 'woocommerce_email_footer', $email );
?>
