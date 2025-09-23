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
        <div class="wlpe-spinner">
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
                        <button type="button" id="wlps-setting-submit-button" onclick="wlpe.saveSettings();">
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
							<?php $enable_expire_point = isset( $options['enable_expire_point'] ) && ! empty( $options['enable_expire_point'] ) && ( $options['enable_expire_point'] === 'yes' ) ?
								$options['enable_expire_point'] : 'no'; ?>
                            <input type="checkbox" id="wlps_enable_expire_point" name="enable_expire_point"
                                   value="<?php echo esc_attr( $enable_expire_point ); ?>"
                                   onclick="wlpe.enableExpiryPoint('wlps_enable_expire_point');"
								<?php echo isset( $options['enable_expire_point'] ) && ! empty( $options['enable_expire_point'] ) && ( $options['enable_expire_point'] == 'yes' ) ?
									'checked="checked"' : ""; ?>><label class="wlps-enable-expire-point-label"
                                                                        for="wlps_enable_expire_point"><?php esc_html_e( 'Enable Points Sharing feature ?', 'wp-loyalty-rules' ); ?></label>
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
										<?php $expire_after = isset( $options ) && ! empty( $options ) && is_array( $options ) && isset( $options['expire_after'] ) && ! empty( $options['expire_after'] ) ? $options['expire_after'] : 45 ?>
                                        <input type="number" min="0" name="expire_after" class="wlps-expire-after"
                                               value="<?php echo esc_attr( $expire_after ); ?>"/>
                                    </div>
                                    <!-- not used soon to remove -->
                                    <div class="wlpe-days">
                                        <p><?php esc_html_e( 'points', 'wp-loyalty-rules' ); ?></p>
                                    </div>
                                    <!-- not used soon to remove -->
                                    <input type="hidden" min="0" name="expire_period" class="wlpe-expired-period"
                                           value="day"/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="wlps-email-expiry-email">
                        <div class="wlps-send-email-checkbox">
							<?php $enable_expire_email = isset( $options['enable_expire_email'] ) && ! empty( $options['enable_expire_email'] ) ? $options['enable_expire_email'] : 0; ?>
                            <input type="checkbox" id="wlpe_enable_expire_email" name="enable_expire_email" value="1"
                                   onclick="wlpe.toggleSection();" <?php echo $enable_expire_email ? 'checked="checked"' : ""; ?>><label
                                    for="wlpe_enable_expire_email"><?php esc_html_e( 'Send an email notification before the expiry of points?', 'wp-loyalty-rules' ); ?></label>
                        </div>
                        <div class="wlps-email-notification"
                             style="display: <?php echo $enable_expire_email ? 'block' : 'none'; ?>">
                            <div id="wlps_expire_email_block">
                                <label
                                        class="wlps-setting-label"><?php esc_html_e( 'How many days before an expiry email notification be sent ?', 'wp-loyalty-rules' ); ?></label>
                            </div>
                            <div class="wlps_expire_email_after_value_block">
                                <div class="wlpe-expire-time-1">
                                    <div class="wlps-input-field">
										<?php $expire_email_after = isset( $options ) && ! empty( $options ) && is_array( $options ) && isset( $options['expire_email_after'] ) && ! empty( $options['expire_email_after'] ) ? $options['expire_email_after'] : 7 ?>
                                        <input type="number" min="0" name="expire_email_after"
                                               class="wlps-email-notification-value"
                                               value="<?php echo esc_attr( $expire_email_after ); ?>"/>
                                    </div>
                                    <div class="wlpe-days">
                                        <p><?php esc_html_e( 'in days', 'wp-loyalty-rules' ); ?></p>
                                    </div>
                                    <input type="hidden" min="0" name="expire_email_period"
                                           class="wlps-email-notification-time"
                                           value="day"/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="wlps-email-expiry-email wlps-email-expiry-editor"
                         style="display: <?php echo $enable_expire_email ? 'flex' : 'flex'; ?>">
                        <div class="wlps-send-email-checkbox">
							<?php $enable_expire_email = isset( $options['enable_expire_email'] ) && ! empty( $options['enable_expire_email'] ) ? $options['enable_expire_email'] : 0; ?>
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
                    <input type="hidden" name="wlps_nonce"
                           value="<?php echo isset( $wlpe_setting_nonce ) && ! empty( $wlpe_setting_nonce ) ? esc_attr( $wlpe_setting_nonce ) : ''; ?>">
                    <input type="hidden" name="option_key"
                           value="<?php echo ! empty( $save_key ) ? esc_attr( $save_key ) : 'wlpe_settings' ?>">
                    <div class="wlps-field-block" id="wlpe_enable_customer_page_expire_content_section"
                         style="<?php
					     echo isset( $options['enable_customer_page_expire_content'] ) &&
					          $options['enable_customer_page_expire_content'] === 'yes' ? 'display:block' : 'display:none'; ?>">
                        <div class="wlps-expire-time-block">
                            <div>
                                <label
                                        class="wlps-setting-label"><?php echo esc_html_e( 'How many days to consider for the "Upcoming Points Expiration" List ?', 'wp-loyalty-rules' ) ?></label>
                            </div>
                            <div class="wlps_expire_after_value_block">
                                <div class="wlps-expire-time-1">
                                    <div class="wlps-input-field">
                                        <input type="number" min="0" name="expire_date_range"
                                               class="wlps-expire-after"
                                               value="<?php echo isset( $options ) && ! empty( $options ) && is_array( $options ) && isset( $options['expire_date_range'] ) && ! empty( $options['expire_date_range'] ) ? (int) $options['expire_date_range'] : 30 ?>"/>
                                    </div>
                                    <div class="wlpe-days">
                                        <p><?php esc_html_e( 'days', 'wp-loyalty-rules' ); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
