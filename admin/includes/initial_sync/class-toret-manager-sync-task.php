<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait Toret_Manager_Sync_Task
{

    /**
     * Log sync output
     *
     * @param mixed $response
     */
    public function log($response)
    {
        //trman_var_error_log($response);
    }

    /**
     * Upload sync item
     *
     * @param array $item
     * @return mixed|null
     * @throws Exception
     */
    protected function uploadItem(array $item)
    {
        if (Toret_Manager_Helper::is_excluded($item[0] , $item[1], $item[2])) {
            return null;
        }

        if($item[1] == 'post'){
            $return = Toret_Manager_Module_Post::get_instance(TORET_MANAGER_SLUG)->upload_missing_post($item[0],'post',true);
        }elseif($item[1] == 'order'){
            $return = Toret_Manager_Module_Order::get_instance(TORET_MANAGER_SLUG)->upload_missing_order($item[0],true);
        }elseif($item[1] == 'product'){
            $return = Toret_Manager_Module_Product::get_instance(TORET_MANAGER_SLUG)->upload_missing_product($item[0],true);
        }elseif($item[1] == 'user'){
            $return = Toret_Manager_Module_User::get_instance(TORET_MANAGER_SLUG)->upload_missing_user($item[0],true);
        }elseif(in_array($item[1], array('comment', 'review', 'order_note'))){
            $return = Toret_Manager_Module_Review::get_instance(TORET_MANAGER_SLUG)->upload_missing_comment($item[0],true);
        }elseif (in_array($item[1], array('category', 'post_tag', 'product_cat', 'product_tag'))) {
            $return = Toret_Manager_Module_Term::get_instance(TORET_MANAGER_SLUG)->upload_missing_term(get_term($item[0]),true);
        }else{
            $return = Toret_Manager_Module_Post::get_instance(TORET_MANAGER_SLUG)->upload_missing_post($item[0],$item[1],true);
        }

        return $return;
    }

}