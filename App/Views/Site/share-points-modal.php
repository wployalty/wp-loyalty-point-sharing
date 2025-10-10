<!-- Share Points Button -->
<a href="#" id="wlps-open-share-modal" class="wlps-open-share"
   onclick="wlps.openSharePointsModal(event); ">
	<?php esc_html_e( 'Share Points', 'wp-loyalty-point-sharing' ); ?>
</a>

<!-- Share Points Modal -->
<div id="wlps-share-points-modal" class="wlps-modal" style="display:none;">
    <div class="wlps-modal-content">
        <button type="button" class="wlps-close-modal"
                onclick="wlps.closeSharePointsModal();">&times;
        </button>
        <h3><?php esc_html_e( 'Transfer Your Points', 'wp-loyalty-point-sharing' ); ?></h3>

        <form id="wlps-transfer-form" onsubmit="wlps.handleTransferFormSubmit();">
            <label for="transfer-email"><?php esc_html_e( 'Recipient Email', 'wp-loyalty-point-sharing' ); ?></label>
            <input type="email" id="transfer-email" name="transfer_email" required>

            <label for="transfer-points"><?php esc_html_e( 'Points to Transfer', 'wp-loyalty-point-sharing' ); ?></label>
            <input type="number" id="transfer-points" name="transfer_points" min="1" required>

            <button type="submit" class="button button-primary">
				<?php esc_html_e( 'Transfer Points', 'wp-loyalty-point-sharing' ); ?>
            </button>
            <span id="transfer-points-error" style="color:red;"></span>
        </form>
    </div>
</div>

<!-- Custom Alert Modal -->
<div id="wlps-alert-modal" class="wlps-modal" style="display:none;">
    <div class="wlps-modal-content wlps-alert-content">
        <button type="button" class="wlps-close-alert-modal">&times;</button>
        <h3 id="wlps-alert-title"><?php esc_html_e( 'Confirmation Required', 'wp-loyalty-point-sharing' ); ?></h3>
        <p id="wlps-alert-message"></p>
        <input type="text" id="wlps-alert-input" placeholder="Type CONFIRM">
        <div class="wlps-alert-buttons">
            <button type="button" class="button button-primary" id="wlps-alert-ok">
				<?php esc_html_e( 'OK', 'wp-loyalty-point-sharing' ); ?>
            </button>
            <button type="button" class="button" id="wlps-alert-cancel">
				<?php esc_html_e( 'Cancel', 'wp-loyalty-point-sharing' ); ?>
            </button>
        </div>
    </div>
</div>
