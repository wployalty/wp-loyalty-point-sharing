<?php
/**
 * Plugin Name: WPLoyalty - Point Sharing
 * Plugin URI: https://www.wployalty.net
 * Description: Customers can share loyalty points between them.
 * Version: 1.0.0
 * Author: Wployalty
 * Slug: wp-loyalty-point-sharing
 * Text Domain: wp-loyalty-point-sharing
 * Domain Path: /i18n/languages/
 * Requires Plugins: woocommerce
 * Requires at least: 4.9.0
 * WC requires at least: 6.5
 * WC tested up to: 9.8
 * Author URI: https://wployalty.net/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) or die;

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( FeaturesUtil::class ) ) {
		FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__ );
	}
} );
if ( ! function_exists( 'isWLPSWoocommerceActive' ) ) {
	function isWLPSWoocommerceActive() {
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins', [] ) );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', [] ) );
		}

		return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
	}
}

if ( ! function_exists( 'isWLPSLoyaltyActive' ) ) {
	function isWLPSLoyaltyActive() {
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins', [] ) );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', [] ) );
		}

		return in_array( 'wp-loyalty-rules/wp-loyalty-rules-lite.php', $active_plugins ) || array_key_exists( 'wp-loyalty-rules/wp-loyalty-rules-lite.php', $active_plugins )
		       || in_array( 'wp-loyalty-rules/wp-loyalty-rules.php', $active_plugins ) || array_key_exists( 'wp-loyalty-rules/wp-loyalty-rules.php', $active_plugins )
		       || in_array( 'wployalty/wp-loyalty-rules-lite.php', $active_plugins ) || array_key_exists( 'wployalty/wp-loyalty-rules-lite.php', $active_plugins );
	}
}

if ( ! isWLPSWoocommerceActive() || ! isWLPSLoyaltyActive() ) {
	return;
}

defined( 'WLPS_PLUGIN_NAME' ) or define( 'WLPS_PLUGIN_NAME', 'WPLoyalty - Point Sharing' );
defined( 'WLPS_PLUGIN_VERSION' ) or define( 'WLPS_PLUGIN_VERSION', '1.0.0' );
defined( 'WLPS_TEXT_DOMAIN' ) or define( 'WLPS_TEXT_DOMAIN', 'wp-loyalty-point-sharing' );
defined( 'WLPS_PLUGIN_SLUG' ) or define( 'WLPS_PLUGIN_SLUG', 'wp-loyalty-point-sharing' );
defined( 'WLPS_PLUGIN_PATH' ) or define( 'WLPS_PLUGIN_PATH', str_replace( '\\', '/', __DIR__ ) . '/' );
defined( 'WLPS_PLUGIN_DIR' ) or define( 'WLPS_PLUGIN_DIR', str_replace( '\\', '/', __DIR__ ) . '/' );
defined( 'WLPS_PLUGIN_URL' ) or define( 'WLPS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
defined( 'WLPS_PLUGIN_FILE' ) or define( 'WLPS_PLUGIN_FILE', __FILE__ );
defined( 'WLPS_PLUGIN_AUTHOR' ) or define( 'WLPS_PLUGIN_AUTHOR', 'WPLoyalty' );
defined( 'WLPS_VIEW_PATH' ) or define( 'WLPS_VIEW_PATH', str_replace( "\\", '/', __DIR__ ) . '/App/Views' );
defined( 'WLPS_MINIMUM_PHP_VERSION' ) or define( 'WLPS_MINIMUM_PHP_VERSION', '7.0.0' );
defined( 'WLPS_MINIMUM_WP_VERSION' ) or define( 'WLPS_MINIMUM_WP_VERSION', '4.9' );
defined( 'WLPS_MINIMUM_WC_VERSION' ) or define( 'WLPS_MINIMUM_WC_VERSION', '6.5' );
defined( 'WLPS_MINIMUM_WLR_VERSION' ) or define( 'WLPS_MINIMUM_WLR_VERSION', '1.2.10' );

if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	return;
}
require_once __DIR__ . '/vendor/autoload.php';

add_action( 'plugins_loaded', function () {
	if ( ! class_exists( '\Wlps\App\Router' ) ) {
		return;
	}
	if ( \Wlps\App\Helper\Plugin::checkDependencies() ) {
		\Wlps\App\Router::init();
	}
} );