<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Toret_Manager_Module_Post extends Toret_Manager_Module_General
{

    /**
     * Class instance
     *
     * @var Toret_Manager_Module_Post|null
     */
    protected static ?Toret_Manager_Module_Post $Toret_Manager_Module_Post_Instance = null;

    /**
     * Module
     *
     * @var string
     */
    protected string $module;

    public function __construct($toret_manager)
    {
        parent::__construct($toret_manager);
        $this->internalID_key = TORET_MANAGER_ITEM_INTERNALID;

        add_action('wp_after_insert_post', array($this, 'on_save_post'), 99, 2);
        add_action('async_on_save_post', array($this, 'async_on_save_post'), 99, 2);

        add_action('before_delete_post', array($this, 'on_before_delete_post'), 99, 2);
        add_action('async_on_delete_post', array($this, 'async_on_delete_post'), 99, 2);
    }

    /**
     * Class instance
     *
     * @param string $toret_manager
     * @return Toret_Manager_Module_Post|null
     */
    public static function get_instance(string $toret_manager): ?Toret_Manager_Module_Post
    {
        if (null == self::$Toret_Manager_Module_Post_Instance) {
            self::$Toret_Manager_Module_Post_Instance = new self($toret_manager);
        }

        return self::$Toret_Manager_Module_Post_Instance;
    }

    /**
     * Upload when trashed
     *
     * @param mixed $item_id
     */
    function process_trash_post($item_id)
    {
        $this->on_save_post($item_id, get_post($item_id));
    }

    /**
     * On local post delete
     *
     * @param mixed $item_id
     * @param mixed $post
     */
    public function on_before_delete_post($item_id, $post)
    {
        $module = Toret_Manager_Helper_Modules::get_module_by_post_type($post->post_type);

        if (!Toret_Manager_Helper_Modules::is_sync_enabled($module, 'delete',)) {
            return;
        }

        if (in_array($post->post_type, array('product', 'shop_order'))) {
            return;
        }

        if (Toret_Manager_Helper::is_excluded($item_id, $module, 'post')) {
            return;
        }

        if (in_array('post', TORET_MANAGER_ASYNC_UPLOAD_TYPES)) {
            wp_schedule_single_event(time(), 'async_on_delete_post', array($item_id, $module));
        } else {
            $this->async_on_delete_post($item_id, $module);
        }
    }

    /**
     * Async On local post delete
     *
     * @param mixed $item
     * @param string $module
     */
    public function async_on_delete_post($item, string $module)
    {
        Toret_Manager_Module_General::process_delete_post($item, $module);
    }


    /**
     * On local post save
     *
     * @param mixed $item_id
     * @param mixed $post
     */
    public function on_save_post($item_id, $post)
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

        if (!Toret_Manager_Helper_Modules::is_any_edit_sync_enabled($post->post_type)) {
            return;
        }

        if (in_array($post->post_type, array('product', 'shop_order'))) {
            return;
        }

        if (in_array($post->post_status, array('auto-draft'))) {
            return;
        }

	    if ( defined( 'DOING_CRON' ) ) {
		    return;
	    }

        if (Toret_Manager_Helper::is_excluded($item_id, $post->post_type, 'post')) {
            return;
        }

	    if(isset($_POST['trman_run']) && $_POST['trman_run'] == 'queue'){
		    return;
	    }

        if ((isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'untrash-post_' . $post->ID))) {
            if (in_array('post', TORET_MANAGER_ASYNC_UPLOAD_TYPES)) {
                wp_schedule_single_event(time(), 'async_on_save_post', array($item_id, $post));
            } else {
                $this->async_on_save_post($item_id, $post);
            }
        }

        if ((isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'trash-post_' . $post->ID))) {
            if (in_array('post', TORET_MANAGER_ASYNC_UPLOAD_TYPES)) {
                wp_schedule_single_event(time(), 'async_on_save_post', array($item_id, $post));
            } else {
                $this->async_on_save_post($item_id, $post);
            }
        }

        if (isset($_POST['trman_post_metabox_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['trman_post_metabox_nonce'])), 'trman_post_metabox')) {
            if (in_array('post', TORET_MANAGER_ASYNC_UPLOAD_TYPES)) {
                wp_schedule_single_event(time(), 'async_on_save_post', array($item_id, $post));
            } else {
                $this->async_on_save_post($item_id, $post);
            }
        }

        if (isset($_POST['_inline_edit']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_inline_edit'])), 'inlineeditnonce')) {
            if (in_array('post', TORET_MANAGER_ASYNC_UPLOAD_TYPES)) {
                wp_schedule_single_event(time(), 'async_on_save_post', array($item_id, $post));
            } else {
                $this->async_on_save_post($item_id, $post);
            }
        }
    }

    /**
     * Async save post
     *
     * @param mixed $item_id
     * @param mixed $post
     * @param bool $force
     * @param string $associated
     * @param array $edit_args
     * @return mixed
     */
    function async_on_save_post($item_id, $post, bool $force = false, string $associated = "", array $edit_args = [])
    {
        $internalID = Toret_Manager_Helper_Db::get_object_meta($item_id, TORET_MANAGER_ITEM_INTERNALID, 'post');
        $update = !empty($internalID);
        if (empty($internalID)) {
            $internalID = Toret_Manager_Helper::generate_internal_id($post->post_type);
        }

        $edit_args['internalID'] = $internalID;
        $edit_args['update'] = $update;
        $edit_args['action'] = $update ? 'update' : 'new';
        $edit_args['module'] = $post->post_type;
        $edit_args['type'] = 'post';
        $edit_args['associated'] = $associated;

        $nonce = wp_create_nonce('trman_edit_args_' . $item_id);

        $edit_args = Toret_Manager_Helper::edit_args_modification($edit_args, $item_id, $nonce);

        $data = Toret_Manager_Module_Post::get_instance($this->toret_manager)->itemDataArray($item_id, $post, $edit_args);
        return Toret_Manager_Module_General::process_save_item_adv($item_id, $data, $force, $edit_args);
    }


    /**
     * Get post data for  upload
     *
     * @param mixed $id
     * @param mixed $post
     * @param array $edit_args
     * @return array
     * @throws Exception
     */
    public function itemDataArray($id, $post, array $edit_args = [])
    {
        $update = $edit_args['update'];
        $associated = $edit_args['associated'];
        $post_type = $post->post_type;

        $parentInternalID = $this->get_parent_internal_ID(wp_get_post_parent_id($id), $post_type, 'post');

        $authorID = $post->post_author;
        $postPassword = $post->post_password;
        $meta = get_post_meta($id);

        $meta = $this->reaarange_meta_array($meta, $post_type);

        $commentCount = get_comments_number($id);

        $authorInternalID = Toret_Manager_Module_User::get_user_internal_ID($authorID, $post_type);

        $commentsInternalID = [];
        if ($associated == "") {
            $commentsInternalID = Toret_Manager_Module_Review::get_instance($this->toret_manager)->get_comments_internal_id(get_comments(array('post_id' => $id)), $edit_args);
        }
        $data = array(
            'postID' => (int)$id,
            'authorID' => (int)$authorID,
            'authorInternalID' => (string)$authorInternalID,
            'title' => get_the_title($id),
            'postStatus' => get_post_status($id),
            'postType' => $post_type,
            'editUrl' => Toret_Manager_Module_General::custom_get_edit_post_link($id),
            'parentID' => wp_get_post_parent_id($id),
            'parentInternalID' => (string)$parentInternalID,
            'excerpt' => get_the_excerpt($id),
            'content' => get_the_content(null, false, $id),
            'thumbnail' => (get_the_post_thumbnail_url($id) ? get_the_post_thumbnail_url($id) : ''),
            'url' => get_permalink($id),
            'commentStatus' => comments_open($id) ? 'open' : 'closed',
            'commentCount' => $commentCount,
            'postPassword' => $postPassword,
            'createdDate' => Toret_Manager_DateTime::format_date_for_api(get_the_date('Y-m-d H:i:s', $id), true),
            'editedDate' => Toret_Manager_DateTime::format_date_for_api(get_the_modified_date('Y-m-d H:i:s', $id), true),
            'meta' => wp_json_encode($meta),
            'commentsInternalID' => wp_json_encode($commentsInternalID),
            'menuOrder' => $post->menu_order,
            //'isSticky' => is_sticky($id),
        );

        $post_taxonomies = get_object_taxonomies($post_type);

        $cats_array = [];
        $tags_array = [];

        foreach ($post_taxonomies as $taxonomy) {

            $taxonomy_terms = [];
            $terms = get_the_terms($id, $taxonomy);
            if (!empty($terms)) {
                foreach ($terms as $term) {
                    $taxonomy_terms[] = $term->term_id;
                }
            }
            if (!empty($taxonomy_terms)) {
                $terms_internal_ids = Toret_Manager_Module_Term::get_instance($this->toret_manager)->get_term_internal_ids($taxonomy, $taxonomy_terms, $post_type);

                if (is_taxonomy_hierarchical($taxonomy)) {
                    $cats_array[$taxonomy] = $terms_internal_ids;
                } else {
                    $tags_array[$taxonomy] = $terms_internal_ids;
                }
            }
        }

        $data['category'] = wp_json_encode(array_merge(...array_values($cats_array)));
        $data['tags'] = wp_json_encode(array_merge(...array_values($tags_array)));

        return apply_filters('toret_manager_sent_' . $post_type . '_data', $data, $id, $update);
    }

    /**
     * Save notified post
     *
     * @param mixed $data
     * @param array $data_to_be_synchronized
     * @param mixed $existing_id
     * @param bool $update
     * @param bool $markSynced
     * @return mixed
     */
    function save_item($data, array $data_to_be_synchronized, $existing_id, bool $update = false, bool $markSynced = false)
    {
        if ($update && !empty($existing_id)) {

            $id = $existing_id;

        } else {

            $new_post = array(
                'post_title' => $data->title,
                'post_type' => $data->postType,
            );

            $id = wp_insert_post($new_post);

            if (is_wp_error($id)) {
                $log = array(
                    'type' => 3,
                    'module' => ucfirst($data->postType),
                    'submodule' => 'Notification - ' . ($update ? 'Update' : 'Created'),
                    'context' => ($update ? __('Failed to update item', 'toret-manager') : __('Failed to create item', 'toret-manager')),
                    'log' => wp_json_encode(array('Local ID' => $id, 'API internal ID' => $data->internalID, 'Error' => wp_json_encode($id))),
                );
                trman_log($this->toret_manager, $log);
                return null;
            }

        }

        if (!empty($data->parentInternalID) && $data->parentInternalID != -1) {
            $parent_id = Toret_Manager_Module_General::get_associted_local_id($data->parentInternalID, $data->postType, $data->postType, 'post', true, true);
        }

        if (!empty($data->authorInternalID) && $data->authorInternalID != -1) {
            $authorID = Toret_Manager_Module_General::get_associted_local_id($data->authorInternalID, $data->postType, 'user', 'user', false, true);
        }

        $update_array = array();

        foreach ($data as $property => $item) {
            if (in_array($property, $data_to_be_synchronized)) {

                $filter = apply_filters('toret_manager_post_notified_should_process_item', false, $item, $property, $data);
                if (!empty($filter)) {
                    do_action('toret_manager_post_notified_process_item', $item, $property, $data);
                    continue;
                }

                if ($property == 'parentID' && !empty($parent_id)) {

                    $update_array['post_parent'] = $parent_id;

                } elseif ($property == 'meta') {

                    $meta = json_decode($item, true);
                    if (!empty($meta)) {
                        foreach ($meta as $meta_key => $meta_value) {
                            $meta_value = maybe_unserialize($meta_value);
                            if ($meta_key == '_elementor_data') {
                                $meta_value = json_decode($meta_value, true);
                                foreach ($meta_value as $a => $element) {

                                    if (isset($element['settings']['background_image']['url'])) {
                                        $img_id = Toret_Manager_Helper::download_file($element['settings']['background_image']['url'], null, $data->postType);
                                        if ($img_id) {
                                            $meta_value[$a]['settings']['background_image']['id'] = $img_id;
                                            $meta_value[$a]['settings']['background_image']['url'] = wp_get_attachment_url($img_id);
                                        }
                                    }

                                    if (isset($element['settings']['background_video_fallback']['url'])) {
                                        $img_id = Toret_Manager_Helper::download_file($element['settings']['background_video_fallback']['url'], null, $data->postType);
                                        if ($img_id) {
                                            $meta_value[$a]['settings']['background_video_fallback']['id'] = $img_id;
                                            $meta_value[$a]['settings']['background_video_fallback']['url'] = wp_get_attachment_url($img_id);
                                        }
                                    }


                                    if (isset($element['elements'])) {
                                        $elements1 = $element['elements'];
                                        foreach ($elements1 as $b => $element1) {
                                            if (isset($element1['elements'])) {
                                                $elements2 = $element1['elements'];
                                                foreach ($elements2 as $c => $element2) {

                                                    if (isset($element2['settings']['slides'])) {
                                                        $slides = $element2['settings']['slides'];
                                                        foreach ($slides as $d => $slide) {
                                                            if (isset($slide['background_image'])) {
                                                                $img_id = Toret_Manager_Helper::download_file($slide['background_image']['url'], null, $data->postType);
                                                                if ($img_id) {
                                                                    $meta_value[$a]['elements'][$b]['elements'][$c]['settings']['slides'][$d]['background_image']['id'] = $img_id;
                                                                    $meta_value[$a]['elements'][$b]['elements'][$c]['settings']['slides'][$d]['background_image']['url'] = wp_get_attachment_url($img_id);
                                                                }
                                                            }
                                                        }
                                                    }

                                                    if (isset($element2['settings']['image'])) {
                                                        $image = $element2['settings']['image'];
                                                        $img_id = Toret_Manager_Helper::download_file($image['url'], null, $data->postType);
                                                        if ($img_id) {
                                                            $meta_value[$a]['elements'][$b]['elements'][$c]['settings']['image']['id'] = $img_id;
                                                            $meta_value[$a]['elements'][$b]['elements'][$c]['settings']['image']['url'] = wp_get_attachment_url($img_id);
                                                        }
                                                    }

                                                    if (isset($element2['settings']['wp_gallery'])) {
                                                        $wp_gallery = $element2['settings']['wp_gallery'];
                                                        foreach ($wp_gallery as $d => $image) {
                                                            $img_id = Toret_Manager_Helper::download_file($image['url'], null, $data->postType);
                                                            if ($img_id) {
                                                                $meta_value[$a]['elements'][$b]['elements'][$c]['settings']['wp_gallery'][$d]['id'] = $img_id;
                                                                $meta_value[$a]['elements'][$b]['elements'][$c]['settings']['wp_gallery'][$d]['url'] = wp_get_attachment_url($img_id);
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            update_post_meta($id, $meta_key, $meta_value);
                        }
                    }

                } else if ($property == 'authorID' && !empty($authorID)) {

                    $update_array['post_author'] = $authorID;

                } else if ($property == 'title') {

                    $update_array['post_title'] = $item;

                } else if ($property == 'postStatus') {

                    if (!$update) {
                        $target_status = get_option('trman_module_' . $data->postType . '_imported_status', 'default');

                        if ($target_status == 'default' || $target_status == 'ok') {
                            $target_status = $item;
                        }
                    } else {
                        $target_status = $item;
                    }

                    $update_array['post_status'] = $target_status;

                } else if ($property == 'postType') {

                    $update_array['post_type'] = $item;

                } else if ($property == 'excerpt') {

                    $update_array['post_excerpt'] = $item;

                } else if ($property == 'content') {

                    $update_array['post_content'] = $item;

                } else if ($property == 'commentStatus') {

                    $update_array['comment_status'] = $item;

                }  else if ($property == 'postPassword') {

                    $update_array['post_password'] = $item;

                } else if ($property == 'menuOrder') {

                    $update_array['menu_order'] = $item;

                } else if ($property == 'createdDate') {

                    $update_array['post_date'] = $item;
                    $update_array['post_date_gmt'] = get_gmt_from_date($item);

                }
            }
        }

        if (!empty($update_array)) {
            $update_array['page_template'] = array();
            $update_array['ID'] = $id;
            wp_update_post($update_array);
        }


        if (in_array('thumbnail', $data_to_be_synchronized)) {
            if (!empty($data->thumbnail)) {
                $img_id = Toret_Manager_Helper::download_file($data->thumbnail, null, $data->postType);
                if ($img_id) {
                    set_post_thumbnail($id, $img_id);
                }
            } else {
                delete_post_thumbnail($id);
            }
        }

        if (!$update) {
            update_post_meta($id, $this->internalID_key, $data->internalID);
        }

        if ($markSynced) {
            update_post_meta($id, TORET_MANAGER_ASSOCIATIVE_SYNC, '1');
        }

        $log = array(
            'type' => 1,
            'module' => ucfirst($data->postType),
            'submodule' => 'Notification - ' . ($update ? 'Update' : 'Created'),
            'context' => ($update ? __('Item updated', 'toret-manager') : __('Item created', 'toret-manager')),
            'log' => wp_json_encode(array('Local ID' => $id, 'API internal ID' => $data->internalID)),
        );
        trman_log($this->toret_manager, $log);


        if (in_array('category', $data_to_be_synchronized)) {
            if ((property_exists($data, 'category') && !empty($data->category)) ||
                (property_exists($data, 'tags') && !empty($data->tags))) {

                $fetched_term_internalIDs = json_decode($data->category);

                if (!empty(json_decode($data->tags))) {
                    $fetched_term_internalIDs = array_merge($fetched_term_internalIDs, json_decode($data->tags));
                }

                $term_ids = Toret_Manager_Module_Term::get_instance($this->toret_manager)->get_associted_local_term_ids($fetched_term_internalIDs, 'unknown', $data->postType, false, true);

                $post_tags = array();
                $post_cats = array();
                foreach ($term_ids as $term_id) {
                    $term_taxonomy = Toret_Manager_Helper::get_term_taxonomy($term_id);
                    if (!empty($term_taxonomy)) {
                        $fetched_term = get_term_by('id', $term_id, $term_taxonomy);
                        if (!empty($fetched_term)) {

                            if ($term_taxonomy == 'post_tag') {
                                $post_tags[] = $fetched_term->term_id;
                            } else if ($term_taxonomy == 'category') {
                                $post_cats[] = $fetched_term->term_id;
                            } else {
                                wp_set_object_terms($id, $fetched_term->slug, $term_taxonomy);
                            }

                        }
                    }
                }

                if (!empty($post_cats)) {
                    wp_set_post_categories($id, $post_cats);
                }

                if (!empty($post_tags)) {
                    wp_set_post_tags($id, $post_tags);
                }

            }

        }

        if (in_array('commentsInternalID', $data_to_be_synchronized) && property_exists($data, 'commentsInternalID')) {
            $internal_ids = json_decode($data->commentsInternalID, true);
            Toret_Manager_Module_Review::delete_comments($id);
            if (!empty($internal_ids)) {
                foreach ($internal_ids as $internal_id) {
                    Toret_Manager_Module_Review::get_instance($this->toret_manager)->get_associated_comment($id, $internal_id, $update_array['post_type']);
                }
            }
        }

        $url_parts = explode("/", $data->url);
        wp_update_post(
            array(
                'ID' => $id,
                'post_date' => $data->createdDate,
                'post_date_gmt' => get_gmt_from_date($data->createdDate),
                'post_name' => $url_parts[count($url_parts) - 2]
            )
        );

		if($data->isSticky){
			stick_post($id);
		}

        return $id;
    }

    /**
     * Upload missing post if not exists
     *
     * @param mixed $item_id
     * @param string $module
     * @param bool $force
     * @param string $associated
     * @return mixed
     */
    function upload_missing_post($item_id, string $module = 'post', bool $force = false, string $associated = "")
    {
        return self::async_on_save_post($item_id, get_post($item_id), $force, $associated);
    }

    /**
     * Get post internal ID
     *
     * @param mixed $id
     * @param string $associated
     * @param bool $force
     * @return int|mixed|null
     */
    static function get_post_internal_ID($id, string $associated, bool $force = false)
    {
        if (empty($id)) {
            return -1;
        }

        if ($associated == 'review')
            $internalID = Toret_Manager_Helper_Db::get_object_meta($id, TORET_MANAGER_ITEM_INTERNALID, 'product');
        else if ($associated == 'order_note')
            $internalID = Toret_Manager_Helper_Db::get_object_meta($id, TORET_MANAGER_ITEM_INTERNALID, 'order');
        else
            $internalID = Toret_Manager_Helper_Db::get_object_meta($id, TORET_MANAGER_ITEM_INTERNALID);

        if (empty($internalID) || $force) {

            if (Toret_Manager_Helper_Modules::should_sync_associated($associated) || $force) {
                if ($associated == 'review')
                    $internalID = Toret_Manager_Module_Product::get_instance(TORET_MANAGER_SLUG)->upload_missing_product($id, true, $associated);
                elseif ($associated == 'order_note')
                    $internalID = Toret_Manager_Module_Post::get_instance(TORET_MANAGER_SLUG)->upload_missing_post($id, 'order', true, $associated);
                else
                    $internalID = Toret_Manager_Module_Post::get_instance(TORET_MANAGER_SLUG)->upload_missing_post($id, get_post_type($id), true, $associated);
            }

            if (empty($internalID)) {
                $internalID = -1;
            }

        }

        return $internalID;

    }

}
