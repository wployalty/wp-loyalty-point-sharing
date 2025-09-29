<?php
/**
 * Point Transfer Sender Email (HTML)
 */
defined( 'ABSPATH' ) || exit;

echo '<h2>' . esc_html( $email_heading ) . '</h2>';
?>

<p>
    Hi <?php echo esc_html( $sender_name ); ?>,
</p>
<p>
    You are about to send
    <strong><?php echo intval( $points_amount ); ?><?php echo esc_html( $points_label ); ?></strong>
    to <strong><?php echo esc_html( $recipient_name ); ?></strong>.
</p>
<p>
    Please confirm by clicking the link below:
</p>
<p>
    <a href="<?php echo esc_url( $confirm_link ); ?>" target="_blank">Confirm Transfer</a>
</p>
<p>
    Thank you,<br>
	<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
</p>
