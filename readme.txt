=== SULly ===
Contributors: GregRoss
Plugin URI: http://toolstack.com/SULly
Author URI: http://toolstack.com
Tags: admin updates log
Requires at least: 3.7.0
Tested up to: 4.3
Stable tag: 4.1
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
= 4.1 =
* Release date: October 2, 2015
* Added translation domain.
* Added plugin name to the front of the change log e-mail title.
* Fixed blank upgrade e-mail when it's SULly being upgraded.
* Fixed github-updater entries not being processed after upgrades are complete.

= 4.0 =
* Release date: August 28, 2015
* Added beta support for github-updater packages (https://github.com/afragen/github-updater).
* Added ability to re-process old failed or unknown packages (see the settings page).
* Added support for markdown style links in the change log.
* Fixed support for language translation packages.
* Updated settings page.
* Updated file layout of plugin.

= 3.0 =
* Release date: June 16, 2015
* Added support for WordPress translation updates.
* Added support for sending out e-mail when update/installs happen to the administrator.
* Added support for disabling the auto update messages from WordPress.
* Fixed status messages on the settings page appearing in the about box instead of the top of the page.
* Fixed additional issues with plugins that start with "wordpress-".
* Updated options to use an array instead of separate entries.

= 2.1 =
* Release date: June 13, 2015
* Fixed the delete button's javascript.
* Fixed the regex that checks for wordpress core updates to not catch plugin's that are named "wordpress-xxx".
* Fixed detection of plugins that do not use their slug as their primary php file name.
* Updated display code for unknown updates now displays the filename in the changelog field.

= 2.0 =
* Release date: May 19, 2015
* Minor fixes for WP Debug mode warnings.
* Fixed item count on page number display.
* Handle cases where pathinfo() doesn't return an extension.
* Fix for wordpress updates not detecting the version information correctly.
* Minor display cleanups.

= 1.5 =
* Release date: December 10, 2013
* Update changelog code to better handle special characters.
* Added notice to end of changelogs when they have been truncated.

= 1.4 =
* Release date: November 22, 2013
* Replaced the table in the dashboard widget with a multiline format to better support the upcoming WordPress 3.8 releases 3 column dashboard layout.

= 1.3 =
* Release date: November 19, 2013
* Added check before adding the widget/menu items to make sure the user has rights to install plugins (aka an admin).

= 1.2 =
* Release date: November 18, 2013
* Added check to theme's code to check for a standard readme/changelog file.

= 1.1 =
* Release date: November 18, 2013
* Updated changelog code to limit size of changelog to 512 characters
* Fixed incorrect url's in the about box

= 1.0 =
* Release date:November 16, 2013
* Added manual entries for when you add a plugin through FTP or other methods.

= 0.7 =
* Release date: November 12, 2013
* Added uninstall routine.
* Fixed bug with deletion of old items not working.

= 0.6 =
* Release date: November 11, 2013
* Added extra alert when deleting records through the admin page.
* Added item deletion option on the dashboard page.

= 0.5.1 =
* Release date: November 10, 2013
* Fixed minor bug caused the initial SULly db entry to be inserted during any upgrade of SULly.

= 0.5 =
* Release date: November 9, 2013
* Added admin page.
* Added separate options for number of items to display on the dashboard widget and page.
* Added old record deletion option from the admin page.

= 0.4.2 =
* Release date: November 9, 2013
* Re-release of 0.4.1 due to missing update of version number in plugin file.

= 0.4.1 = 
* Release date: November 9, 2013
* Fixed bug with Previous/Next buttons in dashboard page.

= 0.4 = 
* Release date: November 9, 2013
* Added dashboard page.

= 0.3.1 = 
* Release date: November 9, 2013
* Update to fix logging when updating SULly itself.

= 0.3 = 
* Release date: November 9, 2013
* Fixed support for WordPress point updates.

= 0.2.2 =
* Release date: November 9, 2013
* Fixed bug in manual change detection
* Added code to create a links in change logs

= 0.2.1 =
* Release date: November 8, 2013
* Fixed bug in changelog detection regex
* Item links in the widget now open in a new window

= 0.2 =
* Release date: November 8, 2013
* Added lots of code for dealing with 'non-standard' plugin formats
* Added check for manual WordPress updates
* Added check for PHP changes (version, plugins)
* Added check for Web Server changes

= 0.1 =
* Release date: November 7, 2013
* Initial release.

== Upgrade Notice ==
= 1.5 =
* Old changelog entries may not display special characters correctly.

== Roadmap ==

* None at this time!
