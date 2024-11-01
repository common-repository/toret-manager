<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * @var Toret_Manager_Draw_Functions $Toret_Manager_Draw_Functions
 */

?>

    <div class="trman-admin-wrap">

        <h1><?php esc_html_e( 'Toret Manager', 'toret-manager' ); ?></h1>

        <div class="trman-admin-body-container">

            <p><a href="<?php echo esc_url( TORET_MANAGER_DOCUMENTATION_URL ); ?>" target="_blank"
                "><?php esc_html_e( 'Plugin documentation', 'toret-manager' ); ?></a></p>

        </div>

        <form method="post">
			<?php wp_nonce_field( 'trman-admin-save', 'trman-admin-save-nonce' ); ?>

            <div class="trman-admin-body-container">
                <h2><?php esc_html_e( 'API Credentials', 'toret-manager' ); ?></h2>
                <p><?php esc_html_e( 'Information about the API can be obtained at ', 'toret-manager' ) ?><a href="<?php echo esc_url( TORET_MANAGER_APP_SHOPS_URL ) ?>" target="_blank"><?php esc_html_e( 'app.toret.net → Websites', 'toret-manager' ); ?></a>.
                </p>
                <table class="form-table trman-admin-table">
                    <tr>
                        <th><?php esc_html_e( 'API status', 'toret-manager' ); ?></th>
                        <td>
                            <span class="<?php echo esc_attr( $Toret_Manager_Draw_Functions->get_api_check_msg_class() ) ?>"><?php echo esc_html( get_option( 'trman_api_check_notice', __( 'Please verify credentials first', 'toret-manager' ) ) ); ?></span>
                        </td>
                    </tr>
					<?php
					$Toret_Manager_Draw_Functions->draw_code_info_row( __( 'Notify URL', 'toret-manager' ), '', home_url( '/wp-json/api/notify' ) );
					$Toret_Manager_Draw_Functions->draw_code_info_row( __( 'Process notification cron URL', 'toret-manager' ), '', home_url( '/wp-json/api/notify/process' ) );
					$Toret_Manager_Draw_Functions->draw_input_checkbox_row(
						__( 'Use WP scheduler for notify postprocessing', 'toret-manager' ), TORET_MANAGER_NOTIFY_WP_SCHEDULER,
						false, '', '', '', '', '', '', false, '', 'ok'
					);

					$Toret_Manager_Draw_Functions->draw_input_text_row( __( 'User hash', 'toret-manager' ), TORET_MANAGER_USER_HASH );
					$Toret_Manager_Draw_Functions->draw_input_text_row( __( 'API key', 'toret-manager' ), TORET_MANAGER_API_KEY );
					$Toret_Manager_Draw_Functions->draw_input_text_row( __( 'Web ID', 'toret-manager' ), TORET_MANAGER_SHOP_ID );
					?>
                </table>
                <div class="toret-manager-admin-module-save api-keys">
                    <input type="submit" class="button button-primary toret-button"
                           name="trman-save-api-keys"
                           value="<?php esc_attr_e( 'Save and test connection', 'toret-manager' ); ?>"/>
                </div>
            </div>

        </form>

		<?php

		if ( get_option( 'trman_api_check', 'verify' ) != "verify" ) {


			/**
			 * Menu
			 * @var array $enabled_post_types
			 * @var array $enabled_term_types
			 */
			$links          = Toret_Manager_Helper_Modules::get_all_modules();
			$links['tools'] = __( 'Tools', 'toret-manager' );

			$enabled_modules = get_option( TORET_MANAGER_ENABLED_MODULES_OPTION, array() );

			echo '<div class="trman-admin-module-navbar">';
			echo '<h2>' . esc_html__( 'Synchronization settings', 'toret-manager' ) . '</h2>';
			echo '<div class="trman-admin-module-navbar-links">';
			foreach ( $links as $anchor => $title ) {
				echo '<a id="trman-anchor-' . esc_attr( $anchor ) . '" class="button trman-admin-module-anchor ' . esc_attr( key_exists( $anchor, $enabled_modules ) ? "active-module" : "" ) . ' link-' . esc_attr( $anchor ) . '" href="#m-' . esc_attr( $anchor ) . '">' . esc_html( $title ) . '</a>';
			}
			echo '</div>';
			echo '</div>';


			/**
			 * Post types
			 */
			foreach ( $enabled_post_types as $enabled_post_type ) {
				$type_data = get_post_type_object( $enabled_post_type );
				trman_module_template( $this->toret_manager, $enabled_post_type, $Toret_Manager_Draw_Functions, $type_data->label, TORET_MANAGER_POST_DATA, TORET_MANAGER_POST_DATA_MANDATORY, 'Post' );
			}

			/**
			 * Woo specific
			 */
			if ( Toret_Manager_Helper::is_woocommerce_active() ) {
				trman_module_template( $this->toret_manager, 'order', $Toret_Manager_Draw_Functions, __( 'Orders', 'toret-manager' ), TORET_MANAGER_ORDER_DATA, TORET_MANAGER_ORDER_DATA_MANDATORY, 'Order' );
				trman_module_template( $this->toret_manager, 'product', $Toret_Manager_Draw_Functions, __( 'Products', 'toret-manager' ), TORET_MANAGER_PRODUCT_DATA, TORET_MANAGER_PRODUCT_DATA_MANDATORY, 'Product' );
			}

			/**
			 * Users
			 */
			trman_module_template( $this->toret_manager, 'user', $Toret_Manager_Draw_Functions, __( 'Users', 'toret-manager' ), TORET_MANAGER_USER_DATA, TORET_MANAGER_USER_DATA_MANDATORY, 'Customer' );


			/**
			 * Terms
			 */
			foreach ( $enabled_term_types as $enabled_term_type ) {
				$type_data = get_taxonomy( $enabled_term_type );

				if ( Toret_Manager_Helper_Modules::allow_woo_module( $enabled_term_type ) ) {
					trman_module_template( $this->toret_manager, $enabled_term_type, $Toret_Manager_Draw_Functions, $type_data->label, TORET_MANAGER_CATEGORY_DATA, TORET_MANAGER_CATEGORY_DATA_MANDATORY, 'Category' );
				}
			}

			/**
			 * Woo specific terms
			 */
			/*if (Toret_Manager_Helper::is_woocommerce_active()) {
				trman_module_template($this->toret_manager, 'product_attribute', $Toret_Manager_Draw_Functions, __('Product attributes','toret-manager'), TORET_MANAGER_CATEGORY_DATA, TORET_MANAGER_CATEGORY_DATA_MANDATORY, 'Category');
			}*/

			/**
			 * Review types
			 */
			trman_module_template( $this->toret_manager, 'comment', $Toret_Manager_Draw_Functions, __( 'Comments', 'toret-manager' ), TORET_MANAGER_REVIEW_DATA, TORET_MANAGER_REVIEW_DATA_MANDATORY, 'Comment' );
			trman_module_template( $this->toret_manager, 'review', $Toret_Manager_Draw_Functions, __( 'Reviews', 'toret-manager' ), TORET_MANAGER_REVIEW_DATA, TORET_MANAGER_REVIEW_DATA_MANDATORY, 'Comment' );

			/**
			 * Woo product stock
			 */
			include_once( 'partials/specific/tools.php' );

			?>
            <div class="clear"></div>
            <button id="trman-to-top-button"
                    title="<?php esc_attr_e( 'To the Top', 'toret-manager' ); ?>"><?php esc_attr_e( 'To the Top', 'toret-manager' ); ?></button>

			<?php
		}
		?>
    </div>
<?php