=== SULly ===
Contributors: GregRoss
Plugin URI: http://toolstack.com/SULly
Author URI: http://toolstack.com
Tags: admin updates log
Requires at least: 3.7.0
Tested up to: 3.7.1
Stable tag: 0.4
License: GPLv2

System Update Logger - Record system updates including plugins, themes and core updates.

== Description ==

With WordPress 3.7, updates happen automatically for you, however there is only e-mail notifications sent.  WordPress has a robust administration interface so SULly records all system updates (either automatic or manually done through the admin interface) in to a table and presents the last 10 updates to you in a dashboard widget.



WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING 

WARNING This is very preliminary code and has only been tested with a   WARNING

WARNING very small set of plugins and themes.                           WARNING

WARNING                                                                 WARNING 

WARNING                DO NOT USE ON PRODUCITON SYSTEMS                 WARNING

WARNING                                                                 WARNING 

WARNING I recommend waiting until version 1.0 for the less adventurous. WARNING

WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING WARNING 



Also note that this plugin can only display logs for items installed after SULly itself is installed.

This code is released under the GPL v2, see license.txt for details.

== Installation ==

1. Extract the archive file into your plugins directory in the SULly folder.
2. Activate the plugin in the Plugin options.
3. Login to WordPress and add the widget to your dashboard.

== Frequently Asked Questions ==

= None =

None

== Screenshots ==

1. The dashboard widget.
2. The dashboard page.

== Changelog ==
= 0.4 = 
* Added dashboard page.

= 0.3.1 = 
* Update to fix logging when updating SULly itself.

= 0.3 = 
* Fixed support for WordPress point updates.

= 0.2.2 =
* Fixed bug in manual change detection
* Added code to create a links in change logs

= 0.2.1 =
* Fixed bug in changelog detection regex
* Item links in the widget now open in a new window

= 0.2 =
* Added lots of code for dealing with 'non-standard' plugin formats
* Added check for manual WordPress updates
* Added check for PHP changes (version, plugins)
* Added check for Web Server changes

= 0.1 =
* Initial release.

== Upgrade Notice ==
= 0.4 =
None.

== Roadmap ==

* Add control of number of updates to keep track of.
* Add manual entries for when you add a plugin through FTP or other method.
* Add administration interface
* Add uninstall routine