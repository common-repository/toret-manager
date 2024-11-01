<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Toret_Manager_Sync_Clear
{

    /**
     * Get synced items
     *
     * @param string $module
     * @param string $mode
     * @return array|int|int[]|string|WP_Post[]|null
     */
    static function get_synced_items(string $module, string $mode)
    {
        global $wpdb;

        if ($mode == 'count') {
            $synced = 0;
        } else {
            $synced = [];
        }

        $postTypes = array('post' => "post", 'product' => 'product', 'order' => 'shop_order', 'page' => 'page');

        if ($mode == 'count') {

            if (key_exists($module, $postTypes)) {
                $type = $postTypes[$module];
                if ($module == 'order') {
                    if (Toret_Manager_HPOS_Compatibility::is_wc_hpos_enabled()) {

                        $synced = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->prefix . "wc_orders_meta WHERE meta_value=%s AND meta_key=%s", array('1', TORET_MANAGER_ASSOCIATIVE_SYNC)));

                    } else {

                        $args = array(
                            'post_type' => 'shop_order',
                            'post_status' => array_keys(wc_get_order_statuses()),
                            'fields' => 'ids',
                            'numberposts' => -1,
                            'meta_query' => array(
                                array(
                                    'key' => TORET_MANAGER_ASSOCIATIVE_SYNC,
                                    'value' => '1'
                                )
                            )
                        );
                        $synced = get_posts($args);
                        $synced = count($synced);


                    }

                } else {

                    $args = array(
                        'post_type' => $type,
                        'post_status' => TORET_MANAGER_ALLOWED_POST_STATUSES,
                        'fields' => 'ids',
                        'numberposts' => -1,
                        'meta_query' => array(
                            array(
                                'key' => TORET_MANAGER_ASSOCIATIVE_SYNC,
                                'value' => '1'
                            )
                        ),
                    );
                    $synced = get_posts($args);
                    $synced = count($synced);

                }

            } elseif ($module == 'user') {
                $synced = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s", TORET_MANAGER_ASSOCIATIVE_SYNC, '1'));
            } elseif (in_array($module, array('comment', 'review', 'order_note'))) {
                $synced = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->commentmeta} cm JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID WHERE cm.meta_key = %s AND cm.meta_value = %s AND c.comment_type=%s", array(TORET_MANAGER_ASSOCIATIVE_SYNC, '1', $module)
                ));
            } elseif (in_array($module, array('category', 'post_tag', 'product_cat', 'product_tag'))) {
                $synced = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->termmeta} AS tm INNER JOIN {$wpdb->terms} AS t ON tm.term_id = t.term_id INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id WHERE tm.meta_key = %s AND tm.meta_value = %s AND tt.taxonomy=%s",
                    array(TORET_MANAGER_ASSOCIATIVE_SYNC, '1', $module)));
            }

        } else {

            if (key_exists($module, $postTypes)) {
                $type = $postTypes[$module];

                if ($module == 'order') {
                    if (Toret_Manager_HPOS_Compatibility::is_wc_hpos_enabled()) {


                        $synced = $wpdb->get_col($wpdb->prepare("SELECT order_id FROM " . $wpdb->prefix . "wc_orders_meta WHERE meta_value=%s AND meta_key=%s", array('1', TORET_MANAGER_ASSOCIATIVE_SYNC)));

                    } else {

                        $args = array(
                            'post_type' => 'shop_order',
                            'post_status' => array_keys(wc_get_order_statuses()),
                            'fields' => 'ids',
                            'numberposts' => -1,
                            'meta_query' => array(
                                array(
                                    'key' => TORET_MANAGER_ASSOCIATIVE_SYNC,
                                    'value' => '1'
                                )
                            )
                        );
                        $synced = get_posts($args);
                    }

                } else {

                    $args = array(
                        'post_type' => $type,
                        'post_status' => TORET_MANAGER_ALLOWED_POST_STATUSES,
                        'fields' => 'ids',
                        'numberposts' => -1,
                        'meta_query' => array(
                            array(
                                'key' => TORET_MANAGER_ASSOCIATIVE_SYNC,
                                'value' => '1'
                            )
                        ),
                    );
                    $synced = get_posts($args);

                }
            } elseif ($module == 'user') {
                $synced = $wpdb->get_col($wpdb->prepare("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s", TORET_MANAGER_ASSOCIATIVE_SYNC, '1'));
            } elseif (in_array($module, array('comment', 'review', 'order_note'))) {
                $synced = $wpdb->get_col($wpdb->prepare(
                    "SELECT cm.comment_id FROM {$wpdb->commentmeta} cm JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID WHERE cm.meta_key = %s AND cm.meta_value = %s AND c.comment_type=%s", array(TORET_MANAGER_ASSOCIATIVE_SYNC, '1', $module)
                ));
            } elseif (in_array($module, array('category', 'post_tag', 'product_cat', 'product_tag'))) {
                $synced = $wpdb->get_col($wpdb->prepare("SELECT tt.term_id FROM {$wpdb->termmeta} AS tm INNER JOIN {$wpdb->terms} AS t ON tm.term_id = t.term_id INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id WHERE tm.meta_key = %s AND tm.meta_value = %s AND tt.taxonomy=%s",
                    array(TORET_MANAGER_ASSOCIATIVE_SYNC, '1', $module)));
            }

        }

        return $synced;
    }

    /**
     * Get not synced items
     *
     * @param string $module
     * @param string $mode
     * @return array|int|int[]|string|WP_Post[]|null
     */
    static function get_not_synced_items(string $module, string $mode)
    {

        global $wpdb;

        if ($mode == 'count') {
            $notSynced = 0;
        } else {
            $notSynced = [];
        }

        $postTypes = array('post' => "post", 'product' => 'product', 'order' => 'shop_order', 'page' => 'page');

        if ($mode == 'count') {

            if (key_exists($module, $postTypes)) {
                $type = $postTypes[$module];
                if ($module == 'order') {
                    if (Toret_Manager_HPOS_Compatibility::is_wc_hpos_enabled()) {

                        $query = "SELECT COUNT(*) FROM {$wpdb->prefix}wc_orders AS os LEFT JOIN " . $wpdb->prefix . "wc_orders_meta AS pm ON os.id = pm.order_id AND pm.meta_key = %s WHERE pm.order_id IS NULL";
                        $prepared_query = $wpdb->prepare($query, TORET_MANAGER_ITEM_INTERNALID);
                        $notSynced = $wpdb->get_var($prepared_query);

                    } else {

                        $args = array(
                            'post_type' => 'shop_order',
                            'post_status' => array_keys(wc_get_order_statuses()),
                            'fields' => 'ids',
                            'numberposts' => -1,
                            'meta_query' => array(
                                array(
                                    'key' => TORET_MANAGER_ITEM_INTERNALID,
                                    'compare' => 'NOT EXISTS'
                                )
                            )
                        );
                        $posts = get_posts($args);
                        $notSynced = count($posts);

                    }


                } else {

                    $args = array(
                        'post_type' => $type,
                        'post_status' => TORET_MANAGER_ALLOWED_POST_STATUSES,
                        'fields' => 'ids',
                        'numberposts' => -1,
                        'meta_query' => array(
                            array(
                                'key' => TORET_MANAGER_ITEM_INTERNALID,
                                'compare' => 'NOT EXISTS'
                            )
                        )
                    );
                    $posts = get_posts($args);
                    $notSynced = count($posts);


                }
            } elseif ($module == 'user') {

                $query = "SELECT COUNT(*) FROM {$wpdb->users} u LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = %s WHERE um.meta_key IS NULL";
                $notSynced = $wpdb->get_var($wpdb->prepare($query, TORET_MANAGER_ITEM_INTERNALID));

            } elseif (in_array($module, array('comment', 'review', 'order_note'))) {

                $query = "SELECT COUNT(*) FROM {$wpdb->comments} c LEFT JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id AND cm.meta_key = %s WHERE cm.meta_key IS NULL AND c.comment_type = %s";
                $notSynced = $wpdb->get_var($wpdb->prepare($query, TORET_MANAGER_ITEM_INTERNALID, $module));

            } elseif (in_array($module, array('category', 'post_tag', 'product_cat', 'product_tag'))) {

                $query = $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->terms} AS t INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id LEFT JOIN {$wpdb->termmeta} AS tm ON t.term_id = tm.term_id AND tm.meta_key = %s WHERE tt.taxonomy = %s AND tm.term_id IS NULL", TORET_MANAGER_ITEM_INTERNALID, $module);
                $notSynced = $wpdb->get_var($query);

            }

        } else {

            if (key_exists($module, $postTypes)) {
                $type = $postTypes[$module];

                if ($module == 'order') {
                    if (Toret_Manager_HPOS_Compatibility::is_wc_hpos_enabled()) {

                        $query = "SELECT os.id FROM {$wpdb->prefix}wc_orders AS os LEFT JOIN " . $wpdb->prefix . "wc_orders_meta AS pm ON os.id = pm.order_id AND pm.meta_key = %s WHERE pm.order_id IS NULL";
                        $prepared_query = $wpdb->prepare($query, TORET_MANAGER_ITEM_INTERNALID);
                        $notSynced = $wpdb->get_col($prepared_query);

                    } else {

                        $args = array(
                            'post_type' => 'shop_order',
                            'post_status' => array_keys(wc_get_order_statuses()),
                            'fields' => 'ids',
                            'numberposts' => -1,
                            'meta_query' => array(
                                array(
                                    'key' => TORET_MANAGER_ITEM_INTERNALID,
                                    'compare' => 'NOT EXISTS'
                                )
                            )
                        );
                        $notSynced = get_posts($args);


                    }

                } else {

                    $args = array(
                        'post_type' => $type,
                        'post_status' => TORET_MANAGER_ALLOWED_POST_STATUSES,
                        'fields' => 'ids',
                        'numberposts' => -1,
                        'meta_query' => array(
                            array(
                                'key' => TORET_MANAGER_ITEM_INTERNALID,
                                'compare' => 'NOT EXISTS'
                            )
                        )
                    );
                    $notSynced = get_posts($args);

                }
            } elseif ($module == 'user') {

                $query = "SELECT u.ID FROM {$wpdb->users} u LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = %s WHERE um.meta_key IS NULL";
                $notSynced = $wpdb->get_col($wpdb->prepare($query, TORET_MANAGER_ITEM_INTERNALID));

            } elseif (in_array($module, array('comment', 'review', 'order_note'))) {

                $query = "SELECT c.comment_ID FROM {$wpdb->comments} c LEFT JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id AND cm.meta_key = %s WHERE cm.meta_key IS NULL AND c.comment_type = %s";
                $notSynced = $wpdb->get_col($wpdb->prepare($query, TORET_MANAGER_ITEM_INTERNALID, $module));

            } elseif (in_array($module, array('category', 'post_tag', 'product_cat', 'product_tag'))) {

                $query = $wpdb->prepare("SELECT t.term_id FROM {$wpdb->terms} AS t INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id LEFT JOIN {$wpdb->termmeta} AS tm ON t.term_id = tm.term_id AND tm.meta_key = %s WHERE tt.taxonomy = %s AND tm.term_id IS NULL", TORET_MANAGER_ITEM_INTERNALID, $module);
                $notSynced = $wpdb->get_col($query);

            }

        }

        return $notSynced;

    }

    /**
     * Get items with internalID
     *
     * @param string $module
     * @param string $mode
     * @return array|int|int[]|string|WP_Post[]|null
     */
    static function get_items_with_internal_id(string $module, string $mode)
    {
        global $wpdb;

        if ($mode == 'count') {
            $notSynced = 0;
        } else {
            $notSynced = [];
        }

        $postTypes = array('post' => "post", 'product' => 'product', 'order' => 'shop_order', 'page' => 'page');

        if ($mode == 'count') {

            if (key_exists($module, $postTypes)) {
                $type = $postTypes[$module];
                if ($module == 'order') {

                    if (Toret_Manager_HPOS_Compatibility::is_wc_hpos_enabled()) {

                        $query = "SELECT COUNT(*) FROM {$wpdb->prefix}wc_orders AS os LEFT JOIN " . $wpdb->prefix . "wc_orders_meta AS pm ON os.id = pm.order_id AND pm.meta_key = %s WHERE pm.order_id IS NOT NULL";
                        $prepared_query = $wpdb->prepare($query, TORET_MANAGER_ITEM_INTERNALID);
                        $notSynced = $wpdb->get_var($prepared_query);
                    } else {

                        $args = array(
                            'post_type' => 'shop_order',
                            'post_status' => array_keys(wc_get_order_statuses()),
                            'fields' => 'ids',
                            'numberposts' => -1,
                            'meta_query' => array(
                                array(
                                    'key' => TORET_MANAGER_ITEM_INTERNALID,
                                    'compare' => 'EXISTS'
                                )
                            )
                        );
                        $notSynced = get_posts($args);
                        $notSynced = count($notSynced);
                    }

                } else {

                    $args = array(
                        'post_type' => $type,
                        'post_status' => TORET_MANAGER_ALLOWED_POST_STATUSES,
                        'fields' => 'ids',
                        'numberposts' => -1,
                        'meta_query' => array(
                            array(
                                'key' => TORET_MANAGER_ITEM_INTERNALID,
                                'compare' => 'EXISTS'
                            )
                        )
                    );

                    $notSynced = get_posts($args);
                    $notSynced = count($notSynced);

                }
            } elseif ($module == 'user') {

                $query = "SELECT COUNT(*) FROM {$wpdb->users} u LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = %s WHERE um.meta_key IS NOT NULL";
                $notSynced = $wpdb->get_var($wpdb->prepare($query, TORET_MANAGER_ITEM_INTERNALID));

            } elseif (in_array($module, array('comment', 'review', 'order_note'))) {

                $query = "SELECT COUNT(*) FROM {$wpdb->comments} c LEFT JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id AND cm.meta_key = %s WHERE cm.meta_key IS NOT NULL AND c.comment_type = %s";
                $notSynced = $wpdb->get_var($wpdb->prepare($query, TORET_MANAGER_ITEM_INTERNALID, $module));

            } elseif (in_array($module, array('category', 'post_tag', 'product_cat', 'product_tag'))) {

                $query = $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->terms} AS t INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id LEFT JOIN {$wpdb->termmeta} AS tm ON t.term_id = tm.term_id AND tm.meta_key = %s WHERE tt.taxonomy = %s AND tm.term_id IS NOT NULL", TORET_MANAGER_ITEM_INTERNALID, $module);
                $notSynced = $wpdb->get_var($query);

            }
        } else {

            if (key_exists($module, $postTypes)) {
                $type = $postTypes[$module];

                if ($module == 'order') {
                    if (Toret_Manager_HPOS_Compatibility::is_wc_hpos_enabled()) {

                        $query = "SELECT os.id FROM {$wpdb->prefix}wc_orders AS os LEFT JOIN " . $wpdb->prefix . "wc_orders_meta AS pm ON os.id = pm.order_id AND pm.meta_key = %s WHERE pm.order_id IS NOT NULL";
                        $prepared_query = $wpdb->prepare($query, TORET_MANAGER_ITEM_INTERNALID);
                        $notSynced = $wpdb->get_col($prepared_query);

                    } else {

                        $args = array(
                            'post_type' => 'shop_order',
                            'post_status' => array_keys(wc_get_order_statuses()),
                            'fields' => 'ids',
                            'numberposts' => -1,
                            'meta_query' => array(
                                array(
                                    'key' => TORET_MANAGER_ITEM_INTERNALID,
                                    'compare' => 'EXISTS'
                                )
                            )
                        );
                        $notSynced = get_posts($args);

                    }

                } else {

                    $args = array(
                        'post_type' => $type,
                        'post_status' => TORET_MANAGER_ALLOWED_POST_STATUSES,
                        'fields' => 'ids',
                        'numberposts' => -1,
                        'meta_query' => array(
                            array(
                                'key' => TORET_MANAGER_ITEM_INTERNALID,
                                'compare' => 'EXISTS'
                            )
                        )
                    );
                    $notSynced = get_posts($args);

                }
            } elseif ($module == 'user') {

                $query = "SELECT u.ID FROM {$wpdb->users} u LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = %s WHERE um.meta_key IS NOT NULL";
                $notSynced = $wpdb->get_col($wpdb->prepare($query, TORET_MANAGER_ITEM_INTERNALID));

            } elseif (in_array($module, array('comment', 'review', 'order_note'))) {

                $query = "SELECT c.comment_ID FROM {$wpdb->comments} c LEFT JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id AND cm.meta_key = %s WHERE cm.meta_key IS NOT NULL AND c.comment_type = %s";
                $notSynced = $wpdb->get_col($wpdb->prepare($query, TORET_MANAGER_ITEM_INTERNALID, $module));

            } elseif (in_array($module, array('category', 'post_tag', 'product_cat', 'product_tag'))) {

                $query = $wpdb->prepare("SELECT t.term_id FROM {$wpdb->terms} AS t INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id LEFT JOIN {$wpdb->termmeta} AS tm ON t.term_id = tm.term_id AND tm.meta_key = %s WHERE tt.taxonomy = %s AND tm.term_id IS NOT NULL", TORET_MANAGER_ITEM_INTERNALID, $module);
                $notSynced = $wpdb->get_col($query);

            }

        }

        return $notSynced;
    }

    /**
     * Get total items on web
     *
     * @param string $module
     * @param string $mode
     * @return mixed
     */
    static function get_total_items(string $module, string $mode = 'count')
    {
        global $wpdb;

        $postTypes = array('post' => "post", 'product' => 'product', 'order' => 'shop_order', 'page' => 'page');

        if ($mode == 'count') {
            $total = 0;
        } else {
            $total = [];
        }
        if ($mode == 'count') {
            if (key_exists($module, $postTypes)) {
                $type = $postTypes[$module];

                if ($module == 'order') {
                    if (Toret_Manager_HPOS_Compatibility::is_wc_hpos_enabled()) {

                        $total = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->prefix . "wc_orders");
                    } else {

                        $orders = wc_get_orders(array('limit' => -1, 'return' => 'ids', 'type' => 'shop_order'));
                        $total = count($orders);

                    }

                } else {

                    $args = array(
                        'post_type' => $type,
                        'post_status' => TORET_MANAGER_ALLOWED_POST_STATUSES,
                        'fields' => 'ids',
                        'numberposts' => -1,
                    );
                    $posts = get_posts($args);
                    $total = count($posts);

                }

            } elseif ($module == 'user') {

                $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");

            } elseif (in_array($module, array('comment', 'review', 'order_note'))) {

                $total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_type=%s", array($module)));

            } elseif (in_array($module, array('category', 'post_tag', 'product_cat', 'product_tag'))) {

                $total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->term_taxonomy} AS tt INNER JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id WHERE tt.taxonomy=%s", array($module)));

            }
        } else {
            if (key_exists($module, $postTypes)) {
                $type = $postTypes[$module];

                if (Toret_Manager_HPOS_Compatibility::is_wc_hpos_enabled() && $module == 'order') {

                    $total = $wpdb->get_col("SELECT id FROM " . $wpdb->prefix . "wc_orders");

                } else {

                    if ($module == 'order') {

                        $total = wc_get_orders(array('limit' => -1, 'return' => 'ids', 'type' => 'shop_order'));

                    } else {

                        $args = array(
                            'post_type' => $type,
                            'post_status' => TORET_MANAGER_ALLOWED_POST_STATUSES,
                            'fields' => 'ids',
                            'numberposts' => -1,
                        );
                        $total = get_posts($args);
                    }

                }

            } elseif ($module == 'user') {

                $total = $wpdb->get_col("SELECT user_id FROM {$wpdb->users}");

            } elseif (in_array($module, array('comment', 'review', 'order_note'))) {

                $total = $wpdb->get_col($wpdb->prepare("SELECT comment_ID FROM {$wpdb->comments} WHERE comment_type=%s", array($module)));

            } elseif (in_array($module, array('category', 'post_tag', 'product_cat', 'product_tag'))) {

                $total = $wpdb->get_col($wpdb->prepare("SELECT t.term_id FROM {$wpdb->term_taxonomy} AS tt INNER JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id WHERE tt.taxonomy=%s", array($module)));

            }
        }

        return $total;
    }

    /**
     * Delete synced items
     *
     * @param string $module
     */
    static function delete_synced_items(string $module)
    {
        $ids = Toret_Manager_Sync_Clear::get_synced_items($module, 'ids');

        if (!empty($ids)) {

            $postTypes = array('post' => "post", 'product' => 'product', 'order' => 'shop_order', 'page' => 'page');

            if (key_exists($module, $postTypes)) {

                Toret_Manager_Helper_Db::delete_hpos_order($module, $ids);

            } elseif ($module == 'user') {

                foreach ($ids as $id) {
                    if (!user_can($id, 'administrator')) {
                        wp_delete_user($id);
                    }
                }

            } elseif (in_array($module, array('comment', 'review', 'order_note'))) {

                foreach ($ids as $id) {
                    wp_delete_comment($id, true);
                }

            } elseif (in_array($module, array('category', 'post_tag', 'product_cat', 'product_tag'))) {

                foreach ($ids as $id) {
                    wp_delete_term($id, $module);
                }

            }

        }
    }

    /**
     * Delete synced items
     *
     * @param string $module
     */
    static function delete_items(string $module)
    {
        $modules = [];
        if ($module == 'all') {
            $modules = Toret_Manager_Helper_Modules::get_all_modules();
        } else {
            $modules[$module] = $module;
        }

        foreach ($modules as $module => $title) {

            $ids = Toret_Manager_Sync_Clear::get_total_items($module, 'ids');

            if (!empty($ids) || $module == 'product_attribute') {

                $postTypes = array('post' => "post", 'product' => 'product', 'order' => 'shop_order', 'page' => 'page');

                if (key_exists($module, $postTypes)) {

                    Toret_Manager_Helper_Db::delete_hpos_order($module, $ids);

                } elseif ($module == 'user') {

                    foreach ($ids as $id) {
                        if (!user_can($id, 'administrator')) {
                            wp_delete_user($id);
                        }
                    }

                } elseif (in_array($module, array('comment', 'review', 'order_note'))) {

                    foreach ($ids as $id) {
                        wp_delete_comment($id, true);
                    }

                } elseif (in_array($module, array('category', 'post_tag', 'product_cat', 'product_tag'))) {

                    foreach ($ids as $id) {
                        wp_delete_term($id, $module, array('force_default' => true));
                    }

                }elseif ($module == 'product_attribute') {

                    update_option('toret_manager_product_attributes_parser', array());
                    foreach (wc_get_attribute_taxonomies() as $values) {
                        wc_delete_attribute($values->attribute_id);
                    }

                }
            }
        }
    }

    /**
     * Clear synced items
     *
     * @param string $module
     */
    static function clear_synced_items(string $module)
    {
        $modules = [];
        if ($module == 'all') {
            $modules = Toret_Manager_Helper_Modules::get_all_modules();
        } else {
            $modules[$module] = $module;
        }

        foreach ($modules as $module => $title) {

            $ids = Toret_Manager_Sync_Clear::get_items_with_internal_id($module, 'ids');

            if (!empty($ids)) {

                $postTypes = array('post' => "post", 'product' => 'product', 'order' => 'shop_order', 'page' => 'page');

                if (key_exists($module, $postTypes)) {

                    if (Toret_Manager_HPOS_Compatibility::is_wc_hpos_enabled() && $module == 'order') {

                        foreach ($ids as $id) {
                            Toret_Manager_HPOS_Compatibility::delete_order_meta($id, TORET_MANAGER_ITEM_INTERNALID);
                        }

                    } else {

                        foreach ($ids as $id) {
                            delete_post_meta($id, TORET_MANAGER_ITEM_INTERNALID);
                        }

                    }
                } elseif ($module == 'user') {

                    foreach ($ids as $id) {
                        delete_user_meta($id, TORET_MANAGER_ITEM_INTERNALID);
                    }

                } elseif (in_array($module, array('comment', 'review', 'order_note'))) {

                    foreach ($ids as $id) {
                        delete_comment_meta($id, TORET_MANAGER_ITEM_INTERNALID);
                    }

                } elseif (in_array($module, array('category', 'post_tag', 'product_cat', 'product_tag'))) {

                    foreach ($ids as $id) {
                        delete_term_meta($id, TORET_MANAGER_ITEM_INTERNALID);
                    }

                }
            }
        }

    }

    /**
     * Check if init sync health cron is running
     *
     * @return bool
     */
    static function is_init_sync_health_cron_running(): bool
    {
        return wp_next_scheduled(TORET_MANAGER_SYNC_CRON);
    }
}