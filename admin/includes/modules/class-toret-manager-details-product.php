<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Toret_Manager_Admin_Product_Details
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
     * Create custom product tab
     *
     * @param array $tabs
     * @return array
     */
    function product_settings_tabs(array $tabs): array
    {
        $tabs['trman'] = array(
            'label' => 'Toret Manager',
            'target' => 'trman_product_data',
            'class' => array('show_if_simple'),
            'priority' => 21,
        );
        return $tabs;
    }

    /**
     * Add custom fields to simple product
     */
    function product_panels()
    {
        wp_nonce_field('trman_product_metabox', 'trman_product_metabox_nonce');

        $option_id = TORET_MANAGER_ITEM_INTERNALID;
        $option_excluded = TORET_MANAGER_EXCLUDED_ITEM . '_' . 'product' . '_' . get_the_ID();

        echo '<div id="trman_product_data" class="panel woocommerce_options_panel hidden">';
        woocommerce_wp_text_input(
            array(
                'id' => $option_id . '_' . get_the_ID(),
                'value' => get_post_meta(get_the_ID(), TORET_MANAGER_ITEM_INTERNALID, true),
                'label' => esc_html__('Toret Manager Internal ID','toret-manager'),
                'desc_tip' => true,
                'custom_attributes' => array('data-id' => get_the_ID()),
                'description' => esc_html__('This is the internal ID in Toret Manager.','toret-manager'),
                'class' => 'trman-internalid-form-field',
            )
        );

        echo '<p><button type="button" id="trman-save-internalid-' . esc_attr(get_the_ID()) . '" data-id="' . esc_attr(get_the_ID()) . '" data-type="product" class="button-primary trman-save-internalid" disabled="disabled">' . esc_html__('Save Internal ID','toret-manager') . '</button></p>';

        woocommerce_wp_checkbox(
            array(
                'id' => $option_excluded,
                'value' => get_post_meta(get_the_ID(), TORET_MANAGER_EXCLUDED_ITEM, true),
                'label' => esc_html__('Exclude from synchronization','toret-manager'),
                'desc_tip' => true,
                'description' => esc_html__('Disable product from synchronization.','toret-manager'),
                'wrapper_class' => 'trman-form-checkbox-field-wrap',
                'class' => 'checkbox trman-form-checkbox-field'
            )
        );
        echo '</div>';
    }

    /**
     * Add custom field to variation
     *
     * @param int $loop
     * @param array $variation_data
     * @param mixed $variation
     */
    function variation_panel(int $loop, array $variation_data, $variation)
    {
        wp_nonce_field('trman_product_metabox', 'trman_product_metabox_nonce');

        $option_id = TORET_MANAGER_ITEM_INTERNALID;
        $option_excluded = TORET_MANAGER_EXCLUDED_ITEM . '_' . 'product' . '_' . $variation->ID;

        woocommerce_wp_text_input(
            array(
                'id' => $option_id . '_' . $variation->ID,
                'label' => 'Toret Manager Internal ID',
                'wrapper_class' => 'form-row',
                'desc_tip' => 'true',
                'custom_attributes' => array('data-id' => $variation->ID),
                'description' => esc_html__('This is the internal ID in Toret Manager.','toret-manager'),
                'value' => get_post_meta($variation->ID, TORET_MANAGER_ITEM_INTERNALID, true),
                'class' => 'trman-internalid-form-field'
            )
        );

        echo '<button type="button" id="trman-save-internalid-' . esc_attr($variation->ID) . '" data-id="' . esc_attr($variation->ID) . '" data-type="product" class="button-primary trman-save-internalid" disabled="disabled">' . esc_html__('Save Internal ID','toret-manager') . '</button>';

        woocommerce_wp_checkbox(
            array(
                'id' => $option_excluded,
                'name' => $option_excluded,
                'value' => get_post_meta($variation->ID, TORET_MANAGER_EXCLUDED_ITEM, true),
                'label' => esc_html__('Exclude from synchronization','toret-manager'),
                'desc_tip' => true,
                'description' => esc_html__('Exclude product variation from synchronization.','toret-manager'),
                'wrapper_class' => 'trman-form-checkbox-field-wrap',
                'class' => 'checkbox trman-form-checkbox-field'
            )
        );
    }

    /**
     * Save custom product field
     *
     * @param mixed $id
     */
    function save_product_field($id)
    {
        if (!isset($_POST['trman_product_metabox_nonce'])) {
            return $id;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST['trman_product_metabox_nonce']));

        if (!wp_verify_nonce($nonce, 'trman_product_metabox')) {
            return $id;
        }

        $option_excluded = TORET_MANAGER_EXCLUDED_ITEM . '_' . 'product' . '_' . $id;

        if (isset($_POST[$option_excluded])) {
            update_post_meta($id, TORET_MANAGER_EXCLUDED_ITEM, sanitize_text_field($_POST[$option_excluded]));
        } else {
            delete_post_meta($id, TORET_MANAGER_EXCLUDED_ITEM);
        }

        return $id;
    }

    /**
     * Save custom variation field
     *
     * @param mixed $variation_id
     * @param mixed $loop
     */
    function save_variation_field($variation_id, $loop)
    {
        if (!isset($_POST['trman_product_metabox_nonce'])) {
            return $variation_id;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST['trman_product_metabox_nonce']));

        if (!wp_verify_nonce($nonce, 'trman_product_metabox')) {
            return $variation_id;
        }

        $option_excluded = TORET_MANAGER_EXCLUDED_ITEM . '_' . 'product'. '_' . $variation_id;

        if (isset($_POST[$option_excluded])) {
            update_post_meta($variation_id, TORET_MANAGER_EXCLUDED_ITEM, sanitize_text_field($_POST[$option_excluded]));
        } else {
            delete_post_meta($variation_id, TORET_MANAGER_EXCLUDED_ITEM);
        }

        return $variation_id;
    }

}