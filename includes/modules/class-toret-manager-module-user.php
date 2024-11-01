<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Toret_Manager_Module_User extends Toret_Manager_Module_General
{

    /**
     * User class instance
     *
     * @Toret_Manager_Module_User Toret_Manager_Module_User
     */
    protected static ?Toret_Manager_Module_User $Toret_Manager_Module_User_Instance = null;

    /**
     * Module
     *
     * @string $internalID_key
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
        $this->module = 'user';
        $this->toret_manager = $toret_manager;

        if (Toret_Manager_Helper_Modules::is_any_edit_sync_enabled($this->module)) {
            add_action('user_register', array($this, 'on_user_save'), 99);
            add_action('profile_update', array($this, 'on_user_save'), 99);
            add_action('async_on_save_user', array($this, 'async_on_save_user'), 99, 1);
        }

        if (Toret_Manager_Helper_Modules::is_sync_enabled($this->module, 'delete')) {
            add_action('delete_user', array($this, 'on_user_delete'), 99, 1);
            add_action('async_on_delete_user', array($this, 'async_on_delete_user'), 99, 1);
        }

		add_filter('additional_capabilities_display',function($show){return false;});
    }

    /**
     * Get class instance
     *
     * @param string $toret_manager
     * @return Toret_Manager_Module_User|null
     */
    public static function get_instance(string $toret_manager): ?Toret_Manager_Module_User
    {
        if (null == self::$Toret_Manager_Module_User_Instance) {
            self::$Toret_Manager_Module_User_Instance = new self($toret_manager);
        }

        return self::$Toret_Manager_Module_User_Instance;
    }

    /**
     * On local user delete
     *
     * @param mixed $item_id
     */
    public function on_user_delete($item_id)
    {
        if (!Toret_Manager_Helper_Modules::is_sync_enabled($this->module, 'delete')) {
            return;
        }

        if (Toret_Manager_Helper::is_excluded($item_id, $this->module, $this->module)) {
            return;
        }

        if (in_array('post', TORET_MANAGER_ASYNC_UPLOAD_TYPES)) {
            wp_schedule_single_event(time(), 'async_on_delete_user', array($item_id));
        } else {
            $this->async_on_delete_user($item_id);
        }
    }

    /**
     * Async on user delete
     *
     * @param mixed $item_id
     */
    function async_on_delete_user($item_id)
    {
        Toret_Manager_Module_General::process_delete_post($item_id, $this->module, $this->module);
    }

    /**
     * On local user save
     *
     * @param mixed $item_id
     */
    public function on_user_save($item_id)
    {

	    if ( defined( 'DOING_CRON' ) ) {
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

        if (in_array($this->module, TORET_MANAGER_ASYNC_UPLOAD_TYPES)) {
            wp_schedule_single_event(time(), 'async_on_save_user', array($item_id));
        } else {
            $this->async_on_save_user($item_id);
        }
    }

    /**
     * Async on user save
     *
     * @param mixed $item_id
     * @param bool $force
     * @param array $edit_args
     * @return mixed|null
     */
    function async_on_save_user($item_id, bool $force = false, array $edit_args = array())
    {

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

        $data = self::itemDataArray(get_user_by('id', $item_id), $edit_args);
        return Toret_Manager_Module_General::process_save_item_adv($item_id, $data, $force, $edit_args);

    }

    /**
     * Get user data for upload
     *
     * @param WP_User $user
     * @param array $edit_args
     * @return array
     * @throws Exception
     */
    public function itemDataArray(WP_User $user, array $edit_args = array()): array
    {
        $update = $edit_args['update'];

        $meta = get_user_meta($user->ID);
        $meta = $this->reaarange_meta_array($meta, $this->module);

        $data = array(
            'userID' => $user->ID,
            'userLogin' => $user->user_login,
            'userNiceName' => $user->user_nicename,
            'userFirstName' => $user->first_name,
            'userLastName' => $user->last_name,
            'userDisplayName' => $user->display_name,
            'userEmail' => $user->user_email,
            'userRegistered' => Toret_Manager_DateTime::format_date_for_api($user->user_registered, false),
            'description' => $user->description,
            'locale' => $user->locale,
            'capabilities' => wp_json_encode($user->allcaps),
            'meta' => wp_json_encode($meta),
            'editUrl' => Toret_Manager_Module_General::custom_get_edit_user_link($user->ID),
        );

        if (Toret_Manager_Helper::is_woocommerce_active() && class_exists('WC_Customer')) {
            $customer = new WC_Customer($user->ID);
            $data_woo = array(
                'billingFirstName' => $customer->get_billing_first_name(),
                'billingLastName' => $customer->get_billing_last_name(),
                'billingAddress' => $customer->get_billing_address_1(),
                'billingAddress2' => $customer->get_billing_address_2(),
                'billingCity' => $customer->get_billing_city(),
                'billingZip' => $customer->get_billing_postcode(),
                'billingCountry' => $customer->get_billing_country(),
                'billingEmail' => $customer->get_billing_email() ?: $user->user_email,
                'billingPhone' => $customer->get_billing_phone(),
                'shippingFirstName' => $customer->get_shipping_first_name(),
                'shippingLastName' => $customer->get_shipping_last_name(),
                'shippingAddress' => $customer->get_shipping_address_1(),
                'shippingAddress2' => $customer->get_shipping_address_2(),
                'shippingCity' => $customer->get_shipping_city(),
                'shippingZip' => $customer->get_shipping_postcode(),
                'shippingCountry' => $customer->get_shipping_country(),
                'shippingEmail' => $customer->get_billing_email() ?: $user->user_email,
                'shippingPhone' => $customer->get_shipping_phone(),
            );
            $data = array_merge($data, $data_woo);
        }

        return apply_filters('toret_manager_sent_' . $this->module . '_data', $data, $user, $update);
    }

    /**
     * Save item from notify
     *
     * @param object $data
     * @param array $data_to_be_synchronized
     * @param $existing_id
     * @param bool $update
     * @param bool $markSynced
     * @return int|null
     */
    function save_item(object $data, array $data_to_be_synchronized, $existing_id, bool $update = false, bool $markSynced = false): ?int
    {

        $login = $data->userLogin;

        if (email_exists($data->userEmail)) {

            $user = get_user_by('email', $data->userEmail);
            $user_id = $user->ID;

        } else if ($update && !empty($existing_id)) {

            $user = get_user_by('id', $existing_id);
            $user_id = $existing_id;

        } else {

            if (username_exists($data->userLogin)) {

                $login = $data->userEmail;

            }

            $user_id = wp_create_user($login, wp_generate_password(), $data->userEmail);

            if (is_wp_error($user_id)) {
                $log = array(
                    'type' => 3,
                    'module' => ucfirst($this->module),
                    'submodule' => 'Notification - ' . ($update ? 'Update' : 'Created'),
                    'context' => ($update ? __('Failed to update item', 'toret-manager') : __('Failed to create item', 'toret-manager')),
                    'log' => wp_json_encode(array('Local ID' => $existing_id, 'API internal ID' => $data->internalID, 'Error' => wp_json_encode($user_id))),
                );
                trman_log($this->toret_manager, $log);
                return null;
            }
            $user = get_user_by('id', $user_id);

        }

        if (empty($user)) {
            $log = array(
                'type' => 3,
                'module' => ucfirst($this->module),
                'submodule' => 'Notification - ' . ($update ? 'Update' : 'Created'),
                'context' => ($update ? __('Failed to update item', 'toret-manager') : __('Failed to create item', 'toret-manager')),
                'log' => wp_json_encode(array('Local ID' => $existing_id, 'API internal ID' => $data->internalID, 'Error' => 'Empty user object')),
            );
            trman_log($this->toret_manager, $log);
            return null;
        }

        $exlude_from_meta = array(
            'user_login',
            'user_nicename',
            'user_nicename',
            'first_name',
            'last_name',
            'display_name',
            'user_email',
            'user_registered',
            'description',
            'locale',
            'billing_first_name',
            'billing_last_name',
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_postcode',
            'billing_country',
            'billing_email',
            'billing_phone',
            'shipping_first_name',
            'shipping_last_name',
            'shipping_address_1',
            'shipping_address_2',
            'shipping_city',
            'shipping_postcode',
            'shipping_country',
            'shipping_email',
            'shipping_phone',
        );

        foreach ($data as $property => $item) {
            if (in_array($property, $data_to_be_synchronized)) {
                if ($property == 'userLogin') {
                    if ($login != $item)
                        update_user_meta($user_id, 'user_login', $login);
                    else
                        update_user_meta($user_id, 'user_login', $item);
                } elseif ($property == 'userNiceName') {
                    update_user_meta($user_id, 'user_nicename', $item);
                } elseif ($property == 'userFirstName') {
                    update_user_meta($user_id, 'first_name', $item);
                } elseif ($property == 'userLastName') {
                    update_user_meta($user_id, 'last_name', $item);
                } elseif ($property == 'userDisplayName') {
                    update_user_meta($user_id, 'display_name', $item);
                } elseif ($property == 'userEmail') {
                    update_user_meta($user_id, 'user_email', $item);
                } elseif ($property == 'userRegistered') {
                    update_user_meta($user_id, 'user_registered', $item);
                } elseif ($property == 'description') {
                    update_user_meta($user_id, 'description', $item);
                } elseif ($property == 'locale') {
                    update_user_meta($user_id, 'locale', $item);
                } elseif ($property == 'billingFirstName') {
                    update_user_meta($user_id, 'billing_first_name', $item);
                } elseif ($property == 'billingLastName') {
                    update_user_meta($user_id, 'billing_last_name', $item);
                } elseif ($property == 'billingAddress') {
                    update_user_meta($user_id, 'billing_address_1', $item);
                } elseif ($property == 'billingAddress2') {
                    update_user_meta($user_id, 'billing_address_2', $item);
                } elseif ($property == 'billingCity') {
                    update_user_meta($user_id, 'billing_city', $item);
                } elseif ($property == 'billingZip') {
                    update_user_meta($user_id, 'billing_postcode', $item);
                } elseif ($property == 'billingCountry') {
                    update_user_meta($user_id, 'billing_country', $item);
                } elseif ($property == 'billingEmail') {
                    update_user_meta($user_id, 'billing_email', $item);
                } elseif ($property == 'billingPhone') {
                    update_user_meta($user_id, 'billing_phone', $item);
                } elseif ($property == 'shippingFirstName') {
                    update_user_meta($user_id, 'shipping_first_name', $item);
                } elseif ($property == 'shippingLastName') {
                    update_user_meta($user_id, 'shipping_last_name', $item);
                } elseif ($property == 'shippingAddress') {
                    update_user_meta($user_id, 'shipping_address_1', $item);
                } elseif ($property == 'shippingAddress2') {
                    update_user_meta($user_id, 'shipping_address_2', $item);
                } elseif ($property == 'shippingCity') {
                    update_user_meta($user_id, 'shipping_city', $item);
                } elseif ($property == 'shippingZip') {
                    update_user_meta($user_id, 'shipping_postcode', $item);
                } elseif ($property == 'shippingCountry') {
                    update_user_meta($user_id, 'shipping_country', $item);
                } elseif ($property == 'shippingEmail') {
                    update_user_meta($user_id, 'shipping_email', $item);
                } elseif ($property == 'shippingPhone') {
                    update_user_meta($user_id, 'shipping_phone', $item);
                } elseif ($property == 'meta') {
                    $meta = json_decode($item, true);
                    if (!empty($meta)) {
                        if (key_exists('tr_capabilities', $meta)) {
                            $user_meta = get_userdata($user_id);
                            $user_roles = $user_meta->roles;
                            foreach ($user_roles as $role) {
                                $user->remove_role($role);
                            }
                            $role = maybe_unserialize($meta['tr_capabilities']);
                            if (!empty($role)) {
                                foreach ($role as $role_key => $role_value) {
                                    $user->add_role($role_key);
                                }
                            }
                            if (!empty($role)) {
                                foreach ($role as $role_key => $role_value) {
                                    $user->add_role($role_key);
                                }
                            }
                        }
                        foreach ($meta as $meta_key => $meta_value) {
                            if (strpos($meta_key, 'capabilities') === false) {
                                if (!in_array($meta_key, $exlude_from_meta))
                                    update_user_meta($user_id, $meta_key, $meta_value);
                            }
                        }
                    }
                }

            }
        }

        update_user_meta($user_id, TORET_MANAGER_ITEM_INTERNALID, $data->internalID);

        if ($markSynced) {
            update_user_meta($user_id, TORET_MANAGER_ASSOCIATIVE_SYNC, '1');
        }

        if (in_array('capabilities', $data_to_be_synchronized)) {
            $capabilities = json_decode($data->capabilities, true);
            foreach ($capabilities as $cappability => $value) {
                $user->add_cap($cappability, $value);
            }
        }

        $log = array(
            'type' => 1,
            'module' => ucfirst($this->module),
            'submodule' => 'Notification - ' . ($update ? 'Update' : 'Created'),
            'context' => ($update ? __('Item updated', 'toret-manager') : __('Item created', 'toret-manager')),
            'log' => wp_json_encode(array('Local ID' => $user_id, 'API internal ID' => $data->internalID)),
        );
        trman_log($this->toret_manager, $log);

        return $user_id;
    }

    /**
     * Upload missing user if not exists
     *
     * @param mixed $item_id
     * @param bool $force
     * @return mixed|null
     */
    function upload_missing_user($item_id, bool $force = false)
    {
        return self::async_on_save_user($item_id, $force);
    }

    /**
     * Get user internal ID
     *
     * @param mixed $id
     * @param string $associated
     * @return int|mixed|null
     */
    static function get_user_internal_ID($id, string $associated)
    {
        if (empty($id)) {
            return -1;
        }

        $internalID = Toret_Manager_Helper_Db::get_object_meta($id, TORET_MANAGER_ITEM_INTERNALID, 'user');
        if (empty($internalID)) {

            if (Toret_Manager_Helper_Modules::should_sync_associated($associated)) {
                $internalID = Toret_Manager_Module_User::get_instance(TORET_MANAGER_SLUG)->upload_missing_user($id, true);
            }

            if (empty($internalID)) {
                $internalID = -1;
            }

        }

        return $internalID;
    }

}
