<?php

use Premmerce\Filter\FilterPlugin;

/**
 * Plugin Name: Premmerce Product Filter for WooCommerce (Premium)
 * Plugin URI:        https://premmerce.com/woocommerce-product-filter/
 * Description:       Premmerce Product Filter for WooCommerce plugin is a convenient and flexible tool for managing filters for WooCommerce products.
 * Version:     3.7.3
 * Update URI: https://api.freemius.com
 *  *
 * Author:            Premmerce
 * Author URI:        https://premmerce.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       premmerce-filter
 * Domain Path:       /languages
 *
 * Tested up to: 6.5
 * WC requires at least: 3.6.0
 * WC tested up to: 8.7.0
 *
 *  *
 *
 * @fs_premium_only /src/Admin/Tabs/SeoSettings.php, /var/migration/seo/, /src/Seo/Sitemap/, /src/Seo/SeoListener.php, /src/Seo/RulesGenerator.php, /src/Seo/WPMLHelper.php, /src/Permalinks/, /src/Exceptions/, src/Filter/Items/Types/InStockFilter.php, src/Filter/Items/Types/OnSaleFilter.php, src/Filter/Items/Types/RatingFilter.php
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

if (! function_exists('premmerce_pwpf_fs')) {
	call_user_func(
		function () {
			include_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
			include_once plugin_dir_path(__FILE__) . '/freemius.php';
			$main = new FilterPlugin(__FILE__);

			register_activation_hook(__FILE__, [$main, 'activate']);

			register_deactivation_hook(__FILE__, [$main, 'deactivate']);

			register_uninstall_hook(__FILE__, [FilterPlugin::class, 'uninstall']);

			$main->run();
		}
	);
}
