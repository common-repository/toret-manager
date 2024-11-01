<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Toret_Manager_Log
{

    /**
     * Log class instance
     *
     * @Toret_Manager_Log Toret_Manager_Log
     */
    protected static ?Toret_Manager_Log $instance = null;

    /**
     * Table name
     *
     * @string $table_name
     */
    protected string $table_name = TORET_MANAGER_LOG_TABLE;

    /**
     * Limit for pagination
     *
     * @int $limit
     */
    private int $limit = 200;

    /**
     * Toret Manager slug
     *
     * @string $toret_manager
     */
    private string $toret_manager;

    /**
     * Constructor
     *
     * @param string $toret_manager
     */
    public function __construct(string $toret_manager)
    {
        $this->toret_manager = $toret_manager;
    }

    /**
     * Get log class instance
     *
     * @param string $toret_manager
     * @return Toret_Manager_Log|null
     */
    public static function get_instance(string $toret_manager): ?Toret_Manager_Log
    {
        if (null == self::$instance) {
            self::$instance = new self($toret_manager);
        }

        return self::$instance;
    }

    /**
     * Create log table
     */
    public function create_log_table()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . $this->table_name;

        $sql = "CREATE TABLE $table_name (
		ID mediumint(9) NOT NULL AUTO_INCREMENT,    
		module varchar(100) NOT NULL,         
		submodule varchar(100) NOT NULL,         
		date varchar(100) NOT NULL,
		datetime bigint(20) NOT NULL,
		type bigint(10) DEFAULT 0,
		context varchar(200),
		log longtext,
		UNIQUE KEY ID (ID)
	) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Clear old logs after certain period
     */
    public function clear_old_logs()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . $this->table_name;
        $target_date = strtotime('-7 days');
        $wpdb->query($wpdb->prepare("DELETE FROM %i WHERE datetime < %s ", $table_name, $target_date));
    }

    /**
     * Render log table
     */
    public function render_table($filter): string
    {
        $logs = $this->get_logs($filter);

        $html = '';

        if (!$logs) {
            $html = '<div class="trman-log-row-wrap"><strong>' . esc_html__('Log is empty', 'toret-manager') . '</strong></div>';
        } else {
            foreach ($logs as $log) {
                $html .= $this->render_table_line($log);
            }
        }

        return $html;
    }

    /**
     * Get logs for table
     *
     * @param string $filter
     * @return array
     */
    public function get_logs(string $filter): array
    {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->table_name;

        if (isset($_GET['trman_log_page_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['trman_log_page_nonce'])), 'trman-log-page')) {
            $offset = esc_attr(sanitize_text_field($_GET['offset']));
        }

        if (isset($offset) && $offset > 1) {


            $start = ($offset * $this->limit) - $this->limit;

            if ($filter == 'all') {
                $logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i ORDER BY datetime DESC LIMIT %d OFFSET %d", $table_name, $this->limit, $start));
            } else {
                $logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i WHERE module=%s ORDER BY datetime DESC LIMIT %d OFFSET %d", $table_name, $filter, $this->limit, $start));
            }

        } else {
            if ($filter == 'all') {
                $logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i ORDER BY datetime DESC LIMIT %d", $table_name, $this->limit));
            } else {
                $logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i WHERE module=%s ORDER BY datetime DESC LIMIT %d", $table_name, $filter, $this->limit));
            }

        }

        if (!empty($logs)) {

            return $logs;

        } else {

            return array();

        }
    }

    /**
     * Render log table row
     *
     * @param object $log
     * @return string
     */
    public function render_table_line(object $log): string
    {
        if ($log->type == 1)
            $log_type = 'trman-log-line-ok';
        else if ($log->type == 2)
            $log_type = 'trman-log-line-warning';
        else if ($log->type == 3)
            $log_type = 'trman-log-line-warning';
        else
            $log_type = 'trman-log-line-info';

        return '
            <div class="trman-log-row-wrap">
            <div class="trman-log-table-header-row ' . esc_html($log_type) . '">' .
            __('Date: ', 'toret-manager') . esc_html(gmdate('H:i:s d.m.Y', $log->datetime)) . '<br>' . __('Module: ', 'toret-manager') . esc_html($log->module) . ' - ' . esc_html($log->submodule) . '<br>' . __('Context: ', 'toret-manager') . esc_html($log->context) . '
            </div>
            <div class="trman-log-table-footer-row">' . esc_html(htmlentities($log->log)) . '
            </div>
            </div>
        ';
    }

    /**
     * Save log into database
     *
     * @param array $data
     */
    public function save_log(array $data)
    {
        $context = $data['context'] ?? '---';
        $type = $data['type'] ?? '---';

        if (!empty($data['log'])) {
            if (is_array($data['log']) || is_object($data['log'])) {
                $log = wp_json_encode($data['log']);
            } else {
                $log = $data['log'];
            }
        } else {
            $log = '---';
        }

        $data = array(
            'module' => $data['module'],
            'submodule' => $data['submodule'],
            'datetime' => time(),
            'context' => $context,
            'type' => $type,
            'log' => $log
        );

        global $wpdb;
        $wpdb->insert($wpdb->prefix . $this->table_name, $data);
    }

    /**
     * Clear log table
     */
    public function delete_logs(): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->table_name;
        $wpdb->query($wpdb->prepare('TRUNCATE TABLE %i', $table_name));
    }

    /**
     * Log table pagination
     *
     * @param string $filter
     * @return string
     */
    public function pagination(string $filter): string
    {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->table_name;

        if ($filter == 'all') {
            $logs = $wpdb->get_results($wpdb->prepare("SELECT ID FROM %i ORDER BY datetime DESC", $table_name));
        } else {
            $logs = $wpdb->get_results($wpdb->prepare("SELECT ID FROM %i WHERE module=%s ORDER BY datetime DESC", $table_name, $filter));
        }

        if (empty($logs)) {
            return '';
        }

        $all = count($logs);
        $pages = ceil($all / $this->limit);
        $current = 1;


        if (isset($_GET['trman_log_page_nonce']) &&  wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['trman_log_page_nonce'])), 'trman-log-page')) {
            if (!empty($_GET['offset'])) {
                $current = esc_attr(sanitize_text_field($_GET['offset']));
            }
        }

        $html = '<div class="trman-log-pagination">';
        $query_string = htmlspecialchars(sanitize_text_field($_SERVER['QUERY_STRING']), ENT_QUOTES); //TODO FIND CORRECT ESCAPE

        if ($pages != 1) {

            for ($i = 1; $i <= $pages; $i++) {

                $url = admin_url('admin.php?') . $query_string . '&offset=' . $i;
                $url = wp_nonce_url($url, 'trman-log-page', 'trman_log_page_nonce');

                if ($current == $i) {
                    $html .= '<span class="button trman-pag-button trman-pag-button-active">' . $i . '</span>';
                } else {
                    $html .= '<a class="button trman-pag-button" href="' . $url . '">' . $i . '</a>';
                }

            }

        }

        $html .= '</div>';

        return $html;
    }

}

/**
 * Save log
 *
 * @param string $slug
 * @param $data
 */
function trman_log(string $slug, $data)
{
    $log = Toret_Manager_Log::get_instance($slug);
    $log->save_log($data);
}

/**
 * Var dump into debug.log
 *
 * @param $object
 */
if (!function_exists('trman_var_error_log')) {
    function trman_var_error_log($object = null)
    {
        ob_start();                    // start buffer capture
        var_dump($object);           // dump the values
        $contents = ob_get_contents(); // put the buffer into a variable
        ob_end_clean();                // end capture
        error_log($contents);        // log contents of the result of var_dump( $object )
    }
}