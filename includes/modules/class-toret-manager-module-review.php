<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Toret_Manager_Module_Review extends Toret_Manager_Module_General
{

    /**
     * Class instance
     *
     * @Toret_Manager_Module_Review Toret_Manager_Module_Review
     */
    protected static ?Toret_Manager_Module_Review $Toret_Manager_Module_Review_Instance = null;

    /**
     * Constructor
     *
     * @param string $toret_manager
     */
    public function __construct(string $toret_manager)
    {
        parent::__construct($toret_manager);

        $this->internalID_key = TORET_MANAGER_ITEM_INTERNALID;

        add_action('comment_post', array($this, 'on_comment_post'), 99, 3);
        add_action('async_on_post_comment', array($this, 'async_on_post_comment'), 99, 2);

        add_action('trash_comment', array($this, 'on_trash_comment'), 99, 2);
        add_action('async_on_trash_comment', array($this, 'async_on_trash_comment'), 99, 2);

        add_action('untrashed_comment', array($this, 'on_untrashed_comment'), 99, 2);
        add_action('async_on_untrashed_comment', array($this, 'async_on_untrashed_comment'), 99, 2);

        add_action('delete_comment', array($this, 'on_delete_comment'), 99, 2);
        add_action('async_on_delete_comment', array($this, 'async_on_delete_comment'), 99, 1);
    }

    /**
     * Get class instance
     *
     * @param string $toret_manager
     * @return Toret_Manager_Module_Review|null
     */
    public static function get_instance(string $toret_manager): ?Toret_Manager_Module_Review
    {
        if (null == self::$Toret_Manager_Module_Review_Instance) {
            self::$Toret_Manager_Module_Review_Instance = new self($toret_manager);
        }

        return self::$Toret_Manager_Module_Review_Instance;
    }

    /**
     * On local comment delete
     *
     * @param mixed $item_id
     */
    public function on_delete_comment($item_id)
    {
        $comment_type = get_comment_type($item_id);

        if (!Toret_Manager_Helper_Modules::is_sync_enabled($comment_type, 'delete',)) {
            return;
        }

        if (in_array('comment', TORET_MANAGER_ASYNC_UPLOAD_TYPES)) {
            wp_schedule_single_event(time(), 'async_on_delete_comment', array($item_id));
        } else {
            $this->async_on_delete_comment($item_id);
        }
    }

    /**
     * On trash comment
     *
     * @param mixed $item_id
     * @param mixed $comment
     */
    function on_trash_comment($item_id, $comment)
    {
        if (in_array('comment', TORET_MANAGER_ASYNC_UPLOAD_TYPES)) {
            wp_schedule_single_event(time(), 'async_on_trash_comment', array($item_id));
        } else {
            $this->async_on_post_comment($item_id, array('comment_post_ID' => $comment->comment_post_ID));
        }
    }

    /**
     * On untrashed comment
     *
     * @param mixed $item_id
     */
    function on_untrashed_comment($item_id,$comment)
    {
        if (in_array('comment', TORET_MANAGER_ASYNC_UPLOAD_TYPES)) {
            wp_schedule_single_event(time(), 'async_on_untrashed_comment', array($item_id));
        } else {
            $this->async_on_post_comment($item_id, array('comment_post_ID' => $comment->comment_post_ID));
        }
    }

    /**
     * Async on comment delete
     */
    function async_on_delete_comment($item_id)
    {
        Toret_Manager_Module_General::process_delete_post($item_id, get_comment_type($item_id), 'comment');
    }

    /**
     * On local comment save
     *
     * @param mixed $item_id
     * @param mixed $b
     * @param mixed $commentdata
     */
    public function on_comment_post($item_id, $b, $commentdata)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

	    if(defined('REST_REQUEST')){
		    return;
	    }

	    if ( defined( 'DOING_CRON' ) ) {
		    return;
	    }

	    if(isset($_POST['trman_run']) && $_POST['trman_run'] == 'queue'){
		    return;
	    }

        if (wp_is_post_revision($item_id) || wp_is_post_autosave($item_id)) {
            return;
        }

        if (!Toret_Manager_Helper_Modules::is_any_edit_sync_enabled(get_comment_type($item_id))) {
            return;
        }

        if (in_array('comment', TORET_MANAGER_ASYNC_UPLOAD_TYPES)) {
            wp_schedule_single_event(time(), 'async_on_post_comment', array($item_id, $commentdata));
        } else {
            $this->async_on_post_comment($item_id, $commentdata);
        }
    }

    /**
     * Async on comment edited
     *
     * @param mixed $item_id
     * @param mixed $commentdata
     * @param bool $force
     * @param array $edit_args
     * @return mixed|null
     * @throws Exception
     */
    function async_on_post_comment($item_id, $commentdata, bool $force = false, array $edit_args = [])
    {
        $internalID = Toret_Manager_Helper_Db::get_object_meta($item_id, $this->internalID_key, 'comment');
        $update = !empty($internalID);
        if (empty($internalID)) {
            $internalID = Toret_Manager_Helper::generate_internal_id(get_comment_type($item_id));
        }

        $edit_args['internalID'] = $internalID;
        $edit_args['postInternalID'] = Toret_Manager_Helper_Db::get_object_meta($commentdata['comment_post_ID'], $this->internalID_key, 'post');
        $edit_args['update'] = $update;
        $edit_args['action'] = $update ? 'update' : 'new';
        $edit_args['module'] = get_comment_type($item_id);
        $edit_args['type'] = 'comment';

        $nonce = wp_create_nonce('trman_edit_args_' . $item_id);

        $edit_args = Toret_Manager_Helper::edit_args_modification($edit_args, $item_id, $nonce);

        $data = self::itemDataArray($item_id, $edit_args);
        return Toret_Manager_Module_General::process_save_item_adv($item_id, $data, $force, $edit_args);
    }

    /**
     * Get comment data for upload
     *
     * @param mixed $comment_id
     * @param array $edit_args
     * @return array
     * @throws Exception
     */
    public function itemDataArray($comment_id, array $edit_args = []): array
    {
        $update = $edit_args['update'];

        $comment = get_comment($comment_id);

        $postInternalID = $edit_args['postInternalID'] ?? ($edit_args['internalID'] ?? '');

        $adjusted_type = $comment->comment_type;
        if (get_post_type($comment->comment_post_ID) == 'product' && $comment->comment_type == 'comment') {
            $adjusted_type = 'review';
        }

        $parentInternalID = $this->get_parent_internal_ID($comment->comment_parent, $adjusted_type, 'review');

        if (empty($postInternalID))
            $postInternalID = Toret_Manager_Module_Post::get_post_internal_ID($comment->comment_post_ID, $adjusted_type, true);


        $authorInternalID = Toret_Manager_Module_User::get_user_internal_ID($comment->user_id, $adjusted_type);

        $meta = get_comment_meta($comment_id);

        $meta = $this->reaarange_meta_array($meta, $adjusted_type);

        if ($adjusted_type == 'order_note') {
            $meta['added_by'] = $comment->comment_author;
        }

        $data = array(
            'commentID' => (int)$comment_id,
            'postID' => (int)$comment->comment_post_ID,
            'postInternalID' => (string)$postInternalID,
            'parentID' => (int)$comment->comment_parent,
            'parentInternalID' => (string)$parentInternalID,
            'commentAuthor' => $comment->comment_author,
            'authorInternalID' => (string)$authorInternalID,
            'commentAuthorEmail' => $comment->comment_author_email,
            'commentContent' => $comment->comment_content,
            'editUrl' => Toret_Manager_Module_General::custom_get_edit_comment_link($comment_id),
            'commentAuthorUrl' => $comment->comment_author_url,
            'commentDate' => Toret_Manager_DateTime::format_date_for_api($comment->comment_date, false),
            'commentApproved' => $comment->comment_approved,
            'commentType' => $adjusted_type,
            'commentUserID' => $comment->user_id,
            'productRating' => (float)(get_comment_meta($comment_id, 'rating', true) ?: 0),
            'verified' => (bool)get_comment_meta($comment_id, 'verified', true),
            'meta' => wp_json_encode($meta),
        );

        return apply_filters('toret_manager_sent_' . $adjusted_type . '_data', $data, $comment, $update);
    }

    /**
     * Save notified comment
     *
     * @param mixed $commentData
     * @param array $data_to_be_synchronized
     * @param bool $markSynced
     * @param array $override
     * @return int|string|null
     */
    function save_item($commentData, array $data_to_be_synchronized, bool $markSynced = false, array $override = [])
    {
        if (!empty($commentData->parentInternalID) && $commentData->parentInternalID != -1) {
            $parent_id = Toret_Manager_Module_General::get_associted_local_id($commentData->parentInternalID, $commentData->commentType, $commentData->commentType, 'comment', true, true);
        }

        if (!empty($commentData->postInternalID) && $commentData->postInternalID != -1) {
            if (!empty($override['comment_post_ID'])) {
                $post_id = $override['comment_post_ID'];
            } else {
                $post_type = 'post';
                if ($commentData->commentType == 'order_note') {
                    $post_type = 'order';
                } elseif ($commentData->commentType == 'review') {
                    $post_type = 'product';
                }
                $post_id = Toret_Manager_Module_General::get_associted_comment_post_local_id($commentData->postInternalID, $commentData->commentType, $post_type, true);
            }
        }

        if (!empty($commentData->authorInternalID) && $commentData->authorInternalID != -1) {
            $user_id = Toret_Manager_Module_General::get_associted_local_id($commentData->authorInternalID, $commentData->commentType, 'user', 'user', false, true);
        }

        $update = false;
        if (!empty($post_id)) {
            $existing_id = Toret_Manager_Helper_Db::get_comment_by_meta_value($post_id, TORET_MANAGER_ITEM_INTERNALID, $commentData->internalID);
            $update = !empty($existing_id);
        }

        foreach ($commentData as $property => $item) {

            if (in_array($property, $data_to_be_synchronized)) {

                $filter = apply_filters('toret_manager_review_notified_should_process_item', false, $item, $property, $commentData);
                if (!empty($filter)) {
                    do_action('toret_manager_review_notified_process_item', $item, $property, $commentData);
                    continue;
                }

                if ($property == 'postID' && !empty($post_id)) {
                    $data['comment_post_ID'] = $post_id;
                } elseif ($property == 'commentContent') {
                    $data['comment_content'] = $commentData->commentContent;
                } elseif ($property == 'parentID' && !empty($parent_id)) {
                    $data['comment_parent'] = $parent_id;
                } elseif ($property == 'commentUserID' && !empty($user_id)) {
                    $data['user_id'] = $user_id;
                } elseif ($property == 'commentAuthor') {
                    $data['comment_author'] = $commentData->commentAuthor;
                } elseif ($property == 'commentAuthorEmail') {
                    $data['comment_author_email'] = $commentData->commentAuthorEmail;
                } elseif ($property == 'commentAuthorUrl') {
                    $data['comment_author_url'] = $commentData->commentAuthorUrl;
                } elseif ($property == 'commentDate') {
                    $data['comment_date'] = $commentData->commentDate;
                } elseif ($property == 'commentApproved') {
                    $data['comment_approved'] = $commentData->commentApproved;
                } elseif ($property == 'commentType') {
                    $data['comment_type'] = $commentData->commentType;
                } elseif ($property == 'productRating') {
                    $data['productRating'] = $commentData->productRating;
                } elseif ($property == 'verified') {
                    $data['verified'] = $commentData->verified;
                }

            }
        }

        if (!empty($data)) {

            if ($update && !empty($existing_id)) {

                $comment_id = $existing_id;

                $updated = wp_update_comment($data);

                if (is_wp_error($comment_id)) {

                    $log = array(
                        'type' => 3,
                        'module' => ucfirst($commentData->commentType),
                        'submodule' => 'Notification - Update',
                        'context' => __('Failed to update item', 'toret-manager'),
                        'log' => wp_json_encode(array('Local ID' => $comment_id, 'API internal ID' => $commentData->internalID, 'Error' => wp_json_encode($comment_id))),
                    );

                } else {
                    $log = array(
                        'type' => 1,
                        'module' => ucfirst($commentData->commentType),
                        'submodule' => 'Notification - Update',
                        'context' => __('Item updated', 'toret-manager'),
                        'log' => wp_json_encode(array('Local ID' => $comment_id, 'API internal ID' => $commentData->internalID)),
                    );
                }
                trman_log($this->toret_manager, $log);

            } else if (!empty($post_id)) {

                $comment_id = wp_insert_comment($data);

                if (is_wp_error($comment_id)) {

                    $log = array(
                        'type' => 3,
                        'module' => ucfirst($commentData->commentType),
                        'submodule' => 'Notification - ' . ($update ? 'Update' : 'Created'),
                        'context' => ($update ? __('Failed to update item', 'toret-manager') : __('Failed to create item', 'toret-manager')),
                        'log' => wp_json_encode(array('Local ID' => $comment_id, 'API internal ID' => $commentData->internalID, 'Error' => wp_json_encode($comment_id))),
                    );
                    trman_log($this->toret_manager, $log);

                    return null;
                } else {
                    $log = array(
                        'type' => 1,
                        'module' => ucfirst($commentData->commentType),
                        'submodule' => 'Notification - ' . ($update ? 'Update' : 'Created'),
                        'context' => ($update ? __('Item updated', 'toret-manager') : __('Item created', 'toret-manager')),
                        'log' => wp_json_encode(array('Local ID' => $comment_id, 'API internal ID' => $commentData->internalID)),
                    );
                    trman_log($this->toret_manager, $log);
                }

            }
        }

        if (in_array('meta', $data_to_be_synchronized) && !empty($comment_id)) {
            $meta = json_decode($commentData->meta, true);
            foreach ($meta as $meta_key => $meta_value) {
                update_comment_meta($comment_id, $meta_key, $meta_value);
            }
        }

        if (!empty($comment_id)) {

            if (!$update) {
                $imported_status = get_option('trman_module_' . $commentData->commentType . '_imported_status', "default");
                if ($imported_status != "default")
                    wp_set_comment_status($comment_id, $imported_status);
            }

            if ($markSynced) {
                update_comment_meta($comment_id, TORET_MANAGER_ASSOCIATIVE_SYNC, '1');
            }

            update_comment_meta($comment_id, TORET_MANAGER_ITEM_INTERNALID, $commentData->internalID);

            return $comment_id;

        } else {
            return null;
        }
    }

    /**
     * Upload missing comment if not exists
     *
     * @param mixed $item_id
     * @param bool $force
     * @param array $edit_args
     * @return mixed|null
     * @throws Exception
     */
    function upload_missing_comment($item_id, bool $force = false, array $edit_args = [])
    {
        $comment = get_comment($item_id);
        $commentData = array(
            'comment_post_ID' => $comment->comment_post_ID,
        );
        return self::async_on_post_comment($item_id, $commentData, $force, $edit_args);
    }

    /**
     * Get post comments internal IDs
     *
     * @param mixed $comments
     * @param array $edit_args
     * @return array
     * @throws Exception
     */
    function get_comments_internal_id($comments, array $edit_args = []): array
    {
        $internalIDs = [];

        foreach ($comments as $comment) {
            $internalid = get_comment_meta($comment->comment_ID, TORET_MANAGER_ITEM_INTERNALID, true);
            if (!empty($internalid)) {
                $internalIDs[] = $internalid;
            } else {
                $internalIDs[] = $this->upload_missing_comment($comment->comment_ID, true, $edit_args);
            }
        }

        return $internalIDs;
    }

    /**
     * Delete comments before load new ones
     *
     * @param mixed $post_id
     * @param string $type
     */
    static function delete_comments($post_id, string $type = '')
    {
        global $wpdb;
        if ($type != '') {
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->comments} WHERE comment_post_ID=%s;", $post_id));
        } else {
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->comments} WHERE comment_type=%s AND comment_post_ID=%s;", array($type, $post_id)));
        }
    }

    /**
     * Get associated local id
     *
     * @param mixed $order_id
     * @param string $internalID
     * @return int|mixed|string|WC_Order|WP_Post|null
     */
    function get_associated_order_note($order_id, string $internalID)
    {
        $local_ID = Toret_Manager_Helper_Db::get_post_by_meta_value($this->internalID_key, $internalID, 'review', 'order_note');

        if (empty($local_ID)) {

            if (Toret_Manager_Helper_Modules::should_sync_associated("order")) {

                $itemData = self::get_item_from_cloud($internalID, 'order_note');

                if (!empty($itemData)) {
                    $data_to_be_synchronized = Toret_Manager_Helper_Modules::data_to_be_sync('order_note', 'comment', 'new', Toret_Manager_Helper_Modules::get_mandatory_items('comment'), 'download');
                    return Toret_Manager_Module_Review::get_instance($this->toret_manager)->save_item($itemData, $data_to_be_synchronized, true, array('comment_post_ID' => $order_id));
                }

            }
        }

        return $local_ID;
    }

    /**
     * Get associated local id
     *
     * @param mixed $post_id
     * @param string $internalID
     * @param string $post_type
     * @param string $comment_type
     * @return int|mixed|string|WC_Order|WP_Post|null
     */
    function get_associated_comment($post_id, string $internalID, string $post_type, string $comment_type = 'comment')
    {
        $local_ID = Toret_Manager_Helper_Db::get_post_by_meta_value($this->internalID_key, $internalID, 'comment', $comment_type);

        if (empty($local_ID)) {

            if (Toret_Manager_Helper_Modules::should_sync_associated($post_type)) {
                $itemData = self::get_item_from_cloud($internalID, $comment_type);

                if (!empty($itemData)) {
                    $data_to_be_synchronized = Toret_Manager_Helper_Modules::data_to_be_sync($comment_type, 'comment', 'new', Toret_Manager_Helper_Modules::get_mandatory_items('comment'), 'download');
                    return Toret_Manager_Module_Review::get_instance($this->toret_manager)->save_item($itemData, $data_to_be_synchronized, true, array('comment_post_ID' => $post_id));
                }

            }
        }

        return $local_ID;
    }

    /**
     * Get associated local id
     *
     * @param mixed $post_id
     * @param string $internalID
     * @param string $post_type
     * @param string $comment_type
     * @return int|mixed|string|WC_Order|WP_Post|null
     */
    function get_associated_review($post_id, string $internalID, string $post_type, string $comment_type = 'review')
    {
        $local_ID = Toret_Manager_Helper_Db::get_post_by_meta_value($this->internalID_key, $internalID, 'review', $comment_type);

        if (empty($local_ID)) {
            if (Toret_Manager_Helper_Modules::should_sync_associated($post_type)) {

                $itemData = self::get_item_from_cloud($internalID, $comment_type);

                if (!empty($itemData)) {
                    $data_to_be_synchronized = Toret_Manager_Helper_Modules::data_to_be_sync($comment_type, 'comment', 'new', Toret_Manager_Helper_Modules::get_mandatory_items('comment'), 'download');
                    return Toret_Manager_Module_Review::get_instance($this->toret_manager)->save_item($itemData, $data_to_be_synchronized, true, array('comment_post_ID' => $post_id));
                }

            }
        }

        return $local_ID;
    }


}
