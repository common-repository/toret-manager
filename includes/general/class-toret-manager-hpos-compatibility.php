<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists('Toret_Manager_HPOS_Compatibility')) {
    class Toret_Manager_HPOS_Compatibility
    {

        /**
         * HPOS compatibility classs class instance
         *
         * @var Toret_Manager_HPOS_Compatibility|null
         */
        private static ?Toret_Manager_HPOS_Compatibility $instance = null;

        /**
         * HPOS enabled status
         *
         * @var bool
         */
        private static bool $hpos_enabled = false;

        /**
         * Get class instance
         *
         * @return Toret_Manager_HPOS_Compatibility|null
         */
        public static function get_instance(): ?Toret_Manager_HPOS_Compatibility
        {
            if (self::$instance == null) {
                self::$instance = new Toret_Manager_HPOS_Compatibility();
            }
            return self::$instance;
        }

        /**
         * Check if HPOS is enabled
         *
         * @return bool|null
         */
        public static function is_wc_hpos_enabled(): ?bool
        {
             if (class_exists('Automattic\WooCommerce\Utilities\OrderUtil')) {
                    self::$hpos_enabled = Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
                } else {
                    self::$hpos_enabled = false;
                }
            return self::$hpos_enabled;
        }

        /**
         * Get WC_Order object from the given value
         *
         * @param mixed $order
         */
        public static function get_order($order)
        {
            return (is_int($order) || is_string($order) ? wc_get_order($order) : $order);
        }

        /**
         * Get order id from the given value
         *
         * @param mixed $order
         */
        public static function get_order_id($order)
        {
            return (is_int($order) || is_string($order) ? (int)$order : $order->get_id());
        }

        /**
         * Get orders based on the arguments provided
         *
         * @param array $args
         * @return stdClass|WC_Order[]
         */
        public static function get_orders(array $args)
        {
            return wc_get_orders($args);
        }

        /**
         * Get order meta value.
         *
         * @param mixed $order
         * @param string $meta_key
         * @param bool $single
         * @param mixed $default
         * @return mixed
         */
        public static function get_order_meta($order, string $meta_key, bool $single = true, $default = '')
        {
            if (self::is_wc_hpos_enabled()) {
                $order = self::get_order($order);
                if (!$order) {
                    return $default;
                }
                $meta_value = $order->get_meta($meta_key);
                return (!$meta_value ? get_post_meta($order->get_id(), $meta_key, $single) : $meta_value);
            } else {
                $order_id = self::get_order_id($order);
                $meta_value = get_post_meta($order_id, $meta_key, $single);
                if (!$meta_value) {
                    $order = wc_get_order($order_id);
                    return $order ? $order->get_meta($meta_key) : $default;
                } else {
                    return $meta_value;
                }
            }
        }

        /**
         * Update order meta
         *
         * @param mixed $order
         * @param string $meta_key
         * @param mixed $value
         * @param bool $save
         * @return bool|int|null
         */
        public static function update_order_meta($order, string $meta_key, $value, bool $save = true)
        {
            if (self::is_wc_hpos_enabled()) {
                $order = self::get_order($order);
                $updated = $order->update_meta_data($meta_key, $value);
                if ($save)
                    $order->save();
				return $updated;
            } else {
                $order_id = self::get_order_id($order);
	            return update_post_meta($order_id, $meta_key, $value);
            }
        }

        /**
         * Delete order meta
         *
         * @param mixed $order
         * @param string $meta_key
         * @param bool $save
         * @return bool
         */
        public static function delete_order_meta($order, string $meta_key, bool $save = true): bool
        {
            $order = self::get_order($order);
            if (self::is_wc_hpos_enabled()) {
                 $order->delete_meta_data($meta_key);
                if ($save)
                    $order->save();
				return true;
            } else {
                $order_id = self::get_order_id($order);
	            return delete_post_meta($order_id, $meta_key);
            }
        }

    }
}