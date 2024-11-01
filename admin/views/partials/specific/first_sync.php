<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $wpdb;

$enabled_modules = get_option(TORET_MANAGER_ENABLED_MODULES_OPTION, array());
$all_modules = Toret_Manager_Helper_Modules::get_all_modules();
$modules_with_types = Toret_Manager_Helper_Modules::get_all_modules(true);
$enabled_modules = get_option(TORET_MANAGER_ENABLED_MODULES_OPTION, array());

$initial_img_icon = WP_PLUGIN_URL . '/toret-manager/admin/img/initial-synchronization.svg';

?>

<div class="trman-admin-container-body-wrap module-clear">

    <div class="trman-admin-option-table">

        <div class="trman-admin-option-header">
            <img alt="<?php esc_html_e('Initial Synchronization','toret-manager') ?>"
                 class="trman-admin-option-img"
                 src="<?php echo esc_url($initial_img_icon); ?>"/>
            <span><?php esc_html_e('Initial Synchronization','toret-manager') ?></span>
        </div>

        <table class="trman-admin-table trman-admin-tools-table">
            <tr>
                <th><?php esc_html_e('Type','toret-manager') ?></th>
                <th><?php esc_html_e('Synchronized / Total','toret-manager') ?></th>
                <th><?php esc_html_e('Action','toret-manager') ?></th>
            </tr>

            <?php
            if (empty($enabled_modules)) {

                echo '<tr><td colspan="3">' . esc_html__('No active modules','toret-manager') . '</td></tr>';

            } else {
                foreach ($all_modules as $module => $title) {

                    if (!Toret_Manager_Helper_Modules::allow_woo_module($module)) {
                        continue;
                    }

                    if (!Toret_Manager_Helper_Modules::is_module_enabled($module))
                        continue;

                    if (!Toret_Manager_Helper_Modules::is_any_edit_sync_enabled($module, 'upload')) {
                        continue;
                    }

                    $total = Toret_Manager_Sync_Clear::get_total_items($module);
                    $synced = Toret_Manager_Sync_Clear::get_items_with_internal_id($module, 'count');
                    ?>

                    <tr>
                        <td><?php echo esc_html($title) ?></td>

                        <?php
                        $status = get_option('trman_init_sync_status');
                        $button_disabled = false;

                        if (!Toret_Manager_Sync_Clear::is_init_sync_health_cron_running()) {
                            update_option('trman_init_sync_cancel_clicked', 0);
                            update_option('trman_init_sync_run_clicked', 0);
                            update_option('trman_init_sync_status', '');
                        }

                        if ($status != '') {
                            if (get_option('trman_init_sync_cancel_clicked') == 1) {
                                $button_disabled = true;
                            }
                            $show_cancel = true;
                        } else {
                            if (get_option('trman_init_sync_run_clicked') == 1) {
                                $button_disabled = true;
                            }
                            $show_cancel = false;
                        }

                        //TODO schovat z poctu excluded?
                        $buttons = '<form method="post">';
                        $buttons.= wp_nonce_field('trman_initial_sync', 'trman_initial_sync_nonce');
                        if ($show_cancel) {
                            $buttons .= '<input type="hidden" name="trman_module_for_sync_cancel" value="' . esc_attr($module) . '"/><input ' . (esc_attr($button_disabled) ? "disabled" : "") . '  type="submit" class="button trman-sync-button" value="' . esc_attr__('Cancel Synchronization','toret-manager') . '"/>';
                        } else {
                            if ($synced < $total) {
                                $buttons .= '<input type="hidden" name="trman_module_for_sync_type" value="' . esc_attr($modules_with_types[$module]) . '"/>';
                                $buttons .= '<input type="hidden" name="trman_module_for_sync_start" value="' . esc_attr($module) . '"/><input ' . (esc_attr($button_disabled) ? "disabled" : "") . '  type="submit" class="button trman-sync-button" value="' . esc_attr__('Start Synchronization','toret-manager') . '"/>';
                            }
                        }

                        $buttons .= '</form>';

                        if (empty($total))
                            $percent = 0;
                        else
                            $percent = $synced / $total * 100;
                        $percent = number_format($percent, 0);
                        $status = '<strong>' . $synced . ' / ' . $total . ' (' . $percent . '%)</strong>';
                        ?>
                        <td><?php echo $status ?></td>
                        <td><?php echo $buttons ?></td>

                    </tr>

                    <?php
                }
            }
            ?>
        </table>

    </div>

</div>
