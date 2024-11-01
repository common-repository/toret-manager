<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Toret_Manager_Notifiy_Queue {

	public string $table_name = TORET_MANAGER_NOTIFY_QUEUE_TABLE;


	/**
	 * Class constructor
	 */
	function __construct() {
		add_action( 'toret_manager_notify_process', array( $this, 'toret_manager_notify_process' ) );
	}


	/**
	 * Create notify queue table
	 */
	public function create_queue_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . $this->table_name;

		$sql = "CREATE TABLE $table_name (  
		module varchar(100) NOT NULL,         
		type varchar(100) NOT NULL,         
		internalID varchar(100) NOT NULL,
		notificationType varchar(100) NOT NULL,
		datetime bigint(20) NOT NULL,
		stockQuantity mediumint(10) NOT NULL,
		checkhash varchar(120) NOT NULL,
		unique Key checkhash (checkhash)
	) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Save notification
	 *
	 * @param $internalID
	 * @param $module
	 * @param $type
	 * @param $notificationType
	 * @param int $stockQuantity
	 */
	public function save_notification( $internalID, $module, $type, $notificationType, int $stockQuantity = 0 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . $this->table_name;

		$checkHash = self::generate_hash();
		$data      = array(
			'internalID'       => $internalID,
			'module'           => $module,
			'type'             => $type,
			'checkhash'        => $checkHash,
			'notificationType' => $notificationType,
			'stockQuantity'    => $stockQuantity,
			'datetime'         => time(),
		);
		$wpdb->insert( $table_name, $data );

		/*if ( get_option( TORET_MANAGER_NOTIFY_WP_SCHEDULER, 'ok' ) == 'ok' ) {
			wp_schedule_single_event( time(), 'toret_manager_notify_process', array( $checkHash ) );
		}*/
	}

	/**
	 * Delete notification
	 *
	 * @param $internalID
	 * @param $notificationType
	 *
	 * @return void
	 */
	function delete_notification( $internalID, $notificationType ) {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->table_name;
		$wpdb->delete( $table_name, array( 'internalID' => $internalID, 'notificationType' => $notificationType ) );
	}

	/**
	 * Delete all notifications
	 *
	 * @return void
	 */
	function delete_notifications() {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->table_name;
		$wpdb->query( "TRUNCATE TABLE $table_name" );
	}

	/**
	 * Get queue
	 *
	 * @param int $limit
	 *
	 * @return array|object|stdClass[]|null
	 */
	public function get_queue( int $limit = 50 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->table_name;

		if ( $limit == - 1 ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i ORDER BY datetime", array(
				$table_name
			) ) );
		} else {
			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i ORDER BY datetime LIMIT %d", array(
				$table_name,
				$limit
			) ) );
		}
	}

	/**
	 * Process notification action
	 *
	 * @param $notify
	 */
	public function process_notification_action( $notify ) {
		$module           = $notify->module;
		$type             = $notify->type;
		$internalID       = $notify->internalID;
		$notificationType = $notify->notificationType;
		$stockQuantity    = $notify->stockQuantity;

		if ( $notificationType == "Post created" || $notificationType == "Post updated" ) {
			Toret_Manager_Module_General::notify_item_change( $internalID, $module, $type );
		} else if ( $notificationType == "Post deleted" ) {
			Toret_Manager_Module_General::notify_item_delete( $internalID, $type, 'any' );
		}

		// Product items
		if ( $notificationType == "Product created" || $notificationType == "Product updated" ) {
			if ( Toret_Manager_Helper_Modules::is_module_enabled( 'product' ) ) {
				Toret_Manager_Module_General::notify_item_change( $internalID, 'product', $type );
			}
		} else if ( $notificationType == "Product deleted" ) {
			if ( Toret_Manager_Helper_Modules::is_module_enabled( 'product' ) ) {
				Toret_Manager_Module_General::notify_item_delete( $internalID, $type, 'product' );
			}
		}

		// Order items
		if ( $notificationType == "Order created" || $notificationType == "Order updated" ) {
			if ( Toret_Manager_Helper_Modules::is_module_enabled( 'order' ) ) {
				Toret_Manager_Module_General::notify_item_change( $internalID, 'order', $type );
			}
		} else if ( $notificationType == "Order deleted" ) {
			if ( Toret_Manager_Helper_Modules::is_module_enabled( 'order' ) ) {
				Toret_Manager_Module_General::notify_item_delete( $internalID, $type, 'order' );
			}
		}

		// Comment items
		if ( $notificationType == "Comment created" || $notificationType == "Comment updated" ) {
			Toret_Manager_Module_General::notify_item_change( $internalID, $module, $type );
		} else if ( $notificationType == "Comment deleted" ) {
			Toret_Manager_Module_General::notify_item_delete( $internalID, $type, 'comment' );

		}

		// Customer items
		if ( $notificationType == "Customer created" || $notificationType == "Customer updated" ) {
			if ( Toret_Manager_Helper_Modules::is_module_enabled( 'user' ) ) {
				Toret_Manager_Module_General::notify_item_change( $internalID, 'user', $type );
			}
		} else if ( $notificationType == "Customer deleted" ) {
			if ( Toret_Manager_Helper_Modules::is_module_enabled( 'user' ) ) {
				Toret_Manager_Module_General::notify_item_delete( $internalID, $type, 'user' );
			}
		}

		// Stock items
		if ( $notificationType == "Stock updated" ) {
			if ( Toret_Manager_Helper_Modules::is_stock_sync_enabled( 'download' ) ) {
				$stock_module = new Toret_Manager_Module_Stock( TORET_MANAGER_SLUG );
				$stock_module->notify_stock_change( $internalID, $stockQuantity );
			}
		}

		// Category items
		if ( $notificationType == "Category created" || $notificationType == "Category updated" ) {
			$product_term_module = new Toret_Manager_Module_Term( TORET_MANAGER_SLUG );
			$product_term_module->notify_term_change( $internalID );
		} else if ( $notificationType == "Category deleted" ) {
			$term_module = new Toret_Manager_Module_Term( TORET_MANAGER_SLUG );
			$term_module->notify_term_delete( $internalID );
		}

		// Delete notifications
		//if ( $notificationType != "Stock updated" ) {
		$this->delete_notification( $internalID, $notificationType );
		//}
	}

	/**
	 * Process notification
	 *
	 */
	public function toret_manager_notify_process() {
		trman_process_notification();
	}


	/**
	 * Get notification hash
	 *
	 * @return string
	 */
	function generate_hash(): string {
		$length     = 40; // Požadovaná délka hashe
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; // Množina znaků pro generování hashe
		$hash       = '';

		// Generování náhodného hashe
		for ( $i = 0; $i < $length; $i ++ ) {
			$randomIndex = random_int( 0, strlen( $characters ) - 1 );
			$hash        .= $characters[ $randomIndex ];
		}

		return $hash;
	}

	/**
	 * Create custom interval
	 *
	 * @param $schedules
	 *
	 * @return mixed
	 */
	function custom_cron_intervals( $schedules ) {
		$schedules['one_minute'] = array(
			'interval' => 60,
			'display'  => 'Každou minutu',
		);

		return $schedules;
	}


	/**
	 * Enable scheduler if needed
	 *
	 * @return void
	 */
	function init_scheduler_if_needed() {
		if ( get_option( TORET_MANAGER_NOTIFY_WP_SCHEDULER, 'ok' ) == 'ok' ) {
			if ( ! wp_next_scheduled( 'toret_manager_notify_process' ) ) {
				wp_schedule_event( time(), 'one_minute', 'toret_manager_notify_process' );
			}
		} else {
			wp_clear_scheduled_hook( 'toret_manager_notify_process' );
		}
	}


}