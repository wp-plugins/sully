<?php
/*
Plugin Name: SULly
Version: 0.1
Plugin URI: http://toolstack.com/sully
Author: Greg Ross
Author URI: http://toolstack.com
Description: System Update Logger - Record system updates including plugins, themes and core updates.  Supports updates done with the new WordPress 3.7 Automatic Updates feature as well as manual uploads through the admin pages.

Compatible with WordPress 3.7+.

Read the accompanying readme.txt file for instructions and documentation.

Copyright (c) 2013 by Greg Ross

This software is released under the GPL v2.0, see license.txt for details
*/

if( !function_exists( 'SULlyLoad' ) )
	{
	Function SULlyLoad()
		{
		wp_add_dashboard_widget( 'sully-dashboard-widget', 'System Update Log', 'SULlyDashBoardContent', $control_callback = null );
		}
		
	function SULlyDashBoardContent() 
		{
		global $wpdb;

		SULlyUpdateFails();
		
		$TableName = $wpdb->prefix . "SULly";
		$NumToDisplay = get_option( 'SULly_Entries_To_Display' );

		if( $NumToDisplay < 1 ) { $NumToDisplay = 10; }
		
		$Rows = $wpdb->get_results( "SELECT * FROM $TableName ORDER BY time desc LIMIT " . $NumToDisplay );
		
		echo "<table class='wp-list-table widefat fixed'><thead><tr><th>Time</th><th>Type</th><th>Item</th><th>Version</th><th>Change Log</th></tr></thead>";
		foreach( $Rows as $CurRow )
			{
			echo "<tr>";
			echo "<td valign='top' width='15%'>";
			
			$phptime = strtotime( $CurRow->time );
			
			echo date( get_option('time_format'), $phptime ); 
			echo "<br>";
			echo date( get_option('date_format'), $phptime ); 
			
			echo "</td>";
			
			$TypeDesc = "Unknown";
			if( $CurRow->type == 'C' ) { $TypeDesc = "WordPress Core"; }
			if( $CurRow->type == 'T' ) { $TypeDesc = "Theme"; }
			if( $CurRow->type == 'P' ) { $TypeDesc = "Plugin"; }

			echo "<td valign='top' width='15%'>" . $TypeDesc . "</td>";
			echo "<td valign='top' width='15%'><a href='" . $CurRow->itemurl . "'>" . $CurRow->nicename . "</a></td>";
			echo "<td valign='top' width='10%'>" . $CurRow->version . "</td>";
			echo "<td valign='top' width='45%'>" . preg_replace( '/\n/', '<br>', $CurRow->changelog ). "</td>";
			
			echo '</tr>';
			}
			
		echo "<tfoot><tr><th colspan=5>&nbsp;</th></tr></tfoot></table>";
		}
	
	function SULlyStoreName( $ret, $packagename )
		{
		global $wpdb;
		
		$TableName = $wpdb->prefix . "SULly";
		
		$wpdb->update( $TableName, array( 'type' => 'F', 'changelog' => $readme ), array( 'type' => '' ) );
		$wpdb->insert( $TableName, array( 'filename' => $packagename, 'type' => '' ) );
		
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
		$type = 'U';
		$readme = '';
	
		if( $lastdir == 'plugin' )
			{
			// if the path is something like download.wordpress.org/plugin/plugin-name.1.0.zip, we're downloading a plugin
			$type = 'P';
			
			// use get_plugin_data() to get more info.
			$PluginInfo = get_plugin_data( WP_PLUGIN_DIR . "/" . $itemname . "/" . $itemname . ".php" );
			
			$nicename = $PluginInfo['Name'];
			$itemurl = $PluginInfo['PluginURI'];
			$version = $PluginInfo['Version'];

			if( $itemurl == "" )
				{
				$itemurl = "http://wordpress.org/plugins/" . $itemname;
				}
			
			$readme = file_get_contents( WP_CONTENT_DIR . '/plugins/' . $itemname . '/readme.txt' ); 

			$readme = preg_replace( "/.*\=\= changelog \=\=/is", "", $readme );
			$readme = preg_replace( "/\=\=.*/s", "", $readme );
			$readme = preg_replace( "/\*\*/s", "=", $readme ); // some people use ** instead of = for their version log
			$readme = preg_replace( "/\=.*.\=/", "", $readme, 1 );
			$readme = preg_replace( "/\=.*/s", "", $readme );
			$readme = trim( $readme );
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
			$type = 'C';

			$nicename = 'WordPress Update';
			$itemurl = 'http://wordpress.org';
			$readme = "Visit the <a href='http://codex.wordpress.org/WordPress_Versions' target=_blank>WordPress Versions</a> page for details.";
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
				// if our guess payed off, rerun the function
				return SULlyGetItemInfo( $itemname, $lastdir );
				}
			}
			
		return array( 'type' => $type, 'nicename' => $nicename, 'itemurl' => $itemurl, 'version' => $version, 'readme' => $readme );
		}

	function SULlyStoreResult( $ret, $hook_extra, $result )
		{
		global $wpdb;
		
		$TableName = $wpdb->prefix . "SULly";
		
		$Rows = $wpdb->get_results( "SELECT * FROM $TableName WHERE type = ''" );

		foreach( $Rows as $CurRow )
			{
			$RowID = $CurRow->id;
			
			$package = $CurRow->filename;
			
			$itemdetails = SULlyGetItemDetails( $package );
			
			if( $result["destination_name"] == $itemdetails['itemname'] )
				{
				if( ! preg_match( '!^(http|https|ftp)://!i', $package ) && file_exists( $package ) ) //Local file or remote?
					{
					// We're a local file, we should do something about that...
					
					if( file_exists( WP_CONTENT_DIR . '/plugins/' . $itemdetails['itemname'] . '/readme.txt' ) )
						{
						$itemdetails['lastdir'] = "plugin";
						}
					else if( file_exists( WP_CONTENT_DIR . '/themes/' . $itemdetails['itemname'] . '/style.css' ) )
						{
						$itemdetails['lastdir'] = "download";
						}
					}
				
				$iteminfo = SULlyGetItemInfo( $itemdetails['itemname'], $itemdetails['lastdir'] );
				
				if( $iteminfo['version'] == "" ) { $iteminfo['version'] = $itemdetails['version']; }

				$wpdb->update( $TableName, array( 'itemname' => $itemdetails['itemname'], 'nicename' => $iteminfo['nicename'], 'itemurl' => $iteminfo['itemurl'], 'version' => $iteminfo['version'], 'type' => $iteminfo['type'], 'changelog' => $iteminfo['readme'] ), array( 'id' => $RowID ) );
				}
			}
		
		SULlyUpdateFails();
		
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

			$wpdb->update( $TableName, array( 'itemname' => $itemdetails['itemname'], 'nicename' => $iteminfo['nicename'], 'itemurl' => $iteminfo['itemurl'], 'version' => $iteminfo['version'], 'type' => $iteminfo['type'], 'changelog' => $iteminfo['readme'] ), array( 'id' => $RowID ) );
			}

		return $ret;
		}

	function SULlySetup()
		{
		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$table_name = $wpdb->prefix . "SULly";
      
		$sql = "CREATE TABLE $table_name (
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

		update_option( 'SULly_DBVersion', '1.0' );
		}
	}

if( get_option( 'SULly_DBVersion' ) != '1.0' ) { SULlySetup(); }
	
add_action( 'wp_dashboard_setup', 'SULlyLoad' );
add_filter( 'upgrader_pre_download', 'SULlyStoreName', 10, 2 );
add_filter( 'upgrader_post_install', 'SULlyStoreResult', 10, 3);

?>