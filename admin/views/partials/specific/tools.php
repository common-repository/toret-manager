<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<div class="trman-admin-body-container trman-module-tools" id="m-tools">
    <div class="trman-admin-container-title-wrap">
        <h2><?php esc_html_e('Tools','toret-manager'); ?></h2>
    </div>
        <?php
        include_once('first_sync.php');
        include_once('clear.php');
        ?>
</div>
