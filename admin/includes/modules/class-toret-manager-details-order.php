<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Toret_Manager_Admin_Order_Details
{

    /**
     * Plugin slug
     *
     * @var string $toret_manager
     */
    protected string $toret_manager;

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
     * Add order metabox
     *
     * @param string $post_type
     * @param mixed $post
     */
    function add_order_metabox(string $post_type, $post): void
    {
        if (Toret_Manager_Helper::is_woocommerce_active() && Toret_Manager_Helper_Modules::is_module_enabled('order')) {

            if ($post instanceof WC_Order)
                $order = wc_get_order($post->get_id());
            else
                $order = wc_get_order($post->ID);

            if (!$order) {
                return;
            }

            $screen = wc_get_container()->get(Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
                ? wc_get_page_screen_id('shop-order')
                : 'shop_order';

            add_meta_box(
                'trman_order_metabox',
                __('Toret Manager','toret-manager'),
                array($this, 'order_metabox_html'),
                $screen,
                'side',
                'high'
            );
        }
    }

    /**
     * Order metabox content
     *
     * @param WP_Post|WC_Order $post_or_order_object
     */
    function order_metabox_html($post_or_order_object)
    {
        wp_nonce_field('trman_order_metabox', 'trman_order_metabox_nonce');

        $order = ($post_or_order_object instanceof WP_Post) ? wc_get_order($post_or_order_object->ID) : $post_or_order_object;

        echo '<div id="trman_order_data">';

        echo '<div id="trman_order_data" class="trman-btn-inline">';

        $option_id = TORET_MANAGER_ITEM_INTERNALID;

        woocommerce_wp_text_input(
            array(
                'id' => $option_id . '_' . $order->get_id(),
                'value' => $order->get_meta(TORET_MANAGER_ITEM_INTERNALID),
                'label' => esc_html__('Toret Manager Internal ID','toret-manager'),
                'desc_tip' => true,
                'custom_attributes' => array('data-id' => $order->get_id()),
                'description' => esc_html__('This is the internal ID in Toret Manager.','toret-manager'),
                'class' => 'trman-internalid-form-field',
            )
        );

        echo '<p><button type="button" id="trman-save-internalid-' . esc_attr($order->get_id()) . '" data-id="' . esc_attr($order->get_id()) . '" data-type="order" class="button-primary trman-save-internalid" disabled="disabled">' . esc_html__('Save Internal ID','toret-manager') . '</button></p>';
        echo '</div>';

        woocommerce_wp_checkbox(
            array(
                'id' => TORET_MANAGER_EXCLUDED_ITEM,
                'value' => $order->get_meta(TORET_MANAGER_EXCLUDED_ITEM),
                'label' => esc_html__('Exclude from synchronization','toret-manager'),
                'desc_tip' => true,
                'description' => esc_html__('Exclude order from synchronization.','toret-manager'),
                'wrapper_class' => 'trman-form-checkbox-field-wrap',
                'class' => 'checkbox trman-form-checkbox-field'
            )
        );
        echo '</div>';
    }

    /**
     * Save metabox data
     *
     * @param mixed $order_id
     */
    function save_order_metabox($order_id)
    {
        // Check if our nonce is set.
        if (!isset($_POST['trman_order_metabox_nonce'])) {
            return $order_id;
        }

        // Verify that the nonce is valid.
        if (!wp_verify_nonce(sanitize_text_field( wp_unslash($_POST['trman_order_metabox_nonce'])), 'trman_order_metabox')) {
            return $order_id;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $order_id;
        }

        if (isset($_POST[TORET_MANAGER_EXCLUDED_ITEM])) {
            Toret_Manager_HPOS_Compatibility::update_order_meta($order_id, TORET_MANAGER_EXCLUDED_ITEM,  sanitize_text_field($_POST[TORET_MANAGER_EXCLUDED_ITEM]));
        } else {
            Toret_Manager_HPOS_Compatibility::delete_order_meta($order_id, TORET_MANAGER_EXCLUDED_ITEM);
        }

        return $order_id;
    }

}