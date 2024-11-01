<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!defined('ABSPATH')) {
    exit;
}

class Toret_Manager_Admin_General_Details
{

    /**
     * AJAX for saving internalID from metabox
     */
    function save_internalid()
    {
        /*if ( ! wp_verify_nonce( $_POST['nonce'], 'ajax-nonce' ) ) {
            die( 'Do not have permission');
        }*/
        check_ajax_referer( 'ajax-nonce', 'nonce' );

        $internailID =  sanitize_text_field($_POST['internal_id']);
        $item_id =  sanitize_text_field($_POST['item_id']);
        $type =  sanitize_text_field($_POST['type']);

        Toret_Manager_Helper_Db::update_object_meta($item_id, TORET_MANAGER_ITEM_INTERNALID, $internailID, $type);

        wp_die();
    }

}