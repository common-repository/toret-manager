<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Toret_Manager_Module_Stock
{

    /**
     * Stock class instance
     *
     * @Toret_Manager_Module_Stock Toret_Manager_Module_Stock
     */
    protected static ?Toret_Manager_Module_Stock $Toret_Manager_Module_Stock_Instance = null;

    /**
     * Toret Manager slug
     *
     * @string $toret_manager
     */
    private string $toret_manager;

    /**
     * Constructor
     *
     * @param string $toret_manager
     */
    public function __construct(string $toret_manager)
    {
        $this->toret_manager = $toret_manager;

        if (Toret_Manager_Helper_Modules::is_stock_sync_enabled('upload')) {
            add_action('woocommerce_product_set_stock', array($this, 'stock_changed'));
            add_action('woocommerce_variation_set_stock', array($this, 'stock_changed'));
            add_action('woocommerce_product_quick_edit_save', array($this, 'stock_changed'));
            //add_action('woocommerce_reduce_order_stock', array($this, 'on_order_stock_change'));
            //add_action('woocommerce_restore_order_stock', array($this, 'on_order_stock_change'));
        }

        /// Upload variations
        add_action('trman_after_upload_create_item', array($this, 'change_stock_after_upload'), 10, 4);
        add_action('trman_after_upload_update_item', array($this, 'change_stock_after_upload'), 10, 4);
    }

    /**
     * Get Stock class instance
     *
     * @param string $toret_manager
     * @return Toret_Manager_Module_Stock|null
     */
    public static function get_instance(string $toret_manager): ?Toret_Manager_Module_Stock
    {
        if (null == self::$Toret_Manager_Module_Stock_Instance) {
            self::$Toret_Manager_Module_Stock_Instance = new self($toret_manager);
        }

        return self::$Toret_Manager_Module_Stock_Instance;
    }

    /**
     * Change stock after product upload
     *
     * @param mixed $item_id
     * @param array $item_data
     * @param string $module
     * @param string $internalID
     */
    function change_stock_after_upload($item_id, array $item_data, string $module, string $internalID)
    {
        if ($module == "product") {
            $this->stock_changed(wc_get_product($item_id), $internalID);
        }
    }

    /**
     * Process stock change
     *
     * @param WC_Product $product
     * @param string|null $internalID
     */
    public function stock_changed(WC_Product $product, string $internalID = null)
    {
        if (Toret_Manager_Helper::is_excluded($product->get_id(), 'product', 'product')) {
            return;
        }

        if (empty($internalID))
            $internalID = get_post_meta($product->get_id(), TORET_MANAGER_ITEM_INTERNALID, true);

        if ($product->get_manage_stock()) {
            if (!empty($internalID)) {
                $data['internalID'] = $internalID;
                $data['stockQuantity'] = $product->get_stock_quantity();
                $Toret_Manager_Api = ToretManagerApi();
                $api_response = $Toret_Manager_Api->updateData->setStock($this->toret_manager, $data);
                if ($api_response != 'none' && $api_response != '404') {
                    $log = array(
                        'module' => 'Stock',
                        'submodule' => 'Set',
                        'context' => __('Stock set', 'toret-manager'),
                        'log' => wp_json_encode($data),
                    );
                } else {
                    $log = array(
                        'type' => 3,
                        'module' => 'Stock',
                        'submodule' => 'Set',
                        'context' => __('Failed to set stock', 'toret-manager'),
                        'log' => wp_json_encode($data),
                    );
                }
                trman_log($this->toret_manager, $log);
            }
        }
    }

    /**
     * Process stock change on order
     *
     * @param WC_Order $order
     */
    function on_order_stock_change(WC_Order $order)
    {
        if (sizeof($order->get_items()) > 0) {
            foreach ($order->get_items() as $item) {
                if ($item->is_type('line_item') && ($product = $item->get_product())) {
                    self::stock_changed($product);
                }
            }
        }
    }

    /**
     * Process stock notification
     *
     * @param string $internalID
     * @param mixed $qty
     */
    public function notify_stock_change(string $internalID, $qty = null)
    {
        $existing_product = Toret_Manager_Helper_Db::get_post_by_meta_value(TORET_MANAGER_ITEM_INTERNALID, $internalID, 'product', 'product');
	    $existing_product = Toret_Manager_Module_General::additional_check_for_existing_item( $existing_product, $internalID, 'product', 'product' );

        if (!empty($existing_product)) {

            if (Toret_Manager_Helper::is_excluded($existing_product, 'product', 'product')) {
                return;
            }

            if (Toret_Manager_Helper_Modules::is_stock_sync_enabled('download')) {
                $Toret_Manager_Api = ToretManagerApi();
                $product = wc_get_product($existing_product);
                if (!empty($product)) {

                    if (!empty($qty)) {
                        $product->set_stock_quantity($qty);
                        $product->save();
                    } else {
                        $api_response = $Toret_Manager_Api->getData->getStock($this->toret_manager, $internalID);
                        if ($api_response != 'none' && $api_response != '404') {
                            $product->set_stock_quantity($api_response->item->stockQuantity);
                            $product->save();
                        }
                    }
                }
            }
        }
    }

}
