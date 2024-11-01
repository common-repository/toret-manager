<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

global $wpdb;

$enabled_modules = get_option(TORET_MANAGER_ENABLED_MODULES_OPTION, array());
$all_modules = Toret_Manager_Helper_Modules::get_all_modules();

$clear_imported_img_icon = WP_PLUGIN_URL . '/toret-manager/admin/img/clear-imported-items.svg';
$clear_img_icon = WP_PLUGIN_URL . '/toret-manager/admin/img/clear-items.svg';

?>

<div class="trman-admin-container-body-wrap module-clear">

    <div class="trman-admin-option-table">

        <div class="trman-admin-option-header">
            <img alt="<?php esc_attr_e('Clear imported as associated items', 'toret-manager') ?>"
                 class="trman-admin-option-img"
                 src="<?php echo esc_url($clear_imported_img_icon); ?>"/>
            <span><?php esc_attr_e('Clear imported items', 'toret-manager') ?></span>
        </div>

        <table class="trman-admin-table trman-admin-tools-table">
            <tr>
                <th><?php esc_html_e('Type', 'toret-manager') ?></th>
                <th><?php esc_html_e('Imported / Total', 'toret-manager') ?></th>
                <th><?php esc_html_e('Action', 'toret-manager') ?></th>
            </tr>

            <?php
            foreach ($all_modules as $module => $title) {

                if (!Toret_Manager_Helper_Modules::allow_woo_module($module)) {
                    continue;
                }

                $total = Toret_Manager_Sync_Clear::get_total_items($module);
                $synced = Toret_Manager_Sync_Clear::get_synced_items($module, 'count');
                if (empty($total))
                    $percent = 0;
                else
                    $percent = $synced / $total * 100;
                $percent = number_format($percent, 0);
                ?>

                <tr>
                    <td><?php echo esc_html($title) ?></td>

                    <?php
                    ?>

                    <td>
                        <?php echo '<strong>' . esc_html($synced . ' / ' . $total . ' (' . $percent . '%)').'</strong>' ?>
                    </td>
                    <td>
                        <?php echo '<form method="post">' . (wp_nonce_field('trman-sync-delete', 'trman-sync-delete-nonce')) . '<input type="hidden" name="trman_module_for_sync_delete" value="' . esc_attr($module) . '"/><input type="submit" class="button" value="' . esc_attr__('Delete', 'toret-manager') . '"/></form>'; ?>
                    </td>

                </tr>

                <?php
            }
            ?>
        </table>

    </div>

</div>

<div class="trman-admin-container-body-wrap module-clear">

    <div class="trman-admin-option-table">

        <div class="trman-admin-option-header">
            <img alt="<?php esc_attr_e('Clear items', 'toret-manager') ?>"
                 class="trman-admin-option-img"
                 src="<?php echo esc_url($clear_img_icon); ?>"/>
            <span><?php esc_html_e('Clear items', 'toret-manager') ?></span>
        </div>

        <table class="trman-admin-table trman-admin-tools-table">
            <tr>
                <th><?php esc_html_e('Type', 'toret-manager') ?></th>
                <th><?php esc_html_e('Has internal ID / Total', 'toret-manager') ?></th>
                <th><?php esc_html_e('Action', 'toret-manager') ?></th>
            </tr>

            <?php
            foreach ($all_modules as $module => $title) {

                if (!Toret_Manager_Helper_Modules::allow_woo_module($module)) {
                    continue;
                }

                $total = Toret_Manager_Sync_Clear::get_total_items($module);
                $synced = Toret_Manager_Sync_Clear::get_items_with_internal_id($module, 'count');

                if (empty($total))
                    $percent = 0;
                else
                    $percent = $synced / $total * 100;
                $percent = number_format($percent, 0);
                ?>

                <tr>
                    <td><?php echo esc_html($title) ?></td>
                    <td><?php echo '<strong>' . esc_html($synced . ' / ' . $total . ' (' . $percent . '%)').'</strong>' ?></td>

                    <td>
                        <?php
                        echo '<form method="post">' . (wp_nonce_field('trman-sync-delete', 'trman-sync-delete-nonce')) . '<input type="hidden" name="trman_module_for_sync_clear" value="' . esc_attr($module) . '"/><input type="submit" class="button" value="' . esc_attr__('Clear Internal ID', 'toret-manager') . '"/></form>';
                        if ($module != 'user')
                            echo '<form method="post">' . (wp_nonce_field('trman-sync-delete', 'trman-sync-delete-nonce')) . '<input type="hidden" name="trman_module_for_delete" value="' . esc_attr($module) . '"/><input type="submit" class="button" value="' . esc_attr__('Delete items', 'toret-manager') . '"/></form>';
                        ?>
                    </td>

                </tr>

                <?php
            }
            ?>
            <tr>
                <td><?php esc_html_e('All items', 'toret-manager') ?></td>
                <td>-</td>
                <td>
                    <?php
                    echo '<form method="post">' . (wp_nonce_field('trman-sync-delete', 'trman-sync-delete-nonce')) . '<input type="hidden" name="trman_module_for_sync_clear" value="all"/><input type="submit" class="button" value="' . esc_attr__('Clear All Internal IDs', 'toret-manager') . '"/></form>';
                    echo '<form method="post">' . (wp_nonce_field('trman-sync-delete', 'trman-sync-delete-nonce')) . '<input type="hidden" name="trman_module_for_delete" value="all"/><input type="submit" class="button" value="' . esc_attr__('Delete All', 'toret-manager') . '"/></form>';
                    echo '<form method="post">' . (wp_nonce_field('trman-sync-delete', 'trman-sync-delete-nonce')) . '<input type="hidden" name="trman_module_for_delete" value="product_attribute"/><input type="submit" class="button" value="' . esc_attr__('Delete Product Attributes', 'toret-manager') . '"/></form>';
                    echo '<form method="post">' . (wp_nonce_field('trman-sync-delete', 'trman-sync-delete-nonce')) . '<input type="hidden" name="trman_test_enable_all_up" value="all"/><input type="submit" class="button" value="' . esc_attr__('Enable All Up', 'toret-manager') . '"/></form>';
                    echo '<form method="post">' . (wp_nonce_field('trman-sync-delete', 'trman-sync-delete-nonce')) . '<input type="hidden" name="trman_test_enable_all_down" value="all"/><input type="submit" class="button" value="' . esc_attr__('Enable All Down', 'toret-manager') . '"/></form>';
                    echo '<form method="post">' . (wp_nonce_field('trman-sync-delete', 'trman-sync-delete-nonce')) . '<input type="hidden" name="trman_test_disable_all_up" value="all"/><input type="submit" class="button" value="' . esc_attr__('Disable All Up', 'toret-manager') . '"/></form>';
                    echo '<form method="post">' . (wp_nonce_field('trman-sync-delete', 'trman-sync-delete-nonce')) . '<input type="hidden" name="trman_test_disable_all_down" value="all"/><input type="submit" class="button" value="' . esc_attr__('Disable All Down', 'toret-manager') . '"/></form>';
                    ?>
                </td>

            </tr>
        </table>

    </div>

</div>
