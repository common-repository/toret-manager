<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * @param string $slug
 * @param string $specific
 * @param $Toret_Manager_Draw_Functions
 * @param string $title
 * @param array $data
 * @param array $mandatory_data
 * @param string $endpoint
 *
 * @return void
 */
function trman_module_template( string $slug, string $specific, $Toret_Manager_Draw_Functions, string $title, array $data, array $mandatory_data, string $endpoint = '' ) {
	$label_upload_new    = esc_html__( 'Enable creation sync', 'toret-manager' );
	$label_upload_update = esc_html__( 'Enable update sync', 'toret-manager' );
	$label_upload_delete = esc_html__( 'Enable delete sync', 'toret-manager' );

	$label_download_new    = esc_html__( 'Sync new', 'toret-manager' );
	$label_download_update = esc_html__( 'Sync updates', 'toret-manager' );
	$label_download_delete = esc_html__( 'Sync deletions', 'toret-manager' );

	$upload_img_url   = WP_PLUGIN_URL . '/toret-manager/admin/img/cloud-upload-solid.svg';
	$download_img_url = WP_PLUGIN_URL . '/toret-manager/admin/img/cloud-download-solid.svg';
	$stock_img_url    = WP_PLUGIN_URL . '/toret-manager/admin/img/inventory-solid.svg';
	$settings_img_url = WP_PLUGIN_URL . '/toret-manager/admin/img/cogs-solid.svg';

	?>
    <div class="trman-admin-body-container trman-module-<?php echo esc_attr( $specific ); ?>">

		<?php $Toret_Manager_Draw_Functions->draw_module_title_with_checkbox( $title, 'trman_module_' . $specific . '_enabled', 'toret-manager', 'module-' . $specific, $specific, $endpoint ); ?>

        <div class="trman-admin-container-body-wrap module-<?php echo esc_attr( $specific ); ?>"
             style="<?php echo esc_attr( get_option( 'trman_module_' . $specific . '_enabled' ) == 'ok' ? "" : "display:none" ); ?>">

            <form method="post">

                <p class="trman-admin-below-title"><?php esc_html_e( 'Configure automatic synchronization with ', 'toret-manager' ) ?>
                    <a href="<?php echo esc_url( TORET_MANAGER_APP_URL ) ?>" target="_blank">app.toret.net</a>.</p>

                <div class="trman-admin-option-table">
                    <div class="trman-admin-option-header">
                        <img alt="<?php esc_attr_e( 'Sync Data from the Web to the Cloud', 'toret-manager' ) ?>"
                             class="trman-admin-option-img"
                             src="<?php echo esc_url( $upload_img_url ); ?>"/>
                        <span><?php esc_html_e( 'Sync Data from the Web to the Cloud', 'toret-manager' ) ?></span>
                    </div>
                    <table class="trman-admin-table">
						<?php
						( $Toret_Manager_Draw_Functions->draw_sync_row( $label_upload_new, 'trman_module_upload_' . esc_attr( $specific ) . '_new', 'toret-manager-sync-checkbox', esc_attr( $specific ) . '_upload_new' ) );
						$Toret_Manager_Draw_Functions->draw_properties_checkboxes( $specific, $data, 'trman_module_upload_' . esc_attr( $specific ) . '_new', $mandatory_data, 'trman_module_upload_new_' . esc_attr( $specific ) . '_items', 'toret-manager', 'trman-property-checkboxes-wrap', esc_attr( $specific ) . '_upload_new' );
						( $Toret_Manager_Draw_Functions->draw_sync_row( $label_upload_update, 'trman_module_upload_' . esc_attr( $specific ) . '_update', 'toret-manager-sync-checkbox', esc_attr( $specific ) . '_upload_update' ) );
						$Toret_Manager_Draw_Functions->draw_properties_checkboxes( $specific, $data, 'trman_module_upload_' . esc_attr( $specific ) . '_update', $mandatory_data, 'trman_module_upload_update_' . esc_attr( $specific ) . '_items', 'toret-manager', 'trman-property-checkboxes-wrap', esc_attr( $specific ) . '_upload_update' );
						( $Toret_Manager_Draw_Functions->draw_sync_delete_row( $label_upload_delete, 'trman_module_upload_' . esc_attr( $specific ) . '_delete' ) );

						?>
                    </table>
                </div>

                <div class="trman-admin-option-table">
                    <div class="trman-admin-option-header">
                        <img alt="<?php esc_attr_e( 'Sync Data from the Cloud to the Web', 'toret-manager' ) ?>"
                             class="trman-admin-option-img"
                             src="<?php echo esc_url( $download_img_url ); ?>"/>
                        <span><?php esc_html_e( 'Sync Data from the Cloud to the Web', 'toret-manager' ) ?></span>
                    </div>
                    <table class="trman-admin-table">
						<?php
						( $Toret_Manager_Draw_Functions->draw_sync_row( $label_download_new, 'trman_module_download_' . esc_attr( $specific ) . '_new', 'toret-manager-sync-checkbox', esc_attr( $specific ) . '_download_new' ) );
						$Toret_Manager_Draw_Functions->draw_properties_checkboxes( $specific, $data, 'trman_module_download_' . esc_attr( $specific ) . '_new', $mandatory_data, 'trman_module_download_new_' . esc_attr( $specific ) . '_items', 'toret-manager', 'trman-property-checkboxes-wrap', esc_attr( $specific ) . '_download_new', 'download' );
						( $Toret_Manager_Draw_Functions->draw_sync_row( $label_download_update, 'trman_module_download_' . esc_attr( $specific ) . '_update', 'toret-manager-sync-checkbox', esc_attr( $specific ) . '_download_update' ) );
						$Toret_Manager_Draw_Functions->draw_properties_checkboxes( $specific, $data, 'trman_module_download_' . esc_attr( $specific ) . '_update', $mandatory_data, 'trman_module_download_update_' . esc_attr( $specific ) . '_items', 'toret-manager', 'trman-property-checkboxes-wrap', esc_attr( $specific ) . '_download_update', 'download' );
						( $Toret_Manager_Draw_Functions->draw_sync_delete_row( $label_download_delete, 'trman_module_download_' . esc_attr( $specific ) . '_delete' ) );

						if ( in_array( $endpoint, array( 'Post', 'Product', 'Comment' ) ) ) {
							$Toret_Manager_Draw_Functions->draw_imported_status_row( esc_html__( 'Status of imported item', 'toret-manager' ), 'trman_module_' . esc_attr( $specific ) . '_imported_status', esc_attr( $endpoint ) );
						}


						?>
                    </table>
                </div>

				<?php
				if ( $specific == 'product' ) {
					?>

                    <div class="trman-admin-option-table">
                        <div class="trman-admin-option-header">
                            <img alt="<?php esc_attr_e( 'Inventory Stock Synchronization', 'toret-manager' ) ?>"
                                 class="trman-admin-option-img"
                                 src="<?php echo esc_url( $stock_img_url ); ?>"/>
                            <span><?php esc_html_e( 'Inventory Stock Synchronization', 'toret-manager' ) ?></span>
                        </div>
                        <table class="trman-admin-table">
							<?php
							( $Toret_Manager_Draw_Functions->draw_stock_sync_row( esc_html__( 'Enable synchronization for stock quantity changes', 'toret-manager' ), 'trman_module_upload_' . 'stock' . '_update', 'toret-manager-sync-checkbox', 'stock' . '_upload_update' ) );
							$Toret_Manager_Draw_Functions->draw_properties_checkboxes( 'stock', TORET_MANAGER_STOCK_DATA, 'trman_module_upload_' . 'stock' . '_update', TORET_MANAGER_STOCK_DATA_MANDATORY, 'trman_module_upload_update_' . 'stock' . '_items', $slug, 'trman-property-checkboxes-wrap', 'stock' . '_upload_update' );
							?>
                        </table>
                    </div>

					<?php
				}
				?>

                <div class="trman-admin-option-table">
                    <div class="trman-admin-option-header">
                        <img alt="<?php esc_attr_e( 'Others', 'toret-manager' ) ?>" class="trman-admin-option-img"
                             src="<?php echo esc_url( $settings_img_url ); ?>"/>
                        <span><?php esc_html_e( 'Sync Settings', 'toret-manager' ) ?></span>
                    </div>

                    <table class="trman-admin-table">

						<?php

						if ( $specific == 'product' ) {
							( $Toret_Manager_Draw_Functions->draw_input_checkbox_row( esc_html__( 'Additionally try to pair product by SKU if Toret ID is not found', 'toret-manager' ), 'trman_module_product_pairing_sku' ) );
							( $Toret_Manager_Draw_Functions->draw_input_text_row( esc_html__( 'EAN Custom Field', 'toret-manager' ), 'trman_module_product_field_ean' ) );
							( $Toret_Manager_Draw_Functions->draw_input_text_row( esc_html__( 'ISBN Custom Field', 'toret-manager' ), 'trman_module_product_field_isbn' ) );
							( $Toret_Manager_Draw_Functions->draw_input_text_row( esc_html__( 'GTIN Custom Field', 'toret-manager' ), 'trman_module_product_field_gtin' ) );
						}

						if ( ! in_array( $specific, TORET_MANAGER_TYPES_WO_PARENT ) ) {
							( $Toret_Manager_Draw_Functions->draw_input_checkbox_row( esc_html__( 'Auto-create parent items when notified', 'toret-manager' ), 'trman_module_' . esc_attr( $specific ) . '_get_parent', '', '', '', '', '', '', '', true, esc_html__( "Automatically downloads and creates a parent item if it's missing.", 'toret-manager' ) ) );
						}

						( $Toret_Manager_Draw_Functions->draw_input_checkbox_row( esc_html__( 'Synchronise related items', 'toret-manager' ), 'trman_module_' . esc_attr( $specific ) . '_get_associated', '', '', '', '', '', '', '', true, esc_html__( "Automatically synchronises other related items. These can be categories, tags or comments, for example.", 'toret-manager' ) ) );
						( $Toret_Manager_Draw_Functions->draw_input_checkbox_row( esc_html__( 'Synchronise files when updating', 'toret-manager' ), 'trman_module_' . esc_attr( $specific ) . '_files_update', '', '', '', '', '', '', '', true, esc_html__( "Ensures that files, including images and other attached files in the content, are re-downloaded from the source site when updating.", 'toret-manager' ) ) );
						?>
                    </table>
                </div>

				<?php wp_nonce_field( 'trman-admin-save', 'trman-admin-save-nonce' ); ?>

                <input type="hidden" name="trman-saving-module"
                       value="<?php echo esc_attr( $specific ); ?>"/>
                <input type="hidden" name="trman-saving-type"
                       value="<?php echo esc_attr( $endpoint ); ?>"/>

                <div class="clear"></div>

            </form>

        </div>
    </div>
	<?php
}
