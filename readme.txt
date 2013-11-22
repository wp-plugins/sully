=== SULly ===
Contributors: GregRoss
Plugin URI: http://toolstack.com/SULly
Author URI: http://toolstack.com
Tags: admin updates log
Requires at least: 3.7.0
Tested up to: 3.7.1
Stable tag: 1.4
License: GPLv2

System Update Logger - Record system updates including plugins, themes and core updates.

== Description ==

With WordPress 3.7, updates happen automatically for you, however there is only e-mail notifications sent.  WordPress has a robust administration interface so SULly records all system updates (either automatic or manually done through the admin interface) in to a table and presents the last 10 updates to you in a dashboard widget.

Also note that this plugin can only display logs for items installed after SULly itself is installed.

This code is released under the GPL v2, see license.txt for details.

== Installation ==

1. Extract the archive file into your plugins directory in the SULly folder.
2. Activate the plugin in the Plugin options.
3. Login to WordPress and add the widget to your dashboard.

== Frequently Asked Questions ==

= Why doesn't SULly show me update from before I installed it? =

WordPress doesn't record this information anywhere so SULly hooks in to the download and update hooks in WordPress 3.7 to keep track of these changes, but that means it has to be installed before it can record the updates.  

= I updated a plugin and the change log in SULly is blank but does have a readme.txt and a change log is there, what's wrong? =

As much as there is a standard for change logs in WordPress's readme standard, it is open to interpretation by plugin authors.

The code in SULly should catch most change logs but if you find one that doesn't, go to the support forum (http://wordpress.org/support/plugin/sully) for SULly on WordPress.org and post a link to the plugin and I'll see what I can do to update the code to catch the change log.

= The manual and system change log entries have a date that's different from when I made the change, why? =

The manual and system change types are only captured the next time someone loads the admin interface, so they may have different time's on them than you expect.  

== Screenshots ==

1. The dashboard widget.
2. The dashboard page.
3. The admin page.

== Changelog ==
= 1.4 =
* Replaced the table in the dashboard widget with a multiline format to better support the upcoming WordPress 3.8 releases 3 column dashboard layout.

= 1.3 =
* Added check before adding the widget/menu items to make sure the user has rights to install plugins (aka an admin).

= 1.2 =
* Added check to theme's code to check for a standard readme/changelog file.

= 1.1 =
* Updated changelog code to limit size of changelog to 512 characters
* Fixed incorrect url's in the about box

= 1.0 =
* Added manual entries for when you add a plugin through FTP or other methods.

= 0.7 =
* Added uninstall routine.
* Fixed bug with deletion of old items not working.

= 0.6 =
* Added extra alert when deleting records through the admin page.
* Added item deletion option on the dashboard page.

= 0.5.1 =
* Fixed minor bug caused the initial SULly db entry to be inserted during any upgrade of SULly.

= 0.5 =
* Added admin page.
* Added separate options for number of items to display on the dashboard widget and page.
* Added old record deletion option from the admin page.

= 0.4.2 =
* Re-release of 0.4.1 due to missing update of version number in plugin file.

= 0.4.1 = 
* Fixed bug with Previous/Next buttons in dashboard page.

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
= 1.4 =
None.

== Roadmap ==

* None at this time!
