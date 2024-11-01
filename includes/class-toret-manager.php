<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * The file that defines the core plugin class
 */
class Toret_Manager
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @var Toret_Manager_Loader $loader
     */
    protected Toret_Manager_Loader $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @var string $toret_manager
     */
    protected string $toret_manager;

    /**
     * The current version of the plugin.
     *
     * @var string $version
     */
    protected string $version;

    /**
     * Define the core functionality of the plugin.
     *
     * @param string $toret_manager
     * @param string $version Plugin version.
     */
    public function __construct(string $toret_manager, string $version)
    {
        $this->version = $version;
        $this->toret_manager = $toret_manager;

        $this->load_dependencies();
        $this->set_locale();
        $this->load_modules();
        $this->define_admin_hooks();
        //$this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies()
    {
        /**
         * API methods data
         */
        require_once TORET_MANAGER_DIR . 'includes/' . $this->toret_manager . '-data.php';

        /**
         * The class responsible for orchestrating the actions and filters of the core plugin.
         */
        require_once TORET_MANAGER_DIR . 'includes/general/class-' . $this->toret_manager . '-loader.php';

        /**
         * The class responsible for compatibility with WooCommerce HPOS of the plugin.
         */
        require_once TORET_MANAGER_DIR . 'includes/general/class-' . $this->toret_manager . '-hpos-compatibility.php';

        /**
         * The class responsible DateTimes of the plugin.
         */
        require_once TORET_MANAGER_DIR . 'includes/general/class-' . $this->toret_manager . '-datetime.php';

        /**
         * The class responsible for defining internationalization functionality of the plugin.
         */
        require_once TORET_MANAGER_DIR . 'includes/general/class-' . $this->toret_manager . '-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once TORET_MANAGER_DIR . 'admin/includes/class-' . $this->toret_manager . '-admin-click-handler.php';
        require_once TORET_MANAGER_DIR . 'admin/class-' . $this->toret_manager . '-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing side of the site.
         */
        require_once TORET_MANAGER_DIR . 'public/class-' . $this->toret_manager . '-public.php';

        /**
         * Admin settings save
         */
        require_once TORET_MANAGER_DIR . 'admin/includes/class-' . $this->toret_manager . '-admin-save.php';

        /**
         * Draw methods
         */
        require_once(TORET_MANAGER_DIR . 'includes/functions/class-' . $this->toret_manager . '-draw.php');

        /**
         * Draw methods
         */
        require_once(TORET_MANAGER_DIR . 'includes/api/class-' . $this->toret_manager . '-api.php');
        require_once(TORET_MANAGER_DIR . 'includes/api/' . $this->toret_manager . '-notify-endpoints.php');
        require_once(TORET_MANAGER_DIR . 'includes/api/' . $this->toret_manager . '-endpoints.php');

        /**
         * Draw methods
         */
        require_once(TORET_MANAGER_DIR . 'includes/class-' . $this->toret_manager . '-log.php');

        /**
         * Helper class
         */
        require_once(TORET_MANAGER_DIR . 'includes/functions/class-' . $this->toret_manager . '-helper.php');
        require_once(TORET_MANAGER_DIR . 'includes/functions/class-' . $this->toret_manager . '-helper-db.php');
        require_once(TORET_MANAGER_DIR . 'includes/functions/class-' . $this->toret_manager . '-helper-modules.php');
        require_once(TORET_MANAGER_DIR . 'includes/functions/class-' . $this->toret_manager . '-clear-sync.php');
        require_once(TORET_MANAGER_DIR . 'includes/functions/class-' . $this->toret_manager . '-notify-queue.php');

        /**
         * Modules
         */
        require_once(TORET_MANAGER_DIR . 'includes/modules/class-' . $this->toret_manager . '-module-general.php');
        require_once(TORET_MANAGER_DIR . 'includes/modules/class-' . $this->toret_manager . '-module-post.php');
        require_once(TORET_MANAGER_DIR . 'includes/modules/class-' . $this->toret_manager . '-module-user.php');
        require_once(TORET_MANAGER_DIR . 'includes/modules/class-' . $this->toret_manager . '-module-product.php');
        require_once(TORET_MANAGER_DIR . 'includes/modules/class-' . $this->toret_manager . '-module-order.php');
        require_once(TORET_MANAGER_DIR . 'includes/modules/class-' . $this->toret_manager . '-module-term.php');
        require_once(TORET_MANAGER_DIR . 'includes/modules/class-' . $this->toret_manager . '-module-product-attributes.php');
        require_once(TORET_MANAGER_DIR . 'includes/modules/class-' . $this->toret_manager . '-module-stock.php');
        require_once(TORET_MANAGER_DIR . 'includes/modules/class-' . $this->toret_manager . '-module-review.php');

        /**
         * Admin details
         */
        require_once(TORET_MANAGER_DIR . 'admin/includes/modules/class-' . $this->toret_manager . '-details-general.php');
        require_once(TORET_MANAGER_DIR . 'admin/includes/modules/class-' . $this->toret_manager . '-details-product.php');
        require_once(TORET_MANAGER_DIR . 'admin/includes/modules/class-' . $this->toret_manager . '-details-post.php');
        require_once(TORET_MANAGER_DIR . 'admin/includes/modules/class-' . $this->toret_manager . '-details-order.php');
        require_once(TORET_MANAGER_DIR . 'admin/includes/modules/class-' . $this->toret_manager . '-details-term.php');
        require_once(TORET_MANAGER_DIR . 'admin/includes/modules/class-' . $this->toret_manager . '-details-user.php');
        require_once(TORET_MANAGER_DIR . 'admin/includes/modules/class-' . $this->toret_manager . '-details-review.php');

        /**
         * Background processor
         */
        require_once(TORET_MANAGER_DIR . 'admin/includes/initial_sync/class-' . $this->toret_manager . '-initial-sync.php');

        /**
         * Plugin supports
         */
        require_once(TORET_MANAGER_DIR . 'includes/supports/class-' . $this->toret_manager . '-plugin-support.php');

        $this->loader = new Toret_Manager_Loader();
    }


    /**
     * Define the locale for this plugin for internationalization
     */
    private function set_locale()
    {
        $plugin_i18n = new Toret_Manager_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }


    /**
     * Register all of the hooks related to the admin area functionality of the plugin.
     */
    private function define_admin_hooks()
    {
        // Init scripts, styles, menu, plugin info and settings
        $plugin_admin = new Toret_Manager_Admin($this->get_toret_manager(), $this->get_version());
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        $this->loader->add_action('wp_ajax_trman_save_module_state', $plugin_admin, 'save_module_state');
        $this->loader->add_action('wp_ajax_trman_save_option', $plugin_admin, 'trman_save_option');
        $this->loader->add_action('wp_ajax_trman_save_option_items', $plugin_admin, 'trman_save_option_items');
        $this->loader->add_filter('plugin_action_links_' . TORET_MANAGER_NAME, $plugin_admin, 'action_links');
        $this->loader->add_filter('plugin_row_meta', $plugin_admin, 'plugin_row_meta', 10, 4);

        // Admin form handlers
        $plugin_admin_click = new Toret_Manager_Admin_Click_Handler($this->toret_manager);
        $this->loader->add_action('admin_init', $plugin_admin_click, 'process_handler');

        //Notify queue
        $notify_queue = new Toret_Manager_Notifiy_Queue();
        $this->loader->add_action('admin_init', $notify_queue, 'create_queue_table');
        $this->loader->add_action('admin_init', $notify_queue, 'init_scheduler_if_needed');
        $this->loader->add_filter('cron_schedules', $notify_queue, 'custom_cron_intervals');


        // Init log table and class
        $plugin_log = new Toret_Manager_Log($this->toret_manager);
        $this->loader->add_action('init', $plugin_log, 'create_log_table');
        $this->loader->add_action('admin_init', $plugin_log, 'clear_old_logs');

        // Custom meta query arguments for wc_get_orders()
        $helper = new Toret_Manager_Helper_Db();
        $this->loader->add_action('woocommerce_order_data_store_cpt_get_orders_query', $helper, 'handle_order_number_custom_query_var', 10, 2);

        // AJAX for saving internalID from metabox
        $general_admin_details = new Toret_Manager_Admin_General_Details();
        $this->loader->add_action('wp_ajax_trman_save_product_internalid', $general_admin_details, 'save_internalid');

        // Product Admin details
        if (Toret_Manager_Helper_Modules::is_module_enabled('product')) {
            $product_admin_module = new Toret_Manager_Admin_Product_Details($this->toret_manager);
            $this->loader->add_action('woocommerce_product_data_tabs', $product_admin_module, 'product_settings_tabs');
            $this->loader->add_action('woocommerce_product_data_panels', $product_admin_module, 'product_panels');
            $this->loader->add_action('woocommerce_product_after_variable_attributes', $product_admin_module, 'variation_panel', 10, 3);
            $this->loader->add_action('woocommerce_process_product_meta', $product_admin_module, 'save_product_field');
            $this->loader->add_action('woocommerce_save_product_variation', $product_admin_module, 'save_variation_field', 10, 2);
        }

        // Post Admin details
        $post_admin_module = new Toret_Manager_Admin_Post_Details($this->toret_manager);
        $this->loader->add_action('add_meta_boxes', $post_admin_module, 'add_post_metabox');
        $this->loader->add_action('save_post', $post_admin_module, 'save_post_metabox', 10);

        // Term Admin details
        $term_admin_module = new Toret_Manager_Admin_Term_Details($this->toret_manager);
        $available_terms = Toret_Manager_Helper_Modules::get_available_types_by_module('term', true);
        foreach ($available_terms as $available_term) {
            $this->loader->add_action($available_term . '_add_form_fields', $term_admin_module, 'add_term_fields');
            $this->loader->add_action($available_term . '_edit_form_fields', $term_admin_module, 'edit_term_fields', 10, 2);
            $this->loader->add_action('created_term', $term_admin_module, 'save_term_fields', 10, 3);
            $this->loader->add_action('edited_term', $term_admin_module, 'save_term_fields', 10, 3);
        }

        // Order Admin details
        if (Toret_Manager_Helper_Modules::is_module_enabled('order')) {
            $order_admin_module = new Toret_Manager_Admin_Order_Details($this->toret_manager);
            $this->loader->add_action('add_meta_boxes', $order_admin_module, 'add_order_metabox', 10, 2);
            $this->loader->add_action('woocommerce_process_shop_order_meta', $order_admin_module, 'save_order_metabox', 10);
        }

        // User Admin details
        if (Toret_Manager_Helper_Modules::is_module_enabled('user')) {
            $user_admin_module = new Toret_Manager_Admin_User_Details($this->toret_manager);
            $this->loader->add_action('show_user_profile', $user_admin_module, 'profile_fields');
            $this->loader->add_action('edit_user_profile', $user_admin_module, 'profile_fields');
            $this->loader->add_action('personal_options_update', $user_admin_module, 'save_profile_fields');
            $this->loader->add_action('edit_user_profile_update', $user_admin_module, 'save_profile_fields');
        }

    }


    /**
     * Register all of the hooks related to the public-facing functionality of the plugin.
     */
    private function define_public_hooks()
    {
        //$plugin_public = new Toret_Manager_Public($this->get_toret_manager(), $this->get_version());
        //$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        //$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }


    /**
     * Load modules
     */
    private function load_modules()
    {
        Toret_Manager_Module_Post::get_instance($this->toret_manager);
        Toret_Manager_Module_Term::get_instance($this->toret_manager);
        Toret_Manager_Module_Review::get_instance($this->toret_manager);

        if (Toret_Manager_Helper_Modules::is_module_enabled('user')) {
            Toret_Manager_Module_User::get_instance($this->toret_manager);
        }

        if (Toret_Manager_Helper_Modules::is_module_enabled('order')) {
            Toret_Manager_Module_Order::get_instance($this->toret_manager);
        }

        if (Toret_Manager_Helper_Modules::is_module_enabled('product')) {
            Toret_Manager_Module_Product::get_instance($this->toret_manager);
            Toret_Manager_Module_Product_Attribute::get_instance($this->toret_manager);
        }

        if (Toret_Manager_Helper_Modules::is_stock_sync_enabled('upload')) {
            Toret_Manager_Module_Stock::get_instance($this->toret_manager);
        }
    }

    /**
     * Run the loader to execute all of the hooks with WordPress
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality
     */
    public function get_toret_manager(): string
    {
        return $this->toret_manager;
    }


    /**
     * The reference to the class that orchestrates the hooks with the plugin
     */
    public function get_loader(): Toret_Manager_Loader
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin
     */
    public function get_version(): string
    {
        return $this->version;
    }

}
