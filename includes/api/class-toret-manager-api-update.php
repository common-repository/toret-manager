<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Toret_Manager_Api_Update
{

    /**
     * Update item API call
     *
     * @param string $slug
     * @param array $data
     * @param string $module
     * @return mixed
     */
    public static function updateItem(string $slug, array $data, string $module = 'product')
    {
        $api_key = get_option(TORET_MANAGER_API_KEY, '');
        $user_hash = get_option(TORET_MANAGER_USER_HASH, '');
        $shop_id = get_option(TORET_MANAGER_SHOP_ID, '');
        $endpoint = (new Toret_Manager_Api)->get_module_endpoint($module, 'update');

        $data['shopID'] = $shop_id;

        $request = new Toret_Manager_Api_Calls();

        $access_token = $request->login($slug, $api_key, $user_hash);

        if ($access_token != 'none') {
            return $request->CreatePostRequest($slug, $endpoint, $access_token, wp_json_encode($data), $module);
        } else {
            return 'none';
        }
    }

    /**
     * Set stock API call
     *
     * @param string $slug
     * @param array $data
     * @param string $module
     * @return mixed
     */
    public static function setStock(string $slug, array $data, string $module = 'product')
    {
        $api_key = get_option(TORET_MANAGER_API_KEY, '');
        $user_hash = get_option(TORET_MANAGER_USER_HASH, '');
        $shop_id = get_option(TORET_MANAGER_SHOP_ID, '');
        $endpoint = (new Toret_Manager_Api)->get_module_endpoint($module, 'setStock');

        $data['shopID'] = $shop_id;

        $request = new Toret_Manager_Api_Calls();

        $access_token = $request->login($slug, $api_key, $user_hash);

        if ($access_token != 'none') {
            return $request->CreatePostRequest($slug, $endpoint, $access_token, wp_json_encode($data));
        } else {
            return 'none';
        }
    }


}