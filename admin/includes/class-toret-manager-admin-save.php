<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Admin Save Class
 */
if (!class_exists('Toret_Manager_Admin_Save')) {

    class Toret_Manager_Admin_Save
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
        }

        /**
         * Save plugin admin page options
         */
        public function save_setting(): void
        {
            if (isset($_POST['trman-admin-save-nonce'])) {
                $nonce = sanitize_text_field(wp_unslash($_POST['trman-admin-save-nonce']));
                if (wp_verify_nonce($nonce, 'trman-admin-save')) {

                    if (isset($_POST['trman-save-api-keys'])) {
                        $this->check_credentials();
                    }

                    if (isset($_POST['toret-manager-save-modules'])) {

                        if (isset($_POST['trman-saving-module'])) {

                            $module = sanitize_text_field($_POST['trman-saving-module']);
                            $type = sanitize_text_field($_POST['trman-saving-type']);

                            if ($module == 'product') {
                                $this->save_module_product_specific();
                                $this->save_module_stock();
                            }

                            $this->save_module_form($module, $type);
                        }

                    }
                }
            }
        }

        /**
         * Update array of options
         *
         * @param array $data
         */
        private function update_option_array(array $data)
        {
            if (isset($_POST['trman-admin-save-nonce'])) {
                $nonce = sanitize_text_field(wp_unslash($_POST['trman-admin-save-nonce']));
                if (wp_verify_nonce($nonce, 'trman-admin-save')) {
                    foreach ($data as $item) {
                        if (isset($_POST[$item])) {
                            if ($_POST[$item] == '0') {
                                update_option($item, '0');
                            } else {
                                if (is_array($_POST[$item])) {
                                    // This is anitized inside function sanitize_text_or_array_field
                                    update_option($item, Toret_Manager_Helper::sanitize_text_or_array_field($_POST[$item]));
                                } else {
                                    update_option($item, sanitize_text_field($_POST[$item]));
                                }
                            }
                        }
                    }
                }
            }
        }

        /**
         * Update checkbox option
         *
         * @param array $data
         */
        private
        function update_option_checkbox_array(array $data)
        {
            if (isset($_POST['trman-admin-save-nonce'])) {
                $nonce = sanitize_text_field(wp_unslash($_POST['trman-admin-save-nonce']));
                if (wp_verify_nonce($nonce, 'trman-admin-save')) {
                    foreach ($data as $item) {
                        update_option($item, sanitize_text_field($_POST[$item] ?? '0'));
                    }
                }
            }
        }

        /**
         * Save module enabled state
         *
         * @param string $modul
         * @param string $endpoint
         * @param string $state
         */
        static function save_module(string $modul, string $endpoint, string $state)
        {
            $enabled_modules = get_option(TORET_MANAGER_ENABLED_MODULES_OPTION, array());

            $option = 'trman_module_' . $modul . '_enabled';
            if ($state == 'enabled') {
                update_option($option, 'ok');
                if (!key_exists($modul, $enabled_modules)) {
                    $enabled_modules[$modul] = $endpoint;
                }
            } else {
                update_option($option, '0');
                if (key_exists($modul, $enabled_modules)) {
                    unset($enabled_modules[$modul]);
                }
            }

            update_option(TORET_MANAGER_ENABLED_MODULES_OPTION, $enabled_modules);
        }

        /*
         * Check API credentials
         */
        private
        function check_credentials()
        {
            if (isset($_POST['trman-admin-save-nonce'])) {
                $nonce = sanitize_text_field(wp_unslash($_POST['trman-admin-save-nonce']));
                if (wp_verify_nonce($nonce, 'trman-admin-save')) {

                    if (isset($_POST[TORET_MANAGER_API_KEY]) && isset($_POST[TORET_MANAGER_USER_HASH])) {

                        $Toret_Manager_Api = ToretManagerApi();
                        $check_credentials = $Toret_Manager_Api->getData->check_api_credentials(
                            $this->toret_manager,
                            sanitize_text_field($_POST[TORET_MANAGER_API_KEY]), //TODO CHECK IF THIS WORKS
                            sanitize_text_field($_POST[TORET_MANAGER_USER_HASH]) //TODO CHECK IF THIS WORKS
                        );
                        if ($check_credentials != 'none') {
                            update_option('trman_api_check', 'ok');
                            update_option('trman_api_check_notice', __('Credentials successfully verified.', 'toret-manager'));
                        } else {
                            update_option('trman_api_check', 'fail');
                            update_option('trman_api_check_notice', __('Validation of credentials failed!', 'toret-manager'));
                        }
                    }

                    self::update_option_array(array(TORET_MANAGER_API_KEY, TORET_MANAGER_USER_HASH, TORET_MANAGER_SHOP_ID));
                    self::update_option_checkbox_array(array(TORET_MANAGER_NOTIFY_WP_SCHEDULER));

	                ( new Toret_Manager_Notifiy_Queue )->init_scheduler_if_needed();
                }
            }
        }

        /**
         * Save module specific options
         */
        private
        function save_module_product_specific()
        {
            $options = array(
                'trman_module_product_files_update',
                'trman_module_product_pairing_sku',
            );
            $this->update_option_checkbox_array($options);

            $options = array(
                'trman_module_product_field_ean',
                'trman_module_product_field_isbn',
                'trman_module_product_field_gtin',

            );
            $this->update_option_array($options);
        }

        /**
         * Save module form options
         *
         * @param string $module
         * @param string $type
         */
        private function save_module_form(string $module, string $type = 'post')
        {
            $options = array(
                'trman_module_upload_' . $module . '_new',
                'trman_module_upload_' . $module . '_update',
                'trman_module_upload_' . $module . '_new_all',
                'trman_module_upload_' . $module . '_update_all',
                'trman_module_upload_' . $module . '_delete',
                'trman_module_download_' . $module . '_new',
                'trman_module_download_' . $module . '_update',
                'trman_module_download_' . $module . '_new_all',
                'trman_module_download_' . $module . '_update_all',
                'trman_module_download_' . $module . '_delete',
                'trman_module_' . $module . '_get_parent',
                'trman_module_' . $module . '_get_associated',
                'trman_module_' . $module . '_files_update',
                'trman_module_' . $module . '_imported_status'
            );

            $this->update_option_checkbox_array($options);

            $options = array(
                'trman_module_upload_new_' . $module . '_items',
                'trman_module_upload_update_' . $module . '_items',
                'trman_module_download_new_' . $module . '_items',
                'trman_module_download_update_' . $module . '_items',
            );
            $this->update_option_array($options);
        }

        /**
         * Save module stock options
         */
        private
        function save_module_stock()
        {
            if (isset($_POST['trman-admin-save-nonce'])) {
                $nonce = sanitize_text_field(wp_unslash($_POST['trman-admin-save-nonce']));
                if (wp_verify_nonce($nonce, 'trman-admin-save')) {

                    if (isset($_POST['trman_module_upload_stock_update_all'])) {
                        update_option('trman_module_upload_stock_update_all', 'ok');
                        update_option('trman_module_upload_stock_update', '');
                        update_option('trman_module_upload_stock_qty', 'ok');
                        update_option('trman_module_download_stock_qty', 'ok');
                    } else if (isset($_POST['trman_module_upload_stock_update'])) {
                        update_option('trman_module_upload_stock_update', 'ok');
                        update_option('trman_module_upload_stock_update_all', '');
                        $options = array(
                            'trman_module_upload_stock_qty',
                            'trman_module_download_stock_qty',
                        );
                        $this->update_option_checkbox_array($options);
                    } else {
                        update_option('trman_module_upload_stock_qty', '');
                        update_option('trman_module_download_stock_qty', '');
                        update_option('trman_module_upload_stock_update_all', '');
                        update_option('trman_module_upload_stock_update', '');
                    }
                }
            }
        }
    }
}