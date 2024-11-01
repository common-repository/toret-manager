<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Toret_Manager_Api_Get
{

    /**
     * Get access token API call
     *
     * @param string $slug
     * @param string $api_key
     * @param string $user_hash
     * @return string
     */
    public static function check_api_credentials(string $slug, string $api_key, string $user_hash): string
    {
        $request = new Toret_Manager_Api_Calls();
        $access_token = $request->login($slug,$api_key,$user_hash);

		if ($access_token != 'none') {
		    return $access_token;
	    } else {
		    return 'none';
	    }
    }

    /**
     * Get items list API call
     *
     * @param string $slug
     * @param string $module
     * @return mixed
     */
    public static function getItemList(string $slug, string $module = 'product') {
	    $api_key = get_option(TORET_MANAGER_API_KEY,'');
	    $user_hash = get_option(TORET_MANAGER_USER_HASH,'');
	    $shop_id = get_option(TORET_MANAGER_SHOP_ID,'');
	    $endpoint = ( new Toret_Manager_Api )->get_module_endpoint($module,'getList');

		$data = array('shopID' => $shop_id);

        $request = new Toret_Manager_Api_Calls();

	    $access_token = $request->login($slug,$api_key,$user_hash);

	   if ($access_token != 'none') {
		    return $request->CreatePostRequest($slug,$endpoint,$access_token,wp_json_encode($data),$module);
	    } else {
		    return 'none';
	    }
    }

    /**
     * Get item API call
     *
     * @param string $slug
     * @param string $internalID
     * @param array $select
     * @param string $module
     * @return mixed
     */
    public static function getItem(string $slug, string $internalID, array $select = array('all'), string $module = 'product') {
	    $api_key = get_option(TORET_MANAGER_API_KEY,'');
	    $user_hash = get_option(TORET_MANAGER_USER_HASH,'');
	    $shop_id = get_option(TORET_MANAGER_SHOP_ID,'');
	    $endpoint = ( new Toret_Manager_Api )->get_module_endpoint($module,'get');

	    $data = array(
		    'shopID' => $shop_id,
		    'internalID' => (string) $internalID,
		    'select' => $select,
	    );

	    $request = new Toret_Manager_Api_Calls();

	    $access_token = $request->login($slug,$api_key,$user_hash);

	   if ($access_token != 'none') {
		    return $request->CreatePostRequest($slug,$endpoint,$access_token,wp_json_encode($data),$module);
	    } else {
		    return 'none';
	    }
    }

    /**
     * Get stock API call
     *
     * @param string $slug
     * @param string $internalID
     * @param array $select
     * @param string $module
     * @return mixed
     */
    public static function getStock(string $slug, string $internalID, array $select = array('all'), string $module = 'product') {
	    $api_key = get_option(TORET_MANAGER_API_KEY,'');
	    $user_hash = get_option(TORET_MANAGER_USER_HASH,'');
	    $shop_id = get_option(TORET_MANAGER_SHOP_ID,'');
	    $endpoint = ( new Toret_Manager_Api )->get_module_endpoint($module,'getStock');

	    $data = array(
		    'shopID' => $shop_id,
		    'internalID' => (string) $internalID,
	    );

	    $request = new Toret_Manager_Api_Calls();

	    $access_token = $request->login($slug,$api_key,$user_hash);

	   if ($access_token != 'none') {
		    return $request->CreatePostRequest($slug,$endpoint,$access_token,wp_json_encode($data));
	    } else {
		    return 'none';
	    }
    }


}