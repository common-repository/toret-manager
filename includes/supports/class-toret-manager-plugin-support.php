<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Toret_Manager_Plugins_Support
{

    /**
     * Add shipping data to order from Packeta plugin
     *
     * @param mixed $order_id
     * @param array $meta
     * @return array
     */
    function add_packeta_data_to_order($order_id, array $meta): array
    {
        if (in_array('packeta/packeta.php', apply_filters('active_plugins', get_option('active_plugins')))) {

            global $wpdb;
            $table_name = $wpdb->prefix . "packetery_order";
            $shipping_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %s", $table_name, $order_id));

            if (!empty($shipping_data)) {
                $packeta_data = [
                    'carrier_id' => $shipping_data->carrier_id,
                    'point_id' => $shipping_data->point_id,
                    'packet_id' => $shipping_data->packet_id,
                    'packet_claim_id' => $shipping_data->packet_claim_id,
                ];
                $meta['packeta_data'] = $packeta_data;

                return $meta;
            }

        }
        return $meta;

    }


}