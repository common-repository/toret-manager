<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Toret_Manager_Helper_Modules
{

    /**
     * Enable/disable all syncs in direction
     *
     * @param string $value
     * @param string $way
     */
    public static function set_all_in_direction(string $value, string $way)
    {
        $modules = self::get_all_modules();

        foreach ($modules as $module => $title) {
            $options = array(
                'trman_module_' . $way . '_' . $module . '_new',
                'trman_module_' . $way . '_' . $module . '_update',
                'trman_module_' . $way . '_' . $module . '_new_all',
                'trman_module_' . $way . '_' . $module . '_update_all',
                'trman_module_' . $way . '_' . $module . '_delete',
                'trman_module_' . $module . '_get_parent',
                'trman_module_' . $module . '_get_associated',
                'trman_module_' . $module . '_files_update',
            );

            foreach ($options as $option) {
                update_option($option, $value);
            }
        }
    }

    /**
     * Chekc wheter sync parent item
     *
     * @param string $module
     * @return bool
     */
    static function should_sync_parent(string $module): bool
    {
        return get_option('trman_module_' . $module . '_get_parent') == 'ok';
    }

    /**
     * Check wheter sync all assoicated items
     *
     * @param string $module
     * @return bool
     */
    static function should_sync_associated(string $module): bool
    {
        return get_option('trman_module_' . $module . '_get_associated') == 'ok';
    }


    /**
     * Check if item sync enabled
     *
     * @param string $module
     * @param string $action
     * @param string $way
     * @return bool
     */
    public static function is_sync_enabled(string $module, string $action, string $way = 'upload'): bool
    {
        if (!Toret_Manager_Helper_Modules::is_module_enabled($module)) {
            return false;
        }

        return get_option('trman_module_' . $way . '_' . $module . '_' . $action) == 'ok' || get_option('trman_module_' . $way . '_' . $module . '_' . $action . '_all','ok') == 'ok';
    }

    /**
     * Check if any edit sync enabled
     *
     * @param string $module
     * @param string $way
     * @return bool
     */
    public static function is_any_edit_sync_enabled(string $module, string $way = 'upload'): bool
    {
        if (!Toret_Manager_Helper_Modules::is_module_enabled($module)) {
            return false;
        }

        if (get_option('trman_module_' . $way . '_' . $module . '_new') == 'ok' ||
            get_option('trman_module_' . $way . '_' . $module . '_update') == 'ok' ||
            get_option('trman_module_' . $way . '_' . $module . '_new_all','ok') == 'ok' ||
            get_option('trman_module_' . $way . '_' . $module . '_update_all','ok') == 'ok') {
            return true;
        }

        return false;
    }

    /**
     * Check if stock sync enabled
     *
     * @param string $way
     * @return bool
     */
    public static function is_stock_sync_enabled(string $way): bool
    {
        return ((get_option('trman_module_upload_stock_update') == 'ok' && get_option('trman_module_' . $way . '_' . 'stock' . '_qty') == 'ok') || get_option('trman_module_upload_stock_update_all','ok') == 'ok');
    }

    /**
     * Check if module enbled
     *
     * @param string $module
     * @return bool
     */
    public static function is_module_enabled(string $module): bool
    {
        $enabled_modules = self::get_enabled_modules();
        return key_exists($module, $enabled_modules);
    }

    /**
     * Get enabled modules
     *
     * @return array
     */
    public static function get_enabled_modules(): array
    {
        $enabled_modules = get_option(TORET_MANAGER_ENABLED_MODULES_OPTION, array());
        return apply_filters('trman_enabled_modules', $enabled_modules);
    }

    /**
     * Get item mandatory properties for sync
     *
     * @param string $type
     * @return array
     */
    public static function get_mandatory_items(string $type): array
    {
        if ($type == 'post') {
            $data = TORET_MANAGER_POST_DATA_MANDATORY;
        } elseif ($type == 'order') {
            $data = TORET_MANAGER_ORDER_DATA_MANDATORY;
        } elseif ($type == 'product') {
            $data = TORET_MANAGER_PRODUCT_DATA_MANDATORY;
        } elseif ($type == 'user') {
            $data = TORET_MANAGER_USER_DATA_MANDATORY;
        } elseif ($type == 'term') {
            $data = TORET_MANAGER_CATEGORY_DATA_MANDATORY;
        } elseif ($type == 'comment') {
            $data = TORET_MANAGER_REVIEW_DATA_MANDATORY;
        } else {
            $data = TORET_MANAGER_POST_DATA_MANDATORY;
        }

        return array_keys($data);
    }

    /**
     * Get item properties for sync
     *
     * @param string $type
     * @return array
     */
    public static function get_all_items(string $type): array
    {
        if ($type == 'post') {
            $data = TORET_MANAGER_POST_DATA;
        } elseif ($type == 'order') {
            $data = TORET_MANAGER_ORDER_DATA;
        } elseif ($type == 'product') {
            $data = TORET_MANAGER_PRODUCT_DATA;
        } elseif ($type == 'user') {
            $data = TORET_MANAGER_USER_DATA;
        } elseif ($type == 'term') {
            $data = TORET_MANAGER_CATEGORY_DATA;
        } elseif ($type == 'product_attribute') {
            $data = TORET_MANAGER_CATEGORY_DATA;
        } elseif ($type == 'comment') {
            $data = TORET_MANAGER_REVIEW_DATA;
        } else {
            $data = TORET_MANAGER_POST_DATA;
        }

        return $data;
    }

    /**
     * Get module from id and type
     *
     * @param mixed $id
     * @param string $type
     * @return string
     */
    static function get_module_from_id_and_type($id, string $type): string
    {
        if ($type == 'post') {
            return get_post_type($id);
        } elseif ($type == 'product') {
            return 'product';
        } elseif ($type == 'order') {
            return 'order';
        } elseif ($type == 'term') {
            $term = get_term($id);
            return $term->taxonomy;
        } elseif ($type == 'user') {
            return 'user';
        } elseif ($type == 'comment') {
            return get_comment_type($id);
        } else {
            return 'post';
        }
    }

    /**
     * Get all modules
     *
     * @param bool $types
     * @return array
     */
    static function get_all_modules(bool $types = false): array
    {
        $enabled_post_types = self::get_available_types_by_module('post', true);
        $enabled_term_types = self::get_available_types_by_module('term', true);

        $modules = [];
        foreach ($enabled_post_types as $enabled_post_type) {
            $type_data = get_post_type_object($enabled_post_type);
            $modules[$enabled_post_type] = ($types ? 'post' : $type_data->label);
        }
        $modules['order'] = ($types ? 'order' : __('Orders', 'toret-manager'));

        if (Toret_Manager_Helper::is_woocommerce_active()) {
            $modules['product'] = ($types ? 'post' : __('Products', 'toret-manager'));
        }

        $modules['user'] = ($types ? 'user' : __('Users', 'toret-manager'));

        foreach ($enabled_term_types as $enabled_term_type) {
            $type_data = get_taxonomy($enabled_term_type);
            if (!empty($type_data))
                $modules[$enabled_term_type] = ($types ? 'term' : $type_data->label);
        }

        /*if (Toret_Manager_Helper::is_woocommerce_active()) {
            $modules['product_attribute'] = ($types ? 'product_attribute' : __('Product attributes', 'toret-manager'));
        }*/

        $modules['comment'] = ($types ? 'comment' : __('Comments', 'toret-manager'));
        $modules['review'] = ($types ? 'comment' : __('Reviews', 'toret-manager'));

        return $modules;
    }

    /**
     * Get all modules and types
     *
     * @param string $module
     * @param bool $include_wc
     * @return array
     */
    static function get_available_types_by_module(string $module, bool $include_wc = false): array
    {
        $available_types = [];

        if ($module == 'post') {
            $all_types = get_post_types(array('public' => true));
            $available_types = array_diff($all_types, TORET_MANAGER_DISABLED_POST_TYPES);
            if (Toret_Manager_Helper::is_woocommerce_active() && !$include_wc) {
                $available_types = array_diff($available_types, TORET_MANAGER_SPECIFIC_POST_TYPES);
            }
        } elseif ($module == 'term') {
            $all_ypes = get_taxonomies(array('public' => true));
            $available_types = array_diff($all_ypes, TORET_MANAGER_DISABLED_TERM_TYPES);
            $available_types = array_diff($available_types, TORET_MANAGER_SPECIFIC_TERM_TYPES);
            if ($include_wc) {
                if (!in_array('product_cat', $available_types)) {
                    $available_types[] = 'product_cat';
                }
                if (!in_array('product_tag', $available_types)) {
                    $available_types[] = 'product_tag';
                }
            }
        }

        return apply_filters('trman_enabled_' . $module . '_types', $available_types);
    }

    /**
     * Check of WooCommerce module is available
     *
     * @param string $module
     * @return bool
     */
    static function allow_woo_module(string $module): bool
    {
        if (Toret_Manager_Helper::is_woocommerce_active() && in_array($module, TORET_MANAGER_WOO_TYPES)) {
            return true;
        } elseif (!Toret_Manager_Helper::is_woocommerce_active() && !in_array($module, TORET_MANAGER_WOO_TYPES)) {
            return true;
        } elseif (!Toret_Manager_Helper::is_woocommerce_active() && in_array($module, TORET_MANAGER_WOO_TYPES)) {
            return false;
        } elseif (Toret_Manager_Helper::is_woocommerce_active() && !in_array($module, TORET_MANAGER_WOO_TYPES)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get data to by sync for given module and action
     *
     * @param string $module
     * @param string $type
     * @param string $action
     * @param array $mandatory
     * @param string $way
     * @return array
     */
    public static function data_to_be_sync(string $module, string $type, string $action, array $mandatory, string $way = 'upload'): array
    {
        if ($module == 'order_note') {
            $to_be_synced = array_keys(TORET_MANAGER_REVIEW_DATA);
        } else if (get_option('trman_module_' . $way . '_' . $module . '_' . $action . '_all','ok') == 'ok') {

            $to_be_synced = array_keys(Toret_Manager_Helper_Modules::get_all_items($type));

        } else {

            $optional = get_option('trman_module_' . $way . '_' . $action . '_' . $module . '_items', $mandatory);
            $to_be_synced = array_merge($optional, $mandatory);

            // Add hidden properties
            $hidden = [];
            foreach (Toret_Manager_Helper_Modules::get_all_items($type) as $item => $mandatory) {
                $exclude = false;
                if (!empty($mandatory)) {
                    $properties = explode(';', $mandatory);
                    $exclude = $properties[1] == 'x';
                }
                if ($exclude) {
                    $hidden[] = $item;
                }
            }

            $to_be_synced = array_merge($to_be_synced, $hidden);
            $to_be_synced = array_unique($to_be_synced);

        }

        return apply_filters('trman_data_to_be_sync', $to_be_synced, $module, $action, $way);
    }

    /**
     * Get module by post type
     *
     * @param $item_type
     * @return string
     */
    static function get_module_by_post_type($item_type): string
    {
        $module = $item_type;

        if ($item_type == 'shop_order') {
            $module = 'order';
        }

        return $module;
    }

}