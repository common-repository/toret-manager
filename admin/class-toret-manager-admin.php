<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * The admin-specific functionality of the plugin.
 */
class Toret_Manager_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @var string
	 */
	private string $toret_manager;

	/**
	 * The version of this plugin.
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $toret_manager The name of the plugin.
	 * @param string $version The version of this plugin.
	 */
	public function __construct( string $toret_manager, string $version ) {
		$this->toret_manager = $toret_manager;
		$this->version       = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->toret_manager, plugin_dir_url( __FILE__ ) . 'css/' . $this->toret_manager . '-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->toret_manager . '-admin', plugin_dir_url( __FILE__ ) . 'js/' . $this->toret_manager . '-admin.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->toret_manager . '-admin', TORET_MANAGER_SHORTCUT_SLUG . '_admin_localize', array(
			'ajaxurl' => admin_url() . 'admin-ajax.php',
			'homeurl' => get_bloginfo( 'url' ),
			'nonce'   => wp_create_nonce( 'ajax-nonce' )
		) );
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 */
	public function add_plugin_admin_menu(): void {
		if ( ! defined( 'TORETMENU' ) ) {
			add_menu_page(
				__( 'Toret plugins', 'toret-manager' ),
				__( 'Toret plugins', 'toret-manager' ),
				'manage_options',
				'toret-plugins',
				array( $this, 'display_toret_plugins_admin_page' ),
				''
			);

			define( 'TORETMENU', true );
		}

		add_submenu_page(
			'toret-plugins',
			__( 'Toret Manager', 'toret-manager' ),
			__( 'Toret Manager', 'toret-manager' ),
			'manage_options',
			$this->toret_manager,
			array( $this, 'display_admin_page' )
		);

		add_submenu_page(
			'toret-plugins',
			__( 'Toret Manager - log', 'toret-manager' ),
			__( 'Toret Manager - log', 'toret-manager' ),
			'manage_options',
			TORET_MANAGER_LOG_SLUG,
			array( $this, 'display_admin_log_page' )
		);

		if(TORET_MANAGER_TOOLS_ENABLED) {
			add_submenu_page(
				'toret-plugins',
				__( 'Toret Manager - tools', 'toret-manager' ),
				__( 'Toret Manager - tools', 'toret-manager' ),
				'manage_options',
				TORET_MANAGER_TOOLS_SLUG,
				array( $this, 'display_admin_tools_page' )
			);
		}
	}

	/**
	 * Show admin Toret plugins menu page
	 */
	function display_toret_plugins_admin_page() {
		include_once( 'views/partials/toret.php' );
	}

	/**
	 * Show admin menu page
	 */
	function display_admin_page() {
		require_once( 'views/partials/specific/module.php' );

		$Toret_Manager_Admin_Save = new Toret_Manager_Admin_Save( $this->toret_manager );
		$Toret_Manager_Admin_Save->save_setting();
		$Toret_Manager_Draw_Functions = new Toret_Manager_Draw_Functions();

		$enabled_post_types = Toret_Manager_Helper_Modules::get_available_types_by_module( 'post', false );
		$enabled_term_types = Toret_Manager_Helper_Modules::get_available_types_by_module( 'term', false );

		if ( Toret_Manager_Helper::is_woocommerce_active() ) {
			$wc_taxonomies      = wc_get_attribute_taxonomies();
			$wc_taxonomies      = array_column( $wc_taxonomies, 'attribute_name' );
			$wc_taxonomies      = preg_filter( '/^/', 'pa_', $wc_taxonomies );
			$enabled_term_types = array_diff( $enabled_term_types, $wc_taxonomies );
		}

		$enabled_term_types = apply_filters( 'trman_enabled_post_types', $enabled_term_types );

		/**
		 * Include admin template
		 */
		include_once( 'views/admin.php' );
	}

	/**
	 * Show admin menu page
	 */
	function display_admin_log_page() {
		include_once( 'views/partials/log.php' );
	}

	/**
	 * Show admin menu page
	 */
	function display_admin_tools_page() {
		include_once( 'views/partials/tools.php' );
	}

	/**
	 * Save module enabled state ajax callback
	 */
	function save_module_state() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );
		$module   = sanitize_text_field( $_POST['module'] );
		$state    = sanitize_text_field( $_POST['state'] );
		$endpoint = sanitize_text_field( $_POST['endpoint'] );
		Toret_Manager_Admin_Save::save_module( $module, $endpoint, $state );

		wp_die();
	}

	/**
	 * Save admin plugin option
	 */
	function trman_save_option() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );
		$option   = sanitize_text_field( $_POST['option'] );
		$checked  = sanitize_text_field( $_POST['checked'] );
		$value    = sanitize_text_field( $_POST['value'] );
		$type     = sanitize_text_field( $_POST['type'] );
		$scope    = sanitize_text_field( $_POST['scope'] );
		$disabled = sanitize_text_field( $_POST['disabled'] );

		$isDisabled = false;
		if ( $disabled == 'true' ) {
			$isDisabled = true;
			if ( $scope == 'all' ) {
				update_option( $option, '' );
				update_option( str_replace( '_all', '', $option ), '' );
			} elseif ( $scope == 'part' ) {
				update_option( $option, '' );
				update_option( $option . '_all', '' );
			}
		}

		if ( $type == 'checkbox' ) {
			if ( $checked == 'true' ) {
				update_option( $option, $value );
				if ( ! $isDisabled ) {
					if ( $scope == 'all' ) {
						update_option( str_replace( '_all', '', $option ), '' );
					} elseif ( $scope == 'part' ) {
						update_option( $option . '_all', '' );
					}
				}
			} else {
				update_option( $option, "" );
				if ( ! $isDisabled ) {
					if ( $scope == 'all' ) {
						update_option( str_replace( '_all', '', $option ), 'ok' );
					} elseif ( $scope == 'part' ) {
						update_option( $option . '_all', 'ok' );
					}
				}
			}
		} else {
			update_option( $option, $value );
		}
		wp_die();
	}

	/**
	 * Save admin plugin option - items
	 */
	function trman_save_option_items() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );

		$name   = sanitize_text_field( $_POST['name'] );
		$way    = sanitize_text_field( $_POST['way'] );
		$mode   = sanitize_text_field( $_POST['mode'] );
		$module = sanitize_text_field( $_POST['module'] );
		$values = $_POST['values'];

		update_option( $name, $values );
		wp_die();
	}

	/**
	 * Add plugins page action links
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	function action_links( array $settings ): array {
		$settings[] = '<a href="' . TORET_MANAGER_ADMIN_PAGE . '">' . __( 'Settings', 'toret-manager' ) . '</a>';

		return $settings;
	}

	/**
	 * Add plugins page row meta
	 *
	 * @param array $plugin_meta
	 * @param string $plugin_file
	 *
	 * @return array
	 */
	function plugin_row_meta( array $plugin_meta, string $plugin_file ): array {
		if ( strpos( $plugin_file, 'toret-manager.php' ) !== false ) {
			$new_links = array(
				'donate' => '<a href="https://www.toret.net/dokumentace/" target="_blank">' . __( 'Documentation', 'toret-manager' ) . '</a>',
				'doc'    => '<a href="https://www.toret.net/podpora/" target="_blank">' . __( 'Support', 'toret-manager' ) . '</a>'
			);

			$plugin_meta = array_merge( $plugin_meta, $new_links );
		}

		return $plugin_meta;
	}

}