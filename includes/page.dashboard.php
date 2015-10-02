<?php
		GLOBAL $wpdb, $_POST, $SULlyUtils;

		$TableName = $wpdb->prefix . "SULly";
		
		// If the user has selected an item to delete, delete it from the database.
		if( array_key_exists('SULlyDeleteItem', $_GET) )
			{
			$wpdb->delete( $TableName, array( 'id' => $_GET['SULlyDeleteItem'] ) );
			}
			
		// If the user has added a manual entry, add it to the database.
		if( array_key_exists('manualadd', $_GET) )
			{
			if( $_POST['SULlyMAItem'] == "" )
				{
				echo "<div class='updated settings-error'><p><strong>" . __('No item type defined!', 'sully') . "</strong></p></div>\n";
				}
			else
				{
				$wpdb->insert( $TableName, array( 'type' => $_POST['SULlyMAType'], 'version' => $_POST['SULlyMAVersion'], 'changelog' => $_POST['SULlyMAChangeLog'], 'itemname' => $_POST['SULlyMAItem'], 'nicename' => $_POST['SULlyMAItem'], 'filename' => 'Manual', 'itemurl' => 'Manual' ) );
				
				echo "<div class='updated settings-error'><p><strong>" . __('Manual item added!', 'sully') . "</strong></p></div>\n";
				}
			}

		// Update any failed installs in the database.
		SULlyUpdateFails();

		// Update any WordPress updates that have happened with details.
		SULlyUpdateCores();

		// Update the database for any updates that have happened to ourselves.
		SULlyUpdateMyself();
		
		// Check for any changes to the system
		SULlyUpdateSystemSettings( SULlyGetSystemInfo(), unserialize( get_option( 'SULly_System_Settings' ) ) );

		$NumToDisplay = $SULlyUtils->get_option( 'PageEntriesToDisplay' );
		if( $NumToDisplay < 1 ) { $NumToDisplay = 10; }

		// Set the current page we're on.
		$curpage = 1;
		if( isset( $_GET["pagenum"] ) ) { $curpage = $_GET["pagenum"]; }
		if( $curpage < 1 ) { $curpage = 1; }
		
		// Determine the first entry we're going to display.
		$pagestart = ( $curpage - 1 ) * $NumToDisplay;
		if( $pagestart < 1 ) { $pagestart = 0; }
		
		// Select the required rows from the database.
		$Rows = $wpdb->get_results( "SELECT * FROM $TableName ORDER BY time desc LIMIT " . $pagestart . "," . $NumToDisplay );
		$NumRows = $wpdb->num_rows;
		
		echo "<div class='wrap'>\r\n";
		echo "<h2>SULly - " . __('System Update Logger', 'sully') . "</h2><br>\r\n";

		echo '<form action="index.php?page=SULlyDashboard&pagenum=' . $curpage . '&manualadd=1" method="post">' . "\r\n";
		echo '<table class="wp-list-table widefat fixed"><thead><tr><th>' . __( 'Manual Entry', 'sully' ) . '</th><th>Type</th><th>' . __( 'Item', 'sully' ) . '</th><th>' . __( 'Version', 'sully' ) . '</th><th width="30%">' . __( 'Change Log', 'sully' ) . '</th><th>' . __( 'Options', 'sully' ) . '</th></tr></thead>' . "\r\n";
		echo '<tr>' . "\r\n";
		echo '<td>&nbsp;</td>' . "\r\n";
		echo '<td><select name="SULlyMAType" style="width: 100%"><option value="C">' . __( 'WordPress Core', 'sully' ) . '</option><option value="P" SELECTED>' . __( 'Plugin', 'sully' ) . '</option><option value="T">' . __( 'Theme', 'sully' ) . '</option><option value="S">' . __( 'System', 'sully') . '</option><option value="U">' . __( 'Unknown', 'sully' ) . '</option></select></td>' . "\r\n";
		echo '<td><input name="SULlyMAItem" type="text" style="width: 100%"/></td>' . "\r\n";
		echo '<td><input name="SULlyMAVersion" type="text"  style="width: 100%"/></td>' . "\r\n";
		echo '<td><textarea style="width: 100%" name="SULlyMAChangeLog"></textarea></td>' . "\r\n";
		echo '<td><input class="button-primary" type="submit" value="add"/></td></tr>' . "\r\n";
		echo '</table>' . "\r\n";
		echo '</form>' . "\r\n";

		echo '<br>' . "\r\n";

		echo '<table class="wp-list-table widefat fixed"><thead><tr><th>' . __('Time', 'sully') . '</th><th>' . __('Type', 'sully') . '</th><th>' . __('Item', 'sully') . '</th><th>' . __('Version', 'sully') . '</th><th>' . __('Change Log', 'sully') . '</th><th>' . __('Options', 'sully') . '</th></tr></thead>' . "\r\n";
		foreach( $Rows as $CurRow )
			{
			echo '<tr>' . "\r\n";
			echo '<td valign="top">' . "\r\n";
			
			$phptime = strtotime( $CurRow->time );
			
			echo date( get_option('time_format'), $phptime ); 
			echo '<br>' . "\r\n";
			echo date( get_option('date_format'), $phptime ); 
			
			echo '</td>' . "\r\n";
			
			$TypeDesc = __("Unknown", 'sully');
			if( $CurRow->type == 'C' ) { $TypeDesc = __('WordPress Core', 'sully'); }
			if( $CurRow->type == 'T' ) { $TypeDesc = __('Theme', 'sully'); }
			if( $CurRow->type == 'P' ) { $TypeDesc = __('Plugin', 'sully'); }
			if( $CurRow->type == 'S' ) { $TypeDesc = __('System', 'sully'); }

			echo '<td valign="top">' . $TypeDesc . "</td>\r\n";
			echo '<td valign="top"><a href="' . $CurRow->itemurl . '" target="_blank">' . $CurRow->nicename . '</a></td>' . "\r\n";
			echo '<td valign="top">' . $CurRow->version . '</td>' . "\r\n";
			if( $CurRow->type != '' ) 
				{
				echo '<td valign="top" width="50%">' . preg_replace( '/\n/', '<br>', $CurRow->changelog ). '</td>' . "\r\n";
				}
			else
				{
				echo '<td valign="top" width="50%">' . $CurRow->filename . '</td>' . "\r\n";
				}
				

			$alertbox = 'if( confirm(\'' . __('Really delete this item?', 'sully') . '\') ) { window.location = \'index.php?page=SULlyDashboard&SULlyDeleteItem=' . $CurRow->id . '\'; }';

			echo '<td><a class=button-primary href="#" onclick="' . $alertbox . '">delete</a></td>' . "\r\n";

			echo '</tr>' . "\r\n";
			}
		
		// Determine what page the "previous page" button should take us to.
		$lastpage = $curpage - 1;
		if( $lastpage < 1 ) { $lastpage = 1; }
		
		echo '<tfoot><tr><th colspan="6" style="text-align: center">' . "\r\n";
		
		// If we're on the first page, don't activate the previous button.
		if( $curpage == 1 )
			{
			echo '<a class="button">' . __('Previous', 'sully') . '</a>' . "\r\n";
			}
		else
			{
			echo '<a class=button-primary href="index.php?page=SULlyDashboard&pagenum=' . $lastpage . '">' . __('Previous', 'sully') . '</a>' . "\r\n";
			}

		// Firgure out the number of rows are in the database.
		$CountRows = $wpdb->get_results( 'SELECT COUNT(*) FROM ' . $TableName, ARRAY_N );
		
		// Add the current and total page count.
		$displaycount = $pagestart + $NumToDisplay;
		if( $pagestart == 0 ) { $pagestart = 1; }
		if( $displaycount > $CountRows[0][0] ) { $displaycount = $CountRows[0][0]; }
		
		echo '&nbsp;&nbsp;&nbsp;&nbsp;Records ' . $pagestart . '-' . $displaycount . ' of ' . $CountRows[0][0] . '&nbsp;&nbsp;&nbsp;&nbsp;';
			
		// If we're on the last page, disable the "next page" button
		if( $NumRows < $NumToDisplay )
			{			
			echo '<a class="button">' . __('Next', 'sully') . '</a>' . "\r\n";
			}
		else
			{
			$nextpage = $curpage + 1;
			echo '<a class="button-primary" href="index.php?page=SULlyDashboard&pagenum=' . $nextpage . '">' . __('Next', 'sully') . '</a>';
			}
			
		echo '</th></tr></tfoot></table></div>' . "\r\n";
?>