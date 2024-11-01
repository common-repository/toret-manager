<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Toret_Manager_Init_Sync
{

    /**
     * Initial sync process
     *
     * @var Toret_Manager_Sync_Process
     */
    protected Toret_Manager_Sync_Process $trman_initial_sync;

    /**
     * Plugin slug
     *
     * @var string $toret_manager
     */
    protected string $toret_manager;

    /**
     * Constructor
     *
     * @param string $toret_manager
     */
    public function __construct(string $toret_manager)
    {
        $this->toret_manager = $toret_manager;

        add_action('plugins_loaded', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'process_handler'));
        add_action('admin_notices', array($this, 'add_notice'));
    }

    /**
     * Add sync notice
     */
    function add_notice()
    {
        $class = 'notice notice-info';
        $status = get_option('trman_init_sync_status');
        if ($status == "is_processing" || $status == "queued") {
            $completed = get_option('trman_sync_process_completed', 0);
            $total = get_option('trman_sync_process_total', 0);
            $message = __('Toret Manager: Synchronization in progress. Synchronized ', 'toret-manager') . $completed . __(' items from ', 'toret-manager') . $total . '.';
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
        }
    }

    /**
     * Load required sync classes
     */
    public function init()
    {
        require_once plugin_dir_path(__FILE__) . 'class-toret-manager-sync-task.php';
        require_once plugin_dir_path(__FILE__) . 'class-toret-manager-sync-process.php';
        $this->trman_initial_sync = new Toret_Manager_Sync_Process();
    }

    /**
     * Process sync handler
     */
    public function process_handler()
    {
        if (isset($_POST['trman_initial_sync_nonce'])) {
            $nonce = sanitize_text_field(wp_unslash($_POST['trman_initial_sync_nonce']));
            if (wp_verify_nonce($nonce, 'trman_initial_sync')) {
                if (isset($_POST['trman_module_for_sync_start'])) {
                    $this->run($_POST['trman_module_for_sync_start'], $_POST['trman_module_for_sync_type']);
                }
                if (isset($_POST['trman_module_for_sync_cancel'])) {
                    $this->cancel();
                }
            }
        }
    }

    /**
     * Cancel sync
     */
    protected function cancel()
    {
        update_option('trman_init_sync_status_cancel', 1);
        update_option('trman_init_sync_cancel_clicked', 1);
    }

    /**
     * Run sync
     *
     * @param string $module
     * @param string $type
     */
    protected function run(string $module, string $type)
    {
        update_option('trman_init_sync_run_clicked', 1);

        $ids = $this->get_ids($module);


        if (empty($ids)) {
            return;
        }

        foreach ($ids as $id) {
            $this->trman_initial_sync->push_to_queue(array($id, $module, $type));
        }

        $this->trman_initial_sync->save()->dispatch();
    }

    /**
     * Get post ids for sync
     *
     * @param string $module
     * @return array
     */
    protected function get_ids(string $module): array
    {
        $ids = Toret_Manager_Sync_Clear::get_not_synced_items($module, 'ids');

        if (empty($ids)) {
            update_option('trman_init_sync_status', '');
            update_option('trman_init_sync_run_clicked', 0);
            update_option('trman_sync_process_total', 0);
            return array();
        }
        update_option('trman_sync_process_total', count($ids));
        return $ids;

    }

}

/**
 * Initialize the sync process class
 */
new Toret_Manager_Init_Sync(TORET_MANAGER_SLUG);