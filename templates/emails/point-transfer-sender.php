<?php
/**
 * Point Transfer Sender Email (HTML)
 */
defined( 'ABSPATH' ) || exit;

echo '<h2>' . esc_html( $email_heading ) . '</h2>';
?>
<p>
    Hi {sender_name},
</p>
<p>
    You are about to send <strong>{points_amount} {points_label}</strong> to <strong>{recipient_name}</strong>.
</p>
<p>
    Please confirm by clicking the link below:
</p>
<p>
    <a href="{confirm_link}" target="_blank">Confirm Transfer</a>
</p>
<p>
    Thank you,<br>
    {site_name}
</p>
