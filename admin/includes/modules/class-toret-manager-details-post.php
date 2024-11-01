<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Toret_Manager_Admin_Post_Details
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
     * Adds the meta box container.
     *
     * @param string $post_type
     */
    public function add_post_metabox(string $post_type)
    {
        $post_types = Toret_Manager_Helper_Modules::get_available_types_by_module('post');

        if (in_array($post_type, $post_types)) {
            add_meta_box(
                'trman_post_metabox',
                esc_html__('Toret Manager', 'toret-manager'),
                array($this, 'post_metabox_html'),
                $post_type,
                'side',
            );
        }
    }

    /**
     * Render metabox content
     *
     * @param WP_Post $post
     */
    public function post_metabox_html(WP_Post $post)
    {
        wp_nonce_field('trman_post_metabox', 'trman_post_metabox_nonce');

        $option_id = TORET_MANAGER_ITEM_INTERNALID;
        $option_excluded = TORET_MANAGER_EXCLUDED_ITEM . '_' . $post->post_type;

        echo '<div id="trman_post_data">';
        echo '<div id="trman_order_data" class="trman-btn-inline">';

        Toret_Manager_Draw_Functions::custom_wp_text_input(
            array(
                'id' => $option_id . '_' . get_the_ID(),
                'value' => get_post_meta($post->ID, TORET_MANAGER_ITEM_INTERNALID, true),
                'label' => 'Internal ID',
                'desc_tip' => true,
                'custom_attributes' => array('data-id' => get_the_ID()),
                'description' => esc_html__('This is the internal ID in Toret Manager.', 'toret-manager'),
                'class' => 'trman-internalid-form-field',
            )
        );
        echo '<p><button type="button" id="trman-save-internalid-' . esc_attr(get_the_ID()) . '" data-id="' . esc_attr(get_the_ID()) . '" data-type="post" class="button-primary trman-save-internalid" disabled="disabled">' . esc_html__('Save Internal ID', 'toret-manager') . '</button></p>';
        echo '</div>';

        Toret_Manager_Draw_Functions::custom_wp_checkbox(
            array(
                'id' => $option_excluded,
                'value' => get_post_meta($post->ID, TORET_MANAGER_EXCLUDED_ITEM, true),
                'label' => esc_html__('Exclude from synchronization', 'toret-manager'),
                'desc_tip' => true,
                'description' => esc_html__('Exclude post from synchronization.', 'toret-manager'),
                'wrapper_class' => 'trman-form-checkbox-field-wrap',
                'class' => 'checkbox trman-form-checkbox-field'
            )
        );
        echo '</div>';
    }

    /**
     * Save the meta when the post is saved.
     *
     * @param mixed $post_id
     */
    public function save_post_metabox($post_id)
    {
        // Check if our nonce is set.
        if (!isset($_POST['trman_post_metabox_nonce'])) {
            return $post_id;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST['trman_post_metabox_nonce']));

        // Verify that the nonce is valid.
        if (!wp_verify_nonce(sanitize_text_field( wp_unslash($nonce)), 'trman_post_metabox')) {
            return $post_id;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        if (in_array(get_post_type($post_id), array('product', 'shop_order'))) {
            return $post_id;
        }

        $option_excluded = TORET_MANAGER_EXCLUDED_ITEM . '_' . get_post_type($post_id);

        if (isset($_POST[$option_excluded])) {
            update_post_meta($post_id, TORET_MANAGER_EXCLUDED_ITEM, sanitize_text_field($_POST[$option_excluded]));
        } else {
            delete_post_meta($post_id, TORET_MANAGER_EXCLUDED_ITEM);
        }

        return $post_id;
    }


}