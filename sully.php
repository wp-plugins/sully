<?php
/*
Plugin Name: SULly
Version: 4.0
Plugin URI: http://toolstack.com/sully
Author: Greg Ross
Author URI: http://toolstack.com
Description: System Update Logger - Record system updates including plugins, themes and core updates.  Supports updates done with the new WordPress 3.7 Automatic Updates feature as well as manual uploads through the admin pages.

Compatible with WordPress 3.7+.

Read the accompanying readme.txt file for instructions and documentation.

Copyright (c) 2013-15 by Greg Ross

This software is released under the GPL v2.0, see license.txt for details
*/

$SULlyVersion = '4.0';

if( !function_exists( 'SULlyLoad' ) )
	{
	/*
		This function is called to add the dashboard widget.
	*/
	function SULlyLoad()
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

		// matches an "[desc](xxxx://yyyy)" markdown style links.
		// xxxx can only be alpha characters.
		// yyyy is anything up to the first space, newline, comma, double quote or <
		$ret = preg_replace( "#\[(.*)\]\(([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*)\)#is", "<a href=\"\\2\" target=\"_blank\">\\1</a>", $ret );
		
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
		include( 'includes/widget.dashboard.php' );
		}
	
	/*
		This function will check to see if any system settings have been changed and if so add
		an entry to the log table.
		
		$current = array of current system settings
		$old = array of previous system settings
	*/
	function SULlyUpdateSystemSettings( $current, $old )
		{
		GLOBAL $wpdb;

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
		GLOBAL $wpdb;
		
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
			last part of the path (ie http://host/path/to/file-name.3.12.zip returns 'to') 
			and the first part of the path (ie http://host/path/to/file-name.3.12.zip returns 'path') 
			
		Special care needs to be taken for WordPress packages as they have a slightly different format:
		
			https://downloads.wordpress.org/release/wordpress-4.2.2-partial-1.zip
			
		Extra special care needs to be taken for non WordPress packages from GitHub or other sources, like:
		
			https://api.github.com/repos/owner/repo/zipball/4.6.2
			https://gitlab.com/owner/repo/repository/archive.zip
			https://bitbucket.org/owner/repo/get/
			
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
		// We also have to make sure there is a number following the "wordpress-" or we might catch plugins that start with it like wordpress-importer.
		if( preg_match( '!wordpress-\d+!i', $path_parts['filename'] ) )
			{
			$version = preg_replace( '/.*-/U', '', $path_parts['filename'], 1 );
			$version = preg_replace( '/-.*/', '', $version );

			$itemname = 'wordpress';
			$lastdir = $ptwo_parts['filename'];
			$firstdir = null;
			}
		else if( preg_match( '!api\.github\.com!i', $path_parts['dirname'] ) )
			{
			$version = '';
			
			// Split the path's dirname on any / or \ or any multiple occurances of them.
			$parts = preg_split("/[\/\\\\]+/", $path_parts['dirname']);
			
			// The first directory path with be the third item returned: 0 = http[s], 1 = api.github.org, 2 = repos, 3 = owner, 4 = itemname, 5 = version
			$itemname = $parts[4];
			$lastdir = 'github-updater';
			$firstdir = $parts[3];
			}
		else if( preg_match( '!api\.gitlab\.com!i', $path_parts['dirname'] ) || preg_match( '!bitbucket\.org!i', $path_parts['dirname'] ) )
			{
			$version = $path_parts['basename'];
			
			// Split the path's dirname on any / or \ or any multiple occurances of them.
			$parts = preg_split("/[\/\\\\]+/", $path_parts['dirname']);
			
			// The first directory path with be the third item returned: 0 = http[s], 1 = host, 2 = owner, 3 = repo, 4 = etc.
			$itemname = $parts[3];
			$lastdir = 'github-updater';
			$firstdir = $parts[2];
			}
		else
			{
			$version = preg_replace( '/.*\./U', '', $path_parts['filename'], 1 );
			$itemname = preg_replace( '/\..*/', '', $path_parts['filename'], 1 );
			$lastdir = $ptwo_parts['basename'];
			
			// Split the path's dirname on any / or \ or any multiple occurances of them.
			$parts = preg_split("/[\/\\\\]+/", $path_parts['dirname']);
			
			// The first directory path with be the third item returned: 0 = http[s], 1 = wordpress.org, 2 = first dir
			$firstdir = $parts[2];
			}
		
		return array( 'itemname' => $itemname, 'version' => $version, 'lastdir' => $lastdir, 'firstdir' => $firstdir );
		}
		
	/*
		This function is the heart of SULly, it will take an item and pull out all the important
		details to provide back to the user.
		
		$itemname = the item name to process
		$lastdir = the last part of the path from the package name
	*/	
	function SULlyGetItemInfo( $itemname, $lastdir, $firstdir = null )
		{	
		GLOBAL $wp_version, $SULlyUtils;
		
		// Set the default to unknown, just in case.
		$type = 'U';
		$readme = 'No changelog found.';
		$fullreadme = '';
		$nicename = '';
		$itemurl = '';
		$version = '';

		// Handle github hosted items that are updated from github-updater
		if( $lastdir == 'github-updater' )
			{
			// Check to see if it was a plugin, if not it must be a theme.  This could have problems if a theme and plugin have the same name, but that seems unlikely.
			if( is_dir( WP_PLUGIN_DIR . "/" . $itemname ) )
				{
				$lastdir = 'plugin';
				}
			else
				{
				$lastdir = 'download';
				}
			}
		
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
					
					if( is_array( $pathsplit ) )
						{
						if( array_key_exists( 'extension', $pathsplit ) )
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

				$readme = preg_replace( "/.*\=\=.?change.?log.?\=\=/is", "", $readme );		// Remove everything above the changelog line.
				$readme = preg_replace( "/\=\=.*/s", "", $readme );							// Remove any line that starts with a double =, like a title of some kind.
				$readme = preg_replace( "/\*\*/s", "=", $readme ); 							// Some people use ** instead of = for their version log.
				$readme = preg_replace( "/\=.*.\=/", "", $readme, 1 );						// Remove the first single equal enclosed lines, aka version number.
				$readme = preg_replace( "/\=.*/s", "", $readme );							// Remove everything from the next equal sign to the end of file.
				$readme = trim( $readme );													// Trim the result to make it look nice.
	
				$fullreadme = $readme;
	
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
			
			// If we didn't find a changelog in the readme file and a changes.md file exists, process it.
			if( ( $readme == 'No changelog found.' || $readme == '' ) && file_exists( WP_CONTENT_DIR . '/plugins/' . $itemname . '/changes.md' ) )
				{
				$readme = file_get_contents( WP_CONTENT_DIR . '/plugins/' . $itemname . '/changes.md' ); 

				$readme = preg_replace( "/^#*.*/", "", $readme, 1 );						// Remove the first line that starts with a series of #'s.
				$readme = preg_replace( "/#.*/s", "", $readme );							// Remove everything from the next line that starts with a # to the end of file.
				$readme = trim( $readme );													// Trim the result to make it look nice.
	
				$fullreadme = $readme;
	
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
		
					$fullreadme = $readme;
					
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
		else if( $lastdir == 'wordpress' || $lastdir == 'release' )
			{
			// if the path is something like wordpress.org/wordpress.3.7.1.zip, we're downloading a core update
			// or https://wordpress.org/wordpress-3.7.1-partial-0.zip or https://download.wordpress.org/release/wordpress-4.2-partital-0.zip
			$type = 'C';

			// Set some variables for later.
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
		else if( $lastdir == 'nightly-builds' )
			{
			// if the path is something like https://wordpress.org/nightly-builds/wordpress-latest.zip
			$type = 'C';

			// Set some variables for later.
			$nicename = 'WordPress Nightly Build Update';
			$itemurl = 'http://wordpress.org';
			$readme = "Nightly builds do not have a changelog.";
			$version = $wp_version;
			
			// Get the current system options.
			$systemoptions = unserialize( get_option( 'SULly_System_Settings' ) );
			
			// Update the old version to the new one and store it.
			$systemoptions['WPVersion'] = $wp_version;
			update_option( 'SULly_System_Settings', serialize( $systemoptions ) );
			}
		else if( $firstdir == 'translation' )
			{
			// if the path is something like https://downloads.wordpress.org/translation/core/4.2.2/fr_FR.zip, we're downloading a translation update
			$type = 'C';
			
			// Set some variables for later.
			$nicename = 'WordPress Translation Update';
			$itemurl = 'http://wordpress.org';
			$readme = "Sorry, translations don't have a change logs.";
			$version = $wp_version;
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
				return SULlyGetItemInfo( $itemname, $lastdir, $firstdir );
				}
			}

		// If something went wrong above and $readme is no longer a string, or really short, reset it.
		if( ! is_string( $readme ) OR strlen( $readme ) < 5 )
			{
			$readme = "No changelog found.";
			}
			
		return array( 'type' => $type, 'nicename' => $nicename, 'itemurl' => $itemurl, 'version' => $version, 'changelog' => $readme, 'fullchangelog' => $fullreadme );
		}

	/*
		This function is called by WordPress after the install of a new item is complete.
		
		$ret = the value to return on success
		$hook_extra = not used and different depending on how the update is being done (aka auto update/manual update/plugin install page/etc)
		$result = an array with the update details in it
	*/
	function SULlyStoreResult( $ret, $hook_extra, $result, $process_all = false )
		{
		GLOBAL $wpdb;
		
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
			
			// If we're processing all unknown's, make sure the result is set to the current item.
			if( $process_all ) { $result["destination_name"] = $itemdetails['itemname']; }
			
			// if the current item passed to use to process matches this row, let's update it.
			if( $result["destination_name"] == $itemdetails['itemname'])
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
					$iteminfo = SULlyGetItemInfo( $itemdetails['itemname'], $itemdetails['lastdir'], $itemdetails['firstdir'] );

					// If there's no version information provided by SULlyGetItemInfo() fall back to what was provided in the item name.
					if( $iteminfo['version'] == "" ) { $iteminfo['version'] = $itemdetails['version']; }

					SULlySendUpdateEmail( $iteminfo );
					}
				else
					{
					// We'll have to grab our details later, otherwise we get wonky results back.
					$iteminfo = array( 'type' => 'M', 'nicename' => '', 'itemurl' => '', 'version' => $itemdetails['version'], 'changelog' => '', 'fullchangelog' => '' );
					}
					
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
		Sends an email to the administrator when an installation or update happens.
	*/
	function SULlySendUpdateEmail( $iteminfo )
		{
		GLOBAL $SULlyUtils;
		
		if( $SULlyUtils->get_option( 'SendEmailNotifications' ) )
			{
			$blogname = get_bloginfo('name');
			$blogemail = get_bloginfo('admin_email');
			
			$headers[] = "From: $blogname <$blogemail>";

			wp_mail( $blogemail, '[' . $blogname . '] ' . $iteminfo['nicename'] . ' has been installed/updated to version ' . $iteminfo['version'], "Change Log:\r\n\r\n" . $iteminfo['fullchangelog'], $headers );
			}
		
		}
		
	/*
		Update any failed entries in the database.  
	*/
	function SULlyUpdateFails()
		{
		GLOBAL $wpdb;
		
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
		GLOBAL $wpdb;
		
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
		GLOBAL $wpdb;
		
		$TableName = $wpdb->prefix . "SULly";
		
		$Rows = $wpdb->get_results( "SELECT * FROM $TableName WHERE type = 'M'" );

		foreach( $Rows as $CurRow )
			{
			$RowID = $CurRow->id;
			
			$itemdetails = SULlyGetItemDetails( $CurRow->filename );
			
			$iteminfo = SULlyGetItemInfo( $itemdetails['itemname'], 'plugin' );

			SULlySendUpdateEmail( $iteminfo );

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
		GLOBAL $wpdb, $SULlyVersion, $SULlyUtils;
		
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

		// If this is the first install, add the initial SULly install log entry.
		if( $CountRows[0][0] == 0 )
			{
			// If this is the first install, let's add an entry for ourselves
			$wpdb->insert( $TableName, array( 'filename' => 'sully.zip', 'itemname' => 'SULly', 'nicename' => 'SULly', 'itemurl' => 'http://toolstack.com/sully', 'version' => $SULlyVersion, 'type' => 'P', 'changelog' => 'Initial SULly install!' ) );
			}
		
		// Convert the old settings to the new array format.
		if( version_compare( get_option( 'SULly_DBVersion' ), '2.1', '<=' ) )
			{
			$SULlyUtils->store_option( 'EntriesToDisplay', get_option( 'SULly_Entries_To_Display' ) );
			$SULlyUtils->store_option( 'PageEntriesToDisplay', get_option( 'SULly_Page_Entries_To_Display' ) );
			$SULlyUtils->store_option( 'DisableWPEmailNotifications', get_option( 'SULly_Disable_WP_Email_Notifications' ) );
			$SULlyUtils->store_option( 'SendEmailNotifications', get_option( 'SULly_Send_Email_Notifications' ) );
			
			delete_option( 'SULly_Entries_To_Display' );
			delete_option( 'SULly_Page_Entries_To_Display' );
			delete_option( 'SULly_Disable_WP_Email_Notifications' );
			delete_option( 'SULly_Send_Email_Notifications' );
			}
		
		// Update the current DB version in the options table
		update_option( 'SULly_DBVersion', $SULlyVersion );
		
		// Setup default options if they don't exist already.
		if( $SULlyUtils->get_option( 'EntriesToDisplay' ) == FALSE ) { $SULlyUtils->store_option( 'EntriesToDisplay', 10 ); }
		if( $SULlyUtils->get_option( 'PageEntriesToDisplay' ) == FALSE ) { $SULlyUtils->store_option( 'PageEntriesToDisplay', 10 ); }

		// Save the settings.
		$SULlyUtils->save_options();
			
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
		include( 'includes/page.dashboard.php' );
		}

	/*
		This function generates the admin page and handle any actions.
	*/
	function SULlyAdminPage()
		{
		include( 'includes/page.settings.php' );
		}
	}
	
include_once( 'ToolStack-WP-Utilities.class.php' );

// Create our global utilities object.  We might be tempted to load the user options now, but that's not possible as WordPress hasn't processed the login this early yet.
$SULlyUtils = new ToolStack_WP_Utilities_V2_4( 'SULly' );

// If the current database version is not the same as the one stored in the options, install or upgrade the database and settings.
if( get_option( 'SULly_DBVersion' ) != $SULlyVersion ) { add_action( 'init', 'SULlySetup', 10 ); }

// Add the settings menu item.
add_action( 'admin_menu', 'SULlyAddDashboardMenu', 1 );

// If the user has removed the database and settings, don't do anything else.
if( get_option( 'SULly_Removed' ) != 'true' )
	{
	if( $SULlyUtils->get_option( 'DisableWPEmailNotifications' ) )
		{
		add_filter( 'auto_core_update_send_email', '__return_false', 50 );
		add_filter( 'send_core_update_notification_email', '__return_false', 50 );
		add_filter( 'automatic_updates_send_debug_email', '__return_false', 50 );
		}

	// Add the dashboard widget.
	add_action( 'wp_dashboard_setup', 'SULlyLoad' );
	// Hook in to the download code.
	add_filter( 'upgrader_pre_download', 'SULlyStoreName', 10, 2 );
	// Hook in to the post install code.
	add_filter( 'upgrader_post_install', 'SULlyStoreResult', 10, 3);
	}

?>