<!-- Share Points Button -->
<a href="#" id="wlps-open-share-modal"
   style="color:#007BFF; text-decoration:none; cursor:pointer;"
   onmouseover="this.style.textDecoration='underline';"
   onmouseout="this.style.textDecoration='none';">
	<?php esc_html_e( 'Share Points', 'wp-loyalty-point-sharing' ); ?>
</a>

<!-- Modal Container -->
<div id="wlps-share-points-modal" class="wlps-modal"
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index:9999;">
    <div class="wlps-modal-content"
         style="background:#fff; max-width:500px; margin:10% auto; padding:20px; border-radius:8px; position:relative;">

        <!-- Close Button -->
        <button type="button" class="wlps-close-modal" style="position:absolute; top:10px; right:10px; font-size:16px;">
            &times;
        </button>

        <!-- Modal Title -->
        <h3><?php esc_html_e( 'Transfer Your Points', 'wp-loyalty-point-sharing' ); ?></h3>

        <!-- Transfer Form -->
        <form id="wlps-transfer-form">
            <label for="transfer-email"><?php esc_html_e( 'Recipient Email', 'wp-loyalty-point-sharing' ); ?></label>
            <input type="email" id="transfer-email" name="transfer_email" required
                   style="width:100%; padding:8px; margin-bottom:10px;">

            <label for="transfer-points"><?php esc_html_e( 'Points to Transfer', 'wp-loyalty-point-sharing' ); ?></label>
            <input type="number" id="transfer-points" name="transfer_points" min="1" required
                   style="width:100%; padding:8px; margin-bottom:10px;">
            <span id="transfer-points-error" style="color:red; font-size:13px; display:none;"></span>

            <button type="submit" class="button button-primary" style="padding:10px 20px;">
				<?php esc_html_e( 'Transfer Points', 'wp-loyalty-point-sharing' ); ?>
            </button>
        </form>
    </div>
</div>

<!-- Custom Alert Modal -->
<div id="wlps-alert-modal" class="wlps-modal"
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000;">
    <div class="wlps-modal-content"
         style="background:#fff; max-width:400px; margin:15% auto; padding:20px; border-radius:8px; position:relative; text-align:center;">

        <button type="button" class="wlps-close-alert-modal"
                style="position:absolute; top:10px; right:10px; font-size:16px;">
            &times;
        </button>

        <h3 id="wlps-alert-title"><?php esc_html_e( 'Confirmation Required', 'wp-loyalty-point-sharing' ); ?></h3>
        <p id="wlps-alert-message"></p>

        <input type="text" id="wlps-alert-input" placeholder="Type CONFIRM"
               style="width:100%; padding:8px; margin:10px 0; display:none;">

        <div style="margin-top:15px;">
            <button type="button" class="button button-primary" id="wlps-alert-ok">
				<?php esc_html_e( 'OK', 'wp-loyalty-point-sharing' ); ?>
            </button>
            <button type="button" class="button" id="wlps-alert-cancel">
				<?php esc_html_e( 'Cancel', 'wp-loyalty-point-sharing' ); ?>
            </button>
        </div>
    </div>
</div>

