<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$notify_queue = new Toret_Manager_Notifiy_Queue();
$notification = $notify_queue->get_queue( - 1 );


if ( isset( $_POST['delete_internalid'] ) ) {
	$internalID                  = $_POST['delete_internalid'];
	$notificationType            = $_POST['delete_type'];
	$Toret_Manager_Notifiy_Queue = new Toret_Manager_Notifiy_Queue();
	$Toret_Manager_Notifiy_Queue->delete_notification( $internalID, $notificationType );
}

if ( isset( $_POST['trman_delete_all'] ) ) {
	$Toret_Manager_Notifiy_Queue = new Toret_Manager_Notifiy_Queue();
	$Toret_Manager_Notifiy_Queue->delete_notifications();
}

if ( isset( $_POST['trman_run'] ) ) {
	trman_process_notification();
}

?>

    <div class="trman-admin-log-wrap">


        <h1>Tools</h1>


        <div>
            <div>
                <h2>Procesy</h2>
				<?php display_toret_manager_notify_process(); ?>
            </div>


            <h2>Notifications</h2>
            <p>Počet notifikací ve frontě: <?php echo count( $notification ); ?></p>

            <div style="display: flex;flex-direction: row">
                <form method="post">
                    <input type="hidden" name="trman_delete_all" value="all"/>
                    <input type="submit" value="Smazat vše"/>
                </form>
                <form method="post">
                    <input type="hidden" name="trman_run" value="queue"/>
                    <input type="submit" value="Spustit"/>
                </form>
            </div>

            <table class="trman-admin-table" style="margin-top: 10px;">
                <tr>
                    <th>Datum</th>
                    <th>Modul</th>
                    <th>Typ</th>
                    <th>Interní ID</th>
                    <th>Typ notifikace</th>
                    <th>Množství na skladě</th>
                    <th>Akce</th>
                </tr>
				<?php

				if ( count( $notification ) == 0 ) {
					?>
                    <tr>
                        <td colspan="7">
                            <p>Ve frontě nejsou žádné notifikace</p>
                        </td>
                    </tr>
					<?php
				} else {
				foreach ( $notification as $notify ) {
					$datetime         = $notify->datetime;
					$module           = $notify->module;
					$type             = $notify->type;
					$internalID       = $notify->internalID;
					$notificationType = $notify->notificationType;
					$stockQuantity    = $notify->stockQuantity;
					?>
                    <tr>
                        <td><?php echo date( 'd.m.Y', $datetime ); ?></td>
                        <td><?php echo $module; ?></td>
                        <td><?php echo $type; ?></td>
                        <td><?php echo $internalID; ?></td>
                        <td><?php echo $notificationType; ?></td>
                        <td><?php echo $stockQuantity; ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="delete_internalid"
                                       value="<?php echo $internalID; ?>"/>
                                <input type="hidden" name="delete_type"
                                       value="<?php echo $notificationType; ?>"/>
                                <input type="submit" value="Smazat"/>
                            </form>
                        </td>
                    </tr>
					<?php
				}
				?>
            </table>
			<?php
			}

			?>
        </div>


    </div>


<?php

function display_toret_manager_notify_process() {
	$scheduled_events = _get_cron_array();

	if ( ! $scheduled_events ) {
		echo '<p>Žádné naplánované procesy nebyly nalezeny.</p>';

		return;
	}

	$found = false;
	foreach ( $scheduled_events as $timestamp => $events ) {
		foreach ( $events as $event_hook => $event_details ) {
			if ( $event_hook === 'toret_manager_notify_process' ) {
				$found         = true;
				$next_run      = wp_next_scheduled( 'toret_manager_notify_process' );
				$event_details = reset( $event_details );
				?>
                <div style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px;">
                    <h3>Toret Manager Notify Process</h3>
                    <p><strong>Název procesu:</strong> <?php echo esc_html( $event_hook ); ?></p>
                    <p><strong>Další spuštění:</strong> <?php echo date( 'Y-m-d H:i:s', $next_run ); ?></p>
                    <p><strong>Parametry:</strong> <?php echo implode( ', ', $event_details['args'] ); ?></p>
                    <p><strong>Interval:</strong> <?php echo $event_details['interval']; ?> sekund</p>
                </div>
				<?php
			}
			if ( $event_hook === 'wp_trman_initial_sync_cron' ) {
				$found         = true;
				$next_run      = wp_next_scheduled( 'wp_trman_initial_sync_cron' );
				$event_details = reset( $event_details );
				?>
                <div style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px;">
                    <h3>Toret Manager Initial Sync</h3>
                    <p><strong>Název procesu:</strong> <?php echo esc_html( $event_hook ); ?></p>
                    <p><strong>Další spuštění:</strong> <?php echo date( 'Y-m-d H:i:s', $next_run ); ?></p>
                    <p><strong>Parametry:</strong> <?php echo implode( ', ', $event_details['args'] ); ?></p>
                    <p><strong>Interval:</strong> <?php echo $event_details['interval']; ?> sekund</p>
                </div>
				<?php
			}
		}
	}

	if ( ! $found ) {
		echo '<p>Proces "toret_manager_notify_process" nebyl nalezen.</p>';
	}
}


