<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Toret_Manager_Admin_Term_Details
{

    /**
     * Plugin slug
     *
     * @var string
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
     * Add custom term field to create screen
     *
     * @param string $taxonomy
     */
    function add_term_fields(string $taxonomy)
    {
        wp_nonce_field('trman_term_metabox', 'trman_term_metabox_nonce');

        $option_id = TORET_MANAGER_ITEM_INTERNALID;
        $option_excluded = TORET_MANAGER_EXCLUDED_ITEM . '_' . $taxonomy;

        ?>
        <div class="form-field">
            <label for="<?php echo esc_attr($option_id); ?>"><?php esc_html_e('Toret Manager Internal ID','toret-manager') ?></label>
            <input type="text" name="<?php echo esc_attr($option_id); ?>"
                   id="<?php echo esc_attr($option_id); ?>"/>
            <p><?php esc_html_e('This is the internal ID in Toret Manager.','toret-manager') ?></p>
        </div>
        <div class="form-field">
            <label for="<?php echo esc_attr($option_excluded); ?>"><?php esc_html_e('Toret Manager Synchronization','toret-manager') ?></label>
            <input type="checkbox" name="<?php echo esc_attr($option_excluded); ?>" value="yes"
                   id="<?php echo esc_attr($option_excluded); ?>"/>
            <?php esc_html_e('Exclude items with term from synchronization.', 'toret-manager') ?>
        </div>
        <?php
    }

    /**
     * Add custom term field to edit screen
     *
     * @param WP_Term $term
     * @param string $taxonomy
     */
    function edit_term_fields(WP_Term $term, string $taxonomy)
    {
        wp_nonce_field('trman_term_metabox', 'trman_term_metabox_nonce');

        $internalID = get_term_meta($term->term_id, TORET_MANAGER_ITEM_INTERNALID, true);
        $excluded = get_term_meta($term->term_id, TORET_MANAGER_EXCLUDED_ITEM, true);

        $option_id = TORET_MANAGER_ITEM_INTERNALID;
        $option_excluded = TORET_MANAGER_EXCLUDED_ITEM . '_' . $taxonomy;

        ?>
        <tr class="form-field">
            <th>
                <label for="<?php echo esc_attr($option_id.'_'.$term->term_id); ?>"><?php esc_html_e('Toret Manager Internal ID','toret-manager') ?></label>
            </th>
            <td>
                <input name="<?php echo esc_attr($option_id); ?>" id="<?php echo esc_attr(($option_id.'_'.$term->term_id)); ?>" data-id="<?php echo esc_attr($term->term_id); ?>"
                       type="text" value="<?php echo esc_attr($internalID) ?>" class="trman-internalid-form-field"/>
                <p class="description"><?php esc_html_e('This is the internal ID in Toret Manager.','toret-manager') ?></p>
            </td>
            <td>
                <button type="button" id="trman-save-internalid-<?php echo esc_attr($term->term_id); ?>" data-id="<?php echo esc_attr($term->term_id); ?>" data-type="term" class="button-primary trman-save-internalid" disabled="disabled"><?php esc_html_e('Save Internal ID','toret-manager')?></button>
            </td>
        </tr>
        <tr class="form-field">
            <th>
                <label for="<?php echo esc_attr($option_excluded); ?>"><?php esc_html_e('Toret Manager Synchronization','toret-manager') ?></label>
            </th>
            <td>
                <input name="<?php echo esc_attr($option_excluded); ?>" id="<?php echo esc_attr($option_excluded); ?>"
                       type="checkbox" <?php echo esc_attr(($excluded == "yes" ? "checked" : "")); ?> value="yes"/>
                <?php esc_html_e('Exclude items with term from synchronization.', 'toret-manager') ?>
            </td>

        </tr>
        <?php
    }

    /**
     * Save term custom fields
     *
     * @param mixed $term_id
     * @param mixed $tt_id
     * @param string $taxonomy
     * @return mixed|void
     */
    function save_term_fields($term_id, $tt_id, string $taxonomy)
    {
        if (!isset($_POST['trman_term_metabox_nonce'])) {
            return $term_id;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST['trman_term_metabox_nonce']));

        if (!wp_verify_nonce($nonce, 'trman_term_metabox')) {
            return $term_id;
        }

        $option_excluded = TORET_MANAGER_EXCLUDED_ITEM . '_' . $taxonomy;

        if (isset($_POST[$option_excluded])) {
            update_term_meta(
                $term_id,
                TORET_MANAGER_EXCLUDED_ITEM,
                sanitize_text_field($_POST[$option_excluded])
            );
        } else {
            delete_term_meta(
                $term_id,
                TORET_MANAGER_EXCLUDED_ITEM
            );
        }
    }


}