<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Toret_Manager_Module_Term extends Toret_Manager_Module_General {

	/**
	 * Internal ID key
	 *
	 * @var Toret_Manager_Module_Term|null
	 */
	protected static ?Toret_Manager_Module_Term $Toret_Manager_Module_General_Term = null;

	/**
	 * Toret Manager slug
	 *
	 * @param string $toret_manager
	 */
	public function __construct( string $toret_manager ) {
		parent::__construct( $toret_manager );

		$this->toret_manager = $toret_manager;

		add_action( 'pre_delete_term', array( $this, 'on_term_delete' ), 10, 2 );
		add_action( 'async_on_delete_term', array( $this, 'async_on_delete_term' ), 99, 2 );


		add_action( 'edited_term', array( $this, 'on_save_term' ), 99, 3 );
		add_action( 'create_term', array( $this, 'on_save_term' ), 99, 3 );
		add_action( 'async_on_save_term', array( $this, 'async_on_save_term' ), 99, 4 );
	}

	/**
	 * Get class instance
	 *
	 * @param string $toret_manager
	 *
	 * @return Toret_Manager_Module_Term|null
	 */
	public static function get_instance( string $toret_manager ): ?Toret_Manager_Module_Term {
		if ( null == self::$Toret_Manager_Module_General_Term ) {
			self::$Toret_Manager_Module_General_Term = new self( $toret_manager );
		}

		return self::$Toret_Manager_Module_General_Term;
	}

	/**
	 * On local term delete
	 *
	 * @param mixed $item_id
	 * @param string $taxonomy
	 */
	public function on_term_delete( $item_id, string $taxonomy ) {
		$term           = get_term( $item_id );
		$checked_module = ( Toret_Manager_Helper::is_wc_taxonomy( $term->taxonomy ) ? 'product_attribute' : $term->taxonomy );

		if ( $checked_module != 'product_attribute' ) {
			if ( ! Toret_Manager_Helper_Modules::is_sync_enabled( $checked_module, 'delete' ) ) {
				return;
			}
		}

		if ( Toret_Manager_Helper::is_excluded( $item_id, $checked_module, 'term' ) ) {
			return;
		}

		if ( in_array( 'term', TORET_MANAGER_ASYNC_UPLOAD_TYPES ) ) {
			wp_schedule_single_event( time(), 'async_on_delete_term', array( $item_id, $checked_module ) );
		} else {
			$this->async_on_delete_term( $item_id, $checked_module );
		}
	}

	/**
	 * Async on term delete
	 *
	 * @param mixed $term_id
	 * @param string $module
	 */
	function async_on_delete_term( $term_id, string $module ) {
		Toret_Manager_Module_General::process_delete_post( $term_id, $module, 'term' );
	}

	/**
	 * On local term save
	 *
	 * @param mixed $item_id
	 * @param mixed $tt_id
	 * @param string $taxonomy
	 */
	public function on_save_term( $item_id, $tt_id, string $taxonomy ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( defined( 'REST_REQUEST' ) ) {
			return;
		}

		if ( defined( 'DOING_CRON' ) ) {
			return;
		}

		if(isset($_POST['trman_run']) && $_POST['trman_run'] == 'queue'){
			return;
		}

		/*if ( wp_is_post_revision( $item_id ) || wp_is_post_autosave( $item_id ) ) {
			return;
		}*/

		$term           = get_term( $item_id );
		$checked_module = ( Toret_Manager_Helper::is_wc_taxonomy( $term->taxonomy ) ? 'product_attribute' : $term->taxonomy );

		if ( $checked_module != 'product_attribute' ) {
			if ( ! Toret_Manager_Helper_Modules::is_any_edit_sync_enabled( $checked_module ) ) {
				return;
			}
		}

		if ( Toret_Manager_Helper::is_excluded( $item_id, $checked_module, 'term' ) ) {
			return;
		}

		if (
			( isset( $_POST['trman_term_metabox_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['trman_term_metabox_nonce'] ) ), 'trman_term_metabox' ) )
			||
			isset( $_POST['action'] ) && $_POST['action'] == 'inline-save-tax'
		) {
			if ( in_array( 'term', TORET_MANAGER_ASYNC_UPLOAD_TYPES ) ) {
				wp_schedule_single_event( time(), 'async_on_save_term', array(
					$item_id,
					$term,
					$taxonomy,
					$checked_module
				) );
			} else {
				$this->async_on_save_term( $item_id, $term, $taxonomy, $checked_module );
			}
		}

	}

	/**
	 * Async on save term
	 *
	 * @param mixed $item_id
	 * @param WP_Term $term
	 * @param string $taxonomy
	 * @param string $checked_module
	 * @param bool $force
	 * @param array $edit_args
	 *
	 * @return mixed
	 */
	function async_on_save_term( $item_id, WP_Term $term, string $taxonomy, string $checked_module, bool $force = false, array $edit_args = array() ) {
		$internalID = Toret_Manager_Helper_Db::get_object_meta( $item_id, $this->internalID_key, 'term' );
		$update     = ! empty( $internalID );
		if ( empty( $internalID ) ) {
			$internalID = Toret_Manager_Helper::generate_internal_id( $taxonomy );
		}

		$edit_args['internalID'] = $internalID;
		$edit_args['update']     = $update;
		$edit_args['action']     = $update ? 'update' : 'new';
		$edit_args['module']     = $checked_module;
		$edit_args['type']       = 'term';

		$nonce = wp_create_nonce( 'trman_edit_args_' . $item_id );

		$edit_args = Toret_Manager_Helper::edit_args_modification( $edit_args, $item_id, $nonce );

		$data = self::termDataArray( $term, $edit_args );

		return Toret_Manager_Module_General::process_save_item_adv( $item_id, $data, $force, $edit_args );
	}

	/**
	 * Get term data for upload
	 *
	 * @param WP_Term $object
	 * @param array $edit_args
	 *
	 * @return mixed|null
	 */
	public
	function termDataArray(
		WP_Term $object, array $edit_args = array()
	) {
		$data = $this->transform_term_to_api_term( $object, $edit_args );

		return apply_filters( 'toret_manager_sent_term_data', $data, $object, $edit_args['update'] );
	}

	/**
	 * Gather term data for upload
	 *
	 * @param WP_Term $term_data
	 * @param array $edit_args
	 *
	 * @return array
	 */
	function transform_term_to_api_term( WP_Term $term_data, array $edit_args = [] ): array {
		$parentInternalID = get_term_meta( $term_data->parent, TORET_MANAGER_ITEM_INTERNALID, true );

		$thumbnail = wp_get_attachment_url( get_term_meta( $term_data->term_id, 'thumbnail_id', true ) );

		$meta = get_term_meta( $term_data->term_id );
		$meta = $this->reaarange_meta_array( $meta, $term_data->taxonomy );

		$data = [
			'categoryID'       => (int) $term_data->term_id,
			'parentID'         => $term_data->parent,
			'parentInternalID' => (string) ( $parentInternalID == '' ? - 1 : $parentInternalID ),
			'title'            => $term_data->name,
			'type'             => $term_data->taxonomy,
			'slug'             => $term_data->slug,
			'description'      => $term_data->description,
			'editUrl'          => Toret_Manager_Module_General::custom_get_edit_term_link( $term_data->term_id, $term_data->taxonomy ),
			'thumbnail'        => ( $thumbnail ?: "" ),
			'meta'             => wp_json_encode( $meta ),
		];

		if ( Toret_Manager_Helper::is_wc_taxonomy( $term_data->taxonomy ) ) {
			$taxonomyInternalID = ( Toret_Manager_Module_Product_Attribute::get_instance( $this->toret_manager )->check_if_taxonomy_in_parser( $term_data->taxonomy ) );
			$taxonomyInternalID = Toret_Manager_Module_Product_Attribute::get_instance( $this->toret_manager )->upload_product_attribute( $term_data->taxonomy, $taxonomyInternalID, null, array( 'internalID' => $taxonomyInternalID ) );

			$data['taxonomyInternalID'] = $taxonomyInternalID;
		}

		$attribute_parser = get_option( 'toret_manager_product_attributes_parser', array() );

		return $data;
	}

	/**
	 * Upload missing term if not exists
	 *
	 * @param WP_Term $term
	 * @param bool $force
	 *
	 * @return mixed
	 */
	function upload_missing_term( WP_Term $term, bool $force = false ) {
		return self::async_on_save_term( $term->term_id, $term, $term->taxonomy, $term->taxonomy, $force );
	}

	/**
	 * Maybe upload term
	 *
	 * @param WP_Term $term
	 * @param array $edit_args
	 *
	 * @return mixed|null
	 */
	function upload_product_attribute_term( WP_Term $term, array $edit_args = array() ) {
		return self::async_on_save_term( $term->term_id, $term, $term->taxonomy, $term->taxonomy, true, $edit_args );
	}

	/**
	 * Process delete notification
	 *
	 * @param string $internalID
	 *
	 * @return void|null
	 */
	public
	function notify_term_delete(
		string $internalID
	) {
		$existing_terms = Toret_Manager_Helper_Db::get_term_by_meta_value( TORET_MANAGER_ITEM_INTERNALID, $internalID, null );

		if ( ! is_wp_error( $existing_terms ) && $existing_terms ) {
			$existing_term_id       = $existing_terms[0]->term_id;
			$existing_term_taxonomy = $existing_terms[0]->taxonomy;

			$checked_taxonomy = ( Toret_Manager_Helper::is_wc_taxonomy( $existing_term_taxonomy ) ? 'product_attribute' : $existing_term_taxonomy );

			if ( Toret_Manager_Helper_Modules::is_sync_enabled( $checked_taxonomy, 'delete', 'download' ) || $checked_taxonomy == 'product_attribute' ) {

				if ( Toret_Manager_Helper::is_excluded( $existing_term_id, $checked_taxonomy, 'term' ) ) {
					return null;
				}

				$deleted = wp_delete_term( $existing_term_id, $existing_term_taxonomy );

				if ( $deleted && ! is_wp_error( $deleted ) ) {
					$log = array(
						'type'      => 1,
						'module'    => ucfirst( $checked_taxonomy ),
						'submodule' => 'Delete',
						'context'   => __( 'Notification - local item deleted', 'toret-manager' ),
						'log'       => wp_json_encode( array(
							'Local ID'        => $existing_term_id,
							'API internal ID' => $internalID
						) ),
					);
				} else {
					$log = array(
						'type'      => 3,
						'module'    => ucfirst( $checked_taxonomy ),
						'submodule' => 'Delete',
						'context'   => __( 'Notification - failed to delete local item', 'toret-manager' ),
						'log'       => wp_json_encode( array(
							'Local ID'        => $existing_term_id,
							'API internal ID' => $internalID,
							'Error'           => wp_json_encode( $deleted )
						) ),
					);
				}

				trman_log( $this->toret_manager, $log );
			}

		} else {
			$product_attribute_module = new Toret_Manager_Module_Product_Attribute( $this->toret_manager );
			$product_attribute_module->notify_product_attribute_delete( $internalID );
		}
	}

	/**
	 * Process term change notification
	 *
	 * @param string $internalID
	 * @param bool $force
	 * @param string $taxonomy
	 * @param bool $markSynced
	 *
	 * @return int|mixed|null
	 */
	public
	function notify_term_change(
		string $internalID, bool $force = false, string $taxonomy = "", bool $markSynced = false
	) {
		$termData = Toret_Manager_Module_General::get_item_from_cloud( $internalID, 'category' );

		if ( empty( $termData ) ) {
			return null;
		}

		$type = $termData->type;

		if ( ! empty( $taxonomy ) && $taxonomy != $type ) {
			return null;
		}

		$checked_taxonomy = ( Toret_Manager_Helper::is_wc_taxonomy( $type ) ? 'product_attribute' : $type );
		$is_taxonomy      = $type == 'product_attribute';
		$is_attribute     = ( Toret_Manager_Helper::is_wc_taxonomy( $type ) && ! $is_taxonomy );

		if ( Toret_Manager_Helper_Modules::is_module_enabled( $checked_taxonomy ) || $force || $checked_taxonomy == 'product_attribute' ) {

			if ( $termData->parentInternalID != - 1 && Toret_Manager_Helper_Modules::should_sync_parent( $checked_taxonomy ) ) {

				if ( $is_taxonomy ) {
					Toret_Manager_Module_Product_Attribute::get_instance( $this->toret_manager )->download_product_attribute( $termData->parentInternalID );
				} else {
					$this->maybe_create_term( $type, $termData->parentInternalID, $markSynced );
				}

			}

			if ( $is_taxonomy ) {

				Toret_Manager_Module_Product_Attribute::get_instance( $this->toret_manager )->download_product_attribute( $termData->internalID, $termData );

			} else {


				if ( property_exists( $termData, 'taxonomyInternalID' ) && ! empty( $termData->taxonomyInternalID ) ) {
					Toret_Manager_Module_Product_Attribute::get_instance( $this->toret_manager )->download_product_attribute( $termData->taxonomyInternalID );
				}

				$existing_terms = Toret_Manager_Helper_Db::get_term_by_meta_value( TORET_MANAGER_ITEM_INTERNALID, $internalID, array( $type ) );

				if ( is_wp_error( $existing_terms ) || empty( $existing_terms ) ) {

					if ( Toret_Manager_Helper_Modules::is_sync_enabled( $checked_taxonomy, 'new', 'download' ) || $force ) {
						return $this->create_term( $termData, $checked_taxonomy, $force, $markSynced );
					}

				} else {

					$existing_term_id = $existing_terms[0]->term_id;

					if ( Toret_Manager_Helper::is_excluded( $existing_term_id, $checked_taxonomy, 'term' ) ) {
						return null;
					}

					if ( Toret_Manager_Helper_Modules::is_sync_enabled( $checked_taxonomy, 'update', 'download' ) || $force ) {
						return $this->update_term( $termData, $checked_taxonomy, $existing_term_id, $force, $markSynced );
					}

				}
			}
		}

		return null;
	}

	/**
	 * Update term is needed
	 *
	 * @param string $taxonomy
	 * @param string $internal_id
	 * @param bool $markSynced
	 *
	 * @return array|int
	 */
	function maybe_create_term( string $taxonomy, string $internal_id, bool $markSynced = false ) {
		$terms = Toret_Manager_Helper_Db::get_term_by_meta_value( TORET_MANAGER_ITEM_INTERNALID, $internal_id, array( $taxonomy ) );
		if ( ! empty( $terms ) ) {
			return $terms[0]->term_id;
		} else {
			return $this->get_missing_post_terms_from_cloud( array( $internal_id ), $markSynced );
		}
	}

	/**
	 * Create term
	 *
	 * @param mixed $termData
	 * @param string $type
	 * @param bool $force
	 * @param bool $markSynced
	 *
	 * @return int|mixed|null
	 */
	private
	function create_term(
		$termData, string $type, bool $force, bool $markSynced = false
	) {
		if ( ! empty( $termData ) ) {
			$checked_taxonomy        = ( Toret_Manager_Helper::is_wc_taxonomy( $type ) ? 'product_attribute' : $type );
			$data_to_be_synchronized = Toret_Manager_Helper_Modules::data_to_be_sync( $checked_taxonomy, 'term', 'new', Toret_Manager_Helper_Modules::get_mandatory_items( 'term' ) );

			return $this->save_item( $termData, $data_to_be_synchronized, null, false, $type, $force, $markSynced );
		}

		return null;
	}

	/**
	 * Update term
	 *
	 * @param mixed $termData
	 * @param string $type
	 * @param mixed $existing_terms_id
	 * @param bool $force
	 * @param bool $markSynced
	 *
	 * @return int|mixed|null
	 */
	function update_term( $termData, string $type, $existing_terms_id, bool $force, bool $markSynced = false ) {
		if ( ! empty( $termData ) ) {
			$checked_taxonomy        = ( Toret_Manager_Helper::is_wc_taxonomy( $type ) ? 'product_attribute' : $type );
			$data_to_be_synchronized = Toret_Manager_Helper_Modules::data_to_be_sync( $checked_taxonomy, 'term', 'update', Toret_Manager_Helper_Modules::get_mandatory_items( 'term' ) );

			return $this->save_item( $termData, $data_to_be_synchronized, $existing_terms_id, true, $type, $force, $markSynced );
		}

		return null;
	}

	/**
	 * Save notified term
	 *
	 * @param mixed $termData
	 * @param array $data_to_be_synchronized
	 * @param mixed $existing_term_id
	 * @param bool $update
	 * @param string $type
	 * @param bool $force
	 * @param bool $markSynced
	 *
	 * @return int|mixed|null
	 *
	 */
	private
	function save_item(
		$termData, array $data_to_be_synchronized, $existing_term_id, bool $update, string $type, bool $force = false, bool $markSynced = false
	) {
		if ( $update && ! empty( $existing_term_id ) ) {

			$existing_term = get_term( $existing_term_id, $type );

			if ( empty( $existing_term ) ) {
				$update = false;
			}

		} else {

			$update = false;

		}

		$title     = '';
		$taxonomy  = '';
		$term_data = [];

		foreach ( $termData as $property => $item ) {
			if ( in_array( $property, $data_to_be_synchronized ) || $force ) {

				$filter = apply_filters( 'toret_manager_term_notified_should_process_item', false, $item, $property, $termData );
				if ( ! empty( $filter ) ) {
					do_action( 'toret_manager_term_notified_process_item', $item, $property, $termData );
					continue;
				}

				if ( $property == 'description' ) {
					$term_data['description'] = $termData->description;
				}
				if ( $property == 'parentID' ) {
					if ( $termData->parentInternalID != - 1 ) {
						$parentTerm = Toret_Manager_Helper_Db::get_term_by_meta_value( TORET_MANAGER_ITEM_INTERNALID, $termData->parentInternalID, $termData->type );
						if ( ! empty( $parentTerm ) && ! is_wp_error( $parentTerm ) ) {
							$parentID            = $parentTerm[0]->term_id;
							$term_data['parent'] = $parentID;
						}

					}
				}
				if ( $property == 'title' ) {
					$title             = $termData->title;
					$term_data['name'] = $title;
				}
				if ( $property == 'type' ) {
					$taxonomy = $termData->type;
				}
				if ( $property == 'slug' ) {
					$term_data['slug'] = $termData->slug;
				}
			}
		}

		$error = '';

		$return_id = null;

		if ( ! empty( $taxonomy ) && ! empty( $term_data ) ) {

			if ( $update ) {

				$updated = wp_update_term( $existing_term_id, $taxonomy, $term_data );
				if ( is_wp_error( $updated ) ) {
					$error = $updated;
				}
				$return_id = $updated['term_id'];

			} else {

				$created_term = wp_insert_term( $title, $taxonomy, $term_data );

				if ( is_wp_error( $created_term ) ) {
					if ( $created_term->get_error_code() == 'term_exists' ) {
						$term = get_term_by( 'slug', $termData->slug, $taxonomy );
						wp_delete_term( $term->term_id, $taxonomy );
						wp_update_term( $term->term_id, $taxonomy, $term_data );
						update_term_meta( $term->term_id, TORET_MANAGER_ITEM_INTERNALID, $termData->internalID );
						$return_id = $term->term_id;
					}
					$error = $created_term;
				} else {

					update_term_meta( $created_term['term_id'], TORET_MANAGER_ITEM_INTERNALID, $termData->internalID );

					$return_id = $created_term['term_id'];
				}
			}

			if ( in_array( 'thumbnail', $data_to_be_synchronized ) ) {
				$uri = $termData->thumbnail;
				if ( $uri != '' && ! empty( $return_id ) ) {
					$id = Toret_Manager_Helper::download_file( $uri, null, $taxonomy );
					if ( $id ) {
						update_term_meta( $return_id, 'thumbnail_id', $id );
					}
				}
			}

			if ( isset( $return_id ) && $markSynced ) {
				update_term_meta( $return_id, TORET_MANAGER_ASSOCIATIVE_SYNC, '1' );
			}

			if ( $return_id ) {
				$log = array(
					'type'      => 1,
					'module'    => ucfirst( $type ),
					'submodule' => 'Notification - ' . ( $update ? 'Update' : 'Created' ),
					'context'   => ( $update ? __( 'Item updated', 'toret-manager' ) : __( 'Item created', 'toret-manager' ) ),
					'log'       => wp_json_encode( array(
						'Local ID'        => $return_id,
						'API internal ID' => $termData->internalID
					) ),
				);

				if ( in_array( 'meta', $data_to_be_synchronized ) || $force ) {
					$meta = json_decode( $termData->meta, true );
					if ( ! empty( $meta ) ) {
						foreach ( $meta as $meta_key => $meta_value ) {
							update_term_meta( $return_id, $meta_key, $meta_value );
						}
					}
				}

			} else {
				$log = array(
					'type'      => 3,
					'module'    => ucfirst( $type ),
					'submodule' => 'Notification - ' . ( $update ? 'Update' : 'Created' ),
					'context'   => ( $update ? __( 'Failed to update item', 'toret-manager' ) : __( 'Failed to create item', 'toret-manager' ) ),
					'log'       => wp_json_encode( array(
						'Local ID'        => '-',
						'API internal ID' => $termData->internalID,
						'Error'           => wp_json_encode( $error )
					) ),
				);
			}
			trman_log( $this->toret_manager, $log );


			return $return_id ?? null;
		}

		$log = array(
			'type'      => 3,
			'module'    => ucfirst( $type ),
			'submodule' => 'Notification - ' . ( $update ? 'Update' : 'Created' ),
			'context'   => ( $update ? __( 'Failed to update item', 'toret-manager' ) : __( 'Failed to create item', 'toret-manager' ) ),
			'log'       => wp_json_encode( array(
				'Local ID'        => '-',
				'API internal ID' => $termData->internalID,
				'Error'           => 'Empty data or taxonomy'
			) ),
		);
		trman_log( $this->toret_manager, $log );

		return null;
	}

	/**
	 * Download missing terms from cloud
	 *
	 * @param array $not_found_internal_ids
	 * @param string $taxonomy
	 * @param bool $markSynced
	 *
	 * @return array
	 */
	function get_missing_post_terms_from_cloud( array $not_found_internal_ids, string $taxonomy = "", bool $markSynced = false ): array {
		$inserted_term_ids = [];

		foreach ( $not_found_internal_ids as $not_found_internal_id ) {
			$inserted_term_ids[] = $this->notify_term_change( $not_found_internal_id, true, $taxonomy, $markSynced );
		}

		return $inserted_term_ids;
	}

	/**
	 * Get associated local term ids
	 *
	 * @param array $internalIDs
	 * @param string $taxonomy
	 * @param string $associated
	 * @param bool $parent
	 * @param bool $markSynced
	 *
	 * @return array
	 */
	function get_associted_local_term_ids( array $internalIDs, string $taxonomy, string $associated, bool $parent = false, bool $markSynced = false ): array {
		if ( empty( $internalIDs ) ) {
			return [];
		}

		$local_IDs = [];

		foreach ( $internalIDs as $internalID ) {

			$existing_terms = Toret_Manager_Helper_Db::get_term_by_meta_value( TORET_MANAGER_ITEM_INTERNALID, $internalID, $taxonomy );

			$local_ID = null;

			if ( ! is_wp_error( $existing_terms ) && $existing_terms ) {
				$local_ID = $existing_terms[0]->term_id;
			}

			$continue = false;

			if ( $parent && Toret_Manager_Helper_Modules::should_sync_parent( $associated ) ) {
				$continue = true;
			} elseif ( ! $parent && Toret_Manager_Helper_Modules::should_sync_associated( $associated ) ) {
				$continue = true;
			}

			if ( empty( $local_ID ) && $continue ) {
				$local_ID = self::notify_term_change( $internalID, true, "", $markSynced );
			}

			if ( ! empty( $local_ID ) ) {
				$local_IDs[] = $local_ID;
			}

		}

		return $local_IDs;
	}

	/**
	 * Get term internal ids from local ids
	 *
	 * @param string $term_slug
	 * @param array $local_ids
	 * @param string $module
	 *
	 * @return array
	 */
	function get_term_internal_ids( string $term_slug, array $local_ids, string $module ): array {
		$term_internal_ids = [];
		foreach ( $local_ids as $local_id ) {
			$term       = get_term( $local_id );
			$internalid = get_term_meta( $term->term_id, TORET_MANAGER_ITEM_INTERNALID, true );
			if ( ! empty( $internalid ) ) {
				$term_internal_ids[] = $internalid;
			} else {
				if ( Toret_Manager_Helper_Modules::should_sync_associated( $module ) ) {
					$term_internal_ids[] = Toret_Manager_Module_Term::get_instance( $this->toret_manager )->upload_missing_term( $term, true );
				}
			}
		}

		return $term_internal_ids;
	}


}
