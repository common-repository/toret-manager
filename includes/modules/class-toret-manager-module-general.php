<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Toret_Manager_Module_General {

	/**
	 * Class instance
	 *
	 * @var Toret_Manager_Module_General|null
	 */
	protected static ?Toret_Manager_Module_General $Toret_Manager_Module_General_Instance = null;

	/**
	 * Toret manager
	 *
	 * @var string
	 */
	public string $toret_manager;

	/**
	 * Module
	 *
	 * @var string
	 */
	protected string $module;

	/**
	 * Internal ID meta key
	 *
	 * @var string
	 */
	protected string $internalID_key;

	/**
	 * Class constructor
	 *
	 * @param string $toret_manager
	 */
	public function __construct( string $toret_manager ) {
		$this->toret_manager  = $toret_manager;
		$this->internalID_key = TORET_MANAGER_ITEM_INTERNALID;
	}

	/**
	 * Additional check for existing item
	 *
	 * @param $existing_item_id
	 * @param $internalID
	 * @param string $module
	 * @param string $type
	 *
	 * @return int
	 */
	public static function additional_check_for_existing_item( $existing_item_id, $internalID, string $module, string $type ) {
		do_action( 'trman_before_additional_check_for_existing_item', $existing_item_id, $internalID, $module, $type );

		if ( $module == 'product' ) {
			if ( empty( $existing_item_id ) ) {
				if ( get_option( 'trman_module_product_pairing_sku' ) == 'ok' ) {
					$itemData = self::get_item_from_cloud( $internalID, $module );
					if ( ! empty( $itemData ) ) {
						$existing_item_id = wc_get_product_id_by_sku( $itemData->sku );
						if ( ! empty( $existing_item_id ) ) {
							update_post_meta( $existing_item_id, TORET_MANAGER_ITEM_INTERNALID, $internalID );
						}
					}
				}
			}
		}

		do_action( 'trman_after_additional_check_for_existing_item', $existing_item_id, $internalID, $module, $type );

		return $existing_item_id;
	}

	/**
	 * Get class instance
	 *
	 * @param string $toret_manager
	 *
	 * @return Toret_Manager_Module_General|null
	 */
	public static function get_instance( string $toret_manager ): ?Toret_Manager_Module_General {
		if ( null == self::$Toret_Manager_Module_General_Instance ) {
			self::$Toret_Manager_Module_General_Instance = new self( $toret_manager );
		}

		return self::$Toret_Manager_Module_General_Instance;
	}

	/**
	 * Process post delete action
	 *
	 * @param mixed $post_id
	 * @param string $module
	 * @param string $type
	 */
	static function process_delete_post( $post_id, string $module = 'product', string $type = 'post' ) {
		$internalID_key = TORET_MANAGER_ITEM_INTERNALID;
		$internalID     = Toret_Manager_Helper_Db::get_object_meta( $post_id, $internalID_key, $type );

		if ( ! empty( $internalID ) ) {

			$data['internalID'] = $internalID;
			$Toret_Manager_Api  = ToretManagerApi();
			$deleted            = $Toret_Manager_Api->deleteData->deleteItem( TORET_MANAGER_SLUG, $data, $module );

			if ( $deleted == 'none' ) {
				$log = array(
					'module'    => ucfirst( $module ),
					'submodule' => 'Delete',
					'context'   => __( 'Failed to delete item', 'toret-manager' ),
					'log'       => wp_json_encode( array( 'Local ID' => $post_id, 'API internal ID' => $internalID ) ),
				);

				do_action( 'trman_after_failed_delete_item', $post_id, $module, $internalID );
			} else {
				$log = array(
					'type'      => 1,
					'module'    => ucfirst( $module ),
					'submodule' => 'Delete',
					'context'   => __( 'Item deleted', 'toret-manager' ),
					'log'       => wp_json_encode( array( 'Local ID' => $post_id, 'API internal ID' => $internalID ) ),
				);

				do_action( 'trman_after_delete_item', $post_id, $module, $internalID );
			}
			trman_log( TORET_MANAGER_SLUG, $log );
		}
	}

	/**
	 * Prepare data for upload to API
	 *
	 * @param mixed $item_id
	 * @param array $item_data
	 * @param string $module
	 * @param string $type
	 * @param array $edit_args
	 *
	 * @return array
	 */
	static function get_item_data_for_api( $item_id, array $item_data, string $module, string $type, array $edit_args = [] ): array {
		$internalID = Toret_Manager_Helper_Db::get_object_meta( $item_id, TORET_MANAGER_ITEM_INTERNALID, $type );
		$update     = ! empty( $internalID );

		$data_to_be_synchronized = Toret_Manager_Helper_Modules::data_to_be_sync( $module, $type, $update ? 'update' : 'new', Toret_Manager_Helper_Modules::get_mandatory_items( $type ) );

		foreach ( array_keys( $item_data ) as $key ) {
			if ( ! in_array( $key, $data_to_be_synchronized ) ) {
				unset( $item_data[ $key ] );
			}
		}

		$item_data['internalID'] = $update ? $internalID : Toret_Manager_Helper::generate_internal_id( $module );

		return apply_filters( 'trman_data_before_upload', $item_data, $item_id, $module, $type, $edit_args );
	}

	/**
	 * Save item process
	 *
	 * @param mixed $item_id
	 * @param array $item_data
	 * @param bool $force
	 * @param array $edit_args
	 *
	 * @return mixed
	 */
	static function process_save_item_adv( $item_id, array $item_data, bool $force = false, array $edit_args = [] ) {
		$module     = $edit_args['module'];
		$type       = $edit_args['type'];
		$update     = $edit_args['update'];
		$action     = $edit_args['action'];
		$internalID = $edit_args['internalID'];

		$internalID_key = TORET_MANAGER_ITEM_INTERNALID;

		$item_data = self::get_item_data_for_api( $item_id, $item_data, $module, $type, $edit_args );

		if ( Toret_Manager_Helper_Modules::is_sync_enabled( $module, $action ) || $force ) {

			do_action( 'trman_before_upload_item', $item_id, $item_data, $module );

			$Toret_Manager_Api = ToretManagerApi();

			if ( $update ) {
				$api_response = $Toret_Manager_Api->updateData->updateItem( TORET_MANAGER_SLUG, $item_data, $module );
			} else {
				$api_response = $Toret_Manager_Api->createData->createItem( TORET_MANAGER_SLUG, $item_data, $module );
			}

			if ( $api_response != 'none' && $api_response != '404' && $api_response != '400' ) {

				$log = array(
					'type'      => 1,
					'module'    => ucfirst( $module ),
					'submodule' => $update ? 'Update' : 'Created',
					'context'   => ( $update ? __( 'Item updated', 'toret-manager' ) : __( 'Item created', 'toret-manager' ) ),
					'log'       => wp_json_encode( array(
						'Local ID'        => $item_id,
						'API internal ID' => $item_data['internalID']
					) ),
				);
				trman_log( TORET_MANAGER_SLUG, $log );

				if ( ! $update ) {

					do_action( 'trman_after_upload_create_item', $item_id, $item_data, $module, $item_data['internalID'] );

					$internalID = $item_data['internalID'];
					Toret_Manager_Helper_Db::update_object_meta( $item_id, $internalID_key, $item_data['internalID'], $type );

				} else {

					do_action( 'trman_after_upload_update_item', $item_id, $item_data, $module, $item_data['internalID'] );

				}

				return $internalID;

			} elseif ( $api_response != 'none' && $api_response == '400' && $update ) {

				$log = array(
					'type'      => 3,
					'module'    => ucfirst( $module ),
					'submodule' => 'Update',
					'context'   => __( 'Failed to update item. Trying to create it.', 'toret-manager' ),
					'log'       => wp_json_encode( array(
						'Local ID'        => $item_id,
						'API internal ID' => $item_data['internalID']
					) ),
				);
				trman_log( TORET_MANAGER_SLUG, $log );

				$api_response = $Toret_Manager_Api->createData->createItem( TORET_MANAGER_SLUG, $item_data, $module, $item_data['internalID'] );

				if ( $api_response != 'none' && $api_response != '404' ) {

					$log = array(
						'type'      => 1,
						'module'    => ucfirst( $module ),
						'submodule' => 'Created',
						'context'   => __( 'Item created', 'toret-manager' ),
						'log'       => wp_json_encode( array(
							'Local ID'        => $item_id,
							'API internal ID' => $item_data['internalID']
						) ),
					);
					trman_log( TORET_MANAGER_SLUG, $log );

					$internalID = $item_data['internalID'];

					Toret_Manager_Helper_Db::update_object_meta( $item_id, $internalID_key, $item_data['internalID'], $type );

					do_action( 'trman_after_upload_create_item', $item_id, $item_data, $module, $item_data['internalID'] );

					return $internalID;

				} else {
					$log = array(
						'type'      => 3,
						'module'    => ucfirst( $module ),
						'submodule' => 'Create',
						'context'   => __( 'Failed to create item after failed updated.', 'toret-manager' ),
						'log'       => wp_json_encode( array(
							'Local ID'        => $item_id,
							'API internal ID' => $item_data['internalID']
						) ),
					);

					do_action( 'trman_after_failed_upload_create_item', $item_id, $item_data, $module, $item_data['internalID'] );
				}

			} else {
				$log = array(
					'type'      => 3,
					'module'    => ucfirst( $module ),
					'submodule' => $update ? 'Update' : 'Create',
					'context'   => $update ? __( 'Failed to update item.', 'toret-manager' ) : __( 'Failed to create item.', 'toret-manager' ),
					'log'       => wp_json_encode( array(
						'Local ID'        => $item_id,
						'API internal ID' => $item_data['internalID']
					) ),
				);

				do_action( 'trman_after_failed_upload_item', $item_id, $item_data, $module );
			}
			trman_log( TORET_MANAGER_SLUG, $log );
		}

		return null;
	}

	/**
	 * Process item delete notification
	 *
	 * @param mixed $internalID
	 * @param string $type
	 * @param string $module
	 */
	public static function notify_item_delete( $internalID, string $type, string $module = 'post' ) {
		$internalID_key   = TORET_MANAGER_ITEM_INTERNALID;
		$existing_item_id = Toret_Manager_Helper_Db::get_post_by_meta_value( $internalID_key, $internalID, $type, $module );
		$existing_item_id = self::additional_check_for_existing_item( $existing_item_id, $internalID, $module, $type );
		if ( ! empty( $existing_item_id ) ) {
			$module = Toret_Manager_Helper_Modules::get_module_from_id_and_type( $existing_item_id, $type );
			if ( Toret_Manager_Helper::is_excluded( $existing_item_id, $module, $type ) ) {
				return;
			}

			if ( Toret_Manager_Helper_Modules::is_sync_enabled( $module, 'delete', 'download' ) ) {
				self::delete_item_by_type( $existing_item_id, $internalID, $type );
			}

		}
	}

	/**
	 * Delete item by type
	 *
	 * @param mixed $item_id
	 * @param mixed $internalID
	 * @param string $type
	 * @param bool $force
	 */
	public static function delete_item_by_type( $item_id, $internalID, string $type, bool $force = true ) {
		$deleted = false;

		if ( $type == 'post' ) {

			$deleted = wp_delete_post( $item_id, $force );

		} else if ( $type == 'order' ) {

			if ( Toret_Manager_HPOS_Compatibility::is_wc_hpos_enabled() ) {

				global $wpdb;
				$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->prefix . "wc_orders WHERE id = %d", $item_id ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->prefix . "wc_orders_meta WHERE order_id = %d", $item_id ) );

			} else {
				$deleted = wp_delete_post( $item_id, true );
			}

		} else if ( $type == 'product' ) {
			Toret_Manager_Helper_Db::delete_product( $item_id );
		} else if ( $type == 'user' ) {
			$deleted = wp_delete_user( $item_id );
		} else if ( $type == 'comment' ) {
			$deleted = wp_delete_comment( $item_id, $force );
		} else {
			$deleted = wp_delete_term( $item_id, Toret_Manager_Helper::get_term_taxonomy( $item_id ) );
		}

		if ( ! empty( $deleted ) && ! is_wp_error( $deleted ) ) {
			$log = array(
				'type'      => 1,
				'module'    => ucfirst( $type ),
				'submodule' => 'Delete',
				'context'   => __( 'Notification - local item deleted', 'toret-manager' ),
				'log'       => wp_json_encode( array( 'Local ID' => $item_id, 'API internal ID' => $internalID ) ),
			);

			do_action( 'trman_after_delete_notified_item', $item_id, $type, $internalID );
		} else {
			$log = array(
				'type'      => 3,
				'module'    => ucfirst( $type ),
				'submodule' => 'Delete',
				'context'   => __( 'Notification - failed to delete local item', 'toret-manager' ),
				'log'       => wp_json_encode( array(
					'Local ID'        => $item_id,
					'API internal ID' => $internalID,
					'Error'           => wp_json_encode( $deleted )
				) ),
			);

			do_action( 'trman_after_failed_delete_notified_item', $item_id, $type, $internalID );
		}
		trman_log( TORET_MANAGER_SLUG, $log );
	}

	/**
	 * Get associated local id
	 *
	 * @param mixed $internalID
	 * @param string $associated
	 * @param string $module
	 * @param string $type
	 * @param bool $parent
	 * @param bool $markSynced
	 *
	 * @return int|WP_Error|null
	 */
	function get_associted_local_id( $internalID, string $associated, string $module, string $type, bool $parent = false, bool $markSynced = false ) {
		$local_ID = Toret_Manager_Helper_Db::get_post_by_meta_value( $this->internalID_key, $internalID, $type, $module );
		$local_ID = self::additional_check_for_existing_item( $local_ID, $internalID, $module, $type );

		$continue = false;

		if ( $parent && Toret_Manager_Helper_Modules::should_sync_parent( $associated ) ) {
			$continue = true;
		} elseif ( ! $parent && Toret_Manager_Helper_Modules::should_sync_associated( $associated ) || $associated == 'forced' ) {
			$continue = true;
		}

		$log = array(
			'module'    => ucfirst( $associated ),
			'submodule' => 'Associated item',
			'context'   => __( 'Associated item - ', 'toret-manager' ) . ucfirst( $module ),
			'log'       => wp_json_encode( array(
				'API internal ID' => $internalID,
				'Action'          => ( empty( $local_ID ) ? "Download from API" : "Local item found" )
			) ),
		);
		trman_log( TORET_MANAGER_SLUG, $log );

		if ( empty( $local_ID ) && $continue ) {
			$local_ID = self::maybe_create_item( $internalID, $module, $type, $parent, $markSynced, $associated );
		}

		return $local_ID;
	}

	/**
	 * Get associated comment local post id
	 *
	 * @param mixed $internalID
	 * @param string $associated
	 * @param string $type
	 * @param bool $markSynced
	 *
	 * @return int|WP_Error|null
	 */
	function get_associted_comment_post_local_id( $internalID, string $associated, string $type, bool $markSynced = false ) {
		$local_id = Toret_Manager_Helper_Db::get_post_by_meta_value( $this->internalID_key, $internalID, $type );
		$local_id = self::additional_check_for_existing_item( $local_id, $internalID, $type, $type );

		$log = array(
			'module'    => ucfirst( $associated ),
			'submodule' => 'Associated item',
			'context'   => __( 'Associated item - ', 'toret-manager' ) . ucfirst( $type ),
			'log'       => wp_json_encode( array(
				'API internal ID' => $internalID,
				'Action'          => ( empty( $local_id ) ? "Download from API" : "Local item found" )
			) ),
		);
		trman_log( TORET_MANAGER_SLUG, $log );

		$get_all_module_with_types = Toret_Manager_Helper_Modules::get_all_modules( true );

		//if (empty($local_id)) {
		if ( $associated == 'order_note' ) {
			$itemData = self::get_item_from_cloud( $internalID, 'order' );
			if ( ! empty( $itemData ) ) {
				if ( property_exists( $itemData, 'parentInternalID' ) ) {
					self::maybe_create_item( $itemData->parentInternalID, 'order', 'order', true );
				}
				$data_to_be_synchronized = Toret_Manager_Helper_Modules::data_to_be_sync( 'order', 'order', 'new', Toret_Manager_Helper_Modules::get_mandatory_items( 'order' ), 'download' );
				$local_id                = self::save_item_by_module( $itemData, $data_to_be_synchronized, $local_id, ! empty( $local_id ), 'order', 'order', $markSynced );
			}

		} else {
			if ( Toret_Manager_Helper_Modules::should_sync_associated( $associated ) ) {
				$itemData = self::get_item_from_cloud( $internalID, ( $associated != 'review' ? 'anypost' : '' ) );
				if ( ! empty( $itemData ) ) {
					if ( property_exists( $itemData, 'parentInternalID' ) ) {
						self::maybe_create_item( $itemData->parentInternalID, $type, $type, true );
					}
					$data_to_be_synchronized = Toret_Manager_Helper_Modules::data_to_be_sync( $itemData->postType, array_search( $itemData->postType, $get_all_module_with_types ), 'new', Toret_Manager_Helper_Modules::get_mandatory_items( $type ), 'download' );
					$local_id                = self::save_item_by_module( $itemData, $data_to_be_synchronized, $local_id, ! empty( $local_id ), $itemData->postType, $type, $markSynced );
				}

			}
		}

		//}

		return $local_id;
	}

	/**
	 * Process item change notification
	 *
	 * @param mixed $internalID
	 * @param string $module
	 * @param string $type
	 *
	 * @return mixed
	 */
	public static function notify_item_change( $internalID, string $module, string $type ) {
		$existing_item_id = Toret_Manager_Helper_Db::get_post_by_meta_value( TORET_MANAGER_ITEM_INTERNALID, $internalID, $type, $module );
		$existing_item_id = self::additional_check_for_existing_item( $existing_item_id, $internalID, $module, $type );
		$update           = ! empty( $existing_item_id );
		$action           = $update ? 'update' : 'new';

		if ( Toret_Manager_Helper_Modules::is_sync_enabled( $module, $action, 'download' ) ) {

			if ( ! empty( $existing_item_id ) && Toret_Manager_Helper::is_excluded( $existing_item_id, $module, $type ) ) {
				return null;
			}

			return self::save_notified_item( $internalID, $action, $update, $existing_item_id, $module, $type );
		}

		return null;
	}


	/**
	 * Download and save notified item
	 *
	 * @param mixed $internalID
	 * @param string $action
	 * @param bool $update
	 * @param mixed $existing_id
	 * @param string $module
	 * @param string $type
	 *
	 * @return mixed
	 */
	static function save_notified_item( $internalID, string $action, bool $update, $existing_id, string $module, string $type ) {
		$itemData = self::get_item_from_cloud( $internalID, $module );
		if ( ! empty( $itemData ) ) {
			$data_to_be_synchronized = Toret_Manager_Helper_Modules::data_to_be_sync( $module, $type, $action, Toret_Manager_Helper_Modules::get_mandatory_items( $type ), 'download' );

			return self::save_item_by_module( $itemData, $data_to_be_synchronized, $existing_id, $update, $module, $type );
		}

		return null;

	}

	/**
	 * Get correct save function by module
	 *
	 * @param $itemData
	 * @param array $data_to_be_synchronized
	 * @param mixed $existing_id
	 * @param bool $update
	 * @param string $module
	 * @param string $type
	 * @param bool $markSynced
	 * @param string|null $associated
	 *
	 * @return mixed
	 */
	static function save_item_by_module( $itemData, array $data_to_be_synchronized, $existing_id, bool $update, string $module, string $type, bool $markSynced = false, string $associated = null ) {
		$itemData = apply_filters( 'trman_data_before_save', $itemData, $data_to_be_synchronized, $existing_id, $update, $module, $type );
		if ( $type == 'post' ) {
			return Toret_Manager_Module_Post::get_instance( TORET_MANAGER_SLUG )->save_item( $itemData, $data_to_be_synchronized, $existing_id, $update, $markSynced );
		} elseif ( $type == 'order' ) {
			return Toret_Manager_Module_Order::get_instance( TORET_MANAGER_SLUG )->save_item( $itemData, $data_to_be_synchronized, $existing_id, $update, $markSynced );
		} elseif ( $type == 'product' ) {
			return Toret_Manager_Module_Product::get_instance( TORET_MANAGER_SLUG )->save_item( $itemData, $data_to_be_synchronized, $existing_id, $update, $markSynced );
		} elseif ( $type == 'user' ) {
			return Toret_Manager_Module_User::get_instance( TORET_MANAGER_SLUG )->save_item( $itemData, $data_to_be_synchronized, $existing_id, $update, $markSynced );
		} elseif ( $type == 'comment' ) {
			return Toret_Manager_Module_Review::get_instance( TORET_MANAGER_SLUG )->save_item( $itemData, $data_to_be_synchronized, $markSynced );
		} else {
			return Toret_Manager_Module_Post::get_instance( TORET_MANAGER_SLUG )->save_item( $itemData, $data_to_be_synchronized, $existing_id, $update, $markSynced );
		}
	}

	/**
	 * Create item if needed
	 *
	 * @param mixed $internal_id
	 * @param string $module
	 * @param string $type
	 * @param bool $parent
	 * @param bool $markSynced
	 * @param string|null $associated
	 *
	 * @return mixed|null
	 */
	static function maybe_create_item( $internal_id, string $module, string $type, bool $parent = false, bool $markSynced = false, string $associated = null ) {
		if ( $parent && ! Toret_Manager_Helper_Modules::should_sync_parent( $module ) ) {
			return null;
		}

		if ( $internal_id == '' || $internal_id == '-1' ) {
			return null;
		}

		$item_id = Toret_Manager_Helper_Db::get_post_by_meta_value( TORET_MANAGER_ITEM_INTERNALID, $internal_id, $type, $module );

		if ( empty( $item_id ) ) {
			$itemData = self::get_item_from_cloud( $internal_id, $module );

			if ( ! empty( $itemData ) ) {
				$data_to_be_synchronized = Toret_Manager_Helper_Modules::data_to_be_sync( $module, $type, 'new', Toret_Manager_Helper_Modules::get_mandatory_items( $type ), 'download' );

				return self::save_item_by_module( $itemData, $data_to_be_synchronized, null, false, $module, $type, $markSynced, $associated );
			}
		}

		return null;
	}

	/**
	 * Download item from api
	 *
	 * @param mixed $internalID
	 * @param string $module
	 *
	 * @return null
	 */
	static function get_item_from_cloud( $internalID, string $module = 'product' ) {
		$Toret_Manager_Api = ToretManagerApi();
		$getItem           = $Toret_Manager_Api->getData->getItem( TORET_MANAGER_SLUG, $internalID, array( 'all' ), $module );
		$getItem           = apply_filters( 'trman_data_after_download', $getItem, $internalID, $module );
		if ( $getItem != 'none' && $getItem != '404' ) {
			return $getItem->item;
		} else {
			return null;
		}
	}

	/**
	 * Check if exists in API
	 *
	 * @param mixed $internalID
	 * @param string $module
	 *
	 * @return bool
	 */
	function check_if_exists_in_api( $internalID, string $module ): bool {
		$Toret_Manager_Api = ToretManagerApi();
		$getItem           = $Toret_Manager_Api->getData->getItem( TORET_MANAGER_SLUG, $internalID, array( 'all' ), $module );
		$getItem           = apply_filters( 'trman_data_after_download', $getItem, $internalID, $module );
		if ( $getItem != 'none' && $getItem != '404' ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get edit link without user rights
	 *
	 * @param mixed $post
	 * @param string $context
	 *
	 * @return mixed|string|null
	 */
	function custom_get_edit_post_link( $post = 0, string $context = 'display' ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return "";
		}

		if ( 'revision' === $post->post_type ) {
			$action = '';
		} elseif ( 'display' === $context ) {
			$action = '&amp;action=edit';
		} else {
			$action = '&action=edit';
		}

		$post_type_object = get_post_type_object( $post->post_type );

		if ( ! $post_type_object ) {
			return "";
		}

		$link = '';

		if ( 'wp_template' === $post->post_type || 'wp_template_part' === $post->post_type ) {
			$slug = urlencode( get_stylesheet() . '//' . $post->post_name );
			$link = admin_url( sprintf( $post_type_object->_edit_link, $post->post_type, $slug ) );
		} elseif ( 'wp_navigation' === $post->post_type ) {
			$link = admin_url( sprintf( $post_type_object->_edit_link, (string) $post->ID ) );
		} elseif ( $post_type_object->_edit_link ) {
			$link = admin_url( sprintf( $post_type_object->_edit_link . $action, $post->ID ) );
		}

		return apply_filters( 'get_edit_post_link', $link, $post->ID, $context );
	}


	/**
	 * Get edit link without user rights
	 *
	 * @param mixed $user_id
	 *
	 * @return string
	 */
	function custom_get_edit_user_link( $user_id = null ): string {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( empty( $user_id ) ) {
			return '';
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return '';
		}

		if ( get_current_user_id() == $user->ID ) {
			$link = get_edit_profile_url( $user->ID );
		} else {
			$link = add_query_arg( 'user_id', $user->ID, self_admin_url( 'user-edit.php' ) );
		}

		return apply_filters( 'get_edit_user_link', $link, $user->ID );
	}


	/**
	 * Get edit link without user rights
	 *
	 * @param mixed $term
	 * @param string $taxonomy
	 * @param string $object_type
	 *
	 * @return mixed|string|null
	 */
	function custom_get_edit_term_link( $term, string $taxonomy = '', string $object_type = '' ) {
		$term = get_term( $term, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			return "";
		}

		$tax     = get_taxonomy( $term->taxonomy );
		$term_id = $term->term_id;
		if ( ! $tax ) {
			return "";
		}

		$args = array(
			'taxonomy' => $taxonomy,
			'tag_ID'   => $term_id,
		);

		if ( $object_type ) {
			$args['post_type'] = $object_type;
		} elseif ( ! empty( $tax->object_type ) ) {
			$args['post_type'] = reset( $tax->object_type );
		}

		if ( $tax->show_ui ) {
			$location = add_query_arg( $args, admin_url( 'term.php' ) );
		} else {
			$location = '';
		}

		return apply_filters( 'get_edit_term_link', $location, $term_id, $taxonomy, $object_type );
	}


	/**
	 * Get edit link without user rights
	 *
	 * @param mixed $comment_id
	 *
	 * @return mixed|string|null
	 */
	function custom_get_edit_comment_link( $comment_id = 0 ) {
		$comment  = get_comment( $comment_id );
		$location = admin_url( 'comment.php?action=editcomment&amp;c=' ) . $comment->comment_ID;

		return apply_filters( 'get_edit_comment_link', $location );
	}


	/**
	 * Get parent internal ID
	 *
	 * @param mixed $id
	 * @param string $module
	 * @param string $type
	 *
	 * @return int|mixed|null
	 */
	function get_parent_internal_ID( $id, string $module, string $type ) {
		if ( $id == 0 ) {
			return - 1;
		}

		$parentInternalID = Toret_Manager_Helper_Db::get_object_meta( $id, $this->internalID_key, $type );

		if ( empty( $parentInternalID ) ) {

			if ( Toret_Manager_Helper_Modules::should_sync_parent( 'order' ) ) {

				if ( $module == 'post' ) {
					$parentInternalID = Toret_Manager_Module_Post::get_instance( TORET_MANAGER_SLUG )->upload_missing_post( $id, $module );
				} elseif ( $module == 'order' ) {
					$parentInternalID = Toret_Manager_Module_Order::get_instance( TORET_MANAGER_SLUG )->upload_missing_order( $id );
				} elseif ( $module == 'product' ) {
					$parentInternalID = Toret_Manager_Module_Product::get_instance( TORET_MANAGER_SLUG )->upload_missing_product( $id );
				} elseif ( $module == 'user' ) {
					$parentInternalID = Toret_Manager_Module_User::get_instance( TORET_MANAGER_SLUG )->upload_missing_user( $id );
				} elseif ( in_array( $module, array( 'comment', 'review', 'order_note' ) ) ) {
					$parentInternalID = Toret_Manager_Module_Review::get_instance( TORET_MANAGER_SLUG )->upload_missing_comment( $id );
				} elseif ( in_array( $module, array( 'category', 'post_tag', 'product_cat', 'product_tag' ) ) ) {
					$parentInternalID = Toret_Manager_Module_Term::get_instance( TORET_MANAGER_SLUG )->upload_missing_term( get_term( $id ) );
				} else {
					$parentInternalID = Toret_Manager_Module_Post::get_instance( TORET_MANAGER_SLUG )->upload_missing_post( $id, $module );
				}

			}

			if ( empty( $parentInternalID ) ) {
				$parentInternalID = - 1;
			}

		}

		return $parentInternalID;
	}

	/**
	 * Clear unwanted meta
	 *
	 * @param array $metaArray
	 * @param string $module
	 *
	 * @return mixed|null
	 */
	function clear_unwanted_meta( array $metaArray, string $module ) {
		$unwanted_meta = array(
			'_edit_lock',
			'_edit_last',
		);

		$unwanted_meta = apply_filters( 'trman_unwanted_meta', $unwanted_meta, $module );

		foreach ( $unwanted_meta as $meta ) {
			unset( $metaArray[ $meta ] );
		}

		return apply_filters( 'trman_clear_unwanted_meta', $metaArray, $module );
	}

	/**
	 * Rearrange meta array
	 *
	 * @param array $metaArray
	 * @param string $module
	 *
	 * @return mixed|null
	 */
	function reaarange_meta_array( array $metaArray, string $module ) {
		$rearraanged = array();

		foreach ( $metaArray as $key => $value ) {
			if ( is_array( $value ) ) {
				$rearraanged[ $key ] = $value[0]; //TODO tohle je pro ted ale je to spatne

			} else {
				$rearraanged[ $key ] = $value;
			}
		}

		$rearraanged = $this->clear_unwanted_meta( $rearraanged, $module );

		return apply_filters( 'trman_reaarange_meta_array', $rearraanged, $module );
	}


}
