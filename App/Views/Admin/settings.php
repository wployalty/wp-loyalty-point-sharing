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
                <div class="wlps-setting-heading"><p><?php esc_html_e( 'SETTINGS', 'wp-loyalty-point-sharing' ) ?></p>
                </div>
                <div class="wlps-button-block">
                    <div class="wlps-back-to-apps">
                        <a class="button" target="_self"
                           href="<?php echo isset( $back_to_apps_url ) ? esc_url( $back_to_apps_url ) : '#'; ?>">
                            <img src="<?php echo ( isset( $back ) && ! empty( $back ) ) ? esc_url( $back ) : ''; //phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>"
                                 alt="<?php esc_html_e( "Back", "wp-loyalty-point-sharing" ); ?>">
							<?php esc_html_e( 'Back to WPLoyalty', 'wp-loyalty-point-sharing' ); ?></a>
                    </div>
                    <div class="wlps-save-changes">
                        <button type="button" id="wlps-setting-submit-button" onclick="wlps.saveSettings();">
                            <img src="<?php echo ( isset( $save ) && ! empty( $save ) ) ? esc_url( $save ) : '';//phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>">
                            <span><?php esc_html_e( 'Save Changes', 'wp-loyalty-point-sharing' ) ?></span>
                        </button>
                    </div>
                    <span class='spinner'></span>
                </div>
            </div>
            <div class="wlps-setting-body">
                <div class="wlps-settings-body-content">
                    <div class="wlps-field-block">
                        <div>
							<?php $wlps_enable_share_point = isset( $options['enable_share_point'] ) && ! empty( $options['enable_share_point'] ) && ( $options['enable_share_point'] === 'yes' ) ?
								$options['enable_share_point'] : \Wlps\App\Helpers\WlpsUtil::getDefaults( 'enable_share_point' ); ?>
                            <input type="checkbox" id="wlps_enable_share_point" name="enable_share_point"
                                   value="<?php echo esc_attr( $wlps_enable_share_point ); ?>"
                                   onclick="wlps.enableSharePoint('wlps_enable_share_point');"
								<?php echo ( $wlps_enable_share_point === 'yes' ) ? 'checked="checked"' : ''; ?>><label
                                    class="wlps-enable-share-point-label"
                                    for="wlps_enable_share_point"><?php esc_html_e( 'Enable Points Sharing feature ?', 'wp-loyalty-point-sharing' ); ?></label>
                        </div>
                    </div>
                    <div class="wlps-field-block">
                        <div>
                            <label
                                    class="wlps-settings-notification-label"><?php esc_html_e( 'Set maximum number of points to be transferred', 'wp-loyalty-point-sharing' ); ?></label>
                        </div>
                        <div class="wlps-point-share-block">
                            <div>
                                <label
                                        class="wlps-setting-label"><?php esc_html_e( 'How Much Maximum points user can send in one request ?', 'wp-loyalty-point-sharing' ) ?></label>
                            </div>
                            <div class="wlps_point_share_value_block">
                                <div class="wlps-point-share-max-points">
                                    <div class="wlps-input-field">
										<?php $wlps_max_transfer_points = isset( $options ) && ! empty( $options ) && is_array( $options ) && isset( $options['max_transfer_points'] ) && ! empty( $options['max_transfer_points'] ) ? $options['max_transfer_points'] : \Wlps\App\Helpers\WlpsUtil::getDefaults( 'max_transfer_points' ) ?>
                                        <input type="number" min="0" name="max_transfer_points"
                                               class="wlps-point-share-max-points-value"
                                               value="<?php echo esc_attr( $wlps_max_transfer_points ); ?>"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="wlps-email-expiry-email wlps-share-point-email-editor">
                        <div class="wlps-share-point-email-template">
                            <label
                                    for="wlps-share-point-email-template-label"><?php esc_html_e( 'Points Sharing Email Template Content', 'wp-loyalty-point-sharing' ); ?></label>
                        </div>
                        <div class="wlps-email-template" id="wlps-email-template-editor-sender">
                            <a href="<?php echo isset( $manage_sender_email ) ? esc_url( $manage_sender_email ) : '#'; ?>"
                               target="_blank" class="redirect-to-loyalty">
								<?php esc_html_e( "Manage sender email template", "wp-loyalty-point-sharing" ); ?>
                            </a>
                        </div>
                        <div class="wlps-email-template" id="wlps-email-template-editor-reciever">
                            <a href="<?php echo isset( $manage_receiver_email ) ? esc_url( $manage_receiver_email ) : '#'; ?>"
                               target="_blank" class="redirect-to-loyalty">
								<?php esc_html_e( "Manage receiver email template", "wp-loyalty-point-sharing" ); ?>
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
