<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Toret_Manager_Helper_Db
{

    /**
     * Delete HPOS order
     *
     * @param string $module
     * @param array $ids
     */
    public static function delete_hpos_order(string $module, array $ids): void
    {
        global $wpdb;

        if (Toret_Manager_HPOS_Compatibility::is_wc_hpos_enabled() && $module == 'order') {
            foreach ($ids as $id) {
                $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "wc_orders WHERE id = %d", $id));
                $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "wc_orders_meta WHERE order_id = %d", $id));
                $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "comments WHERE comment_post_ID = %d", $id));
            }
        } else {
            foreach ($ids as $id) {
                wp_delete_post($id, true);
            }
        }
    }


    /**
     * Woo order query custom variable
     *
     * @param array $query
     * @param array $query_vars
     * @return array
     */
    function handle_order_number_custom_query_var(array $query, array $query_vars): array
    {
        if (!empty($query_vars['internal_id'])) {
            $query['meta_query'][] = array(
                'key' => TORET_MANAGER_ITEM_INTERNALID,
                'value' => esc_attr($query_vars['internal_id']),
            );
        }

        return $query;
    }

    /**
     * Get term by meta value
     *
     * @param string $meta_key
     * @param string $meta_value
     * @param mixed $taxonomy
     * @return int[]|string|string[]|WP_Error|WP_Term[]|null
     */
    static function get_term_by_meta_value(string $meta_key, string $meta_value, $taxonomy = TORET_MANAGER_PRODUCT_TERMS)
    {
        if ($taxonomy == 'unknown') {

            global $wpdb;
            $result = $wpdb->get_var($wpdb->prepare("SELECT tm.term_id FROM {$wpdb->termmeta} AS tm INNER JOIN {$wpdb->term_taxonomy} AS tt ON tm.term_id = tt.term_id WHERE tm.meta_key = %s AND tm.meta_value = %s",
                $meta_key, $meta_value
            ));

            if (!empty($result)) {
                return array(get_term($result));
            }

        } else {

            $args = array(
                'hide_empty' => false,
                'meta_query' => array(
                    array(
                        'key' => $meta_key,
                        'value' => $meta_value,
                        'compare' => 'LIKE'
                    )
                )
            );

            if ($taxonomy != null) {
                $args['taxonomy'] = $taxonomy;
            }

            return get_terms($args);

        }

        return null;
    }

    /**
     * Get item by meta value
     *
     * @param string $meta_key
     * @param string $meta_value
     * @param string $type
     * @param string $module
     * @return int|mixed|string|WC_Order|WP_Post|null
     */
    static function get_post_by_meta_value(string $meta_key, string $meta_value, string $type = 'post', string $module = 'any')
    {
        if ($type == 'order') {

            $order_id = wc_get_orders(array('internal_id' => $meta_value, 'limit' => 1, 'return' => 'ids'));

            if (!$order_id || is_wp_error($order_id)) return null;

            return $order_id[0];

        } elseif ($type == 'review' || $type == 'comment') {

            return self::get_comment_by_meta_value($module, $meta_key, $meta_value);

        } elseif ($type == 'user') {

            return self::get_user_by_meta_value($meta_key, $meta_value);

        } else {

            $args = array(
                'meta_query' => array(
                    array(
                        'key' => $meta_key,
                        'value' => $meta_value
                    )
                ),
                'fields' => 'ids',
                'post_status' => TORET_MANAGER_ALLOWED_POST_STATUSES,
                'post_type' => $module,
                'posts_per_page' => '-1'
            );

            if ($type == 'product') {
                $args['post_type'] = array('product', 'product_variation');
            } elseif ($module != 'any') {
                $args['post_type'] = $module;
            }

            $posts = get_posts($args);

            if (!$posts || is_wp_error($posts)) return null;

            return $posts[0];
        }
    }

    /**
     * Get comment by meta value
     *
     * @param string $module
     * @param string $meta_key
     * @param string $meta_value
     * @return string|null
     */
    static function get_comment_by_meta_value(string $module, string $meta_key, string $meta_value): ?string
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT c.comment_ID FROM {$wpdb->comments} c LEFT JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id AND cm.meta_key = %s WHERE cm.meta_value =%s AND c.comment_type = %s", $meta_key, $meta_value, $module));
    }

    /**
     * Get user by meta value
     *
     * @param string $meta_key
     * @param string $meta_value
     * @return mixed|null
     */
    static function get_user_by_meta_value(string $meta_key, string $meta_value)
    {
        $user_query = new WP_User_Query(
            array(
                'fields' => 'ids',
                'meta_key' => $meta_key,
                'meta_value' => $meta_value
            )
        );

        $users = $user_query->get_results();
        return $users[0] ?? null;
    }

    /**
     * Get item meta
     *
     * @param mixed $post_id
     * @param string $internalID_key
     * @param string $type
     * @return mixed
     */
    public static function get_object_meta($post_id, string $internalID_key, string $type = 'post')
    {
        if ($type == 'order') {
            return Toret_Manager_HPOS_Compatibility::get_order_meta($post_id, $internalID_key);
        } elseif ($type == 'user') {
            return get_user_meta($post_id, $internalID_key, true);
        } elseif ($type == 'comment') {
            return get_comment_meta($post_id, $internalID_key, true);
        } elseif ($type == 'term') {
            return get_term_meta($post_id, $internalID_key, true);
        } else {
            return get_post_meta($post_id, $internalID_key, true);
        }
    }

    /**
     * Update item meta
     *
     * @param mixed $post_id
     * @param string $internalID_key
     * @param mixed $value
     * @param string $type
     * @return bool|int|WP_Error|null
     */
    public static function update_object_meta($post_id, string $internalID_key, $value, string $type)
    {
        if ($type == 'order') {
            return Toret_Manager_HPOS_Compatibility::update_order_meta($post_id, $internalID_key, $value);
        } elseif ($type == 'user') {
            return update_user_meta($post_id, $internalID_key, $value);
        } elseif ($type == 'comment') {
            return update_comment_meta($post_id, $internalID_key, $value);
        } elseif ($type == 'term') {
            return update_term_meta($post_id, $internalID_key, $value);
        } else {
            return update_post_meta($post_id, $internalID_key, $value);
        }
    }

    /**
     * Delete object meta
     *
     * @param mixed $post_id
     * @param string $internalID_key
     * @param string $type
     * @return bool
     */
    public static function delete_object_meta($post_id, string $internalID_key, string $type): bool
    {
        if ($type == 'order') {
            return Toret_Manager_HPOS_Compatibility::delete_order_meta($post_id, $internalID_key);
        } elseif ($type == 'user') {
            return delete_user_meta($post_id, $internalID_key);
        } elseif ($type == 'comment') {
            return delete_comment_meta($post_id, $internalID_key);
        } elseif ($type == 'term') {
            return delete_term_meta($post_id, $internalID_key);
        } else {
            return delete_post_meta($post_id, $internalID_key);
        }
    }

    /**
     * Delete WooCommerce product and its stuff
     *
     * @param mixed $id
     */
    static function delete_product($id)
    {
        global $wpdb;

        $wpdb->delete($wpdb->posts, array(
            'post_type' => 'product',
            'ID' => $id,
        ));
        $wpdb->delete($wpdb->posts, array(
            'post_type' => 'product_variation',
            'ID' => $id,
        ));

        /*$wpdb->query(
            "
				DELETE {$wpdb->posts}.* FROM {$wpdb->posts}
				LEFT JOIN {$wpdb->posts} wp ON wp.ID = {$wpdb->posts}.post_parent
				WHERE wp.ID IS NULL AND {$wpdb->posts}.post_type = 'product_variation'
			"
        );*/
        $wpdb->query($wpdb->prepare(
            "
    DELETE {$wpdb->posts}.* FROM {$wpdb->posts}
    LEFT JOIN {$wpdb->posts} wp ON wp.ID = {$wpdb->posts}.post_parent
    WHERE wp.ID IS NULL AND {$wpdb->posts}.post_type = %s
    ",
            'product_variation'
        ));

        /*$wpdb->query(
            "
				DELETE {$wpdb->postmeta}.* FROM {$wpdb->postmeta}
				LEFT JOIN {$wpdb->posts} wp ON wp.ID = {$wpdb->postmeta}.post_id
				WHERE wp.ID IS NULL
			"
        );*/

        $wpdb->query(
            "
    DELETE {$wpdb->postmeta}.* FROM {$wpdb->postmeta}
    LEFT JOIN {$wpdb->posts} wp ON wp.ID = {$wpdb->postmeta}.post_id
    WHERE wp.ID IS NULL
    "        );


        /*$wpdb->query("
				DELETE tr.* FROM {$wpdb->term_relationships} tr
				LEFT JOIN {$wpdb->posts} wp ON wp.ID = tr.object_id
				LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				WHERE wp.ID IS NULL
				AND tt.taxonomy IN ( '" . implode("','", array_map('esc_sql', get_object_taxonomies('product'))) . "' )
			");*/
        $taxonomies = get_object_taxonomies('product');
        //$placeholders = implode(',', array_fill(0, count($taxonomies), '%s'));
        $wpdb->query($wpdb->prepare("DELETE tr.* FROM {$wpdb->term_relationships} tr LEFT JOIN {$wpdb->posts} wp ON wp.ID = tr.object_id LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE wp.ID IS NULL AND tt.taxonomy IN (%s)",implode(',',$taxonomies)));
        
    }
}