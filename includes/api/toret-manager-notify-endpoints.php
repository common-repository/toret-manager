<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Save notification to queue endpoint
 */
add_action( 'rest_api_init', function () {
	register_rest_route( 'api', '/notify', array(
		'methods'             => 'POST',
		'callback'            => 'trman_save_notification',
		'permission_callback' => '__return_true',
	) );
} );

function trman_save_notification( WP_REST_Request $request ) {
	$parameters = $request->get_body_params();

	if ( isset( $parameters['toretApi'] ) ) {
		$counter = (int) get_option( TORET_MANAGER_NOTIFY_COUNTER, '0' );
		$counter = $counter + 1;
		update_option( TORET_MANAGER_NOTIFY_COUNTER, $counter );
		$notification = $parameters['toretApi'];

		if ( ! isset( $notification['code'] ) ) {
			exit;
		}

		$code = $notification['code'];
		$type = $notification['data']['type'];
		$log  = array(
			'module'    => 'Notify',
			'submodule' => $type,
			'context'   => __( 'Notification', 'toret-manager' ),
			'log'       => wp_json_encode( $notification ),
		);
		trman_log( TORET_MANAGER_SLUG, $log );

		if ( $code == "200" ) {

			$notify_queue = new Toret_Manager_Notifiy_Queue();

			// Post items
			if ( $type == "Post created" || $type == "Post updated" ) {
				$post_type = $notification['data']['data']['postType'] ?? '';
				if ( Toret_Manager_Helper_Modules::is_module_enabled( $post_type ) ) {
					$notify_queue->save_notification( $notification['data']['data']['internalID'], $post_type, 'post', $type );
				}
			} else if ( $type == "Post deleted" ) {
				$notify_queue->save_notification( $notification['data']['internalID'], 'any', 'post', $type );
			}

			// Product items
			if ( $type == "Product created" || $type == "Product updated" ) {
				if ( Toret_Manager_Helper_Modules::is_module_enabled( 'product' ) ) {
					$notify_queue->save_notification( $notification['data']['data']['internalID'], 'product', 'product', $type );
				}
			} else if ( $type == "Product deleted" ) {
				if ( Toret_Manager_Helper_Modules::is_module_enabled( 'product' ) ) {
					$notify_queue->save_notification( $notification['data']['internalID'], 'product', 'product', $type );
				}
			}

			// Order items
			if ( $type == "Order created" || $type == "Order updated" ) {
				if ( Toret_Manager_Helper_Modules::is_module_enabled( 'order' ) ) {
					$notify_queue->save_notification( $notification['data']['data']['internalID'], 'order', 'order', $type );
				}
			} else if ( $type == "Order deleted" ) {
				if ( Toret_Manager_Helper_Modules::is_module_enabled( 'order' ) ) {
					$notify_queue->save_notification( $notification['data']['internalID'], 'order', 'order', $type );
				}
			}

			// Comment items
			if ( $type == "Comment created" || $type == "Comment updated" ) {
				$review_type = $notification['data']['data']['type'] ?? '';
				if ( Toret_Manager_Helper_Modules::is_module_enabled( $review_type ) ) {
					$notify_queue->save_notification( $notification['data']['data']['internalID'], $review_type, 'comment', $type );
				}
			} else if ( $type == "Comment deleted" ) {
				$notify_queue->save_notification( $notification['data']['internalID'], 'comment', 'comment', $type );
			}

			// Customer items
			if ( $type == "Customer created" || $type == "Customer updated" ) {
				if ( Toret_Manager_Helper_Modules::is_module_enabled( 'user' ) ) {
					$notify_queue->save_notification( $notification['data']['data']['internalID'], 'user', 'user', $type );
				}
			} else if ( $type == "Customer deleted" ) {
				if ( Toret_Manager_Helper_Modules::is_module_enabled( 'user' ) ) {
					$notify_queue->save_notification( $notification['data']['internalID'], 'user', 'user', $type );
				}
			}

			// Stock items
			if ( $type == "Stock updated" ) {
				if ( Toret_Manager_Helper_Modules::is_stock_sync_enabled( 'download' ) ) {
					//$stock_module = new Toret_Manager_Module_Stock(TORET_MANAGER_SLUG);
					$notify_queue->save_notification( $notification['data']['data']['internalID'], 'stock', 'stock', $type, $notification['data']['data']['stockQuantity'] );
				}
			}

			// Category items
			if ( $type == "Category created" || $type == "Category updated" ) {
				$cat_type = $notification['data']['data']['type'] ?? '';
				$notify_queue->save_notification( $notification['data']['data']['internalID'], $cat_type, 'category', $type );
			} else if ( $type == "Category deleted" ) {
				$notify_queue->save_notification( $notification['data']['internalID'], 'category', 'category', $type );
			}

		}
	}
}


/**
 * Process notification endpoint
 */
add_action( 'rest_api_init', function () {
	register_rest_route( 'api', '/notify/process', array(
		'methods'             => 'GET',
		'callback'            => 'trman_process_notification',
		'permission_callback' => '__return_true',
	) );
} );

function trman_process_notification() {
	$notify_queue = new Toret_Manager_Notifiy_Queue();
	$notification = $notify_queue->get_queue();
	foreach ( $notification as $notify ) {
		$notify_queue->process_notification_action( $notify );
	}
}



/**
 * Process notification by WP scheduler
 */