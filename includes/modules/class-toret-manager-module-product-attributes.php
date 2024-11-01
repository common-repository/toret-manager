<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Toret_Manager_Module_Product_Attribute
{

    /**
     * Product attribute instance
     *
     * @var Toret_Manager_Module_Product_Attribute|null
     */
    protected static ?Toret_Manager_Module_Product_Attribute $Toret_Manager_Module_Product_Attribute_Instance = null;

    /**
     * Toret Manager slug
     *
     * @var string
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

        if (Toret_Manager_Helper_Modules::is_any_edit_sync_enabled('product')) {
            add_action('woocommerce_attribute_added', array($this, 'on_save_attribute'), 10, 2);
            add_action('woocommerce_attribute_updated', array($this, 'on_save_attribute'), 10, 2);
        }

        if (Toret_Manager_Helper_Modules::is_sync_enabled('product', 'delete')) {
            add_action('woocommerce_before_attribute_delete', array($this, 'on_attribute_delete'), 10, 3);
        }

        add_action('woocommerce_before_attribute_delete', array($this, 'maybe_on_attribute_delete'), 10, 3);
    }

    /**
     * Get class instance
     *
     * @param string $toret_manager
     * @return Toret_Manager_Module_Product_Attribute|null
     */
    public static function get_instance(string $toret_manager): ?Toret_Manager_Module_Product_Attribute
    {
        if (null == self::$Toret_Manager_Module_Product_Attribute_Instance) {
            self::$Toret_Manager_Module_Product_Attribute_Instance = new self($toret_manager);
        }

        return self::$Toret_Manager_Module_Product_Attribute_Instance;
    }

    /**
     * Process save attribute hook
     *
     * @param mixed $id
     * @param array $attribute
     * @return string|void|null
     */
    public function on_save_attribute($id, array $attribute)
    {
	    if(isset($_POST['trman_run']) && $_POST['trman_run'] == 'queue'){
		    return;
	    }

        $attribute_parser = get_option('toret_manager_product_attributes_parser', array());
        $internalID = $attribute_parser[$attribute['attribute_name']]['internalid'] ?? "";

        $edit_args = array('categoryID' => $id);

        $found_localid = Toret_Manager_Helper::search_in_multidim_array($attribute_parser, 'localid', $id);
        if (isset($found_localid['localid'])) {
            $internalID = $found_localid['internalid'];
            $edit_args['internalID'] = $internalID;
        }

        return self::upload_product_attribute($attribute['attribute_name'], $internalID, $attribute, $edit_args);
    }

    /**
     * Process delete attribute hook
     *
     * @param mixed $id
     * @param string $attribute_name
     * @param string $taxonomy
     */
    public function on_attribute_delete($id, string $attribute_name, string $taxonomy)
    {
        $internalID = $this->remove_attribute_from_parser($taxonomy);

        if (!empty($internalID)) {
            $data['internalID'] = $internalID;
            $Toret_Manager_Api = ToretManagerApi();
            $Toret_Manager_Api->deleteData->deleteItem($this->toret_manager, $data, 'product_attribute');
            do_action('toret_manager_product_attribute_deleted', $data);
        }
    }

    /**
     * Remove attribute from local parser
     *
     * @param string $taxonomy
     * @return mixed|string
     */
    function remove_attribute_from_parser(string $taxonomy)
    {
        $taxonomy = preg_replace('/^pa\_/', '', wc_sanitize_taxonomy_name($taxonomy));
        $attribute_parser = get_option('toret_manager_product_attributes_parser', array());
        $internalID = $attribute_parser[$taxonomy]['internalid'] ?? "";
        if (!empty($internalID)) {
            unset($attribute_parser[$taxonomy]);
            update_option('toret_manager_product_attributes_parser', $attribute_parser);
        }

        return $internalID;
    }

    /**
     * Delele attribute if needed
     *
     * @param mixed $id
     * @param mixed $attribute_name
     * @param string $taxonomy
     */
    public function maybe_on_attribute_delete($id, $attribute_name, string $taxonomy)
    {
        $this->remove_attribute_from_parser($taxonomy);
    }

    /**
     * Create attribute in API
     *
     * @param string $taxonomy
     * @param mixed $taxonomy_data
     * @param array $edit_args
     * @return string|null
     */
    function create_api_product_attribute(string $taxonomy, $taxonomy_data = null, array $edit_args = []): ?string
    {
        $attribute_parser = get_option('toret_manager_product_attributes_parser', array());
        $Toret_Manager_Api = ToretManagerApi();
        $data = Toret_Manager_Module_Product_Attribute::get_instance($this->toret_manager)->transform_wc_attribute_to_api_term(!empty($taxonomy_data) ? $taxonomy_data : Toret_Manager_Helper::get_attribute_taxonomy_by_name($taxonomy), $edit_args);
        $data['internalID'] = Toret_Manager_Helper::generate_internal_id('product_attribute');
        $create = $Toret_Manager_Api->createData->createItem($this->toret_manager, $data, 'product_attribute');

        if ($create != 'none' && $create != '404') {
            $attribute_parser[$data['slug']] = array(
                'internalid' => $data['internalID'],
                'localid' => $data['categoryID']
            );
            update_option('toret_manager_product_attributes_parser', $attribute_parser);

            $log = array(
                'module' => ucfirst('product attribute'),
                'submodule' => 'Created',
                'context' => __('Item created', 'toret-manager'),
                'log' => wp_json_encode(array('Local ID' => $data['categoryID'], 'API internal ID' => $data['internalID'])),
            );
            trman_log($this->toret_manager, $log);

            do_action('toret_manager_product_attribute_created', $data);

            return $data['internalID'];
        }

        $log = array(
            'module' => ucfirst('product attribute'),
            'submodule' => 'Created',
            'context' => __('Failed to create item', 'toret-manager'),
            'log' => wp_json_encode(array('Local ID' => $data['categoryID'], 'API internal ID' => $data['internalID'])),
        );
        trman_log($this->toret_manager, $log);

        do_action('toret_manager_product_attribute_create_failed', $data);

        return null;
    }

    /**
     * Update attribute in API
     *
     * @param string $taxonomy
     * @param mixed $taxonomy_data
     * @param array $edit_args
     * @return string|null
     */
    function update_api_product_attribute(string $taxonomy, $taxonomy_data = null, array $edit_args = []): ?string
    {
        $Toret_Manager_Api = ToretManagerApi();
        $data = Toret_Manager_Module_Product_Attribute::get_instance($this->toret_manager)->transform_wc_attribute_to_api_term(!empty($taxonomy_data) ? $taxonomy_data : Toret_Manager_Helper::get_attribute_taxonomy_by_name($taxonomy), $edit_args);
        $data['internalID'] = $edit_args['internalID'];
        $updated = $Toret_Manager_Api->updateData->updateItem($this->toret_manager, $data, 'product_attribute');

        if ($updated != 'none' && $updated != '404') {

            $log = array(
                'module' => ucfirst('product attribute'),
                'submodule' => 'Update',
                'context' => __('Item created', 'toret-manager'),
                'log' => wp_json_encode(array('Local ID' => $data['categoryID'], 'API internal ID' => $data['internalID'])),
            );
            trman_log($this->toret_manager, $log);

            do_action('toret_manager_product_attribute_updated', $data);

            return $data['internalID'];
        }

        $log = array(
            'module' => ucfirst('product attribute'),
            'submodule' => 'Created',
            'context' => __('Failed to create item', 'toret-manager'),
            'log' => wp_json_encode(array('Local ID' => $data['categoryID'], 'API internal ID' => $data['internalID'])),
        );
        trman_log($this->toret_manager, $log);

        do_action('toret_manager_product_attribute_update_failed', $data);

        return null;
    }

    /**
     * Check if taxonomy has internalID in local parser
     *
     * @param string $taxonomy
     * @return mixed|null
     */
    function check_if_taxonomy_in_parser(string $taxonomy)
    {
        $attribute_parser = get_option('toret_manager_product_attributes_parser', array());
        if (!isset($attribute_parser[str_replace('pa_', '', $taxonomy)])) {
            return null;
        } else {
            if (isset($attribute_parser[str_replace('pa_', '', $taxonomy)]['internalid'])) {
                return $attribute_parser[str_replace('pa_', '', $taxonomy)]['internalid'];
            } else {
                return null;
            }
        }
    }


    /**
     * Transform WC attribute to API term structure
     *
     * @param mixed $wc_data
     * @param array $edit_args
     * @return array
     */
    function transform_wc_attribute_to_api_term($wc_data, array $edit_args = []): array
    {
        if (!is_object($wc_data)) {
            $wc_data = (object)$wc_data;
        }

        if (property_exists($wc_data, 'attribute_id')) {
            $id = $wc_data->attribute_id;
        } elseif (property_exists($wc_data, 'id')) {
            $id = $wc_data->id;
        }

        if (isset($edit_args['categoryID'])) {
            $id = $edit_args['categoryID'];
        }

        return [
            'categoryID' => (int)$id,
            'parentID' => 0,
            'parentInternalID' => "-1",
            'title' => $wc_data->attribute_label,
            'type' => 'product_attribute',
            'slug' => $wc_data->attribute_name,
            'editUrl' => esc_url(admin_url(add_query_arg('edit', $id, 'edit.php?post_type=product&amp;page=product_attributes'))),
            'description' => "",
            'taxonomyInternalID' => "",
        ];
    }

    /**
     * Prepare attribute data for API
     *
     * @param mixed $object
     * @param bool $update
     * @return mixed|null
     */
    public function attributeDataArray($object, bool $update = false)
    {
        $data_to_be_synchronized = Toret_Manager_Helper_Modules::data_to_be_sync('product_attribute', 'term', ($update ? 'update' : 'new'), Toret_Manager_Helper_Modules::get_mandatory_items('term'));
        $data = $this->transform_wc_attribute_to_api_term(!is_object($object) ? (object)$object : $object);
        foreach (array_keys($data) as $key) {
            if (!in_array($key, $data_to_be_synchronized)) {
                unset($data[$key]);
            }
        }

        return apply_filters('toret_manager_sent_product_attribute_data', $data, $object, $update);
    }

    /**
     * Notify product attribute delete
     *
     * @param mixed $internalID
     */
    function notify_product_attribute_delete($internalID)
    {
        if (Toret_Manager_Helper_Modules::is_sync_enabled('product', 'delete', 'download')) {
            $attribute_parser = get_option('toret_manager_product_attributes_parser', array());
            if (in_array($internalID, array_column($attribute_parser, 'internalid'))) {
                $existing_id = Toret_Manager_Helper::get_local_taxonomy($internalID, 'id');

                if (!empty($existing_id)) {
                    $deleted = wc_delete_attribute($existing_id);

                    if ($deleted) {
                        $log = array(
                            'type' => 1,
                            'module' => ucfirst('product attribute'),
                            'submodule' => 'Delete',
                            'context' => __('Notification - local item deleted', 'toret-manager'),
                            'log' => wp_json_encode(array('Local ID' => $existing_id, 'API internal ID' => $internalID)),
                        );
                    } else {
                        $log = array(
                            'type' => 3,
                            'module' => ucfirst('product attribute'),
                            'submodule' => 'Delete',
                            'context' => __('Notification - failed to delete local item', 'toret-manager'),
                            'log' => wp_json_encode(array('Local ID' => $existing_id, 'API internal ID' => $internalID, 'Error' => 'Delete failed')),
                        );
                    }
                } else {
                    $log = array(
                        'type' => 3,
                        'module' => ucfirst('product attribute'),
                        'submodule' => 'Delete',
                        'context' => __('Notification - failed to delete local item', 'toret-manager'),
                        'log' => wp_json_encode(array('Local ID' => '-', 'API internal ID' => $internalID, 'Error' => 'Local ID not found')),
                    );
                }
                trman_log($this->toret_manager, $log);
            }
        }
    }

    /**
     * Notify product attribute change
     *
     * @param mixed $internalID
     * @param bool $force
     * @param mixed $productTermData
     * @return mixed|null
     */
    public function notify_product_attribute_change($internalID, bool $force = false, $productTermData = null)
    {
        return $this->download_product_attribute($internalID, $productTermData, $force);
    }

    /**
     * Save product attribute
     *
     * @param mixed $productTermData
     * @param mixed $existing_taxonomy
     * @param bool $update
     * @param mixed $internalid
     * @param bool $force
     * @return mixed|null
     */
    function saveProductAttribute($productTermData, $existing_taxonomy, bool $update, $internalid, bool $force = false)
    {
        $data = array(
            'name' => $productTermData->title,
            'slug' => $productTermData->slug,
        );

        if ($update && !empty($existing_taxonomy)) {

            if (Toret_Manager_Helper_Modules::is_sync_enabled('product', 'update', 'download') || $force) {

                wc_update_attribute($existing_taxonomy->attribute_id, $data);

                $attribute_parser = get_option('toret_manager_product_attributes_parser', array());
                if ($existing_taxonomy->attribute_name != $productTermData->slug) {
                    $attribute_parser[$data['slug']] = array(
                        'internalid' => $internalid,
                        'localid' => $existing_taxonomy->attribute_id
                    );
                    unset($attribute_parser[$existing_taxonomy->attribute_name]);
                    update_option('toret_manager_product_attributes_parser', $attribute_parser);
                }

                if ($internalid != $attribute_parser[$data['slug']]['internalid']) {
                    $attribute_parser[$data['slug']] = array(
                        'internalid' => $internalid,
                        'localid' => $existing_taxonomy->attribute_id
                    );
                    unset($attribute_parser[$existing_taxonomy->attribute_name]);
                    update_option('toret_manager_product_attributes_parser', $attribute_parser);
                }

                $log = array(
                    'type' => 1,
                    'module' => ucfirst($productTermData->slug),
                    'submodule' => 'Notification - Update',
                    'context' => __('Item updated', 'toret-manager'),
                    'log' => wp_json_encode(array('Local ID' => $existing_taxonomy->attribute_id, 'API internal ID' => $internalid)),
                );
                trman_log($this->toret_manager, $log);

                return $productTermData->slug;
            }
        } else {
            if (Toret_Manager_Helper_Modules::is_sync_enabled('product', 'new', 'download') || $force) {

                $id = wc_create_attribute($data);
                if (!is_wp_error($id)) {

                    WC_Post_Types::register_taxonomies();
                    register_taxonomy('pa_' . $productTermData->slug, array('product'), array());

                    $attribute_parser = get_option('toret_manager_product_attributes_parser', array());
                    $attribute_parser[$data['slug']] = array(
                        'internalid' => $internalid,
                        'localid' => $id
                    );

                    update_option('toret_manager_product_attributes_parser', $attribute_parser);

                    $log = array(
                        'type' => 1,
                        'module' => ucfirst($productTermData->slug),
                        'submodule' => 'Notification - Created',
                        'context' => __('Item created', 'toret-manager'),
                        'log' => wp_json_encode(array('Local ID' => $id, 'API internal ID' => $internalid)),
                    );
                    trman_log($this->toret_manager, $log);
                }

                return $productTermData->slug;
            }
        }

        $log = array(
            'type' => 3,
            'module' => ucfirst($productTermData->slug),
            'submodule' => 'Notification - ' . ($update ? 'Update' : 'Created'),
            'context' => ($update ? __('Failed to update item', 'toret-manager') : __('Failed to create item', 'toret-manager')),
            'log' => wp_json_encode(array('Local ID' => '-', 'API internal ID' => $internalid, 'Error' => 'Unknown problem durict product attribute save')),
        );
        trman_log($this->toret_manager, $log);

        return null;
    }

    /**
     * Download product attribute
     *
     * @param mixed $internalID
     * @param mixed $termData
     */
    function download_product_attribute($internalID, $termData = null, $force = false)
    {
        if (empty($productTermData)) {
            $productTermData = Toret_Manager_Module_General::get_item_from_cloud($internalID, 'product_attribute');
        }

        if ($productTermData == 'none') {
            return null;
        }

        $update = false;
        $existing_taxonomy = null;

        $attribute_parser = get_option('toret_manager_product_attributes_parser', array());

        $searched_tax_key = $productTermData->type;
        if ($searched_tax_key == 'product_attribute') {
            $searched_tax_key = str_replace('pa_', '', $productTermData->slug);
        }
        if (in_array($internalID, array_column($attribute_parser, 'internalid')) || key_exists($searched_tax_key, $attribute_parser)) {
            $existing_taxonomy = Toret_Manager_Helper::get_local_taxonomy($internalID, 'all', key_exists($searched_tax_key, $attribute_parser) ? $searched_tax_key : null);
            if (!empty($existing_taxonomy)) {
                $update = true;
            }
        }

        return $this->saveProductAttribute($productTermData, $existing_taxonomy, $update, $internalID, $force);
    }


    /**
     * Get product attributes for API
     *
     * @param mixed $object
     * @return array
     */
    function get_product_attributes_for_api($object): array
    {
        $attribute_ids = [];
        $global_attribute_ids = [];
        $attributes_meta = [];
        $attributes = $object->get_attributes();

        if ($object->get_type() == 'variation') {

            $parent_product_attributes = get_post_meta($object->get_parent_id(), '_product_attributes', true);

            foreach ($attributes as $name => $value) {
                $attribute_parser = get_option('toret_manager_product_attributes_parser', array());

                if (taxonomy_exists($name)) {

                    $taxonomy = preg_replace('/^pa\_/', '', wc_sanitize_taxonomy_name($name));
                    $taxonomy_internalID = $attribute_parser[$taxonomy]['internalid'] ?? 0;
                    $taxonomy_internalID = Toret_Manager_Module_Product_Attribute::get_instance($this->toret_manager)->upload_product_attribute($taxonomy, $taxonomy_internalID, null, array('internalID' => $taxonomy_internalID));

                    $term = get_term_by('name', $value, $name);
                    $global_attribute_ids[$taxonomy_internalID] = Toret_Manager_Module_Term::get_instance($this->toret_manager)->upload_product_attribute_term($term);
                } else {
                    $attributes_meta[$parent_product_attributes[$name]['name']] = $parent_product_attributes[$name];
                    $attributes_meta[$parent_product_attributes[$name]['name']]['value'] = $value;
                }

            }

        } else {

            $attributes_meta = get_post_meta($object->get_id(), '_product_attributes', true);

            foreach ($attributes as $name => $values) {

                $attribute_parser = get_option('toret_manager_product_attributes_parser', array());

                if (taxonomy_exists($name)) {

                    $global_attribute_term_ids = [];

                    $taxonomy = preg_replace('/^pa\_/', '', wc_sanitize_taxonomy_name($name));

                    $taxonomy_internalID = $attribute_parser[$taxonomy]['internalid'] ?? 0;

                    $taxonomy_internalID = Toret_Manager_Module_Product_Attribute::get_instance($this->toret_manager)->upload_product_attribute($taxonomy, $taxonomy_internalID, null, array('internalID' => $taxonomy_internalID));

                    if (!empty($taxonomy_internalID)) {

                        $terms = get_terms(array(
                            'taxonomy' => $name,
                            'hide_empty' => false,
                        ));

                        foreach ($terms as $term) {
                            if (in_array($term->term_id, $values['options'])) {
                                $internalid = get_term_meta($term->term_id, TORET_MANAGER_ITEM_INTERNALID, true);
                                if (!empty($internalid)) {
                                    $global_attribute_term_ids[] = $internalid;
                                } else {
                                    $global_attribute_term_ids[] = Toret_Manager_Module_Term::get_instance($this->toret_manager)->upload_product_attribute_term($term);
                                }
                            }

                        }
                    }

                    unset($attributes_meta[$name]);

                    $global_attribute_ids[$taxonomy_internalID] = $global_attribute_term_ids;
                }
            };
        }

        $attribute_ids['local'] = $attributes_meta;
        $attribute_ids['global'] = $global_attribute_ids;

        return $attribute_ids;
    }

    /**
     * Upload attribute
     *
     * @param string $taxonomy
     * @param mixed $internalID
     * @param mixed $taxonomy_data
     * @param array $edit_args
     * @return string|null
     */
    function upload_product_attribute(string $taxonomy, $internalID, $taxonomy_data = null, array $edit_args = [])
    {
        if (empty($internalID)) {
            return self::create_api_product_attribute($taxonomy, $taxonomy_data, $edit_args);
        } else {
            $data = Toret_Manager_Module_General::get_item_from_cloud($internalID, 'product_attribute');
            if (!empty($data)) {
                return self::update_api_product_attribute($taxonomy, $taxonomy_data, $edit_args);
            } else {
                return self::create_api_product_attribute($taxonomy, $taxonomy_data, $edit_args);
            }
        }
    }
}
