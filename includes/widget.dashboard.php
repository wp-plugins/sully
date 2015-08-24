<?php		
		GLOBAL $wpdb, $SULlyUtils;

		// Update any failed installs in the database.
		SULlyUpdateFails();

		// Update any WordPress updates that have happened with details.
		SULlyUpdateCores();

		// Update the database for any updates that have happened to ourselves.
		SULlyUpdateMyself();
		
		// Check for any changes to the system
		SULlyUpdateSystemSettings( SULlyGetSystemInfo(), unserialize( get_option( 'SULly_System_Settings' ) ) );

		$TableName = $wpdb->prefix . "SULly";
		$NumToDisplay = $SULlyUtils->get_option( 'EntriesToDisplay' );

		if( $NumToDisplay < 1 ) { $NumToDisplay = 10; }
		
		$Rows = $wpdb->get_results( "SELECT * FROM $TableName ORDER BY time desc LIMIT " . $NumToDisplay );
		
		echo "<div>";
		foreach( $Rows as $CurRow )
			{
			echo "<div>";

			echo "<div style='clear: both; float: left; font-size: 14pt'>";
			echo "<a href='" . $CurRow->itemurl . "' target=_blank>" . $CurRow->nicename . "</a>";
			echo "</div>";
			
			echo "<div style='float: right; font-size: 14pt'>";
			echo $CurRow->version;
			echo "</div>";

			echo "<div style='clear: both; float: left;'>";
			$phptime = strtotime( $CurRow->time );
			echo date( get_option('time_format'), $phptime ) . "&nbsp;" . date( get_option('date_format'), $phptime ); 
			echo "</div>";
			
			$TypeDesc = "Unknown";
			if( $CurRow->type == 'C' ) { $TypeDesc = "WordPress Core"; }
			if( $CurRow->type == 'T' ) { $TypeDesc = "Theme"; }
			if( $CurRow->type == 'P' ) { $TypeDesc = "Plugin"; }
			if( $CurRow->type == 'S' ) { $TypeDesc = "System"; }

			echo "<div style='float: right;'>";
			echo $TypeDesc;
			echo "</div>";
			
			echo "<div style='clear: both;'><br></div>";
		
			echo "<div style='clear: both; float: left;'>";
			if( $CurRow->type != '' ) 
				{
				echo preg_replace( '/\n/', '<br>', $CurRow->changelog );
				}
			else
				{
				echo $CurRow->filename;
				}
				
			echo "</div>";

			echo "<div style='clear: both;'><br></div>";
			
			echo "</div>";

			}
			
		echo "<div style='clear: both;'></div>";
		echo "<div style='float: right;'><a class=button-primary href='index.php?page=SULlyDashboard'>SULly Dashboard</a></div>";
		echo "<div style='clear: both;'></div>";
		echo "</div>";
?>