<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Toret_Manager_Module_Order extends Toret_Manager_Module_General
{

    /**
     * Order module instance
     *
     * @var Toret_Manager_Module_Order|null
     */
    protected static ?Toret_Manager_Module_Order $Toret_Manager_Module_Order_Instance = null;

    /**
     * Internal ID key
     *
     * @var string
     */
    protected string $module;

    /**
     * Constructor
     *
     * @param string $toret_manager
     */
    public function __construct(string $toret_manager)
    {
        parent::__construct($toret_manager);

        $this->internalID_key = TORET_MANAGER_ITEM_INTERNALID;
        $this->module = 'order';

        if (Toret_Manager_Helper_Modules::is_any_edit_sync_enabled($this->module)) {
            add_action('woocommerce_order_status_changed', array($this, 'on_save_post'), 99, 2);
            add_action('woocommerce_process_shop_order_meta', array($this, 'on_save_post'), 99, 2);
            add_action('async_on_save_order', array($this, 'async_on_save_order'), 99, 1);

            add_action('woocommerce_trash_order', array($this, 'on_trash_order'));
            add_action('async_on_trash_order', array($this, 'async_on_trash_order'), 99, 1);
        }

        if (Toret_Manager_Helper_Modules::is_sync_enabled($this->module, 'delete')) {
            if (Toret_Manager_HPOS_Compatibility::is_wc_hpos_enabled()) {
                add_action('woocommerce_before_delete_order', array($this, 'on_before_delete_post'), 99, 2);
            } else {
                add_action('before_delete_post', array($this, 'on_before_delete_post'), 99, 2);
            }
            add_action('async_on_delete_order', array($this, 'async_on_delete_order'), 99, 2);
        }

        // Save cart weight to order meta
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_cart_weight'));
    }

    /**
     * Sync on trash order
     *
     * @param mixed $item_id
     */
    function on_trash_order($item_id)
    {
        if (in_array($this->module, TORET_MANAGER_ASYNC_UPLOAD_TYPES)) {
            wp_schedule_single_event(time(), 'async_on_trash_order', array($item_id));
        } else {
            $this->on_save_post($item_id);
        }
    }

    /**
     * Async on trash order
     *
     * @param mixed $item_id
     */
    function async_on_trash_order($item_id)
    {
        $this->on_save_post($item_id);
    }

    /**
     * Get class instance
     *
     * @param string $toret_manager
     * @return Toret_Manager_Module_Order|null
     */
    public static function get_instance(string $toret_manager): ?Toret_Manager_Module_Order
    {
        if (null == self::$Toret_Manager_Module_Order_Instance) {
            self::$Toret_Manager_Module_Order_Instance = new self($toret_manager);
        }

        return self::$Toret_Manager_Module_Order_Instance;
    }

    /**
     * On local order delete
     *
     * @param mixed $item_id
     * @param mixed $post
     */
    public function on_before_delete_post($item_id, $post)
    {
        $module = Toret_Manager_Helper_Modules::get_module_by_post_type($this->module);

        if ($post->post_type != 'shop_order') {
            return;
        }

        if (!Toret_Manager_Helper_Modules::is_sync_enabled($module, 'delete',)) {
            return;
        }

        if (Toret_Manager_Helper::is_excluded($item_id, $module, $this->module)) {
            return;
        }

        if (in_array($this->module, TORET_MANAGER_ASYNC_UPLOAD_TYPES)) {
            wp_schedule_single_event(time(), 'async_on_delete_order', array($item_id, $this->module));
        } else {
            $this->async_on_delete_order($item_id, $this->module);
        }
    }


    /**
     * Async on order delete
     *
     * @param mixed $item_id
     * @param mixed $module
     */
    function async_on_delete_order($item_id, $module)
    {
        Toret_Manager_Module_General::process_delete_post($item_id, $module, $this->module);
    }

    /**
     * On local new order or update
     *
     * @param mixed $item_id
     * @throws Exception
     */
    public function on_save_post($item_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

	    if(defined('REST_REQUEST')){
		    return;
	    }

        if (wp_is_post_revision($item_id) || wp_is_post_autosave($item_id)) {
            return;
        }

        if (!Toret_Manager_Helper_Modules::is_any_edit_sync_enabled($this->module)) {
            return;
        }

        if (Toret_Manager_Helper::is_excluded($item_id, $this->module, $this->module)) {
            return;
        }

		if(isset($_POST['trman_run']) && $_POST['trman_run'] == 'queue'){
			return;
		}

	    if ( defined( 'DOING_CRON' ) ) {
		    return;
	    }

	    $order = wc_get_order($item_id);

		if (empty($order)) {
			return;
		}

        if (in_array($order->get_status(), array('auto-draft'))) {
            return;
        }

        if (in_array($this->module, TORET_MANAGER_ASYNC_UPLOAD_TYPES)) {
            wp_schedule_single_event(time(), 'async_on_save_order', array($item_id));
        } else {
            $this->async_on_save_order($item_id);
        }
    }

    /**
     * Async on save order
     *
     * @param mixed $item_id
     * @param bool $force
     * @param array $edit_args
     * @return mixed|null
     * @throws Exception
     */
    function async_on_save_order($item_id, bool $force = false, array $edit_args = array())
    {
        $order = wc_get_order($item_id);

        $internalID = Toret_Manager_Helper_Db::get_object_meta($item_id, $this->internalID_key, $this->module);
        $update = !empty($internalID);
        if (empty($internalID)) {
            $internalID = Toret_Manager_Helper::generate_internal_id($this->module);
        }

        $edit_args['internalID'] = $internalID;
        $edit_args['update'] = $update;
        $edit_args['action'] = $update ? 'update' : 'new';
        $edit_args['module'] = $this->module;
        $edit_args['type'] = $this->module;

        $nonce = wp_create_nonce('trman_edit_args_' . $item_id);

        $edit_args = Toret_Manager_Helper::edit_args_modification($edit_args, $item_id, $nonce);

        $data = self::itemDataArray($order, $edit_args);
        return Toret_Manager_Module_General::process_save_item_adv($item_id, $data, $force, $edit_args);
    }

    /**
     * Get order data for upload
     *
     * @param WC_Order $order
     * @param array $edit_args
     * @return array
     * @throws Exception
     */
    public function itemDataArray(WC_Order $order, array $edit_args = array()): array
    {
        $update = $edit_args['update'];

        $parentInternalID = $this->get_parent_internal_ID($order->get_parent_id(), $this->module, $this->module);
        $customerInternalID = Toret_Manager_Module_User::get_user_internal_ID($order->get_customer_id(), "order");

        $meta = [];
        foreach ($order->get_meta_data() as $object) {
            $object_array = array_values((array)$object);
            foreach ($object_array as $object_item) {
                $meta[$object_item['key']] = $object_item['value'];
            }
        }

        $meta = $this->reaarange_meta_array($meta, $this->module);
        $meta = (new Toret_Manager_Plugins_Support)->add_packeta_data_to_order($order->get_id(), $meta);

        $data = array(
            'orderID' => (string)$order->get_id(),
            'orderNumber' => $order->get_order_number(),
            'orderTitle' => $order->get_title(),
            'orderStatus' => $order->get_status(),
            'parentID' => (string)$order->get_parent_id(),
            'parentInternalID ' => (string)$parentInternalID,
            'orderCustomerID' => (string)$order->get_customer_id(),
            'customerInternalID' => (string)$customerInternalID,
            'orderDownloadPermission' => $order->get_download_permissions_granted() ? 'yes' : 'no',
            'orderStockReduced' => $order->get_order_stock_reduced() ? 'yes' : 'no',
            'orderBillingCountry' => $order->get_billing_country(),
            'orderShippingCountry' => $order->get_shipping_country(),
            'orderCurrency' => $order->get_currency(),
            'orderCartDiscount' => $order->get_discount_total(),
            'orderCartDiscountTax' => $order->get_discount_tax(),
            'orderShipping' => $order->get_shipping_total(),
            'orderShippingTax' => $order->get_shipping_tax(),
            'orderTax' => $order->get_cart_tax(),
            'orderTotal' => $order->get_total(),
            'priceIncludedTax' => $order->get_prices_include_tax() ? 'yes' : 'no',
            'billingFirstName' => $order->get_billing_first_name(),
            'billingLastName' => $order->get_billing_last_name(),
            'billingAddress' => $order->get_billing_address_1(),
            'billingAddress2' => $order->get_billing_address_2(),
            'billingCity' => $order->get_billing_city(),
            'billingZip' => $order->get_billing_postcode(),
            'billingCountry' => $order->get_billing_country(),
            'billingEmail' => $order->get_billing_email(),
            'billingPhone' => $order->get_billing_phone(),
            'shippingFirstName' => $order->get_shipping_first_name(),
            'shippingLastName' => $order->get_shipping_last_name(),
            'shippingAddress' => $order->get_shipping_address_1(),
            'shippingAddress2' => $order->get_shipping_address_2(),
            'shippingCity' => $order->get_shipping_city(),
            'shippingZip' => $order->get_shipping_postcode(),
            'shippingCountry' => $order->get_shipping_country(),
            'shippingEmail' => $order->get_billing_email(),
            'shippingPhone' => $order->get_billing_phone(),
            'paymentMethod' => $order->get_payment_method(),
            'paymentMethodTitle' => $order->get_payment_method_title(),
            'meta' => wp_json_encode($meta),
            'editUrl' => esc_url($order->get_edit_order_url()),
            'shippingMethod' => wp_json_encode(self::get_shipping_lines($order, $order->get_items('shipping'))),
            'usedCoupons' => wp_json_encode(self::get_coupon_lines($order, $order->get_items('coupon'))),
            'items' => wp_json_encode(self::get_item_lines($order, $order->get_items())),
            'fees' => wp_json_encode(self::get_fee_lines($order, $order->get_items('fee'))),
            'customerNote' => $order->get_customer_note(),
            'weight' => (float)Toret_Manager_HPOS_Compatibility::get_order_meta($order->get_id(), '_trman_cart_weight', true, 0)
        );

        $orderCreatedDate = Toret_Manager_DateTime::format_date_for_api($order->get_date_created(), false);

        if (!empty($orderCreatedDate)) {
            $data['orderCreatedDate'] = $orderCreatedDate;
        }

        $orderEditedDate = Toret_Manager_DateTime::format_date_for_api($order->get_date_modified(), false);

        if (!empty($orderEditedDate)) {
            $data['orderEditedDate'] = $orderEditedDate;
        }

        $order_notes = wc_get_order_notes([
            'order_id' => $order->get_id(),
        ]);

        $comment_ids = $this->get_order_notes_internal_ids($order_notes, $edit_args);
        if (!empty($comment_ids)) {
            $data['orderComments'] = wp_json_encode($comment_ids);
        }

        return apply_filters('toret_manager_sent_' . $this->module . '_data', $data, $order, $update);
    }


    /**
     * Save order from notify
     *
     * @param mixed $data
     * @param mixed $data_to_be_synchronized
     * @param mixed $existing_id
     * @param bool $update
     * @param bool $markSynced
     * @return int|null
     * @throws WC_Data_Exception
     */
    function save_item($data, $data_to_be_synchronized, $existing_id, bool $update = false, bool $markSynced = false): ?int
    {
        if ($update && !empty($existing_id)) {

            $order = wc_get_order($existing_id);

        } else {

            $order = new WC_Order();

        }

        if (empty($order)) {
            $log = array(
                'type' => 3,
                'module' => ucfirst($this->module),
                'submodule' => 'Notification - ' . ($update ? 'Update' : 'Created'),
                'context' => ($update ? __('Failed to update item', 'toret-manager') : __('Failed to create item', 'toret-manager')),
                'log' => wp_json_encode(array('Local ID' => $existing_id, 'API internal ID' => $data->internalID, 'Error' => 'Empty order object')),
            );
            trman_log($this->toret_manager, $log);
            return null;
        }

        if (!empty($data->parentInternalID) && $data->parentInternalID != -1) {
            $parent_id = Toret_Manager_Module_General::get_associted_local_id($data->parentInternalID, $this->module, $this->module, $this->module, true, true);
        }

        if (!empty($data->customerInternalID) && $data->customerInternalID != -1) {
            $customerID = Toret_Manager_Module_General::get_associted_local_id($data->customerInternalID, $this->module, 'user', 'user', false, true);
        }

        $needs_update = false;
        foreach ($data as $property => $item) {
            if (in_array($property, $data_to_be_synchronized)) {

                $filter = apply_filters('toret_manager_order_notified_should_process_item', false, $item, $property, $data);
                if (!empty($filter)) {
                    do_action('toret_manager_order_notified_process_item', $item, $property, $data);
                    continue;
                }

                if ($property == 'orderCreatedDate') {
                    try {
                        $order->set_date_created($item);
                    } catch (WC_Data_Exception $e) {
                        $needs_update = true;
                    }
                } else if ($property == 'orderEditedDate') {
                    try {
                        $order->set_date_modified($item);
                        $needs_update = true;
                    } catch (WC_Data_Exception $e) {
                    }
                } else if ($property == 'orderStatus') {
                    $order->set_status($item, 'Plugin name:  ');
                    $needs_update = true;
                } else if ($property == 'parentID' && !empty($parent_id)) {
                    try {
                        $order->set_parent_id($parent_id);
                        $needs_update = true;
                    } catch (WC_Data_Exception $e) {
                    }
                } else if ($property == 'customerInternalID' && !empty($customerID)) {
                    try {
                        $order->set_customer_id($customerID);
                        $needs_update = true;
                    } catch (WC_Data_Exception $e) {
                    }
                } else if ($property == 'orderDownloadPermission') {
                    $order->set_download_permissions_granted($item == 'yes');
                    $needs_update = true;
                } else if ($property == 'orderStockReduced') {
                    $order->set_order_stock_reduced($item == 'yes');
                    $needs_update = true;
                } else if ($property == 'orderBillingCountry') {
                    try {
                        $order->set_billing_country($item);
                        $needs_update = true;
                    } catch (WC_Data_Exception $e) {
                    }
                } else if ($property == 'orderShippingCountry') {
                    try {
                        $order->set_shipping_country($item);
                        $needs_update = true;
                    } catch (WC_Data_Exception $e) {
                    }
                } else if ($property == 'orderCurrency') {
                    try {
                        $order->set_currency($item);
                        $needs_update = true;
                    } catch (WC_Data_Exception $e) {
                    }
                } else if ($property == 'orderCartDiscount') {
                    try {
                        $order->set_discount_total($item);
                        $needs_update = true;
                    } catch (WC_Data_Exception $e) {
                    }
                } else if ($property == 'orderCartDiscountTax') {
                    try {
                        $order->set_discount_tax($item);
                        $needs_update = true;
                    } catch (WC_Data_Exception $e) {
                    }
                } else if ($property == 'orderShipping') {
                    try {
                        $order->set_shipping_total($item);
                    } catch (WC_Data_Exception $e) {
                    }
                    $needs_update = true;
                } else if ($property == 'orderShippingTax') {
                    $order->set_shipping_tax($item);
                    $needs_update = true;
                } else if ($property == 'orderTax') {
                    $order->set_cart_tax($item);
                    $needs_update = true;
                } else if ($property == 'orderTotal') {
                    $order->set_total($item);
                    $needs_update = true;
                } else if ($property == 'priceIncludedTax') {
                    $order->set_prices_include_tax($item == 'yes');
                    $needs_update = true;
                } else if ($property == 'billingFirstName') {
                    $order->set_billing_first_name($item);
                    $needs_update = true;
                } else if ($property == 'billingLastName') {
                    $order->set_billing_last_name($item);
                    $needs_update = true;
                } else if ($property == 'billingAddress') {
                    $order->set_billing_address_1($item);
                    $needs_update = true;
                } else if ($property == 'billingAddress2') {
                    $order->set_billing_address_2($item);
                    $needs_update = true;
                } else if ($property == 'billingCity') {
                    $order->set_billing_city($item);
                    $needs_update = true;
                } else if ($property == 'billingZip') {
                    $order->set_billing_postcode($item);
                    $needs_update = true;
                } else if ($property == 'billingCountry') {
                    $order->set_billing_country($item);
                    $needs_update = true;
                } else if ($property == 'billingEmail') {
                    $order->set_billing_email($item);
                    $needs_update = true;
                } else if ($property == 'billingPhone') {
                    $order->set_billing_phone($item);
                    $needs_update = true;
                } else if ($property == 'shippingFirstName') {
                    $order->set_shipping_first_name($item);
                    $needs_update = true;
                } else if ($property == 'shippingLastName') {
                    $order->set_shipping_last_name($item);
                    $needs_update = true;
                } else if ($property == 'shippingAddress') {
                    $order->set_shipping_address_1($item);
                    $needs_update = true;
                } else if ($property == 'shippingAddress2') {
                    $order->set_shipping_address_2($item);
                    $needs_update = true;
                } else if ($property == 'shippingCity') {
                    $order->set_shipping_city($item);
                    $needs_update = true;
                } else if ($property == 'shippingZip') {
                    $order->set_shipping_postcode($item);
                    $needs_update = true;
                } else if ($property == 'shippingCountry') {
                    try {
                        $order->set_shipping_country($item);
                    } catch (WC_Data_Exception $e) {
                    }
                    $needs_update = true;
                } else if ($property == 'shippingPhone') {
                    try {
                        $order->set_shipping_phone($item);
                    } catch (WC_Data_Exception $e) {
                    }
                    $needs_update = true;
                } else if ($property == 'paymentMethod') {
                    try {
                        $order->set_payment_method($item);
                    } catch (WC_Data_Exception $e) {
                    }
                    $needs_update = true;
                } else if ($property == 'paymentMethodTitle') {
                    try {
                        $order->set_payment_method_title($item);
                    } catch (WC_Data_Exception $e) {
                    }
                    $needs_update = true;
                } else if ($property == 'customerNote') {
                    try {
                        $order->set_customer_note($item);
                    } catch (WC_Data_Exception $e) {
                    }
                    $needs_update = true;
                }
            }
        }

        if ($needs_update) {
            $order->save();
        }

        if (in_array('meta', $data_to_be_synchronized)) {
            $meta = json_decode($data->meta, true);
            $order->set_meta_data($meta);
        }

        if (in_array('shippingMethod', $data_to_be_synchronized)) {
            $shippingMethods = json_decode($data->shippingMethod, true);
            self::clear_order_lines($order, 'shipping');
            if (!empty($shippingMethods)) {
                foreach ($shippingMethods as $shippingMethod) {
                    $shippingMethod = (object)$shippingMethod;
                    $item = new WC_Order_Item_Shipping();
                    $item->set_method_title($shippingMethod->method_title);
                    $item->set_method_id($shippingMethod->method_id);
                    $item->set_total($shippingMethod->total);
                    $item->set_taxes($shippingMethod->taxes);
                    $item->set_meta_data($shippingMethod->meta_data);
                    $order->add_item($item);
                    $order->calculate_totals();
                }
            }
        }

        if (in_array('usedCoupons', $data_to_be_synchronized)) {
            $usedCoupons = json_decode($data->usedCoupons, true);
            self::clear_order_lines($order, 'coupon');
            if (!empty($usedCoupons)) {
                foreach ($usedCoupons as $usedCoupon) {
                    $usedCoupon = (object)$usedCoupon;
                    $order->apply_coupon($usedCoupon->code);
                }
            }
        }

        if (in_array('fees', $data_to_be_synchronized)) {
            $fees = json_decode($data->fees, true);
            self::clear_order_lines($order, 'fee');
            if (!empty($fees)) {
                foreach ($fees as $fee) {
                    $fee = (object)$fee;
                    $item = new WC_Order_Item_Fee();
                    $item->set_name($fee->name);
                    $item->set_amount($fee->total);
                    $item->set_total($fee->total);
                    $item->set_taxes($fee->taxes);
                    $item->set_tax_class($fee->tax_class);
                    $item->set_tax_status($fee->tax_status);
                    $item->set_meta_data($fee->meta_data);
                    $order->add_item($item);
                    $order->calculate_totals();
                }
            }
        }

        if (in_array('items', $data_to_be_synchronized)) {
            $items = json_decode($data->items, true);
            self::clear_order_lines($order, 'line_item');

            if (!empty($items)) {
                foreach ($items as $itemLine) {
                    $itemLine = (object)$itemLine;

                    $productID = self::get_missing_order_item($itemLine->productInternalID);
                    $variationID = self::get_missing_order_item($itemLine->variationInternalID);

                    $item = new WC_Order_Item_Product();
                    $item->set_name($itemLine->name);

                    if (!empty($productID))
                        $item->set_product_id($productID);

                    if (!empty($variationID))
                        $item->set_variation_id($variationID);

                    $item->set_quantity($itemLine->quantity);
                    $item->set_tax_class($itemLine->tax_class);
                    $item->set_subtotal($itemLine->subtotal);
                    $item->set_subtotal_tax($itemLine->subtotal_tax);
                    $item->set_total($itemLine->total);
                    $item->set_total_tax($itemLine->total_tax);
                    $item->set_taxes($itemLine->taxes);
                    $item->set_meta_data($itemLine->meta_data);
                    $order->add_item($item);
                    $order->calculate_totals();
                }
            }
        }

        if (in_array('orderComments', $data_to_be_synchronized)) {
            $internal_ids = json_decode($data->orderComments, true);
            Toret_Manager_Module_Review::delete_comments($order->get_id(), 'order_note');
            if (!empty($internal_ids)) {
                foreach ($internal_ids as $internal_id) {
                    Toret_Manager_Module_Review::get_instance($this->toret_manager)->get_associated_order_note($order->get_id(), $internal_id);
                }
            }
        }

        if (!$update) {
            Toret_Manager_HPOS_Compatibility::update_order_meta($order->get_id(), $this->internalID_key, $data->internalID);
        }

        if ($markSynced) {
            Toret_Manager_HPOS_Compatibility::update_order_meta($order->get_id(), TORET_MANAGER_ASSOCIATIVE_SYNC, '1');
        }

        $log = array(
            'type' => 1,
            'module' => ucfirst($this->module),
            'submodule' => 'Notification - ' . ($update ? 'Update' : 'Created'),
            'context' => ($update ? __('Item updated', 'toret-manager') : __('Item created', 'toret-manager')),
            'log' => wp_json_encode(array('Local ID' => $order->get_id(), 'API internal ID' => $data->internalID)),
        );
        trman_log($this->toret_manager, $log);

        return $order->get_id();
    }

    /**
     * Get order item lines
     *
     * @param WC_Order $order
     * @param array $items
     * @return mixed|null
     */
    function get_item_lines(WC_Order $order, array $items)
    {
        $item_lines = [];

        foreach ($items as $order_item_id => $line_item) {

            $productInternalID = Toret_Manager_Module_Product::get_instance($this->toret_manager)->get_product_internal_ID($line_item->get_product_id());
            $variationInternalID = Toret_Manager_Module_Product::get_instance($this->toret_manager)->get_product_internal_ID($line_item->get_variation_id());

            $item_line_date = array(
                'id' => $order_item_id,
                'productID' => $line_item->get_product_id(),
                'productInternalID' => (string)$productInternalID,
                'variationID' => $line_item->get_variation_id(),
                'variationInternalID' => (string)$variationInternalID,
                'name' => $line_item->get_name(),
                'quantity' => $line_item->get_quantity(),
                'tax_class' => $line_item->get_tax_class(),
                'subtotal' => $line_item->get_subtotal(),
                'subtotal_tax' => $line_item->get_subtotal_tax(),
                'total' => $line_item->get_total(),
                'total_tax' => $line_item->get_total_tax(),
                'taxes' => $line_item->get_taxes(),
                'meta_data' => $line_item->get_meta_data(),
            );

            $product = wc_get_product($line_item->get_product_id());

            if (!empty($product)) {
                $item_line_date['sku'] = $product->get_sku();
            }

            $item_lines[] = $item_line_date;

        }

        return apply_filters('toret_manager_sent_' . $this->module . '_item_lines', $item_lines, $order);
    }

    /**
     * Get order shipping lines
     *
     * @param WC_Order $order
     * @param array $items
     * @return mixed|null
     */
    function get_shipping_lines(WC_Order $order, array $items)
    {

        $shipping_lines = [];

        foreach ($items as $shipping_line) {

            $shipping_lines[] = array(
                'id' => $shipping_line->get_id(),
                'method_title' => $shipping_line->get_method_title(),
                'method_id' => $shipping_line->get_method_id(),
                'total' => $shipping_line->get_total(),
                'total_tax' => $shipping_line->get_total_tax(),
                'taxes' => $shipping_line->get_taxes(),
                'meta_data' => $shipping_line->get_meta_data()
            );

        }

        return apply_filters('toret_manager_sent_' . $this->module . '_shipping_lines', $shipping_lines, $order);
    }

    /**
     * Get order fee lines
     *
     * @param WC_Order $order
     * @param array $items
     * @return mixed|null
     */
    function get_fee_lines(WC_Order $order, array $items)
    {
        $fee_lines = [];

        foreach ($items as $fee_line) {

            $fee_lines[] = array(
                'id' => $fee_line->get_id(),
                'name' => $fee_line->get_name(),
                'tax_class' => $fee_line->get_tax_class(),
                'tax_status' => $fee_line->get_tax_status(),
                'total' => $fee_line->get_total(),
                'total_tax' => $fee_line->get_total_tax(),
                'taxes' => $fee_line->get_taxes(),
                'meta_data' => $fee_line->get_meta_data()
            );

        }

        return apply_filters('toret_manager_sent_' . $this->module . '_fee_lines', $fee_lines, $order);
    }

    /**
     * Get order coupon lines
     *
     * @param WC_Order $order
     * @param array $items
     * @return mixed|null
     */
    function get_coupon_lines(WC_Order $order, array $items)
    {
        $coupon_lines = [];

        foreach ($items as $coupon_line) {

            $coupon_lines[] = array(
                'id' => $coupon_line->get_id(),
                'code' => $coupon_line->get_code(),
                'discount' => $coupon_line->get_discount(),
                'discount_tax' => $coupon_line->get_discount_tax(),
                'meta_data' => $coupon_line->get_meta_data()
            );

        }

        return apply_filters('toret_manager_sent_' . $this->module . '_coupon_lines', $coupon_lines, $order);
    }

    /**
     * Get order notes internal IDS for upload
     *
     * @param array $customer_order_notes
     * @param array $edit_args
     * @return array
     */
    private function get_order_notes_internal_ids(array $customer_order_notes, array $edit_args): array
    {
        $internalIDs = [];

        foreach ($customer_order_notes as $customer_order_note) {
            $internalid = get_comment_meta($customer_order_note->id, TORET_MANAGER_ITEM_INTERNALID, true);
            if (!empty($internalid)) {
                $internalIDs[] = $internalid;
            } else {
                $internalID = Toret_Manager_Module_Review::get_instance($this->toret_manager)->upload_missing_comment($customer_order_note->id, true, $edit_args);
                if (!empty($internalID)) {
                    $internalIDs[] = $internalID;
                }
            }
        }

        return $internalIDs;
    }

    /**
     * Clear order lines
     *
     * @param WC_Order $order
     * @param string $type
     */
    private function clear_order_lines(WC_Order $order, string $type)
    {
        $items = (array)$order->get_items($type);

        if (sizeof($items) > 0) {
            foreach ($items as $item_id => $item) {
                $order->remove_item($item_id);
            }
            $order->calculate_totals();
        }
    }

    /**
     * Upload missing order if not exists
     *
     * @param mixed $item_id
     * @throws Exception
     */
    function upload_missing_order($item_id, $force = false)
    {
        return self::async_on_save_order($item_id, $force);
    }

    /**
     * Get missing order item (product)
     *
     * @param mixed $internalID
     */
    private function get_missing_order_item($internalID)
    {
        return Toret_Manager_Module_General::get_associted_local_id($internalID, $this->module, 'product', 'product', false, true);
    }

    /**
     * Save cart weight
     *
     * @param mixed $order_id
     */
    function save_cart_weight($order_id)
    {
        $weight = WC()->cart->get_cart_contents_weight();
        $multiplier = (new Toret_Manager_Helper())->get_weight_multiplier();
        Toret_Manager_HPOS_Compatibility::update_order_meta($order_id, '_trman_cart_weight', $weight / $multiplier);
    }


}
