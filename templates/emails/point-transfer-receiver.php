<?php
/**
 * Point Transfer Receiver Email (HTML)
 *
 * This file is rendered via WooCommerce email system.
 */
defined( 'ABSPATH' ) || exit;

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
WC_Emails::instance()->email_header($email_heading);
?>

<p>
	<?php esc_html_e( 'Hi', 'wp-loyalty-point-sharing' ); ?> <?php echo esc_html( '{wlr_recipient_name}' ); ?>,
</p>

<p>
	<?php esc_html_e( 'Great news! {wlr_sender_name} has sent you {wlr_transfer_points} {wlr_points_label}', 'wp-loyalty-point-sharing' ); ?>
</p>

<p><?php esc_html_e( 'You can view and use your points by visiting your account:', 'wp-loyalty-point-sharing' ); ?></p>

<p>
    <a href="{wlr_account_link}" class="button" target="_blank">
		<?php esc_html_e( 'View My Points', 'wp-loyalty-point-sharing' ); ?>
    </a>
</p>

<p>
	<?php esc_html_e( 'Thank you,', 'wp-loyalty-point-sharing' ); ?><br>
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
WC_Emails::instance()->email_footer();
?>
