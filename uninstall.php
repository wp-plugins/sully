<?php
//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();

delete_option( 'SULly_DBVersion' );
delete_option( 'SULly_Removed' );
?>
