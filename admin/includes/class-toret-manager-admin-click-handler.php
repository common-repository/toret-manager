<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Admin form click handlers
 */
if (!class_exists('Toret_Manager_Admin_Click_Handler')) {

    class Toret_Manager_Admin_Click_Handler
    {

        /**
         * Plugin slug
         *
         * @var string $toret_manager
         */
        public string $toret_manager;

        /**
         * Constructor
         *
         * @param string $toret_manager
         */
        public function __construct(string $toret_manager)
        {

            $this->toret_manager = $toret_manager;

            add_action('admin_init', array($this, 'process_handler'));

        }

        /**
         * Process form actions
         */
        function process_handler()
        {
            if (isset($_POST['trman-sync-delete-nonce'])) {
                $nonce = sanitize_text_field(wp_unslash($_POST['trman-sync-delete-nonce']));
                if (wp_verify_nonce($nonce, 'trman-sync-delete')) {

                    if (isset($_POST['trman_module_for_sync_delete'])) {
                        $module = sanitize_text_field($_POST['trman_module_for_sync_delete']);
                        Toret_Manager_Sync_Clear::delete_synced_items($module);
                    }

                    if (isset($_POST['trman_module_for_sync_clear'])) {
                        $module = sanitize_text_field($_POST['trman_module_for_sync_clear']);
                        Toret_Manager_Sync_Clear::clear_synced_items($module);
                    }

                    if (isset($_POST['trman_module_for_delete'])) {
                        $module = sanitize_text_field($_POST['trman_module_for_delete']);
                        Toret_Manager_Sync_Clear::delete_items($module);
                    }

                    if (isset($_POST['trman_test_enable_all_up'])) {
                        Toret_Manager_Helper_Modules::set_all_in_direction('ok', 'upload');
                    }

                    if (isset($_POST['trman_test_enable_all_down'])) {
                        Toret_Manager_Helper_Modules::set_all_in_direction('ok', 'download');
                    }

                    if (isset($_POST['trman_test_disable_all_up'])) {
                        Toret_Manager_Helper_Modules::set_all_in_direction('', 'upload');
                    }

                    if (isset($_POST['trman_test_disable_all_down'])) {
                        Toret_Manager_Helper_Modules::set_all_in_direction('', 'download');
                    }

                }
            }
        }


    }

}