<?php
/**
 * @author      Wployalty (Alagesan)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://www.wployalty.net
 * */

defined( 'ABSPATH' ) or die;
?>
<div id="wlps-settings">
    <div class="wlps-setting-page-holder">
        <div class="wlps-spinner">
            <span class="spinner"></span>
        </div>
        <form id="wlps-settings_form" method="post">
            <div class="wlps-settings-header">
                <div class="wlpe-setting-heading"><p><?php esc_html_e( 'SETTINGS', 'wp-loyalty-rules' ) ?></p></div>
                <div class="wlps-button-block">
                    <div class="wlps-back-to-apps">
                        <a class="button" target="_self"
                           href="<?php echo isset( $app_url ) ? esc_url( $app_url ) : '#'; ?>">
                            <img src="<?php echo ( isset( $back ) && ! empty( $back ) ) ? esc_url( $back ) : ''; //phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>"
                                 alt="<?php esc_html_e( "Back", "wp-loyalty-rules" ); ?>">
							<?php esc_html_e( 'Back to WPLoyalty', 'wp-loyalty-rules' ); ?></a>
                    </div>
                    <div class="wlps-save-changes">
                        <button type="button" id="wlps-setting-submit-button" onclick="wlps.saveSettings();">
                            <img src="<?php echo ( isset( $save ) && ! empty( $save ) ) ? esc_url( $save ) : '';//phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>">
                            <span><?php esc_html_e( 'Save Changes', 'wp-loyalty-rules' ) ?></span>
                        </button>
                    </div>
                    <span class='spinner'></span>
                </div>
            </div>
            <div class="wlps-setting-body">
                <div class="wlps-settings-body-content">
                    <div class="wlps-field-block">
                        <div>
							<?php $enable_share_point = isset( $options['enable_share_point'] ) && ! empty( $options['enable_share_point'] ) && ( $options['enable_share_point'] === 'yes' ) ?
								$options['enable_share_point'] : 'no'; ?>
                            <input type="checkbox" id="wlps_enable_share_point" name="enable_share_point"
                                   value="<?php echo esc_attr( $enable_share_point ); ?>"
                                   onclick="wlps.enableSharePoint('wlps_enable_share_point');"
								<?php echo isset( $options['enable_share_point'] ) && ! empty( $options['enable_share_point'] ) && ( $options['enable_share_point'] == 'yes' ) ?
									'checked="checked"' : ""; ?>><label class="wlps-enable-expire-point-label"
                                                                        for="wlps_enable_share_point"><?php esc_html_e( 'Enable Points Sharing feature ?', 'wp-loyalty-rules' ); ?></label>
                        </div>
                    </div>
                    <div class="wlps-field-block">
                        <div>
                            <label
                                    class="wlps-settings-notification-label"><?php esc_html_e( 'Set maximum number of points to be transferred', 'wp-loyalty-rules' ); ?></label>
                        </div>
                        <div class="wlps-expire-time-block">
                            <div>
                                <label
                                        class="wlps-setting-label"><?php esc_html_e( 'How Much Maximum points user can send in one request ?', 'wp-loyalty-rules' ) ?></label>
                            </div>
                            <div class="wlps_expire_after_value_block">
                                <div class="wlps-expire-time-1">
                                    <div class="wlps-input-field">
										<?php $max_transfer_points = isset( $options ) && ! empty( $options ) && is_array( $options ) && isset( $options['max_transfer_points'] ) && ! empty( $options['max_transfer_points'] ) ? $options['max_transfer_points'] : 45 ?>
                                        <input type="number" min="0" name="max_transfer_points"
                                               class="wlps-expire-after"
                                               value="<?php echo esc_attr( $max_transfer_points ); ?>"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="wlps-email-expiry-email wlps-email-expiry-editor">
                        <div class="wlps-send-email-checkbox">
                            <label
                                    for="wlps-expire-email-template-label"><?php esc_html_e( 'Points Sharing Email Template Content', 'wp-loyalty-rules' ); ?></label>
                        </div>
                        <div class="wlps-email-template" id="wlps-email-template-editor">
                            <a href="<?php echo isset( $manage_sender_email ) ? esc_url( $manage_sender_email ) : '#'; ?>"
                               target="_blank" class="redirect-to-loyalty">
								<?php esc_html_e( "Manage sender email template", "wp-loyalty-rules" ); ?>
                            </a>
                        </div>
                        <div class="wlps-email-template" id="wlps-email-template-editor">
                            <a href="<?php echo isset( $manage_receiver_email ) ? esc_url( $manage_receiver_email ) : '#'; ?>"
                               target="_blank" class="redirect-to-loyalty">
								<?php esc_html_e( "Manage receiver email template", "wp-loyalty-rules" ); ?>
                            </a>
                        </div>
                    </div>
                    <input type="hidden" name="action" value="wlps_save_settings">
                    <input type="hidden" name="option_key"
                           value="<?php echo ! empty( $save_key ) ? esc_attr( $save_key ) : 'wlps_settings' ?>">
                </div>
            </div>
        </form>
    </div>
</div>
