<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * The public-facing functionality of the plugin.
 */
class Toret_Manager_Public {

	/**
	 * The ID of this plugin.
     *
     * @string $toret_manager
	 */
	private string $toret_manager;

	/**
	 * The version of this plugin.
     *
     * @string $version
	 */
	private string $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $toret_manager
     * @param $version
     */
	public function __construct(string $toret_manager, $version ) {
		$this->toret_manager = $toret_manager;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 */
	public function enqueue_styles() {
		//wp_enqueue_style( $this->toret_manager, plugin_dir_url( __FILE__ ) . 'css/'.$this->toret_manager.'-public.css', array(), $this->version);
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->toret_manager, plugin_dir_url( __FILE__ ) . 'js/'.$this->toret_manager.'-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script($this->toret_manager . '-public', TORET_MANAGER_SHORTCUT_SLUG.'_public_localize', array(
			'ajaxurl' => admin_url() . 'admin-ajax.php',
			'homeurl' => get_bloginfo('url'),
		));
	}

}
