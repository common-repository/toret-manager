<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Admin Log Page
 */
global $wpdb;

$Toret_Manager_Log = Toret_Manager_Log::get_instance( $this->toret_manager );


if (( isset( $_GET[ 'trman_log_delete_nonce' ] ) && wp_verify_nonce(sanitize_text_field(wp_unslash( $_GET[ 'trman_log_delete_nonce' ])), 'trman-log-delete' )) && isset( $_GET['delete'] ) ) {
	$Toret_Manager_Log->delete_logs();
	wp_redirect( TORET_MANAGER_LOG_PAGE );
}

/*if ( isset( $_POST['reset-counter'] ) ) {
    update_option(TORET_MANAGER_NOTIFY_COUNTER,0);
}*/

$filter = 'all';
if ( isset( $_GET['filter'] ) ) {
	$filter = sanitize_text_field($_GET['filter']);
}

$log_table_name = $wpdb->prefix . 'toret_manager_log';
$unique_modules = $wpdb->get_results($wpdb->prepare( "SELECT DISTINCT module  FROM  %i",$log_table_name));

//$counter =  '<div style="display: flex;align-items: center;">' .__( 'Number of notifications: ', 'toret-manager') . get_option(TORET_MANAGER_NOTIFY_COUNTER,0);
//$counter .= '<form method="post"><input style="margin-left: 5px" type="submit" name="reset-counter" id="tr-reset-counter" class="button" value="'.__('Reset counter','toret-manager').'"/></form></div>';
?>

<div class="trman-admin-log-wrap">

    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php
	if ($Toret_Manager_Log->get_logs( $filter ) ) {
		?>

        <form method="get">
            <label for="trman-log-filter"><?php esc_html_e( 'Filter by module', 'toret-manager'); ?></label>
            <select name="filter" id="trman-log-filter">
				<?php
				echo '<option ' . ( $filter == 'all' ? "selected" : "" ) . ' value="all">' . esc_html__( 'All', 'toret-manager') . '</option>';
				foreach ( $unique_modules as $unique_module ) {
					echo '<option ' . ( esc_attr($unique_module->module) == $filter ? "selected" : "" ) . ' value="' . esc_attr($unique_module->module) . '">' . esc_html($unique_module->module) . '</option>';
				}
				?>
            </select>
            <input type="hidden" name="page" value="toret-manager-log"/>
        </form>

		<?php
	}
	?>

    <!--
    <p><?php //echo $counter;?></p>-->

    <div class="trman-admin-log-body-wrap">
		<?php
		echo wp_kses_post($Toret_Manager_Log->render_table( $filter ))
		?>
    </div>

    <div class="clear"></div>

    <div class="trman-admin-log-footer-wrap">
        <?php
        $url = wp_nonce_url(TORET_MANAGER_LOG_DELETE , 'trman-log-delete','trman_log_delete_nonce');
        ?>
        <div><a href="<?php echo esc_url($url); ?>"
                class="button button-primary toret-button"><?php esc_html_e( 'Delete log', 'toret-manager'); ?></a></div>
		<?php
		echo wp_kses_post($Toret_Manager_Log->pagination( $filter ));
		?>
    </div>

</div>

