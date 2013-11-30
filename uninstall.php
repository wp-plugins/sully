<?php
// if not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();

// SULly leaves all users settings alone during uninstall, however an admin can remove 
// them from the admin settings page.  If that has been done then the two last setting will be
// left to remove.
delete_option( 'SULly_DBVersion' );
delete_option( 'SULly_Removed' );
?>
