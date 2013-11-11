<?php
/*
Plugin Name: SULly
Version: 0.5
Plugin URI: http://toolstack.com/sully
Author: Greg Ross
Author URI: http://toolstack.com
Description: System Update Logger - Record system updates including plugins, themes and core updates.  Supports updates done with the new WordPress 3.7 Automatic Updates feature as well as manual uploads through the admin pages.

Compatible with WordPress 3.7+.

Read the accompanying readme.txt file for instructions and documentation.

Copyright (c) 2013 by Greg Ross

This software is released under the GPL v2.0, see license.txt for details
*/

$SULlyVersion = "0.5";

if( !function_exists( 'SULlyLoad' ) )
	{
	Function SULlyLoad()
		{
		wp_add_dashboard_widget( 'sully-dashboard-widget', 'System Update Log', 'SULlyDashBoardContent', $control_callback = null );
		}
		
	function SULlyAddLinksToChangeLog( $text )
		{
		// this function deserves credit to the fine folks at phpbb.com
		$text = preg_replace( '#(script|about|applet|activex|chrome):#is', "\\1:", $text );

		// pad it with a space so we can match things at the start of the 1st line.
		$ret = ' ' . $text;

		// matches an "xxxx://yyyy" URL at the start of a line, or after a space.
		// xxxx can only be alpha characters.
		// yyyy is anything up to the first space, newline, comma, double quote or <
		$ret = preg_replace( "#(^|[\n ])([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*)#is", "\\1<a href=\"\\2\" target=\"_blank\">\\2</a>", $ret );

		// matches a "www|ftp.xxxx.yyyy[/zzzz]" kinda lazy URL thing
		// Must contain at least 2 dots. xxxx contains either alphanum, or "-"
		// zzzz is optional.. will contain everything up to the first space, newline, 
		// comma, double quote or <.
		$ret = preg_replace( "#(^|[\n ])((www|ftp)\.[\w\#$%&~/.\-;:=,?@\[\]+]*)#is", "\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>", $ret );

		// matches an email@domain type address at the start of a line, or after a space.
		// Note: Only the followed chars are valid; alphanums, "-", "_" and or ".".
		$ret = preg_replace( "#(^|[\n ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $ret );

		// Remove our padding..
		$ret = substr($ret, 1);

		return $ret;
		}

	function SULlyDashBoardContent() 
		{
		global $wpdb;

		// Update any failed installs in the database.
		SULlyUpdateFails();

		// Update any WordPress updates that have happened with details.
		SULlyUpdateCores();

		// Update the database for any updates that have happened to ourselves.
		SULlyUpdateMyself();
		
		// Check for any changes to the system
		SULlyUpdateSystemSettings( SULlyGetSystemInfo(), unserialize( get_option( 'SULly_System_Settings' ) ) );

		$TableName = $wpdb->prefix . "SULly";
		$NumToDisplay = get_option( 'SULly_Entries_To_Display' );

		if( $NumToDisplay < 1 ) { $NumToDisplay = 10; }
		
		$Rows = $wpdb->get_results( "SELECT * FROM $TableName ORDER BY time desc LIMIT " . $NumToDisplay );
		
		echo "<table class='wp-list-table widefat fixed'><thead><tr><th>Time</th><th>Type</th><th>Item</th><th>Version</th><th>Change Log</th></tr></thead>";
		foreach( $Rows as $CurRow )
			{
			echo "<tr>";
			echo "<td valign='top'>";
			
			$phptime = strtotime( $CurRow->time );
			
			echo date( get_option('time_format'), $phptime ); 
			echo "<br>";
			echo date( get_option('date_format'), $phptime ); 
			
			echo "</td>";
			
			$TypeDesc = "Unknown";
			if( $CurRow->type == 'C' ) { $TypeDesc = "WordPress Core"; }
			if( $CurRow->type == 'T' ) { $TypeDesc = "Theme"; }
			if( $CurRow->type == 'P' ) { $TypeDesc = "Plugin"; }
			if( $CurRow->type == 'S' ) { $TypeDesc = "System"; }

			echo "<td valign='top'>" . $TypeDesc . "</td>";
			echo "<td valign='top'><a href='" . $CurRow->itemurl . "' target=_blank>" . $CurRow->nicename . "</a></td>";
			echo "<td valign='top'>" . $CurRow->version . "</td>";
			echo "<td valign='top' width='50%'>" . preg_replace( '/\n/', '<br>', $CurRow->changelog ). "</td>";
			
			echo '</tr>';
			}
			
		echo "<tfoot><tr><th colspan=5 width='100%' style='text-align: right'><a class=button-primary href='index.php?page=SULlyDashboard'>SULly Dashboard</a></th></tr></tfoot></table>";
		}
	
	function SULlyUpdateSystemSettings( $current, $old )
		{
		global $wpdb;

		$TableName = $wpdb->prefix . "sully";
		$UpdateOptions = false;

		if( $current['WPVersion'] != $old['WPVersion'] )
			{
			$wpdb->insert( $TableName,  array( 'filename' => 'wordpress-' . $current['WPVersion'] . '.zip', 'itemname' => 'wordpress', 'nicename' => 'WordPress Update', 'itemurl' => 'http://wordpress.org', 'version' => $current['WPVersion'], 'type' => 'C', 'changelog' => "Manual update detected!<br>Old version was: " . $old['WPVersion']. "<br>Visit the <a href='http://codex.wordpress.org/WordPress_Versions' target=_blank>WordPress Versions</a> page for details." ) );
			$UpdateOptions = true;
			}

		if( $current['PHPVersion'] != $old['PHPVersion'] )
			{
			$wpdb->insert( $TableName, array( 'filename' => 'PHP-' . $current['PHPVersion'] . '.zip', 'itemname' => 'php', 'nicename' => 'PHP Update', 'itemurl' => 'http://php.net', 'version' => $current['PHPVersion'], 'type' => 'S', 'changelog' => "Manual update detected!<br>Old version was: " . $old['PHPVersion']. "<br>Visit the <a href='http://www.php.net/ChangeLog-5.php#" . $current['PHPVersion'] . "' target=_blank>PHP Changelog</a> page for details." ) );
			$UpdateOptions = true;
			}

		if( $current['HTTPServer'] != $old['HTTPServer'] )
			{
			$wpdb->insert( $TableName, array( 'filename' => 'HTTPServer', 'itemname' => 'httpserver', 'nicename' => 'HTTP Server Update', 'itemurl' => '', 'version' => $current['HTTPServer'], 'type' => 'S', 'changelog' => "Manual update detected!<br>Old version was: " . $old['HTTPServer'] ) );
			$UpdateOptions = true;
			}

		if( $current['PHPExtensions'] != $old['PHPExtensions'] )
			{
			$add = array_diff( $old['PHPExtensions'], $current['PHPExtensions'] );
			$delete = array_diff( $current['PHPExtensions'], $old['PHPExtensions']);
			$output = "PHP Extensions Added:<br>";
			
			foreach( $add as $extension )
				{
				$output = $output . $extension . "<br>";
				}

			$output = $output . "<br>PHP Extensions Removed:<br>";
	
			foreach( $delete as $extension )
				{
				$output = $output . $extension . "<br>";
				}

			$wpdb->insert( $TableName, array( 'filename' => 'PHPExtensions', 'itemname' => 'phpextensions', 'nicename' => 'PHP Extensions', 'itemurl' => '', 'version' => 'N/A', 'type' => 'S', 'changelog' => $output ) );
			$UpdateOptions = true;
			}
			
		if( $UpdateOptions == true )
			{
			update_option( 'SULly_System_Settings', serialize( $current ) );
			}
		}
	
	function SULlyStoreName( $ret, $packagename )
		{
		global $wpdb;
		
		$TableName = $wpdb->prefix . "SULly";
		
		$wpdb->update( $TableName, array( 'type' => 'F', 'changelog' => $readme ), array( 'type' => '' ) );

		$type = '';
		
		// Deal with WP core updates, since the download is captured but the upgrade function is never called
		if( preg_match( '!^(http|https|ftp)://wordpress.org/wordpress-!i', $packagename ) ) // https://wordpress.org/wordpress-3.7.1-partial-0.zip
			{
			$type = 'C';
			}
			
		$wpdb->insert( $TableName, array( 'filename' => $packagename, 'type' => $type ) );

		return $ret;
		}
		
	function SULlyGetItemDetails( $infilename )
		{
		$path_parts = pathinfo( $infilename );
		$ptwo_parts = pathinfo( $path_parts['dirname'] );
		
		$new_filename = $path_parts['filename'];
		$filename = "";
		$version = "";
		
		while( $filename != $new_filename ) 
			{ 
			$filename = $new_filename;
			
			$tempsplit = pathinfo( $filename );
			
			$new_filename = $tempsplit['filename'];
			$version = $tempsplit['extension'] . '.' . $version;
			}
			
		$version = substr( $version, 1, -1 );
		$itemname = $new_filename;
		$lastdir = $ptwo_parts['filename'];
		
		return array( 'itemname' => $itemname, 'version' => $version, 'lastdir' => $lastdir );
		}
		
	function SULlyGetItemInfo( $itemname, $lastdir )
		{	
		GLOBAL $wp_version;
		
		$type = 'U';
		$readme = 'No changelog found.';

		if( $lastdir == 'plugin' )
			{
			$PluginInfo = array();

			// if the path is something like download.wordpress.org/plugin/plugin-name.1.0.zip, we're downloading a plugin
			$type = 'P';
			
			// use get_plugin_data() to get more info.
			if( file_exists( WP_PLUGIN_DIR . "/" . $itemname . "/" . $itemname . ".php" ) )
				{
				$PluginInfo = get_plugin_data( WP_PLUGIN_DIR . "/" . $itemname . "/" . $itemname . ".php" );
				}
			else
				{
				// Some plugins don't follow the standard so loop through all the PHP files in the main plugin directory
				$dirlist = scandir( WP_PLUGIN_DIR . "/" . $itemname );
				
				foreach( $dirlist as $file )
					{
					$pathsplit = pathinfo( $file );
					
					if( $pathsplit['extension'] == 'php' )
						{
						$PluginInfo = get_plugin_data( WP_PLUGIN_DIR . "/" . $itemname . "/" . $file );
						
						if( $PluginInfo['Name'] != "" )
							{
							break;
							}
						}
					}
				}
			
			$nicename = $PluginInfo['Name'];
			$itemurl = $PluginInfo['PluginURI'];
			$version = $PluginInfo['Version'];

			if( $itemurl == "" OR $itemurl == 'http://-/' OR $itemurl == 'http://-' )
				{
				$itemurl = "http://wordpress.org/plugins/" . $itemname;
				}
			
			if( file_exists( WP_CONTENT_DIR . '/plugins/' . $itemname . '/readme.txt' ) )
				{
				$readme = file_get_contents( WP_CONTENT_DIR . '/plugins/' . $itemname . '/readme.txt' ); 

				$readme = preg_replace( "/.*\=\=.?change.?log.?\=\=/is", "", $readme );
				$readme = preg_replace( "/\=\=.*/s", "", $readme );
				$readme = preg_replace( "/\*\*/s", "=", $readme ); // some people use ** instead of = for their version log
				$readme = preg_replace( "/\=.*.\=/", "", $readme, 1 );
				$readme = preg_replace( "/\=.*/s", "", $readme );
				$readme = trim( $readme );
				
				$readme = SULlyAddLinksToChangeLog( $readme );
				}
			}
		else if( $lastdir == 'download' )
			{
			// if the path is something like wordpress.org/themes/download/twentythirteen.1.1.zip, we're downloading a theme
			$type = 'T';
			
			// use wp_get_theme() to get more info
			$ThemeInfo = wp_get_theme( $itemname );
			
			$nicename = $ThemeInfo->Name;
			$itemurl = $ThemeInfo->ThemeURI;
			$version = $ThemeInfo->Version;

			if( $itemurl == "" )
				{
				$itemurl = $ThemeInfo->AuthorURI;
				}

			if( $itemurl == "" )
				{
				$itemurl = "http://wordpress.org/themes/" . $itemname;
				}
				
			$readme = "Sorry, theme's do not have a standard change log, please visit the theme's home page.";
			}
		else if( $lastdir == 'wordpress' )
			{
			// if the path is something like wordpress.org/wordpress.3.7.1.zip, we're downloading a core update
			// or https://wordpress.org/wordpress-3.7.1-partial-0.zip
			$type = 'C';

			$nicename = 'WordPress Update';
			$itemurl = 'http://wordpress.org';
			$readme = "Visit the <a href='http://codex.wordpress.org/WordPress_Versions' target=_blank>WordPress Versions</a> page for details.";
			$version = $wp_version;
			
			$systemoptions = unserialize( get_option( 'SULly_System_Settings' ) );
			$systemoptions['WPVersion'] = $wp_version;
			update_option( 'SULly_System_Settings', serialize( $systemoptions ) );
			}
			
		if( $type == 'U' )
			{
			$found_item = false;
			
			// We didn't find a match with the standard wordpress.org download locations so let's try and guess at it
			if( file_exists( WP_CONTENT_DIR . '/plugins/' . $itemname . '/readme.txt' ) )
				{
				$lastdir = "plugin";
				$found_item = true;
				}
			else if( file_exists( WP_CONTENT_DIR . '/themes/' . $itemname . '/style.css' ) )
				{
				$lastdir = "download";
				$found_item = true;
				}
				
			if( $found_item )
				{
				// if our guess paid off, rerun the function
				return SULlyGetItemInfo( $itemname, $lastdir );
				}
			}

		if( ! is_string( $readme ) OR strlen( $readme ) < 5 )	// if something went wrong above and $readme is no longer a string, or really short, reset it.
			{
			$readme = "No changelog found.";
			}
			
		return array( 'type' => $type, 'nicename' => $nicename, 'itemurl' => $itemurl, 'version' => $version, 'changelog' => $readme );
		}

	function SULlyStoreResult( $ret, $hook_extra, $result )
		{
		global $wpdb;
		
		SULlyUpdateCores();

		$TableName = $wpdb->prefix . "SULly";
		
		$Rows = $wpdb->get_results( "SELECT * FROM $TableName WHERE type = ''" );

		foreach( $Rows as $CurRow )
			{
			$RowID = $CurRow->id;
			
			$package = $CurRow->filename;
			
			$itemdetails = SULlyGetItemDetails( $package );
			
			if( $result["destination_name"] == $itemdetails['itemname'] )
				{
				if( ! preg_match( '!^(http|https|ftp)://!i', $package ) ) //Local file or remote?
					{
					// We're a local file, we should do something about that...
					
					if( file_exists( WP_CONTENT_DIR . '/plugins/' . $itemdetails['itemname'] ) )
						{
						$itemdetails['lastdir'] = "plugin";
						}
					else if( file_exists( WP_CONTENT_DIR . '/themes/' . $itemdetails['itemname'] ) )
						{
						$itemdetails['lastdir'] = "download";
						}
					}

				// Get the details, unless we're upgrading ourselves.  
				if( $result["destination_name"] != 'sully' )
					{
					$iteminfo = SULlyGetItemInfo( $itemdetails['itemname'], $itemdetails['lastdir'] );
					}
				else
					{
					// We'll have to grab our details later, otherwise we get wonky results back.
					$iteminfo = array( 'type' => 'M', 'nicename' => '', 'itemurl' => '', 'version' => $version, 'changelog' => '' );
					}
					
				if( $iteminfo['version'] == "" ) { $iteminfo['version'] = $itemdetails['version']; }

				$wpdb->update( $TableName, array( 'filename' => $package, 'itemname' => $itemdetails['itemname'], 'nicename' => $iteminfo['nicename'], 'itemurl' => $iteminfo['itemurl'], 'version' => $iteminfo['version'], 'type' => $iteminfo['type'], 'changelog' => $iteminfo['changelog'] ), array( 'id' => $RowID ) );
				}
			}
		
		SULlyUpdateFails();
		
		SULlyUpdateSystemSettings( SULlyGetSystemInfo(), unserialize( get_option( 'SULly_System_Settings' ) ) );
		
		return $ret;
		}
		
	function SULlyUpdateFails()
		{
		global $wpdb;
		
		$TableName = $wpdb->prefix . "SULly";
		
		$Rows = $wpdb->get_results( "SELECT * FROM $TableName WHERE type = 'F'" );

		foreach( $Rows as $CurRow )
			{
			$RowID = $CurRow->id;
			
			$itemdetails = SULlyGetItemDetails( $CurRow->filename );
			
			$iteminfo = array( 'type' => 'F', 'nicename' => $itemdetails['itemname'], 'itemurl' => $CurRow->filename, 'version' => $version, 'readme' => 'Item failed to install correctly!' );

			$wpdb->update( $TableName, array( 'itemname' => $itemdetails['itemname'], 'nicename' => $iteminfo['nicename'], 'itemurl' => $iteminfo['itemurl'], 'version' => $iteminfo['version'], 'type' => $iteminfo['type'], 'changelog' => $iteminfo['changelog'] ), array( 'id' => $RowID ) );
			}

		return $ret;
		}

	function SULlyUpdateCores()
		{
		global $wpdb;
		
		$TableName = $wpdb->prefix . "SULly";
		
		$Rows = $wpdb->get_results( "SELECT * FROM $TableName WHERE type = 'C' AND itemname IS NULL" );

		foreach( $Rows as $CurRow )
			{
			$RowID = $CurRow->id;
			
			$itemdetails = SULlyGetItemDetails( $CurRow->filename );
			
			$iteminfo = SULlyGetItemInfo( $itemdetails['itemname'], 'wordpress' );

			$wpdb->update( $TableName, array( 'itemname' => $itemdetails['itemname'], 'nicename' => $iteminfo['nicename'], 'itemurl' => $iteminfo['itemurl'], 'version' => $iteminfo['version'], 'type' => $iteminfo['type'], 'changelog' => $iteminfo['changelog'] ), array( 'id' => $RowID ) );
			}

		return $ret;
		}
		
	function SULlyUpdateMyself()
		{
		global $wpdb;
		
		$TableName = $wpdb->prefix . "SULly";
		
		$Rows = $wpdb->get_results( "SELECT * FROM $TableName WHERE type = 'M'" );

		foreach( $Rows as $CurRow )
			{
			$RowID = $CurRow->id;
			
			$itemdetails = SULlyGetItemDetails( $CurRow->filename );
			
			$iteminfo = SULlyGetItemInfo( $itemdetails['itemname'], 'plugin' );

			$wpdb->update( $TableName, array( 'itemname' => $itemdetails['itemname'], 'nicename' => $iteminfo['nicename'], 'itemurl' => $iteminfo['itemurl'], 'version' => $iteminfo['version'], 'type' => $iteminfo['type'], 'changelog' => $iteminfo['changelog'] ), array( 'id' => $RowID ) );
			}

		return $ret;
		}

	function SULlyGetSystemInfo()
		{
		GLOBAL $wp_version;
				
		return array( "WPVersion" => $wp_version, "PHPVersion" => phpversion(), "PHPExtensions" => get_loaded_extensions(), "HTTPServer" => $_SERVER["SERVER_SOFTWARE"] );
		}
		
	function SULlySetup()
		{
		global $wpdb;
		global $SULlyVersion;
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$TableName = $wpdb->prefix . "SULly";
      
		$sql = "CREATE TABLE $TableName (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				time TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
				filename tinytext NOT NULL,
				itemname tinytext,
				nicename tinytext,
				itemurl tinytext,
				version tinytext,
				type char(1),
				changelog text,
				UNIQUE KEY id (id),
				KEY (type)
				);";
		
		dbDelta( $sql );

		$CountRows = $wpdb->get_results( 'SELECT COUNT(*) FROM ' . $TableName, ARRAY_N );

		if( $CountRows[0][0] == 0 )
			{
			// If this is the first install, let's add an entry for ourselves
			$wpdb->insert( $TableName, array( 'filename' => 'sully.zip', 'itemname' => 'SULly', 'nicename' => 'SULly', 'itemurl' => 'http://toolstack.com/sully', 'version' => $SULlyVersion, 'type' => 'P', 'changelog' => 'Initial SULly install!' ) );
			}
		
		update_option( 'SULly_DBVersion', $SULlyVersion );
		
		if( get_option( 'SULly_Entries_To_Display' ) == FALSE ) { update_option( 'SULly_Entries_To_Display', 10 ); }
		if( get_option( 'SULly_Page_Entries_To_Display' ) == FALSE ) { update_option( 'SULly_Page_Entries_To_Display', 10 ); }
		
		if( get_option( 'SULly_System_Settings' ) == FALSE ) { update_option( 'SULly_System_Settings', serialize( SULlyGetSystemInfo() ) ); }
		}
		
	function SULlyAddDashboardMenu()
		{
		add_submenu_page( 'index.php', __( 'SULly' ), __( 'SULly' ), 'manage_options', 'SULlyDashboard', 'SULlyGenerateDashboard' );
		add_options_page( 'SULly', 'SULly', 9, basename( __FILE__ ), 'SULlyAdminPage');
		}
		
	function SULLyGenerateDashboard()
		{
		global $wpdb;
		global $_POST;

		// Update any failed installs in the database.
		SULlyUpdateFails();

		// Update any WordPress updates that have happened with details.
		SULlyUpdateCores();

		// Update the database for any updates that have happened to ourselves.
		SULlyUpdateMyself();
		
		// Check for any changes to the system
		SULlyUpdateSystemSettings( SULlyGetSystemInfo(), unserialize( get_option( 'SULly_System_Settings' ) ) );

		$TableName = $wpdb->prefix . "SULly";
		$NumToDisplay = get_option( 'SULly_Page_Entries_To_Display' );
		if( $NumToDisplay < 1 ) { $NumToDisplay = 10; }

		$curpage = 1;
		if( isset( $_GET["page"] ) ) { $curpage = $_GET["pagenum"]; }
		if( $curpage < 1 ) { $curpage = 1; }
			
		$pagestart = ( $curpage - 1 ) * $NumToDisplay;
		if( $pagestart < 1 ) { $pagestart = 0; }
		
		$Rows = $wpdb->get_results( "SELECT * FROM $TableName ORDER BY time desc LIMIT " . $pagestart . "," . $NumToDisplay );
		$NumRows = $wpdb->num_rows;
		
		echo "<div class='wrap'>";
		echo "<h2>SULly - System Update Logger</h2><br>";
		echo "<table class='wp-list-table widefat fixed'><thead><tr><th>Time</th><th>Type</th><th>Item</th><th>Version</th><th>Change Log</th></tr></thead>";
		foreach( $Rows as $CurRow )
			{
			echo "<tr>";
			echo "<td valign='top'>";
			
			$phptime = strtotime( $CurRow->time );
			
			echo date( get_option('time_format'), $phptime ); 
			echo "<br>";
			echo date( get_option('date_format'), $phptime ); 
			
			echo "</td>";
			
			$TypeDesc = "Unknown";
			if( $CurRow->type == 'C' ) { $TypeDesc = "WordPress Core"; }
			if( $CurRow->type == 'T' ) { $TypeDesc = "Theme"; }
			if( $CurRow->type == 'P' ) { $TypeDesc = "Plugin"; }
			if( $CurRow->type == 'S' ) { $TypeDesc = "System"; }

			echo "<td valign='top'>" . $TypeDesc . "</td>";
			echo "<td valign='top'><a href='" . $CurRow->itemurl . "' target=_blank>" . $CurRow->nicename . "</a></td>";
			echo "<td valign='top'>" . $CurRow->version . "</td>";
			echo "<td valign='top' width='50%'>" . preg_replace( '/\n/', '<br>', $CurRow->changelog ). "</td>";
			
			echo '</tr>';
			}
		
		$lastpage = $curpage - 1;
		if( $lastpage < 1 ) { $lastpage = 1; }
		
		echo "<tfoot><tr><th colspan=5 style='text-align: center'>";
		
		if( $curpage == 1 )
			{
			echo "<a class='button''>Previous</a>";
			}
		else
			{
			echo "<a class=button-primary href='index.php?page=SULlyDashboard&pagenum=$lastpage'>Previous</a>";
			}

		$CountRows = $wpdb->get_results( 'SELECT COUNT(*) FROM ' . $TableName, ARRAY_N );
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;Records " . $pagestart . "-" . ( $pagestart + 1 ) * $NumToDisplay . " of " . $CountRows[0][0] . "&nbsp;&nbsp;&nbsp;&nbsp;";
			
		if( $NumRows < $NumToDisplay )
			{			
			echo "<a class=button>Next</a>";
			}
		else
			{
			$nextpage = $curpage + 1;
			echo "<a class=button-primary href='index.php?page=SULlyDashboard&pagenum=$nextpage'>Next</a>";
			}
			
		echo "</th></tr></tfoot></table></div>";
		}

	function SULlyAdminPage()
		{
		global $wpdb;
		global $SULlyVersion;

		$deletedays = 90;
		
		if( isset( $_POST['SULlyDeleteAction'] ) )
			{
			if( !isset( $_POST['SULlyActions']['DeleteOld'] ) ) { $_POST['SULlyOptions']['DeleteOld'] = 90; }

			$deletedays = $_POST['SULlyActions']['DeleteOld'];

			$TableName = $wpdb->prefix . "SULly";

			// This is a bit of a hack, $wpdb doesn't return the right (aka any) count for a delete statement
			// so we have to count the rows in the table before and after we execute the delete to actually
			// the actual number of rows we deleted.
			$CountRows = $wpdb->get_results( 'SELECT COUNT(*) FROM ' . $TableName, ARRAY_N );
			$StartingRows = $CountRows[0][0];

			$SQLStatement = "DELETE FROM $TableName WHERE time < DATE_SUB(NOW(), INTERVAL $deletedays DAY);";

			$results = $wpdb->get_results( $SQLStatement );
			
			$CountRows = $wpdb->get_results( 'SELECT COUNT(*) FROM ' . $TableName, ARRAY_N );
			
			$NumRows = $StartingRows - $CountRows[0][0];
			
			print "<div class='updated settings-error'><p><strong>$NumRows records over " . $deletedays . " days old have been deleted.</strong></p></div>\n";
			}
		
		if( $_POST['SULlyOptions'] AND isset( $_POST['SULlyUpdateOptions'] ) ) 
			{
			if( !isset( $_POST['SULlyOptions']['WidgetDisplayLines'] ) ) { $_POST['SULlyOptions']['WidgetDisplayLines'] = 10; }
			if( !isset( $_POST['SULlyOptions']['PageDisplayLines'] ) ) { $_POST['SULlyOptions']['PageDisplayLines'] = 10; }
				
			update_option( 'SULly_Entries_To_Display', $_POST['SULlyOptions']['WidgetDisplayLines'] );
			update_option( 'SULly_Page_Entries_To_Display', $_POST['SULlyOptions']['PageDisplayLines'] );
			
			print "<div id='setting-error-settings_updated' class='updated settings-error'><p><strong>Settings saved.</strong></p></div>\n";
			}

		$SULlyOptions['WidgetDisplayLines'] = get_option( 'SULly_Entries_To_Display' );
		$SULlyOptions['PageDisplayLines'] = get_option( 'SULly_Page_Entries_To_Display' );
		
	?>
<div class="wrap">
	
	<fieldset style="border:1px solid #cecece;padding:15px; margin-top:25px" >
		<legend><span style="font-size: 24px; font-weight: 700;">&nbsp;SULly Options&nbsp;</span></legend>
		<form method="post">

				<div><?php _e('Number of entries to display in the Dashboard Widget'); ?>:&nbsp;<input name="SULlyOptions[WidgetDisplayLines]" type="text" id="SULlyOptions_WidgetDisplayLines" size="3" maxlength="3" value="<?php echo $SULlyOptions['WidgetDisplayLines']; ?>" /> </div>
				<div><?php _e('Number of entries to display in the Dashboard Page'); ?>:&nbsp;<input name="SULlyOptions[PageDisplayLines]" type="text" id="SULlyOptions_PageDisplayLines" size="3" maxlength="3" value="<?php echo $SULlyOptions['PageDisplayLines']; ?>" /> </div>
				
			<div class="submit"><input type="submit" name="SULlyUpdateOptions" value="<?php _e('Update Options') ?> &raquo;" /></div>
		</form>
		
	</fieldset>

	<fieldset style="border:1px solid #cecece;padding:15px; margin-top:25px" >
		<legend><span style="font-size: 24px; font-weight: 700;">&nbsp;Database Actions&nbsp;</span></legend>

		<form method="post">
				<div style="font-size: 16px;">**WARNING** No further confirmation will be given after you press the delete button, make sure you REALLY want to delete the old records before continuing.</div>
				<div>&nbsp;</div>
				<div><?php _e('Delete records older than '); ?>:&nbsp;<input name="SULlyActions[DeleteOld]" type="text" id="SULlyActions_DeletOld" size="3" maxlength="3" value="<?php echo $deletedays; ?>" /> days <input type="submit" name="SULlyDeleteAction" value="<?php _e('Delete') ?> &raquo;" />
		</form>

	</fieldset>
	
	<fieldset style="border:1px solid #cecece;padding:15px; margin-top:25px" >
		<legend><span style="font-size: 24px; font-weight: 700;">&nbsp;About&nbsp;</span></legend>
		<p>SULly - System Update Logger Version <?php echo $SULlyVersion; ?></p>
		<p>by Greg Ross</p>
		<p>&nbsp;</p>
		<p>Licenced under the <a href="http://www.gnu.org/licenses/gpl-2.0.html" target=_blank>GPL Version 2</a></p>
		<p>To find out more, please visit the <a href='http://wordpress.org/plugins/just-writing/' target=_blank>WordPress Plugin Directory page</a> or the plugin home page on <a href='http://toolstack.com/just-writing' target=_blank>ToolStack.com</a></p> 
		<p>&nbsp;</p>
		<p>Don't forget to <a href='http://wordpress.org/plugins/just-writing/' target=_blank>rate</a> and <a href='http://wordpress.org/support/view/plugin-reviews/just-writing' target=_blank>review</a> it too!</p>

</fieldset>
</div>
	<?php
		}
	}

if( get_option( 'SULly_DBVersion' ) != $SULlyVersion ) { SULlySetup(); }

add_action( 'admin_menu', 'SULlyAddDashboardMenu', 1 );
add_action( 'wp_dashboard_setup', 'SULlyLoad' );
add_filter( 'upgrader_pre_download', 'SULlyStoreName', 10, 2 );
add_filter( 'upgrader_post_install', 'SULlyStoreResult', 10, 3);

?>