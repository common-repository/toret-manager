<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * @wordpress-plugin
 * Plugin Name:             Toret Manager
 * Plugin URI:              https://toret.net
 * Description:             Connects WordPress and WooCommerce website with Toret.net.
 * Version:                 1.1.1
 * WC requires at least:    6.7
 * WC tested up to:         9.3.3
 * Tested up to:            6.6
 * Requires PHP:            7.4
 * Requires at least:       6.2
 * Author:                  Toret
 * Author URI:              https://www.toret.net/cs/kontakt/
 * License:                 GPLv2 or later
 * License URI:             License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:             toret-manager
 * Domain Path:             /languages
 */

/**
 * If this file is called directly, abort.
 */
if (!defined('WPINC')) {
    die;
}

/**
 * Constants
 */
// Base
define('TORET_MANAGER_VERSION', '1.1.1');
define('TORET_MANAGER_SLUG', 'toret-manager');
define('TORET_MANAGER_FUNCTION_SLUG', 'toret_manager');
define('TORET_MANAGER_SHORTCUT_SLUG', 'trman');
define('TORET_MANAGER_DIR', plugin_dir_path(__FILE__));
define('TORET_MANAGER_URL', plugin_dir_url(__FILE__));
define('TORET_MANAGER_NAME', plugin_basename(__FILE__));
define('TORET_MANAGER_SETTINGS', 'admin.php?page=' . TORET_MANAGER_SLUG);
define('TORET_MANAGER_ADMIN_PAGE', admin_url(TORET_MANAGER_SETTINGS));
// Modules
define('TORET_MANAGER_ENABLED_MODULES_OPTION', 'toret_manager_enabled_modules');
define('TORET_MANAGER_ITEM_INTERNALID', 'toret_manager_item_internal_id');
define('TORET_MANAGER_EXCLUDED_ITEM', 'toret_manager_excluded_item');
define('TORET_MANAGER_PRODUCT_TERMS', array('product_cat', 'product_tag', 'product_attribute'));
define('TORET_MANAGER_PRODUCT_TERMS_WOO', array('product_cat', 'product_tag'));
define('TORET_MANAGER_MODULES', array('order', 'product', 'review', 'user', 'post'));
define('TORET_MANAGER_TERMS', array('category', 'post_tag'));
// Log
define('TORET_MANAGER_LOG_SLUG', TORET_MANAGER_SLUG . '-log');
define('TORET_MANAGER_LOG_PAGE', admin_url() . 'admin.php?page=' . TORET_MANAGER_LOG_SLUG);
define('TORET_MANAGER_LOG_DELETE', admin_url() . 'admin.php?page=' . TORET_MANAGER_LOG_SLUG . "&delete=log");
define('TORET_MANAGER_LOG_TABLE', TORET_MANAGER_FUNCTION_SLUG . '_log');
define('TORET_MANAGER_LOG_API', true);
// API
define('TORET_MANAGER_API_URL', 'https://app.toret.net/v1/Endpoints/');
define('TORET_MANAGER_API_KEY', 'trman_api_key');
define('TORET_MANAGER_USER_HASH', 'trman_user_hash');
define('TORET_MANAGER_SHOP_ID', 'trman_shop_id');
define('TORET_MANAGER_API_ADMIN', 'https://app.toret.net');
define('TORET_MANAGER_ASSOCIATIVE_SYNC', 'trman_associative_sync');
define('TORET_MANAGER_NOTIFY_WP_SCHEDULER', 'trman_notify_wp_scheduler');
// Documentation and App
define('TORET_MANAGER_DOCUMENTATION_URL', 'https://www.toret.net/dokumentace/');
define('TORET_MANAGER_APP_URL', "https://app.toret.net");
define('TORET_MANAGER_APP_SHOPS_URL', "https://app.toret.net/admin/shops.php");
// Async upload
//define('TORET_MANAGER_ASYNC_UPLOAD_TYPES', array('post','order','product','term','comment','user'));
define('TORET_MANAGER_ASYNC_UPLOAD_TYPES', array());
// Notify queue
define('TORET_MANAGER_NOTIFY_QUEUE_TABLE', TORET_MANAGER_FUNCTION_SLUG . '_notify_queue');
// Tools
define('TORET_MANAGER_TOOLS_SLUG', TORET_MANAGER_SLUG . '-tools');
define('TORET_MANAGER_TOOLS_ENABLED', false);




/**
 * The code that runs during plugin activation.
 */
function trman_plugin_activation()
{
    require_once plugin_dir_path(__FILE__) . 'includes/general/class-' . TORET_MANAGER_SLUG . '-activator.php';
    Toret_Manager_Activator::activate();
}

register_activation_hook(__FILE__, 'trman_plugin_activation');

/**
 * The code that runs during plugin deactivation.
 */
function trman_plugin_deactivation()
{
    require_once plugin_dir_path(__FILE__) . 'includes/general/class-' . TORET_MANAGER_SLUG . '-deactivator.php';
    Toret_Manager_Deactivator::deactivate();
}

register_deactivation_hook(__FILE__, 'trman_plugin_deactivation');

/**
 * Define compatibility with WooCommerce HPOS
 */
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('before_woocommerce_init', function () {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__);
        }
    });
}

/**
 * The core plugin class.
 */
require_once plugin_dir_path(__FILE__) . 'includes/class-' . TORET_MANAGER_SLUG . '.php';

/**
 * Load libraries
 */
require_once plugin_dir_path(__FILE__) . 'includes/libraries/vendor/autoload.php';

/**
 * Begins execution of the plugin.
 */
function trman_run()
{
    $plugin = new Toret_Manager(TORET_MANAGER_SLUG, TORET_MANAGER_VERSION);
    $plugin->run();
}

trman_run();