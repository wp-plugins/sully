<?php
/*
Plugin Name: SULly
Version: 2.0
Plugin URI: http://toolstack.com/sully
Author: Greg Ross
Author URI: http://toolstack.com
Description: System Update Logger - Record system updates including plugins, themes and core updates.  Supports updates done with the new WordPress 3.7 Automatic Updates feature as well as manual uploads through the admin pages.

Compatible with WordPress 3.7+.

Read the accompanying readme.txt file for instructions and documentation.

Copyright (c) 2013-15 by Greg Ross

This software is released under the GPL v2.0, see license.txt for details
*/

$SULlyVersion = '2.0';

if( !function_exists( 'SULlyLoad' ) )
	{
	/*
		This function is called to add the dashboard widget.
	*/
	Function SULlyLoad()
		{
		// Check to make sure the user is an admin.
		if( current_user_can( 'install_plugins' ) ) 
			{
			wp_add_dashboard_widget( 'sully-dashboard-widget', 'System Update Log', 'SULlyDashBoardContent', $control_callback = null );
			}
		}
		
	/*
		This function will add porper <a> links to URL's found in a string of text.
		
		This function deserves credit to the fine folks at phpbb.com
		
		$text = string to process.
	*/
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

	/*
		This function will generate the content for the dashboard widget.
	*/
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
			echo preg_replace( '/\n/', '<br>', $CurRow->changelog );
			echo "</div>";

			echo "<div style='clear: both;'><br></div>";
			
			echo "</div>";

			}
			
		echo "<div style='clear: both;'></div>";
		echo "<div style='float: right;'><a class=button-primary href='index.php?page=SULlyDashboard'>SULly Dashboard</a></div>";
		echo "<div style='clear: both;'></div>";
		echo "</div>";
		}
	
	/*
		This function will check to see if any system settings have been changed and if so add
		an entry to the log table.
		
		$current = array of current system settings
		$old = array of previous system settings
	*/
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
	
	/*
		This function store a new update in the database, it's called by WordPress before the download happens.
		
		$ret = the return value to return if successfully
		$packagename = the package name we're processing
		
	*/
	function SULlyStoreName( $ret, $packagename )
		{
		global $wpdb;
		
		$TableName = $wpdb->prefix . "SULly";
		
		// First, handle any updates that failed in install correctly.
		$wpdb->update( $TableName, array( 'type' => 'F', 'changelog' => $readme ), array( 'type' => '' ) );

		$type = '';
		
		// Deal with WP core updates, since the download is captured but the upgrade function is never called
		if( preg_match( '!^(http|https|ftp)://downloads.wordpress.org/release/wordpress-\d+!i', $packagename ) ) // https://downloads.wordpress.org/release/wordpress-3.7.1-partial-0.zip
			{
			$type = 'C';
			}
			
		$wpdb->insert( $TableName, array( 'filename' => $packagename, 'type' => $type ) );

		return $ret;
		}
		
	/*
		This function will parse a package name and return the 
			version (ie http://host/path/to/file-name.3.12.zip returns '3.12') 
			item name (ie http://host/path/to/file-name.3.12.zip returns 'file-name') 
			and last part of the path http://host/path/to/file-name.3.12.zip returns 'to') 
			
		Special care needs to be taken for WordPress packages as they have a slightly different format:
		
			https://downloads.wordpress.org/release/wordpress-4.2.2-partial-1.zip
			
		$infilename = the input package.
	*/
	function SULlyGetItemDetails( $infilename )
		{
		$path_parts = pathinfo( $infilename );
		$ptwo_parts = pathinfo( $path_parts['dirname'] );

		$new_filename = $path_parts['filename'];
		$filename = "";
		$version = "";

		// Check to see if we're a wordpress update, which uses a dash instead of a dot for the version separator.
		if( substr( $path_parts['filename'], 0, 10 ) == 'wordpress-' ) 
			{
			$version = preg_replace( '/.*-/U', '', $path_parts['filename'], 1 );
			$version = preg_replace( '/-.*/', '', $version );

			$itemname = 'wordpress';
			$lastdir = $ptwo_parts['filename'];
			}
		else
			{
			$version = preg_replace( '/.*\./U', '', $path_parts['filename'], 1 );
			$itemname = preg_replace( '/\..*/', '', $path_parts['filename'], 1 );
			$lastdir = $ptwo_parts['filename'];
			}
		
		return array( 'itemname' => $itemname, 'version' => $version, 'lastdir' => $lastdir );
		}
		
	/*
		This function is the heart of SULly, it will take an item and pull out all the imporant
		details to proivde back to the user.
		
		$itemname = the item name to process
		$lastdir = the last part of the path from the package name
	*/	
	function SULlyGetItemInfo( $itemname, $lastdir )
		{	
		GLOBAL $wp_version;
		
		// Set the default to unknown, just in case.
		$type = 'U';
		$readme = 'No changelog found.';

		// Try and determine the item type via the lastdir.
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
					
					if( array_key_exists( 'extension', $pathinfo ) )
						{
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
				}
			
			// Use the info from the plugin to set some vairables for later.
			$nicename = $PluginInfo['Name'];
			$itemurl = $PluginInfo['PluginURI'];
			$version = $PluginInfo['Version'];

			// If we don't have an itemurl or it's one of the WordPress default values, just provide a link to the plugin directory on WordPress.org
			if( $itemurl == "" OR $itemurl == 'http://-/' OR $itemurl == 'http://-' )
				{
				$itemurl = "http://wordpress.org/plugins/" . $itemname;
				}
			
			// If a readme.txt file exists, process the changelog
			if( file_exists( WP_CONTENT_DIR . '/plugins/' . $itemname . '/readme.txt' ) )
				{
				$readme = file_get_contents( WP_CONTENT_DIR . '/plugins/' . $itemname . '/readme.txt' ); 

				$readme = preg_replace( "/.*\=\=.?change.?log.?\=\=/is", "", $readme );
				$readme = preg_replace( "/\=\=.*/s", "", $readme );
				$readme = preg_replace( "/\*\*/s", "=", $readme ); // some people use ** instead of = for their version log
				$readme = preg_replace( "/\=.*.\=/", "", $readme, 1 );
				$readme = preg_replace( "/\=.*/s", "", $readme );
				$readme = trim( $readme );
	
				// Only keep the first 512 bytes of the changelog.
				if( strlen( $readme ) > 512 )
					{
					$readme = substr( $readme, 0, 512 );
					
					$readme .= "\n\nChange log truncated, visit the plugin site for more details.";
					}
	
				// Escape any html entities that are in the changelog.
				$readme = htmlentities( $readme );
	
				// Add some html <a> links to the changelog
				$readme = SULlyAddLinksToChangeLog( $readme );
				}
			}
		else if( $lastdir == 'download' )
			{
			// if the path is something like wordpress.org/themes/download/twentythirteen.1.1.zip, we're downloading a theme
			$type = 'T';
			
			// use wp_get_theme() to get more info
			$ThemeInfo = wp_get_theme( $itemname );
			
			// Use the info from the theme to set some vairables for later.
			$nicename = $ThemeInfo->Name;
			$itemurl = $ThemeInfo->ThemeURI;
			$version = $ThemeInfo->Version;

			// If not itemurl has been provided, set it to the Author's url.
			if( $itemurl == "" )
				{
				$itemurl = $ThemeInfo->AuthorURI;
				}

			// If the item url is still blank, set it to the WordPress.org theme directory.
			if( $itemurl == "" )
				{
				$itemurl = "http://wordpress.org/themes/" . $itemname;
				}
				
			// Set the default text for the changelog.
			$readme = "Sorry, theme's do not have a standard change log, please visit the theme's home page.";
			
			// While theme's don't have a "standard" changelog, check to see if the author used a readme file format anyway...
			if( file_exists( WP_CONTENT_DIR . '/themes/' . $itemname . '/readme.txt' ) )
				{
				$tempreadme = file_get_contents( WP_CONTENT_DIR . '/themes/' . $itemname . '/readme.txt' ); 

				if( preg_match( "/change.?log/is", $tempreadme ) == 1 )
					{
					$readme = $tempreadme;
					$readme = preg_replace( "/.*\=\=.?change.?log.?\=\=/is", "", $readme );
					$readme = preg_replace( "/\=\=.*/s", "", $readme );
					$readme = preg_replace( "/\*\*/s", "=", $readme ); // some people use ** instead of = for their version log
					$readme = preg_replace( "/\=.*.\=/", "", $readme, 1 );
					$readme = preg_replace( "/\=.*/s", "", $readme );
					$readme = trim( $readme );
		
					// Only keep the first 512 bytes of the changelog.
					if( strlen( $readme ) > 512 )
						{
						$readme = substr( $readme, 0, 512 );

						$readme .= "\n\nChange log truncated, visit the theme site for more details.";
						}
		
					// Escape any html entities that are in the changelog.
					$readme = htmlentities( $readme );

					// Add some html <a> links to the changelog
					$readme = SULlyAddLinksToChangeLog( $readme );
					}
				}
			}
		else if( $lastdir == 'wordpress' || $lastdir == 'release')
			{
			// if the path is something like wordpress.org/wordpress.3.7.1.zip, we're downloading a core update
			// or https://wordpress.org/wordpress-3.7.1-partial-0.zip or https://download.wordpress.org/release/wordpress-4.2-partital-0.zip
			$type = 'C';

			// Set some vairables for later.
			$nicename = 'WordPress Update';
			$itemurl = 'http://wordpress.org';
			$readme = "Visit the <a href='http://codex.wordpress.org/WordPress_Versions' target=_blank>WordPress Versions</a> page for details.";
			$version = $wp_version;
			
			// Get the current system options.
			$systemoptions = unserialize( get_option( 'SULly_System_Settings' ) );
			
			// Update the old version to the new one and store it.
			$systemoptions['WPVersion'] = $wp_version;
			update_option( 'SULly_System_Settings', serialize( $systemoptions ) );
			}
			
		// If we've still gotten all the way down here and haven't determined the type of udpate it is, let's do some more work to see if
		// we can't figure it out.
		if( $type == 'U' )
			{
			$found_item = false;
			
			// First, check to see if there is a plugin directory with a readme.txt file in it that matches.
			// Second, check to see if there is a theme directory with a style.css file in it that matches.
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
				
			// If we matched a plugin or theme above, call ourselves again to handle it with the new parameters.
			if( $found_item )
				{
				// if our guess paid off, rerun the function
				return SULlyGetItemInfo( $itemname, $lastdir );
				}
			}

		// If something went wrong above and $readme is no longer a string, or really short, reset it.
		if( ! is_string( $readme ) OR strlen( $readme ) < 5 )
			{
			$readme = "No changelog found.";
			}
			
		return array( 'type' => $type, 'nicename' => $nicename, 'itemurl' => $itemurl, 'version' => $version, 'changelog' => $readme );
		}

	/*
		This function is called by WordPress after the install of a new item is complete.
		
		$ret = the value to return on success
		$hook_extra = not used and differenet depending on how the update is being done (aka auto update/manual update/plugin install page/etc)
		$result = an array with the update details in it
	*/
	function SULlyStoreResult( $ret, $hook_extra, $result )
		{
		global $wpdb;
		
		// As WordPress does not hook the download of new Core updates, let's check to see if one has happened.
		SULlyUpdateCores();

		$TableName = $wpdb->prefix . "SULly";
		
		// Get any updates that have happened but we haven't processed yet.
		$Rows = $wpdb->get_results( "SELECT * FROM $TableName WHERE type = ''" );

		foreach( $Rows as $CurRow )
			{
			$RowID = $CurRow->id;
			
			$package = $CurRow->filename;
			
			// Get the item details.
			$itemdetails = SULlyGetItemDetails( $package );
			
			// if the current item passed to use to process matches this row, let's update it.
			if( $result["destination_name"] == $itemdetails['itemname'] )
				{
				// Local file or remote?
				if( ! preg_match( '!^(http|https|ftp)://!i', $package ) )
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
					
				// If there's no version information provided by SULlyGetItemInfo() fall back to what was provided in the item name.
				if( $iteminfo['version'] == "" ) { $iteminfo['version'] = $itemdetails['version']; }

				$wpdb->update( $TableName, array( 'filename' => $package, 'itemname' => $itemdetails['itemname'], 'nicename' => $iteminfo['nicename'], 'itemurl' => $iteminfo['itemurl'], 'version' => $iteminfo['version'], 'type' => $iteminfo['type'], 'changelog' => $iteminfo['changelog'] ), array( 'id' => $RowID ) );
				}
			}
		
		// Update any failed updates in the database.
		SULlyUpdateFails();
		
		// Check to see if any system updates have happened.
		SULlyUpdateSystemSettings( SULlyGetSystemInfo(), unserialize( get_option( 'SULly_System_Settings' ) ) );
		
		return $ret;
		}

	/*
		Update any failed entries in the database.  
	*/
	function SULlyUpdateFails()
		{
		global $wpdb;
		
		$TableName = $wpdb->prefix . "SULly";
		
		$Rows = $wpdb->get_results( "SELECT * FROM $TableName WHERE type = 'F'" );

		foreach( $Rows as $CurRow )
			{
			$RowID = $CurRow->id;
			
			$itemdetails = SULlyGetItemDetails( $CurRow->filename );
			
			$iteminfo = array( 'type' => 'F', 'nicename' => $itemdetails['itemname'], 'itemurl' => $CurRow->filename, 'version' => $itemdetails['version'], 'readme' => 'Item failed to install correctly!' );

			$wpdb->update( $TableName, array( 'itemname' => $itemdetails['itemname'], 'nicename' => $iteminfo['nicename'], 'itemurl' => $iteminfo['itemurl'], 'version' => $iteminfo['version'], 'type' => $iteminfo['type'], 'changelog' => $iteminfo['readme'] ), array( 'id' => $RowID ) );
			}
		}

	/*
		Update SULly entries for any WordPress core updates that are outsanding.
	*/
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
		}
		
	/*
		Handle the specail case where we're updating SULly itself.
	*/
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
		}
	/*
		This function returns an array of the system info we check for updates to.
	*/
	function SULlyGetSystemInfo()
		{
		GLOBAL $wp_version;
				
		return array( "WPVersion" => $wp_version, "PHPVersion" => phpversion(), "PHPExtensions" => get_loaded_extensions(), "HTTPServer" => $_SERVER["SERVER_SOFTWARE"] );
		}
		
	/*
		This function is called to setup or upgrade the SULly database and settings.
	*/
	function SULlySetup()
		{
		global $wpdb;
		global $SULlyVersion;
		
		// upgrade.php inncludes the dbDelta function
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
		
		// This is part of WordPress and will either create or update a database structure based on a SQL query.
		dbDelta( $sql );

		// Check to see if this is the first install
		$CountRows = $wpdb->get_results( 'SELECT COUNT(*) FROM ' . $TableName, ARRAY_N );

		// If this is the first install, add the inital SULly install log entry.
		if( $CountRows[0][0] == 0 )
			{
			// If this is the first install, let's add an entry for ourselves
			$wpdb->insert( $TableName, array( 'filename' => 'sully.zip', 'itemname' => 'SULly', 'nicename' => 'SULly', 'itemurl' => 'http://toolstack.com/sully', 'version' => $SULlyVersion, 'type' => 'P', 'changelog' => 'Initial SULly install!' ) );
			}
		
		// Update the current DB version in the options table
		update_option( 'SULly_DBVersion', $SULlyVersion );
		
		// Setup default options if they don't exist already.
		if( get_option( 'SULly_Entries_To_Display' ) == FALSE ) { update_option( 'SULly_Entries_To_Display', 10 ); }
		if( get_option( 'SULly_Page_Entries_To_Display' ) == FALSE ) { update_option( 'SULly_Page_Entries_To_Display', 10 ); }
		
		if( get_option( 'SULly_System_Settings' ) == FALSE ) { update_option( 'SULly_System_Settings', serialize( SULlyGetSystemInfo() ) ); }
		}
		
	/*
		This function adds the dashboard and settings menu items to the admin menus.
	*/
	function SULlyAddDashboardMenu()
		{
		if( current_user_can( 'install_plugins' ) )
			{
			add_submenu_page( 'index.php', __( 'SULly' ), __( 'SULly' ), 'manage_options', 'SULlyDashboard', 'SULlyGenerateDashboard' );
			add_options_page( 'SULly', 'SULly', 'manage_options', basename( __FILE__ ), 'SULlyAdminPage');
			}
		}
		
	/*
		This function generates the dashboard page and handles adding manual entries.
	*/
	function SULLyGenerateDashboard()
		{
		global $wpdb;
		global $_POST;

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
				print "<div class='updated settings-error'><p><strong>No item type defined!</strong></p></div>\n";
				}
			else
				{
				$wpdb->insert( $TableName, array( 'type' => $_POST['SULlyMAType'], 'version' => $_POST['SULlyMAVersion'], 'changelog' => $_POST['SULlyMAChangeLog'], 'itemname' => $_POST['SULlyMAItem'], 'nicename' => $_POST['SULlyMAItem'], 'filename' => 'Manual', 'itemurl' => 'Manual' ) );
				
				print "<div class='updated settings-error'><p><strong>Manual item added!</strong></p></div>\n";
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

		$NumToDisplay = get_option( 'SULly_Page_Entries_To_Display' );
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
		
		echo "<div class='wrap'>";
		echo "<h2>SULly - System Update Logger</h2><br>";

		echo '<form action="index.php?page=SULlyDashboard&pagenum=' . $curpage . '&manualadd=1" method="post">';
		echo '<table class="wp-list-table widefat fixed"><thead><tr><th>Manual Entry</th><th>Type</th><th>Item</th><th>Version</th><th width="30%">Change Log</th><th>Options</th></tr></thead>';
		echo '<tr>';
		echo '<td>&nbsp;</td>';
		echo '<td><select name="SULlyMAType" style="width: 100%"><option value="C">WordPress Core</option><option value="P" SELECTED>Plugin</option><option value="T">Theme</option><option value="S">System</option><option value="U">Unknown</option></select></td>';
		echo '<td><input name="SULlyMAItem" type="text" style="width: 100%"/></td>';
		echo '<td><input name="SULlyMAVersion" type="text"  style="width: 100%"/></td>';
		echo '<td><textarea style="width: 100%" name="SULlyMAChangeLog"></textarea></td>';
		echo '<td><input class="button-primary" type="submit" value="add"/></td></tr>';
		echo '</table>';
		echo '</form>';

		echo '<br>';

		echo '<table class="wp-list-table widefat fixed"><thead><tr><th>Time</th><th>Type</th><th>Item</th><th>Version</th><th>Change Log</th><th>Options</th></tr></thead>';
		foreach( $Rows as $CurRow )
			{
			echo '<tr>';
			echo '<td valign="top">';
			
			$phptime = strtotime( $CurRow->time );
			
			echo date( get_option('time_format'), $phptime ); 
			echo '<br>';
			echo date( get_option('date_format'), $phptime ); 
			
			echo '</td>';
			
			$TypeDesc = "Unknown";
			if( $CurRow->type == 'C' ) { $TypeDesc = 'WordPress Core'; }
			if( $CurRow->type == 'T' ) { $TypeDesc = 'Theme'; }
			if( $CurRow->type == 'P' ) { $TypeDesc = 'Plugin'; }
			if( $CurRow->type == 'S' ) { $TypeDesc = 'System'; }

			echo '<td valign="top">' . $TypeDesc . "</td>";
			echo '<td valign="top"><a href="' . $CurRow->itemurl . '" target="_blank">' . $CurRow->nicename . '</a></td>';
			echo '<td valign="top">' . $CurRow->version . '</td>';
			echo '<td valign="top" width="50%">' . preg_replace( '/\n/', '<br>', $CurRow->changelog ). '</td>';

			$alertbox = 'if( confirm(\'Really delete this item?\') ) { window.location = \'index.php?page=SULlyDashboard&SULlyDeleteItem=' . $CurRow->id . '\'; }';

			echo '<td><a class=button-primary href="#" onclick="' . $alertbox . '">delete</a></td>';

			echo '</tr>';
			}
		
		// Determine what page the "previous page" button should take us to.
		$lastpage = $curpage - 1;
		if( $lastpage < 1 ) { $lastpage = 1; }
		
		echo '<tfoot><tr><th colspan="6" style="text-align: center">';
		
		// If we're on the first page, don't activate the previous button.
		if( $curpage == 1 )
			{
			echo '<a class="button">Previous</a>';
			}
		else
			{
			echo '<a class=button-primary href="index.php?page=SULlyDashboard&pagenum=' . $lastpage . '">Previous</a>';
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
			echo '<a class="button">Next</a>';
			}
		else
			{
			$nextpage = $curpage + 1;
			echo '<a class="button-primary" href="index.php?page=SULlyDashboard&pagenum=' . $nextpage . '">Next</a>';
			}
			
		echo '</th></tr></tfoot></table></div>';
		}

	/*
		This function generates the admin page and handle any actions.
	*/
	function SULlyAdminPage()
		{
		global $wpdb;
		global $SULlyVersion;

		// set the default number of days old to delete items for.
		$deletedays = 90;
		
		// If we're deleting old entries, do so now...
		if( isset( $_GET['SULlyDeleteAction'] ) )
			{
			if( !isset( $_GET['SULlyActionsDeleteOld'] ) ) { $_GET['SULlyOptionsDeleteOld'] = $deletedays; }

			$deletedays = $_GET['SULlyActionsDeleteOld'];

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
		
		// If the user wants to recreate the SULly tables and options, do so.
		if( isset( $_GET['SULlyRecreateAction'] ) )
			{
			SULlySetup();

			delete_option( "SULly_Removed" );

			print "<div class='updated settings-error'><p><strong>Table and settings recreated!</strong></p></div>\n";
			}
			
		// If the user wants to delete the SULly tables and options, do so.
		if( isset( $_GET['SULlyRemoveAction'] ) )
			{
			$TableName = $wpdb->prefix . "SULly";

			// This is a bit of a hack, $wpdb doesn't return the right (aka any) count for a delete statement
			// so we have to count the rows in the table before and after we execute the delete to actually
			// the actual number of rows we deleted.
			$wpdb->get_results( 'DROP TABLE ' . $TableName );
			
			delete_option( 'SULly_Entries_To_Display' );
			delete_option( 'SULly_Page_Entries_To_Display' );
			delete_option( 'SULly_System_Settings' );

			// We add this option here so SULly won't do anything but the admin menu will still be available
			// in case they want recreate it later.  This option is removed during the uninstall process.
			update_option( 'SULly_Removed', "true" );
			
			print "<div class='updated settings-error'><p><strong>Table and settings removed!</strong></p></div>\n";
			}
			
		// Save the options if the user click save.
		if( array_key_exists( 'SULlyOptions', $_POST ) AND isset( $_POST['SULlyUpdateOptions'] ) ) 
			{
			if( !isset( $_POST['SULlyOptions']['WidgetDisplayLines'] ) ) { $_POST['SULlyOptions']['WidgetDisplayLines'] = 10; }
			if( !isset( $_POST['SULlyOptions']['PageDisplayLines'] ) ) { $_POST['SULlyOptions']['PageDisplayLines'] = 10; }
				
			update_option( 'SULly_Entries_To_Display', $_POST['SULlyOptions']['WidgetDisplayLines'] );
			update_option( 'SULly_Page_Entries_To_Display', $_POST['SULlyOptions']['PageDisplayLines'] );
			
			print "<div id='setting-error-settings_updated' class='updated settings-error'><p><strong>Settings saved.</strong></p></div>\n";
			}

		// Retreive the options.
		$SULlyOptions['WidgetDisplayLines'] = get_option( 'SULly_Entries_To_Display' );
		$SULlyOptions['PageDisplayLines'] = get_option( 'SULly_Page_Entries_To_Display' );
		
	?>
<div class="wrap">
	
	<fieldset style="border:1px solid #cecece;padding:15px; margin-top:25px" >
		<legend><span style="font-size: 24px; font-weight: 700;">SULly Options</span></legend>
		<form method="post">

				<div><?php _e('Number of entries to display in the Dashboard Widget'); ?>:&nbsp;<input name="SULlyOptions[WidgetDisplayLines]" type="text" id="SULlyOptions_WidgetDisplayLines" size="3" maxlength="3" value="<?php echo $SULlyOptions['WidgetDisplayLines']; ?>" /> </div>
				<div><?php _e('Number of entries to display in the Dashboard Page'); ?>:&nbsp;<input name="SULlyOptions[PageDisplayLines]" type="text" id="SULlyOptions_PageDisplayLines" size="3" maxlength="3" value="<?php echo $SULlyOptions['PageDisplayLines']; ?>" /> </div>
				
			<div class="submit"><input type="submit" class="button-primary" name="SULlyUpdateOptions" value="<?php _e('Update Options'); ?>" /></div>
		</form>
		
	</fieldset>

	<fieldset style="border:1px solid #cecece;padding:15px; margin-top:25px" >
		<legend><span style="font-size: 24px; font-weight: 700;">Database Actions</span></legend>

		<div style="font-size: 16px;">**WARNING** No further confirmation will be given after you press the delete button, make sure you REALLY want to delete the old records before continuing.</div>
		<div>&nbsp;</div>
		<div><?php _e('Delete records older than '); ?>:&nbsp;<input name="SULlyActionsDeleteOld" type="text" id="SULlyActionsDeletOld" size="3" maxlength="3" value="<?php echo $deletedays; ?>" /> days <input type="button" id="SullyDeleteAction" class="button-primary" name="SULlyDeleteAction" value="<?php _e('Delete'); ?>" onclick="if( confirm('Ok, last chance, really delete records over ' + document.getElementById('SULlyActionsDeletOld').value + ' days?') ) { window.location = 'options-general.php?page=sully.php&SULlyDeleteAction=TRUE&SULlyActionsDeleteOld=' + document.getElementById('SULlyActionsDeletOld').value; }"/>
		
	</fieldset>
		
	<fieldset style="border:1px solid #cecece;padding:15px; margin-top:25px" >
		<legend><span style="font-size: 24px; font-weight: 700;">Uninstall Actions</span></legend>

<?php if( get_option( "SULly_Removed" ) != 'true' )
		{ 
?>
		<div style="font-size: 16px;">**WARNING** No further confirmation will be given after you press the delete button, make sure you REALLY want to remove the database table and settings!</div>
		<div>&nbsp;</div>
		<div><?php _e('Remove the database table and all settings:')?>&nbsp;<input type="button" class="button-primary" id="SullyRemoveAction" name="SULlyRemoveAction" value="<?php _e('Remove'); ?>" onclick="if( confirm('Ok, last chance, really remove the database table?') ) { window.location = 'options-general.php?page=sully.php&SULlyRemoveAction=TRUE'}"/>
<?php
		}
	else
		{
?>
		<div><?php _e('Recreate database table and settings:')?>&nbsp;<input type="button" id="SullyRecreateAction" name="SULlyRecreateAction" value="<?php _e('Recreate') ?> &raquo;" onclick="window.location = 'options-general.php?page=sully.php&SULlyRecreateAction=TRUE'"/>
<?php 
		}
?>
		
	</fieldset>
	
	<fieldset style="border:1px solid #cecece;padding:15px; margin-top:25px" >
		<legend><span style="font-size: 24px; font-weight: 700;">About</span></legend>
		<h2>SULly - System Update Logger Version <?php echo $SULlyVersion; ?></h2>
		<p>by Greg Ross</p>
		<p>&nbsp;</p>
		<p>Licenced under the <a href="http://www.gnu.org/licenses/gpl-2.0.html" target=_blank>GPL Version 2</a></p>
		<p>To find out more, please visit the <a href='http://wordpress.org/plugins/sully/' target=_blank>WordPress Plugin Directory page</a> or the plugin home page on <a href='http://toolstack.com/sully' target=_blank>ToolStack.com</a></p> 
		<p>&nbsp;</p>
		<p>Don't forget to <a href='http://wordpress.org/support/view/plugin-reviews/sully' target=_blank>rate and review</a> it too!</p>

</fieldset>
</div>
	<?php
		}
	}

// If the current database version is not the same as the one stored in the options, install or upgrade the database and settings.
if( get_option( 'SULly_DBVersion' ) != $SULlyVersion ) { SULlySetup(); }

// Add the settings menu item.
add_action( 'admin_menu', 'SULlyAddDashboardMenu', 1 );

// If the user has removed the database and settings, don't do anything else.
if( get_option( 'SULly_Removed' ) != 'true' )
	{
	// Add the dashboard widget.
	add_action( 'wp_dashboard_setup', 'SULlyLoad' );
	// Hook in to the download code.
	add_filter( 'upgrader_pre_download', 'SULlyStoreName', 10, 2 );
	// Hook in to the post install code.
	add_filter( 'upgrader_post_install', 'SULlyStoreResult', 10, 3);
	}
?>