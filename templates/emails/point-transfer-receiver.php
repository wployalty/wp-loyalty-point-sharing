<?php
/**
 * Point Transfer Receiver Email (HTML)
 */
defined( 'ABSPATH' ) || exit;

echo '<h2>' . esc_html( $email_heading ) . '</h2>';
?>
<p>
    Hi {recipient_name},
</p>
<p>
    Great news! <strong>{sender_name}</strong> has sent you <strong>{points_amount} {points_label}</strong>.
</p>
<p>
    You can view and use your points by visiting your account:
</p>
<p>
    <a href="{account_link}" target="_blank">View My Points</a>
</p>
<p>
    Thank you,<br>
    {site_name}
</p>
