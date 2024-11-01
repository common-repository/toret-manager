<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Toret_Manager_Module_Product extends Toret_Manager_Module_General {

	/**
	 * Class instance
	 *
	 * @var Toret_Manager_Module_Product|null
	 */
	protected static ?Toret_Manager_Module_Product $Toret_Manager_Module_Product_Instance = null;

	/**
	 * Module
	 *
	 * @var string
	 */
	protected string $module;

	/**
	 * Constructor
	 *
	 * @param string $toret_manager
	 */
	public function __construct( string $toret_manager ) {
		parent::__construct( $toret_manager );

		$this->internalID_key = TORET_MANAGER_ITEM_INTERNALID;
		$this->module         = 'product';

		if ( Toret_Manager_Helper_Modules::is_any_edit_sync_enabled( $this->module ) ) {
			add_action( 'wp_after_insert_post', array( $this, 'on_save_post' ), 99, 4 );
			add_action( 'async_on_save_product', array( $this, 'async_on_save_product' ), 99, 1 );
			add_action( 'woocommerce_product_duplicate', array( $this, 'on_product_duplicate' ), 99, 1 );

			add_action( 'woocommerce_save_product_variation', array( $this, 'on_save_product_variation' ) );
			add_action( 'async_on_save_product_variation', array( $this, 'async_on_save_product_variation' ), 99, 1 );

			add_action( 'woocommerce_product_import_inserted_product_object', array(
				$this,
				'on_imported_product'
			), 10, 2 );
			add_filter( 'woocommerce_duplicate_product_exclude_meta', array(
				$this,
				'exclude_meta_from_duplicate'
			), 999, 2 );

			add_action( 'trashed_post', array( $this, 'process_trash_post' ) );
			add_action( 'untrashed_post', array( $this, 'process_trash_post' ) );

		}

		if ( Toret_Manager_Helper_Modules::is_sync_enabled( $this->module, 'delete' ) ) {
			add_action( 'before_delete_post', array( $this, 'on_before_delete_post' ), 99, 2 );
			add_action( 'async_on_delete_product', array( $this, 'async_on_delete_product' ), 99, 2 );

			add_action( 'woocommerce_before_delete_product_variation', array( $this, 'on_delete_product_variation' ) );
			add_action( 'async_on_delete_product_variation', array(
				$this,
				'async_on_delete_product_variation'
			), 99, 1 );
		}

		// Upload variations on product created
		add_action( 'trman_after_upload_create_item', array( $this, 'upload_product_variations' ), 10, 4 );
		add_action( 'trman_after_upload_update_item', array( $this, 'upload_product_variations' ), 10, 4 );
		add_action( 'wp_ajax_woocommerce_feature_product', array( $this, 'feature_product' ), 50 );
		add_action( 'woocommerce_after_product_object_save', array( $this, 'feature_product' ), 50 );
	}

	/**
	 * Get class instance
	 *
	 * @param string $toret_manager
	 *
	 * @return Toret_Manager_Module_Product|null
	 */
	public static function get_instance( string $toret_manager ): ?Toret_Manager_Module_Product {
		if ( null == self::$Toret_Manager_Module_Product_Instance ) {
			self::$Toret_Manager_Module_Product_Instance = new self( $toret_manager );
		}

		return self::$Toret_Manager_Module_Product_Instance;
	}

	/**
	 * On local product delete
	 *
	 * @param mixed $post_id
	 * @param mixed $post
	 */
	public function on_before_delete_post( $post_id, $post ) {
		$module = Toret_Manager_Helper_Modules::get_module_by_post_type( $post->post_type );

		if ( ! Toret_Manager_Helper_Modules::is_sync_enabled( $module, 'delete' ) ) {
			return;
		}

		if ( $post->post_type != $this->module ) {
			return;
		}

		if ( Toret_Manager_Helper::is_excluded( $post_id, $this->module, $this->module ) ) {
			return;
		}

		Toret_Manager_Module_General::process_delete_post( $post_id, $module, $this->module );
	}

	/**
	 * Upload when trashed
	 *
	 * @param mixed $item_id
	 */
	function process_trash_post( $item_id ) {
		$this->on_save_post( $item_id, get_post( $item_id ) );
	}

	/**
	 * On local product save
	 *
	 * @param mixed $product
	 * @param mixed $data
	 */
	public function on_imported_product( $product, $data ) {
		$item_id = $product->get_id();

		$internalID = Toret_Manager_Helper_Db::get_object_meta( $item_id, $this->internalID_key, $this->module );
		$update     = ! empty( $internalID );
		if ( empty( $internalID ) ) {
			$internalID = Toret_Manager_Helper::generate_internal_id( $this->module );
		}

		$edit_args = array(
			'internalID' => $internalID,
			'update'     => $update,
			'action'     => $update ? 'update' : 'new',
			'module'     => $this->module,
			'type'       => $this->module,
			'associated' => '',
		);

		$nonce = wp_create_nonce( 'trman_edit_args_' . $item_id );

		$edit_args = Toret_Manager_Helper::edit_args_modification( $edit_args, $item_id, $nonce );

		$data = self::itemDataArray( wc_get_product( $item_id ), $edit_args );
		Toret_Manager_Module_General::process_save_item_adv( $item_id, $data, false, $edit_args );
	}

	/**
	 * On local product save
	 *
	 * @param mixed $item_id
	 * @param mixed $post
	 */
	public function on_save_post( $item_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( defined( 'REST_REQUEST' ) ) {
			return;
		}

		if ( defined( 'DOING_CRON' ) ) {
			return;
		}

		if ( wp_doing_cron() ) {
			return;
		}

		/*if (wp_is_post_revision($item_id) || wp_is_post_autosave($item_id)) {
			return;
		}*/

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'woocommerce_do_ajax_product_import' ) {
			return;
		}

		if ( $post->post_type !== $this->module ) {
			return;
		}

		if ( ! Toret_Manager_Helper_Modules::is_any_edit_sync_enabled( $this->module ) ) {
			return;
		}

		if ( in_array( $post->post_status, array( 'auto-draft' ) ) ) {
			return;
		}

		if ( Toret_Manager_Helper::is_excluded( $item_id, $this->module, $this->module ) ) {
			return;
		}

		if(isset($_POST['trman_run']) && $_POST['trman_run'] == 'queue'){
			return;
		}

		if ( in_array( $this->module, TORET_MANAGER_ASYNC_UPLOAD_TYPES ) ) {
			wp_schedule_single_event( time(), 'async_on_save_product', array( $item_id ) );
		} else {
			$this->async_on_save_product( $item_id );
		}
	}

	/**
	 * Async on product save
	 *
	 * @param mixed $item_id
	 * @param bool $force
	 * @param string $associated
	 * @param array $edit_args
	 *
	 * @return mixed|null
	 */
	function async_on_save_product( $item_id, bool $force = false, string $associated = "", array $edit_args = array() ) {
		$internalID = Toret_Manager_Helper_Db::get_object_meta( $item_id, $this->internalID_key, $this->module );
		$update     = ! empty( $internalID );
		if ( empty( $internalID ) ) {
			$internalID = Toret_Manager_Helper::generate_internal_id( $this->module );
		}

		$edit_args['internalID'] = $internalID;
		$edit_args['update']     = $update;
		$edit_args['action']     = $update ? 'update' : 'new';
		$edit_args['module']     = $this->module;
		$edit_args['type']       = $this->module;
		$edit_args['associated'] = $associated;

		$nonce = wp_create_nonce( 'trman_edit_args_' . $item_id );

		$edit_args = Toret_Manager_Helper::edit_args_modification( $edit_args, $item_id, $nonce );

		$data = self::itemDataArray( wc_get_product( $item_id ), $edit_args );

		return Toret_Manager_Module_General::process_save_item_adv( $item_id, $data, $force, $edit_args );
	}

	/**
	 * On local product variation delete
	 *
	 * @param mixed $item_id
	 */
	function on_delete_product_variation( $item_id ) {
		if ( Toret_Manager_Helper::is_excluded( $item_id, $this->module, $this->module ) ) {
			return;
		}

		if ( in_array( $this->module, TORET_MANAGER_ASYNC_UPLOAD_TYPES ) ) {
			wp_schedule_single_event( time(), 'async_on_delete_product_variation', array( $item_id ) );
		} else {
			$this->async_on_delete_product_variation( $item_id );
		}
	}

	/**
	 * Async on product delete
	 *
	 * @param mixed $item_id
	 */
	function async_on_delete_product_variation( $item_id ) {
		Toret_Manager_Module_General::process_delete_post( $item_id, $this->module, $this->module );
	}

	/**
	 * On local product variation save
	 *
	 * @param mixed $item_id
	 */
	function on_save_product_variation( $item_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( defined( 'REST_REQUEST' ) ) {
			return;
		}

		if ( wp_is_post_revision( $item_id ) || wp_is_post_autosave( $item_id ) ) {
			return;
		}

		if ( Toret_Manager_Helper::is_excluded( $item_id, $this->module, $this->module ) ) {
			return;
		}

		if ( in_array( $this->module, TORET_MANAGER_ASYNC_UPLOAD_TYPES ) ) {
			wp_schedule_single_event( time(), 'async_on_save_product_variation', array( $item_id ) );
		} else {
			$this->async_on_save_product_variation( $item_id );
		}
	}

	/**
	 * Async product variation save
	 *
	 * @param mixed $item_id
	 */
	function async_on_save_product_variation( $item_id ) {
		self::async_on_save_product( $item_id );
	}

	/**
	 * Get product data for upload
	 *
	 * @param WC_Product $object
	 * @param array $edit_args
	 *
	 * @return mixed|null
	 * @throws Exception
	 */
	public function itemDataArray( WC_Product $object, array $edit_args = [] ) {
		$update     = $edit_args['update'];
		$associated = $edit_args['associated'];

		$is_variation = $object->get_type() == 'variation';

		$regular_price_no_vat = wc_get_price_excluding_tax( $object, array(
			'price' => $object->get_regular_price(),
		) );

		$regular_price_vat = wc_get_price_including_tax( $object, array(
			'price' => $object->get_regular_price(),
		) );

		$sale_price_vat = wc_get_price_including_tax( $object, array(
			'price' => $object->get_sale_price(),
		) );

		$sale_price_no_vat = wc_get_price_excluding_tax( $object, array(
			'price' => $object->get_sale_price(),
		) );

		$tax_rates = WC_Tax::get_rates( $object->get_tax_class() );
		if ( ! empty( $tax_rates ) ) {
			$tax_rate = reset( $tax_rates );
			$vat      = (int) $tax_rate['rate'];
		} else {
			$vat = 0;
		}

		$meta = get_post_meta( $object->get_id() );
		$meta = array_filter( $meta, function ( $meta_key ) {
			return $meta_key[0] !== '_';
		}, ARRAY_FILTER_USE_KEY );


		$meta = $this->reaarange_meta_array( $meta, $this->module );

		$gallery_urls = [];
		$gallery_ids  = $object->get_gallery_image_ids();
		foreach ( $gallery_ids as $gallery_id ) {
			$gallery_urls[] = wp_get_attachment_url( $gallery_id );
		}

		$description = $object->get_description();

		$short_description = '';
		if ( $is_variation ) {
			$parent = wc_get_product( $object->get_parent_id() );
			if ( ! empty( $parent ) ) {
				$short_description = $parent->get_short_description();
				if ( $description == '' ) {
					$description = $parent->get_description();
				}
			}
		} else {
			$short_description = $object->get_short_description();
		}

		$parentInternalID = $edit_args['parentInternalID'] ?? $this->get_parent_internal_ID( $object->get_parent_id(), $this->module, $this->module );

		$downloadable_files = [];
		if ( $object->is_downloadable() ) {
			$product_downloadable_files = $object->get_downloads();
			foreach ( $product_downloadable_files as $product_downloadable_file ) {
				$downloadable_files[ $product_downloadable_file->get_name() ] = $product_downloadable_file->get_file();
			}
		}

		$manageStock = $object->get_manage_stock();

		$reviewInternalID = [];
		if ( $associated != "review" ) {
			$reviewInternalID = Toret_Manager_Module_Review::get_instance( $this->toret_manager )->get_comments_internal_id( get_comments( array(
				'post_id' => $object->get_id(),
				'type'    => array(
					'comment',
					'review'
				)
			) ), $edit_args );
		}

		if ( empty( $object->get_parent_id() ) ) {
			$editUrl = esc_url( Toret_Manager_Module_General::custom_get_edit_post_link( $object->get_id() ) );
		} else {
			$editUrl = esc_url( Toret_Manager_Module_General::custom_get_edit_post_link( $object->get_parent_id() ) );
		}

		$tax_class = $object->get_tax_class();
		if(empty($tax_class)){
			$tax_class = 'woo-standard-rate';
		}
		$data = [
			'productID'           => $object->get_id(),
			'parentID'            => $object->get_parent_id(),
			'parentInternalID'    => (string) $parentInternalID,
			'sku'                 => $object->get_sku(),
			'productType'         => $object->get_type(),
			'title'               => $object->get_name(),
			'price'               => $regular_price_no_vat,
			'priceVat'            => $regular_price_vat,
			'currency'            => get_woocommerce_currency(),
			'salePrice'           => $sale_price_no_vat,
			'salePriceVat'        => $sale_price_vat,
			'vat'                 => $vat,
			'stockStatus'         => $object->get_stock_status(),
			'shortDescription'    => $short_description,
			'description'         => $description,
			'totalSales'          => (int) $object->get_total_sales(),
			'taxStatus'           => $object->get_tax_status(),
			'taxClass'            => $tax_class,
			'shippingClass'       => $object->get_shipping_class(),
			'manageStock'         => $manageStock,
			'lowStockAmount'      => (string) $object->get_low_stock_amount(),
			'backorders'          => $object->backorders_allowed(),
			'backordersNotify'    => $object->backorders_require_notification(),
			'soldIndividually'    => $object->is_sold_individually(),
			'virtual'             => $object->is_virtual(),
			'downloadable'        => $object->is_downloadable(),
			'files'               => wp_json_encode( $downloadable_files ),
			'productImageGallery' => wp_json_encode( $gallery_urls ),
			'downloadLimit'       => $object->get_download_limit(),
			'downloadExpiry'      => $object->get_download_expiry(),
			'thumbnail'           => ( get_the_post_thumbnail_url( $object->get_id() ) ? get_the_post_thumbnail_url( $object->get_id() ) : '' ),
			'url'                 => $object->get_permalink(),
			'allowReview'         => $object->get_reviews_allowed(),
			'averageRating'       => (float) $object->get_average_rating(),
			'reviewCount'         => $object->get_review_count(),
			'ratingCount'         => $object->get_rating_count(),
			'visibility'          => $object->get_catalog_visibility(),
			'weight'              => (float) $object->get_weight(),
			'length'              => (float) $object->get_length(),
			'width'               => (float) $object->get_width(),
			'height'              => (float) $object->get_height(),
			'weightUnit'          => get_option( 'woocommerce_weight_unit' ),
			'dimensionsUnit'      => get_option( 'woocommerce_dimension_unit' ),
			'purchaseNote'        => $object->get_purchase_note(),
			'menuOrder'           => $object->get_menu_order(),
			'meta'                => wp_json_encode( $meta ),
			'wooUniqueID'         => $object->get_global_unique_id(),
			'editUrl'             => $editUrl,
			'postStatus'          => $object->get_status(),
			'reviewInternalID'    => wp_json_encode( $reviewInternalID ),
			'isSticky'            => $object->is_featured(),
		];

		if ( $object->get_type() == 'external' ) {
			$data['productUrl'] = $object->get_product_url();
			$data['buttonText'] = $object->get_button_text();
		} else {
			$data['productUrl'] = "";
			$data['buttonText'] = "";
		}

		$saleFromDate = Toret_Manager_DateTime::format_date_for_api( $object->get_date_on_sale_from(), false );
		if ( ! empty( $saleFromDate ) ) {
			$data['saleFromDate'] = $saleFromDate;
		}

		$saleToDate = Toret_Manager_DateTime::format_date_for_api( $object->get_date_on_sale_to(), false );
		if ( ! empty( $saleToDate ) ) {
			$data['saleToDate'] = $saleToDate;
		}

		if ( ! empty( get_option( 'trman_module_product_field_ean' ) ) ) {
			$data['ean'] = get_post_meta( $object->get_id(), get_option( 'trman_module_product_field_ean' ), true );
		}
		if ( ! empty( get_option( 'trman_module_product_field_isbn' ) ) ) {
			$data['isbn'] = get_post_meta( $object->get_id(), get_option( 'trman_module_product_field_isbn' ), true );
		}
		if ( ! empty( get_option( 'trman_module_product_field_gtin' ) ) ) {
			$data['gtin'] = get_post_meta( $object->get_id(), get_option( 'trman_module_product_field_gtin' ), true );
		}

		$attribute_ids      = Toret_Manager_Module_Product_Attribute::get_instance( $this->toret_manager )->get_product_attributes_for_api( $object );
		$data['attributes'] = wp_json_encode( $attribute_ids );

		$category_internal_ids = Toret_Manager_Module_Term::get_instance( $this->toret_manager )->get_term_internal_ids( 'product_cat', $object->get_category_ids(), $this->module );
		if ( ! empty( $category_internal_ids ) ) {
			$data['category'] = wp_json_encode( $category_internal_ids );
		}

		$tag_internal_ids = Toret_Manager_Module_Term::get_instance( $this->toret_manager )->get_term_internal_ids( 'product_tag', $object->get_tag_ids(), $this->module );
		if ( ! empty( $tag_internal_ids ) ) {
			$data['tags'] = wp_json_encode( $tag_internal_ids );
		}


		$cross_sell_internal_ids = array_map( function ( $product_id ) {
			return self::get_product_internal_ID( $product_id );
		}, $object->get_cross_sell_ids() );
		if ( ! empty( $cross_sell_internal_ids ) ) {
			$data['crossSell']         = wp_json_encode( $object->get_cross_sell_ids() );
			$data['crossSellInternal'] = wp_json_encode( $cross_sell_internal_ids );
		}


		$linked_items = get_post_meta( $object->get_id(), '_children', true );
		if ( ! empty( $linked_items ) ) {
			$linked_internal_ids = array_map( function ( $linked_id ) {
				return self::get_product_internal_ID( $linked_id );
			}, $linked_items );
			if ( ! empty( $linked_internal_ids ) ) {
				$data['grouped']         = wp_json_encode( $linked_items );
				$data['groupedInternal'] = wp_json_encode( $linked_internal_ids ); //TODO prohozenz s upsell asi pri create product
			}
		}


		$upsell_internal_ids = array_map( function ( $product_id ) {
			return self::get_product_internal_ID( $product_id );
		}, $object->get_upsell_ids() );
		if ( ! empty( $upsell_internal_ids ) ) {
			$data['upSell']         = wp_json_encode( $object->get_upsell_ids() );
			$data['upSellInternal'] = wp_json_encode( $upsell_internal_ids );
		}

		return apply_filters( 'toret_manager_sent_product_data', $data, $object, $update );
	}

	/**
	 * Save notified product
	 *
	 * @param mixed $productData
	 * @param array $data_to_be_synchronized
	 * @param mixed $existing_product_id
	 * @param bool $update
	 * @param bool $markSynced
	 *
	 * @return mixed|null
	 * @throws WC_Data_Exception
	 */
	function save_item( $productData, array $data_to_be_synchronized, $existing_product_id, bool $update = false, bool $markSynced = false ) {
		if ( $update && ! empty( $existing_product_id ) ) {
			$product = wc_get_product( $existing_product_id );
		} else {
			$product = $this->get_WC_Product_by_productType( $productData->productType );
		}

		if ( $product->get_type() != $productData->productType ) {
			$product_classname = WC_Product_Factory::get_product_classname( $product->get_id(), $productData->productType );
			$product           = new $product_classname( $product->get_id() );
			$product->save();
		}

		if ( empty( $product ) ) {
			$log = array(
				'type'      => 3,
				'module'    => ucfirst( $this->module ),
				'submodule' => 'Notification - ' . ( $update ? 'Update' : 'Created' ),
				'context'   => ( $update ? __( 'Failed to update item', 'toret-manager' ) : __( 'Failed to create item', 'toret-manager' ) ),
				'log'       => wp_json_encode( array(
					'Local ID'        => $existing_product_id,
					'API internal ID' => $productData->internalID,
					'Error'           => 'Empty product object'
				) ),
			);
			trman_log( $this->toret_manager, $log );

			return null;
		}

		$is_variation = $productData->productType == 'variation';

		if ( ! empty( $productData->parentInternalID ) && $productData->parentInternalID != - 1 ) {
			$parent_id = Toret_Manager_Module_General::get_associted_local_id( $productData->parentInternalID, $this->module, $this->module, $this->module, false, true );
		}

		$category_ids = [];
		if ( in_array( 'category', $data_to_be_synchronized ) ) {
			if ( ! empty( $productData->category ) ) {
				$category_ids = Toret_Manager_Module_Term::get_instance( $this->toret_manager )->get_associted_local_term_ids( json_decode( $productData->category ), 'product_cat', $this->module, false, true );
			}
		}

		$tag_ids = [];
		if ( in_array( 'tags', $data_to_be_synchronized ) ) {
			if ( ! empty( $productData->tags ) ) {
				$tag_ids = Toret_Manager_Module_Term::get_instance( $this->toret_manager )->get_associted_local_term_ids( json_decode( $productData->tags ), 'product_tag', $this->module, false, true );
			}
		}

		$saved_gallery = [];

		$needs_update = false;
		foreach ( $productData as $property => $item ) {
			if ( in_array( $property, $data_to_be_synchronized ) ) {

				$filter = apply_filters( 'toret_manager_product_notified_should_process_item', false, $item, $property, $productData );
				if ( ! empty( $filter ) ) {
					do_action( 'toret_manager_product_notified_process_item', $item, $property, $productData );
					continue;
				}

				switch ( $property ) {
					case 'title':
						$product->set_name( $item );
						$needs_update = true;
						break;
					case 'price':
						if ( ! wc_prices_include_tax() ) {
							$product->set_regular_price( $item );
						} else {
							$product->set_regular_price( $productData->priceVat );
						}
						$needs_update = true;
						break;
					case 'salePrice':
						if ( ! wc_prices_include_tax() ) {
							$product->set_sale_price( $item );
						} else {
							$product->set_sale_price( $productData->salePriceVat );
						}
						$needs_update = true;
						break;
					case 'saleFromDate':
						$product->set_date_on_sale_from( $item );
						$needs_update = true;
						break;
					case 'saleToDate':
						$product->set_date_on_sale_to( $item );
						$needs_update = true;
						break;
					case 'vat':
						$tax_rates = WC_Tax::get_rates();
						foreach ( $tax_rates as $tax_rate ) {
							if ( $tax_rate['rate'] == $item ) {
								$product->set_tax_class( $item );
								$needs_update = true;
							}
						}
						break;
					case 'stockStatus':
						$product->set_stock_status( $item );
						$needs_update = true;
						break;
					case 'shortDescription':
						$product->set_short_description( $item );
						$needs_update = true;
						break;
					case 'description':
						$product->set_description( $item );
						$needs_update = true;
						break;
					case 'totalSales':
						$product->set_total_sales( $item );
						$needs_update = true;
						break;
					case 'taxStatus':
						$product->set_tax_status( $item );
						$needs_update = true;
						break;
					case 'taxClass':
						$tax_class = $item;
						if($tax_class == 'woo-standard-rate'){
							$tax_class = '';
						}
						$product->set_tax_class( $tax_class );
						$needs_update = true;
						break;
					case 'shippingClass':
						$product->set_shipping_class_id( $item );
						$needs_update = true;
						break;
					case 'manageStock':
						if ( $is_variation && $item == 'parent' ) {
							$item = false;
						}
						$product->set_manage_stock( $item );
						$needs_update = true;
						break;
					case 'backorders':
						$product->set_backorders( $item ? 'yes' : 'no' );
						$needs_update = true;
						break;
					case 'lowStockAmount':
						$product->set_low_stock_amount( $item );
						$needs_update = true;
						break;
					case 'isSticky':
						$product->set_featured( $item );
						$needs_update = true;
						break;
					case 'backordersNotify':
						if ( $item ) {
							$product->set_backorders( 'notify' );
							$needs_update = true;
						}
						break;
					case 'sku':
						try {
							$product->set_sku( $item );
						} catch ( WC_Data_Exception $e ) {
							$log = array(
								'type'      => 3,
								'module'    => ucfirst( $this->module ),
								'submodule' => 'Notification - ' . ( $update ? 'Update' : 'Created' ),
								'context'   => ( $update ? __( 'Failed to update item', 'toret-manager' ) : __( 'Failed to create item', 'toret-manager' ) ),
								'log'       => wp_json_encode( array(
									'Local ID'        => $existing_product_id,
									'API internal ID' => $productData->internalID,
									'Error'           => $e->getMessage()
								) ),
							);
							trman_log( $this->toret_manager, $log );
						}
						$needs_update = true;
						break;
					case 'wooUniqueID':
						try {
							$product->set_global_unique_id( $item );
						} catch ( WC_Data_Exception $e ) {
							$log = array(
								'type'      => 3,
								'module'    => ucfirst( $this->module ),
								'submodule' => 'Notification - ' . ( $update ? 'Update' : 'Created' ),
								'context'   => ( $update ? __( 'Failed to update item', 'toret-manager' ) : __( 'Failed to create item', 'toret-manager' ) ),
								'log'       => wp_json_encode( array(
									'Local ID'        => $existing_product_id,
									'API internal ID' => $productData->internalID,
									'Error'           => $e->getMessage()
								) ),
							);
							trman_log( $this->toret_manager, $log );
						}
						$needs_update = true;
						break;
					case 'soldIndividually':
						$product->set_sold_individually( $item );
						$needs_update = true;
						break;
					case 'virtual':
						$product->set_virtual( $item );
						$needs_update = true;
						break;
					case 'downloadable':
						$product->set_downloadable( $item );
						$needs_update = true;
						break;
					case 'productImageGallery':
						$gallery_urls = json_decode( $item );
						$ids          = [];
						if ( ! empty( $gallery_urls ) ) {

							foreach ( $gallery_urls as $gallery_url ) {
								if ( ! empty( $gallery_url ) ) {
									$id = Toret_Manager_Helper::download_file( $gallery_url,null,'produkt',false);
									if ( $id ) {
										$saved_gallery[ $gallery_url ] = $id;
										$ids[]                         = $id;
									}
								}
							}

							$product->set_gallery_image_ids( $ids );
							$needs_update = true;
						}
						break;
					case 'downloadLimit':
						$product->set_download_limit( $item );
						$needs_update = true;
						break;
					case 'downloadExpiry':
						$product->set_download_expiry( $item );
						$needs_update = true;
						break;
					case 'allowReview':
						$product->set_reviews_allowed( $item );
						$needs_update = true;
						break;
					case 'averageRating':
						$product->set_average_rating( $item );
						$needs_update = true;
						break;
					case 'reviewCount':
						$product->set_review_count( $item );
						$needs_update = true;
						break;
					case 'purchaseNote':
						$product->set_purchase_note( $item );
						$needs_update = true;
						break;
					case 'menuOrder':
						$product->set_menu_order( $item );
						$needs_update = true;
						break;
					case 'visibility':
						if ( ! empty( $item ) ) {
							$product->set_catalog_visibility( $item );
							$needs_update = true;
						}
						break;
					case 'ean':
						update_post_meta( $existing_product_id, get_option( 'trman_module_product_field_ean' ), $item );
						break;
					case 'isbn':
						update_post_meta( $existing_product_id, get_option( 'trman_module_product_field_isbn' ), $item );
						break;
					case 'gtin':
						update_post_meta( $existing_product_id, get_option( 'trman_module_product_field_gtin' ), $item );
						break;
					case 'width':
						$product->set_width( $item );
						$needs_update = true;
						break;
					case 'length':
						$product->set_length( $item );
						$needs_update = true;
						break;
					case 'height':
						$product->set_height( $item );
						$needs_update = true;
						break;
					case 'weight':
						$product->set_weight( $item );
						$needs_update = true;
						break;
					case 'category':
						$product->set_category_ids( $category_ids );
						$needs_update = true;
						break;
					case 'tags':
						$product->set_tag_ids( $tag_ids );
						$needs_update = true;
						break;
					case 'productUrl':
						if ( $productData->productType == 'external' ) {
							$product->set_product_url( $item );
							$needs_update = true;
						}
						break;
					case 'buttonText':
						if ( $productData->productType == 'external' ) {
							$product->set_button_text( $item );
							$needs_update = true;
						}
						break;
					case 'meta':
						$meta = json_decode( $item );
						$product->set_meta_data( $meta );
						$needs_update = true;
						break;
					case 'postStatus':
						if ( ! $update ) {
							$target_status = get_option( 'trman_module_' . $this->module . '_imported_status', "default" );
							if ( $target_status == 'default' ) {
								$target_status = $item;
							}
						} else {
							$target_status = $item;
						}

						if ( $item == 'importing' ) {
							$target_status = 'publish';
						}

						$product->set_status( $target_status );
						$needs_update = true;
						break;
					case 'parentID':
						if ( $is_variation && ! empty( $parent_id ) ) {
							$product->set_parent_id( $parent_id );
							$needs_update = true;
						}
						break;
				}

			}

		}

		if ( in_array( 'files', $data_to_be_synchronized ) ) {
			$file_urls = json_decode( $productData->files );
			$downloads = Toret_Manager_Helper::create_downloads( $product->get_id(), $file_urls );
			try {
				$product->set_downloads( $downloads );
			} catch ( WC_Data_Exception $e ) {
				//error_log($e->getMessage());
			}
			$needs_update = true;
		}

		if ( in_array( 'crossSell', $data_to_be_synchronized ) ) {
			if ( ! empty( $productData->crossSellInternal ) ) {
				$internal_ids = json_decode( $productData->crossSellInternal );
				$local_ids    = [];
				if ( ! empty( $internal_ids ) ) {
					foreach ( $internal_ids as $internal_id ) {
						$local_id = Toret_Manager_Module_General::get_associted_local_id( $internal_id, $this->module, $this->module, $this->module );
						if ( ! empty( $local_id ) ) {
							$local_ids[] = $local_id;
						}
					}
					$product->set_cross_sell_ids( $local_ids );
					$needs_update = true;
				}
			}
		}

		if ( in_array( 'upSell', $data_to_be_synchronized ) ) {
			if ( ! empty( $productData->upSellInternal ) ) {
				$internal_ids = json_decode( $productData->upSellInternal );
				$local_ids    = [];

				if ( ! empty( $internal_ids ) ) {
					foreach ( $internal_ids as $internal_id ) {
						$local_id = Toret_Manager_Module_General::get_associted_local_id( $internal_id, $this->module, $this->module, $this->module );
						if ( ! empty( $local_id ) ) {
							$local_ids[] = $local_id;
						}
					}
					$product->set_upsell_ids( $local_ids );
					$needs_update = true;
				}
			}
		}

		//if (in_array('attributes', $data_to_be_synchronized)) {
		if ( ! empty( $productData->attributes ) ) {
			$attributes = $this->get_product_attributes( $product, $productData, $productData->attributes, $update, $existing_product_id );
			if ( $is_variation ) {
				$product->set_attributes( $attributes['local'] );
				$needs_update = true;
			}
		}
		//}

		if ( $needs_update ) {
			$product->save();
		}

		if ( ! $update ) {
			update_post_meta( $product->get_id(), TORET_MANAGER_ITEM_INTERNALID, $productData->internalID );
		}

		if ( in_array( 'thumbnail', $data_to_be_synchronized ) ) {
			if ( ! empty( $productData->thumbnail ) ) {
				if ( key_exists( $productData->thumbnail, $saved_gallery ) ) {
					set_post_thumbnail( $product->get_id(), $saved_gallery[ $productData->thumbnail ] );
				} else {
					$id = Toret_Manager_Helper::download_file( $productData->thumbnail,null,'produkt',false );
					if ( $id ) {
						set_post_thumbnail( $product->get_id(), $id );
					}
				}

			} else {
				delete_post_thumbnail( $product->get_id(), true );
			}
		}

		if ( $markSynced ) {
			update_post_meta( $product->get_id(), TORET_MANAGER_ASSOCIATIVE_SYNC, '1' );
		}

		$log = array(
			'type'      => 1,
			'module'    => ucfirst( $this->module ),
			'submodule' => 'Notification - ' . ( $update ? 'Update' : 'Created' ),
			'context'   => ( $update ? __( 'Item updated', 'toret-manager' ) : __( 'Item created', 'toret-manager' ) ),
			'log'       => wp_json_encode( array(
				'Local ID'        => $product->get_id(),
				'API internal ID' => $productData->internalID
			) ),
		);
		trman_log( $this->toret_manager, $log );

		if ( in_array( 'reviewInternalID', $data_to_be_synchronized ) && property_exists( $productData, 'reviewInternalID' ) ) {
			$internal_ids = json_decode( $productData->reviewInternalID, true );
			Toret_Manager_Module_Review::delete_comments( $product->get_id(), 'review' );
			if ( ! empty( $internal_ids ) ) {
				foreach ( $internal_ids as $internal_id ) {
					Toret_Manager_Module_Review::get_instance( $this->toret_manager )->get_associated_review( $product->get_id(), $internal_id, $this->module, 'review' );
				}
			}
		}

		if ( $product->get_manage_stock() ) {
			Toret_Manager_Module_Stock::get_instance( $this->toret_manager )->notify_stock_change( $productData->internalID );
		}

		if ( in_array( 'grouped', $data_to_be_synchronized ) ) {
			if ( ! empty( $productData->groupedInternal ) ) {
				$internal_ids = json_decode( $productData->groupedInternal );
				$local_ids    = [];

				if ( ! empty( $internal_ids ) ) {
					foreach ( $internal_ids as $internal_id ) {
						$local_id = Toret_Manager_Module_General::get_associted_local_id( $internal_id, $this->module, $this->module, $this->module );
						if ( ! empty( $local_id ) ) {
							$local_ids[] = $local_id;
						}
					}
					update_post_meta( $product->get_id(), '_children', $local_ids );
				}
			}
		}

		if ( ! empty( $attributes['merged'] ) ) {
			if ( ! $is_variation ) {

				update_post_meta( $product->get_id(), '_product_attributes', $attributes['merged'] );

				$att_var = array();
				foreach ( $attributes['merged'] as $downloaded_attribute ) {

					$attribute         = new WC_Product_Attribute();
					$existing_taxonomy = Toret_Manager_Helper::get_attribute_taxonomy_by_name( $downloaded_attribute['name'] );
					if ( $existing_taxonomy &&  !key_exists($downloaded_attribute['name'],$attributes['local']) ) {
						$attribute->set_id( $existing_taxonomy->attribute_id );
						$attribute->set_name( $downloaded_attribute['name'] );
						$attribute->set_options( $downloaded_attribute['value'] );
						$attribute->set_position( $downloaded_attribute['position'] );
						$attribute->set_visible( $downloaded_attribute['is_visible'] );
						$attribute->set_variation( $downloaded_attribute['is_variation'] );
						$att_var[] = $attribute;
					}

				}

				if ( ! empty( $att_var ) ) {
					$product->set_attributes( $att_var );
					$product->save();
				}

			}
		}

		return $product->get_id();
	}


	/**
	 * Get WC_Product by product type
	 *
	 * @param string $productType
	 *
	 * @return WC_Product_External|WC_Product_Grouped|WC_Product_Simple|WC_Product_Variable|WC_Product_Variation|null
	 */
	function get_WC_Product_by_productType( string $productType ) {
		if ( $productType == 'simple' ) {
			return new WC_Product_Simple();
		} elseif ( $productType == 'grouped' ) {
			return new WC_Product_Grouped();
		} elseif ( $productType == 'external' ) {
			return new WC_Product_External();
		} elseif ( $productType == 'variable' ) {
			return new WC_Product_Variable();
		} elseif ( $productType == 'variation' ) {
			return new WC_Product_Variation();
		} else {
			return null;
		}
	}

	/**
	 * Get product attributes
	 *
	 * @param mixed $product
	 * @param mixed $productData
	 * @param mixed $attributes
	 * @param bool $update
	 * @param mixed $existing_product_id
	 *
	 * @return array
	 */
	private function get_product_attributes( $product, $productData, $attributes, bool $update = false, $existing_product_id = null ): array {
		$attribute_array = json_decode( $attributes, true );

		$global_raw_attributes = $attribute_array['global'];
		$local_raw_attributes  = $attribute_array['local'];
		$global_attributes     = $this->get_global_product_attributes( $product, $global_raw_attributes );

		if ( $productData->productType != 'variation' ) {

			if ( is_array( $local_raw_attributes ) ) {
				return array('local'=>$local_raw_attributes,'global'=>$global_attributes,'merged' => array_merge( $global_attributes, $local_raw_attributes ));
				//return array_merge( $global_attributes, $local_raw_attributes );
			} else {
				//return $global_attributes;
				return array('local'=>[],'global'=>$global_attributes,'merged' => $global_attributes );
			}

		} else {

			$local_variation = [];
			foreach ( $local_raw_attributes as $attribute ) {
				$local_variation[ 'attribute_' . strtolower( $attribute['name'] ) ] = $attribute['value'];
			}

			foreach ( $global_attributes as $taxonomy => $global_attribute ) {
				$local_variation[ 'attribute_' . $global_attribute['name'] ] = $global_attribute['value'];
			}
			return array('local'=>$local_variation,'global'=>$global_attributes,'merged' => $global_attributes );
			//return $local_variation;
		}
	}

	/**
	 * Get global product attributes
	 *
	 * @param mixed $product
	 * @param mixed $global_attributes
	 *
	 * @return array
	 */
	function get_global_product_attributes( $product, $global_attributes ): array {
		$attribute_parser = get_option( 'toret_manager_product_attributes_parser', array() );

		/**
		 * Get taxonomies first
		 */
		$taxonomies = array_keys( $global_attributes );

		foreach ( $taxonomies as $taxonomy_internalid ) {
			Toret_Manager_Module_Product_Attribute::get_instance( $this->toret_manager )->download_product_attribute( $taxonomy_internalid );
		}

		/**
		 * Now get terms
		 */
		if ( count( $global_attributes ) > 1 ) {
			$term_internalids = call_user_func_array( 'array_merge', array_values( $global_attributes ) );
			//$term_internalids = call_user_func_array( 'array_merge', $global_attributes ); PHP < 8 ?
		} else {
			$term_internalids = reset( $global_attributes );
		}

		$global_attributes_term_ids = [];
		if ( ! empty( $term_internalids ) ) {
			foreach ( $term_internalids as $term_internalid ) {
				if ( ! empty( $term_internalid ) ) {
					//$existing_terms = Toret_Manager_Helper_Db::get_term_by_meta_value( TORET_MANAGER_ITEM_INTERNALID, $term_internalid, get_object_taxonomies( $this->module ) );
					$term_id = Toret_Manager_Module_Term::get_instance( $this->toret_manager )->notify_term_change( $term_internalid, true );
					if ( ! empty( $term_id ) && ! is_wp_error( $term_id ) ) {
						$term                         = get_term( $term_id );
						$global_attributes_term_ids[] = array(
							'taxonomy' => $term->taxonomy,
							'term_id'  => $term_id,
							'slug'     => $term->slug
						);
					}
				}
			}
		}


		/**
		 * Sort taxonomies and their terms
		 */
		$global_attributes_term_ids_sorted = Toret_Manager_Helper::group_array_by( $global_attributes_term_ids, 'taxonomy' );
		$attributes_data                   = [];
		foreach ( $global_attributes_term_ids_sorted as $taxonomy => $terms ) {
			$values = [];
			foreach ( $terms as $term_ ) {
				$values[] = $term_['slug'];
			}
			$attributes_data[] = array(
				'name'      => $taxonomy,
				'options'   => $values,
				'visible'   => 1,
				'variation' => 1,
				'slug'      => $term_['slug']
			);
		}

		$attributes = [];

		if ( sizeof( $attributes_data ) > 0 ) {
			$attributes = array();
			if ( $product->get_type() != 'variation' ) {

				foreach ( $attributes_data as $key => $attribute_array ) {
					if ( isset( $attribute_array['name'] ) && isset( $attribute_array['options'] ) ) {

						$taxonomy = wc_sanitize_taxonomy_name( $attribute_array['name'] );

						$option_term_ids = array();

						foreach ( $attribute_array['options'] as $option ) {
							if ( term_exists( $option, $taxonomy ) ) {
								wp_set_object_terms( $product->get_id(), $option, $taxonomy, true );
								$option_term_ids[] = get_term_by( 'name', $option, $taxonomy )->term_id;
							}
						}
					}

					$attributes[ $taxonomy ] = array(
						'name'         => $taxonomy,
						'value'        => $option_term_ids,
						'position'     => $key + 1,
						'is_visible'   => $attribute_array['visible'],
						'is_variation' => $attribute_array['variation'],
						'is_taxonomy'  => 1
					);
				}
			} else {

				$option_term_slug = '';
				$taxonomy         = '';

				foreach ( $attributes_data as $key => $attribute_array ) {
					if ( isset( $attribute_array['name'] ) && isset( $attribute_array['slug'] ) ) {

						$taxonomy = wc_sanitize_taxonomy_name( $attribute_array['name'] );

						$attributes[ $taxonomy ] = array(
							'name'  => $taxonomy,
							'value' => $attribute_array['slug'],
						);


					}

				}
			}

		}

		return $attributes;
	}

	/**
	 * Upload missing product if not exists
	 *
	 * @param mixed $item_id
	 * @param bool $force
	 * @param string $associated
	 * @param array $edit_args
	 *
	 * @return mixed
	 */
	function upload_missing_product( $item_id, bool $force = false, string $associated = "", array $edit_args = array() ) {
		return self::async_on_save_product( $item_id, $force, $associated, $edit_args );
	}

	/**
	 * Exclude meta from product duplicate
	 *
	 * @param mixed $exclude_meta
	 * @param mixed $existing_meta_keys
	 */
	function exclude_meta_from_duplicate( $exclude_meta, $existing_meta_keys ) {
		$exclude_meta[] = TORET_MANAGER_ITEM_INTERNALID;

		return $exclude_meta;
	}

	/**
	 * Upload product variations after product created
	 *
	 * @param mixed $item_id
	 * @param mixed $item_data
	 * @param string $module
	 * @param mixed $parentInternalID
	 */
	function upload_product_variations( $item_id, $item_data, string $module, $parentInternalID ) {

		if ( $module != $this->module ) {
			return;
		}

		$product = wc_get_product( $item_id );

		if ( $product->get_type() != 'variable' ) {
			return;
		}

		//if ( Toret_Manager_Helper_Modules::should_sync_associated( $this->module ) ) { // Do it everytime
			$variation_ids = wc_get_products( array(
				'status' => 'publish',
				'type'   => 'variation',
				'parent' => $item_id,
				'limit'  => - 1,
				'return' => 'ids',
			) );

			foreach ( $variation_ids as $variation_id ) {
				$internalID = Toret_Manager_Helper_Db::get_object_meta( $variation_id, $this->internalID_key, $this->module );
				$update     = ! empty( $internalID );
				if ( empty( $internalID ) ) {
					$internalID = Toret_Manager_Helper::generate_internal_id( $this->module );
				}

				$edit_args = array(
					'internalID'       => $internalID,
					'update'           => $update,
					'action'           => $update ? 'update' : 'new',
					'module'           => $this->module,
					'type'             => $this->module,
					'parentInternalID' => $parentInternalID,
				);

				$this->upload_missing_product( $variation_id, true, "", $edit_args );
			}
		//}
	}


	/**
	 * Get product internalID
	 *
	 * @param mixed $product_id
	 */
	public function get_product_internal_ID( $product_id ) {
		if ( empty( $product_id ) ) {
			return null;
		}

		$productInternalID = get_post_meta( $product_id, $this->internalID_key, true );

		if ( empty( $productInternalID ) ) {

			if ( Toret_Manager_Helper_Modules::should_sync_associated( 'order' ) ) {
				$productInternalID = Toret_Manager_Module_Product::get_instance( $this->toret_manager )->upload_missing_product( $product_id, true );
			}

			if ( empty( $productInternalID ) ) {
				$productInternalID = - 1;
			}

		}

		return $productInternalID;
	}

	/**
	 * Save duplicated product
	 *
	 * @param $duplicate
	 */
	function on_product_duplicate( $duplicate ) {
		$this->async_on_save_product( $duplicate->get_id() );
	}

	/**
	 * On set featured
	 *
	 * @return void
	 */
	function feature_product() {
		if ( isset( $_GET['product_id'] ) && isset( $_GET['action'] ) && $_GET['action'] == 'woocommerce_feature_product' ) {
			{
				$this->on_save_post( $_GET['product_id'], wc_get_product( $_GET['product_id'] ) );
			}
		}
	}
}