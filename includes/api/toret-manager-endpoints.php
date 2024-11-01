<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Sent file to be downloaded on the client side
 */
add_action('rest_api_init', function () {
    register_rest_route('api', '/download', array(
        'methods' => 'GET',
        'callback' => 'trman_download_file',
        'permission_callback' => '__return_true',
    ));
});

function trman_download_file(WP_REST_Request $request)
{
    $parameters = $request->get_params();

    $post_attachments = get_posts(array(
        'post_type' => 'attachment',
        'numberposts' => -1,
        'post_status' => null,
        'post_parent' => null,

    ));

    $url = sanitize_url($parameters['trmandownloadfile']);
    foreach ($post_attachments as $post_attachment) {
        if (wp_get_attachment_url($post_attachment->ID) == $url) {
            $found_id = $post_attachment->ID;
        }
    }

    if (isset($found_id)) {
        $file_path = get_attached_file($found_id);
        $file_url = wp_get_attachment_url($found_id);
        if (file_exists($file_path)) {
            $file_data = wp_remote_get($file_url);
            if (!is_wp_error($file_data)) {
                global $wp_filesystem;
                if (!WP_Filesystem()) {
                    return '-1';
                }
                //return wp_remote_retrieve_body($file_data);
                //header('Content-Type: image/jpeg'); // Nastavte správný MIME typ podle vašeho obrázku
                echo $wp_filesystem->get_contents($file_path);
                //return new WP_REST_Response( array( 'image' => wp_remote_retrieve_body($file_data) ) );
            } else {
                return '-1';
            }
        } else {
            return '-1';
        }
    }
}