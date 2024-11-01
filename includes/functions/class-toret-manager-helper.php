<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Toret_Manager_Helper {

	/**
	 * Check if item is excluded from sync
	 *
	 * @param mixed $item_id
	 * @param string $module
	 * @param string $type
	 *
	 * @return bool
	 */
	public static function is_excluded( $item_id, string $module, string $type ): bool {
		$exluded = Toret_Manager_Helper_Db::get_object_meta( $item_id, TORET_MANAGER_EXCLUDED_ITEM, $type );
		$exluded = apply_filters( 'trman_is_item_excluded', $exluded, $item_id, $module, $type );

		return $exluded == 'yes';
	}

	/**
	 * Check if taxonomy is from WooCommerce
	 *
	 * @param string $taxonomy
	 *
	 * @return bool
	 */
	public static function is_wc_taxonomy( string $taxonomy ): bool {
		if ( ! Toret_Manager_Helper::is_woocommerce_active() ) {
			return false;
		}

		foreach ( wc_get_attribute_taxonomies() as $values ) {

			if ( 'pa_' . $values->attribute_name == $taxonomy ) {
				return true;
			}
		}

		if ( substr( $taxonomy, 0, 3 ) === "pa_" ) {
			return true;
		}

		return false;
	}

	/**
	 * Get local taxonomy from internalID
	 *
	 * @param string $internalID
	 * @param string $return
	 * @param string $direct_name
	 *
	 * @return false|mixed|null
	 */
	public static function get_local_taxonomy( string $internalID, string $return = 'all', string $direct_name = "" ) {
		$attribute_parser = get_option( 'toret_manager_product_attributes_parser', array() );
		$taxonomy_name    = Toret_Manager_Helper::search_parent_key_in_multidim_array( $attribute_parser, 'internalid', $internalID );

		if ( ! empty( $direct_name ) && empty( $taxonomy_name ) ) {
			$taxonomy_name = $direct_name;
		}

		$taxonomy = Toret_Manager_Helper::get_attribute_taxonomy_by_name( $taxonomy_name );

		if ( isset( $taxonomy->attribute_id ) ) {
			if ( $return == 'all' ) {
				return $taxonomy;
			} else {
				return $taxonomy->attribute_id;
			}
		}

		return null;
	}

	/**
	 * Generate internalID
	 *
	 * @param string $prefix
	 * @param int $length
	 *
	 * @return string
	 * @throws RandomException
	 */
	static function generate_internal_id( string $prefix, int $length = 15 ): string {
		$characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen( $characters );
		$randomString     = '';
		for ( $i = 0; $i < $length; $i ++ ) {
			$randomString .= $characters[ random_int( 0, $charactersLength - 1 ) ];
		}

		$generated_string = $prefix . '_' . md5( $randomString );
		do_action( 'trman_after_generate_internal_id', $generated_string );

		return $generated_string;
	}

	/**
	 * Get attribute taxonomy by name
	 *
	 * @param string $name
	 *
	 * @return false|mixed
	 */
	public static function get_attribute_taxonomy_by_name( string $name ) {
		if ( ! Toret_Manager_Helper::is_woocommerce_active() ) {
			return false;
		}

		$name = str_replace( 'pa_', '', $name );

		$taxonomies = wc_get_attribute_taxonomies();
		$result     = null;
		foreach ( $taxonomies as $object ) {
			if ( $object->attribute_name === $name ) {
				$result = $object;
				break;
			}
		}
		unset( $object );

		return $result ?? false;
	}

	/**
	 * Get term taxonomy
	 *
	 * @param mixed $id *
	 */
	static function get_term_taxonomy( $id ): ?string {
		$term = get_term( (int) $id );
		if ( ! empty( $term ) && ! is_wp_error( $term ) ) {
			return $term->taxonomy;
		}

		return null;
	}

	/**
	 * Download file
	 *
	 * @param string $url
	 * @param string|null $title
	 * @param string $module
	 *
	 * @return int|WP_Error|null
	 */
	static function download_file( string $url, string $title = null, string $module = 'product',$isDownloadable = true ) {
		$filename  = pathinfo( $url, PATHINFO_FILENAME );
		$extension = pathinfo( $url, PATHINFO_EXTENSION );
		$full_name = "$filename.$extension";

		$post = get_post( 0 );
		if ( $post && substr( $post->post_date, 0, 4 ) > 0 ) {
			$time = $post->post_date;
		} else {
			$time = current_time( 'mysql' );
		}


		$uploads = wp_upload_dir( $time );

		if ( $module == 'product' && $isDownloadable ) {
			$file_path = $uploads['path'] . '/woocommerce_uploads' . "/$full_name";
			$file_url  = $uploads['url'] . '/woocommerce_uploads' . "/$full_name";
		} else {
			$file_path = $uploads['path'] . "/$full_name";
			$file_url  = $uploads['url'] . "/$full_name";

		}
		if ( file_exists( $file_path ) && get_option( 'trman_module_' . $module . '_files_update' ) != 'ok' ) {
			return null;
		}

		$post_attachments = get_posts( array(
			'post_type'      => 'attachment',
			'posts_per_page' => - 1,
			'post_parent'    => 0

		) );
		foreach ( $post_attachments as $post_attachment ) {

			if ( wp_get_attachment_url( $post_attachment->ID ) == $file_url ) {
				wp_delete_attachment( $post_attachment->ID, true );
				wp_delete_file( $file_path );
			}
		}

		$parse = wp_parse_url( $url );
		$url   = $parse['scheme'] . '://' . $parse['host'] . '/wp-json/api/download?trmandownloadfile=' . $url;

		$remote_response = wp_remote_get( $url, [ 'timeout' => 15 ] );
		if ( is_wp_error( $remote_response ) ) {
			$log = array(
				'type'      => 3,
				'module'    => ucfirst( $module ),
				'submodule' => 'File download',
				'context'   => __( 'Failed to download file: ', 'toret-manager' ) . $url,
				'log'       => wp_json_encode( $remote_response ),
			);
			trman_log( TORET_MANAGER_SLUG, $log );

			return null;
		}
		$response = wp_remote_retrieve_body( $remote_response );

		if ( $response == '-1' ) {
			$log = array(
				'type'      => 3,
				'module'    => ucfirst( $module ),
				'submodule' => 'File download',
				'context'   => __( 'Failed to download file: ', 'toret-manager' ) . $url,
				'log'       => __( 'Not send by remote server', 'toret-manager' ),
			);
			trman_log( TORET_MANAGER_SLUG, $log );

			return null;
		}

		//file_put_contents($file_path, $response);

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}
		global $wp_filesystem;
		if ( ! WP_Filesystem() ) {
			return false;
		}

		$wp_filesystem->put_contents( $file_path, $response, FS_CHMOD_FILE );


		$filetype = wp_check_filetype( basename( $full_name ), null );

		$attachment = array_merge(
			array(
				'post_mime_type' => $filetype['type'],
				'guid'           => $file_url,
				'post_parent'    => 0,
				'post_title'     => $full_name,
				'post_content'   => '',
			),
			[]
		);

		unset( $attachment['ID'] );

		$attachment_id = wp_insert_attachment( $attachment, $file_path, 0, true );

		if ( ! is_wp_error( $attachment_id ) ) {
			wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $file_path ) );
			$log = array(
				'type'      => 1,
				'module'    => ucfirst( $module ),
				'submodule' => 'File download',
				'context'   => __( 'Attachment created for: ', 'toret-manager' ) . $url,
				'log'       => __( 'Attachment created: ', 'toret-manager' ) . $attachment_id,
			);
			trman_log( TORET_MANAGER_SLUG, $log );

			return $attachment_id;
		}

		$log = array(
			'type'      => 3,
			'module'    => ucfirst( $module ),
			'submodule' => 'File download',
			'context'   => __( 'Failed to create attachment for: ', 'toret-manager' ) . $url,
			'log'       => wp_json_encode( $attachment_id ),
		);
		trman_log( TORET_MANAGER_SLUG, $log );

		return null;
	}

	/**
	 * Create product downloads
	 *
	 * @param mixed $product_id
	 * @param mixed $file_urls
	 *
	 * @return array
	 */
	static function create_downloads( $product_id, $file_urls ): array {
		$downloads = [];

		if ( empty( $file_urls ) ) {
			return $downloads;
		}
		foreach ( $file_urls as $filename => $file_url ) {

			if ( empty( $file_url ) ) {
				continue;
			}

			$attachment_id = Toret_Manager_Helper::download_file( $file_url, $filename );

			$file_url    = wp_get_attachment_url( $attachment_id );
			$download_id = md5( $file_url );

			$pd_object = new WC_Product_Download();

			$pd_object->set_id( $download_id );
			$pd_object->set_name( $filename );
			$pd_object->set_file( $file_url );

			$downloads[ $download_id ] = $pd_object;
		}

		return $downloads;
	}

	/**
	 * Check of WooCommerce is active
	 *
	 * @return bool
	 */
	static function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Modify edit arguments on item created/update
	 *
	 * @param array $args
	 * @param mixed $item_id
	 * @param string $nonce
	 *
	 * @return mixed|null
	 * @throws RandomException
	 */
	static function edit_args_modification( array $args, $item_id, $nonce = "" ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), 'trman_edit_args_' . $item_id ) ) {
			return null;
		}

		if ( isset( $_POST['m4c_duplicate_post'] ) ) {
			$args['internalID'] = Toret_Manager_Helper::generate_internal_id( 'product' );
			$args['update']     = false;
			$args['action']     = 'new';
		}

		return apply_filters( 'toret_manager_item_edit_args_modification', $args, $item_id );

	}

	/**
	 * Group array by key
	 *
	 * @param array $array
	 * @param string $key
	 *
	 * @return array
	 */
	static function group_array_by( array $array, string $key ): array {
		$return = array();
		foreach ( $array as $val ) {
			$return[ $val[ $key ] ][] = $val;
		}

		return $return;
	}

	/**
	 * Get site url
	 *
	 * @return string
	 */
	static function get_site_url(): string {
		global $wpdb;
		$siteurl = $wpdb->get_row( "SELECT * FROM $wpdb->options WHERE option_name = 'siteurl'" );

		$url = $siteurl->option_value;

		if ( is_multisite() ) {
			$url = network_site_url();
		}

		return apply_filters( 'trman_licence_url', $url, TORET_MANAGER_SLUG );
	}

	/**
	 * Search in multidimensional array
	 *
	 * @param mixed $array
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return array
	 */
	static function search_in_multidim_array( $array, string $key, $value ): array {
		$results = array();

		if ( is_array( $array ) ) {
			if ( isset( $array[ $key ] ) && $array[ $key ] == $value ) {
				return $array;
			}

			foreach ( $array as $subarray ) {
				$results = array_merge( $results, Toret_Manager_Helper::search_in_multidim_array( $subarray, $key, $value ) );
			}
		}

		return $results;
	}

	/**
	 * Search parent key in multidimensional array
	 *
	 * @param array $array
	 * @param string $key
	 * @param mixed $st
	 *
	 * @return int|string|null
	 */
	static function search_parent_key_in_multidim_array( array $array, string $key, $st ) {

		foreach ( $array as $k => $v ) {
			if ( $v[ $key ] === $st ) {
				return $k;
			}
		}

		return null;
	}


	/**
	 * Sanitize  array
	 *
	 * @param $array_or_string
	 *
	 * @return mixed|string
	 */
	static function sanitize_text_or_array_field( $array_or_string ) {
		if ( is_string( $array_or_string ) ) {
			$array_or_string = sanitize_text_field( $array_or_string );
		} elseif ( is_array( $array_or_string ) ) {
			foreach ( $array_or_string as $key => &$value ) {
				if ( is_array( $value ) ) {
					$value = self::sanitize_text_or_array_field( $value );
				} else {
					$value = sanitize_text_field( $value );
				}
			}
		}

		return $array_or_string;
	}

	/**
	 * Get weight multiplier
	 *
	 * @return float|int
	 */
	function get_weight_multiplier() {
		$multiplier = 1;

		$unit = get_option( 'woocommerce_weight_unit' );

		if ( $unit == 'g' ) {
			$multiplier = 1000;
		} elseif ( $unit == 'lbs' ) {
			$multiplier = 2.205;
		} elseif ( $unit == 'oz' ) {
			$multiplier = 35.2739619;
		}

		return $multiplier;
	}


	static function trman_verify_nonce( $nonce, $action ): bool {
		if ( isset( $_POST[ $nonce ] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST[ $nonce ] ) );
			if ( wp_verify_nonce( $nonce, $action ) ) {
				return true;
			}
		}

		return wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), $action );
	}

}