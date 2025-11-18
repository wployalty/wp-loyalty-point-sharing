<?php

defined( 'ABSPATH' ) or die();
?>
<div id="wlps-main">
    <div class="wlps-main-header">
        <h1><?php echo esc_html__( 'WPLoyalty - Point Sharing', 'wp-loyalty-point-sharing' ); ?> </h1>
        <div><b><?php echo esc_html( 'v' . WLPS_PLUGIN_VERSION ); ?></b></div>
    </div>
    <div class="wlps-tabs">
        <a class="<?php echo ( isset( $current_view ) && $current_view == "point_sharing" ) ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url( admin_url( 'admin.php?' . http_build_query( array(
				   'page' => WLPS_PLUGIN_SLUG,
				   'view' => 'point_sharing'
			   ) ) ) ); ?>"
        ><i class="wlr wlps-customers"></i><?php esc_html_e( 'Manage Point Sharing', 'wp-loyalty-point-sharing' ) ?></a>
        <a class="<?php echo ( isset( $current_view ) && $current_view == "settings" ) ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url( admin_url( 'admin.php?' . http_build_query( array(
				   'page' => WLPS_PLUGIN_SLUG,
				   'view' => 'settings'
			   ) ) ) ) ?>"
        ><i class="wlr wlps-settings"></i><?php esc_html_e( 'Settings', 'wp-loyalty-point-sharing' ) ?></a>
    </div>
    <div>
		<?php echo wp_kses_post(apply_filters( 'wlps_extra_content', ( isset( $extra ) ? $extra : '' ) )); ?>
        <?php //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php echo isset( $tab_content ) ? $tab_content : null; ?>
    </div>
</div>
