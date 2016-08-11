=== Updater by BestWebSoft ===
Contributors: bestwebsoft
Donate link: http://bestwebsoft.com/donate/
Tags: updater, updater plugin, recent version, recent wordpress version, update frequency, update manually, update notification, update plugins, update wordpres, update to latest version, update automatically, update manually
Requires at least: 3.8
Tested up to: 4.6
Stable tag: 1.34
License: GPLv2 or later

Automatically check and update WordPress website core with all installed plugins and themes to the latest versions.

== Description ==

This plugin allows you to update your WordPress, themes, and plugins to the recent versions in the easiest way. You can select auto or manual mode for updating. Also you're able to make a backup before updating, set email notifications before and after update. If necessary, you can disable auto WordPress update.

http://www.youtube.com/watch?v=I63426HTJjI

<a href="http://www.youtube.com/watch?v=CmHctvGHWMs" target="_blank">Updater by BestWebSoft Video instruction on Installation</a>

<a href="http://wordpress.org/plugins/updater/faq/" target="_blank">Updater by BestWebSoft FAQ</a>

<a href="http://support.bestwebsoft.com" target="_blank">Updater by BestWebSoft Support</a>

<a href="http://bestwebsoft.com/products/updater/?k=49e226d45dc4d3465a079fa62317eab2" target="_blank" title="Updater Pro">Upgrade to Updater Pro by BestWebSoft</a>

= Features =

* Update your plugins, themes and WordPress
* Choose the updating mode: manual or automatic
* Set the update periodicity (for automatic mode)
* Choose what is necessary to search/update:  WordPress, themes or/and plugins
* Receive email notifications when new versions are available or/and after update is completed

If you have a feature, suggestion or idea you'd like to see in the plugin, we'd love to hear about it! <a href="http://support.bestwebsoft.com/hc/en-us/requests/new" target="_blank">Suggest a Feature</a>

= User Guide =

1. After downloading and activating the plugin 'Updater', you should go to the page 'Updater' => 'Settings' in the 'BWS Panel' menu, where the plugin settings are available.
2. On this page you should choose the plugin mode and edit email notifications settings.
3. If you want to update your plugins, themes or WordPress in the manual mode you should go to the page 'Updater' in the 'Tools' menu. If WordPress or some of your plugins and themes needs update it will be highlighted in red. Check it and click 'Update'.

= Translation =

* Russian (ru_RU)
* Serbian (sr_RS) (thanks to <a href="mailto:borisad@webhostinghub.com">Borisa Djuraskovic</a>)
* Ukrainian (uk)

Some of these translations are not complete. We are constantly adding new features which should be translated. If you would like to create your own language pack or update the existing one, you can send <a href="http://codex.wordpress.org/Translating_WordPress" target="_blank">the text of PO and MO files</a> for <a href="http://support.bestwebsoft.com/hc/en-us/requests/new" target="_blank">BestWebSoft</a> and we'll add it to the plugin. You can download the latest version of the program for work with PO and MO files  <a href="http://www.poedit.net/download.php" target="_blank">Poedit</a>.

= Technical support =

Dear users, our plugins are available for free download. If you have any questions or recommendations regarding the functionality of our plugins (existing options, new options, current issues), please feel free to contact us. Please note that we accept requests in English only. All messages in other languages won't be accepted.

If you notice any bugs in the plugins, you can notify us about it and we'll investigate and fix the issue then. Your request should contain URL of the website, issues description and WordPress admin panel credentials.
Moreover we can customize the plugin according to your requirements. It's a paid service (as a rule it costs $40, but the price can vary depending on the amount of the necessary changes and their complexity). Please note that we could also include this or that feature (developed for you) in the next release and share with the other users then.
We can fix some things for free for the users who provide translation of our plugin into their native language (this should be a new translation of a certain plugin, you can check available translations on the official plugin page).

== Installation ==

1. Upload the `updater` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin via the 'Plugins' menu in WordPress.
3. Plugin settings are located in 'BWS Panel', 'Updater', 'Settings'.

<a href="https://docs.google.com/document/d/1tzCpHUUzvRsAKrW-9vOLjJkT-43cBI0bMc05YAqNzHM/edit" target="_blank">View a PDF version of Step-by-step Instruction on Updater Installation</a>.

http://www.youtube.com/watch?v=CmHctvGHWMs

== Frequently Asked Questions ==

= The plugin doesn't send any emails =

Please follow the instructions below:

1. Check the plugin settings '/wp-admin/admin.php?page=updater-options'
2. The plugin sends email notifications if any updates are available.
3. Some email messages can be marked as spam on the server. Enter email address ('Sender's name and email address'), for example, gmail account or similar. If the message is approved (sent), this means that the email filtering is configured on your server.
4. Perhaps you didn't configure email sending. In this case You should install <a href="https://wordpress.org/plugins/bws-smtp/" target="_blank">SMTP by BestWebSoft</a> plugin and configure it. Afterwards you should send a test email from this plugin and you will see if the plugin logs have errors.

= The plugin stopped sending emails after some time. What's the problem? =

The plugin sends email notification if any updates are available. If there are no updates available, you won't get anything.

= Sending time does not correspond to that specified on the settings page =

The function of sending emails and updating runs when the site is active (when someone visits your site.) If there is no activity, it will run when the first activity appears. So the time can be shifted.

= I have some problems with the plugin's work. What Information should I provide to receive proper support? =

Please make sure that the problem hasn't been discussed yet on our forum (<a href="http://support.bestwebsoft.com" target="_blank">http://support.bestwebsoft.com</a>). If no, please provide the following data along with your problem's description:

1. the link to the page where the problem occurs
2. the name of the plugin and its version. If you are using a pro version - your order number.
3. the version of your WordPress installation
4. copy and paste into the message your system status report. Please read more here: <a href="https://docs.google.com/document/d/1Wi2X8RdRGXk9kMszQy1xItJrpN0ncXgioH935MaBKtc/edit" target="_blank">Instruction on System Status</a>

== Screenshots ==

1. Updater Tools page.
2. Updater Settings page.

== Changelog ==

= V1.34 - 10.08.2016 =
* Update : All functionality for WordPress 4.6 was updated.

= V1.33 - 21.06.2016 =
* Update : We updated all functionality for wordpress 4.5.2.
* Bugfix : An error with checking inputs validation was fixed.
* Pro : Envato compatibility was added.

= V1.32 - 03.03.2016 =
* Bugfix : The bug with email notifications was fixed.
* Bugfix : Auto update was fixed.

= V1.31 - 20.01.2016 =
* NEW : Added the ability to update the themes.
* NEW : Added ability to choose what is necessary to search/update:  WordPress, themes or/and plugins.

= V1.30 - 02.12.2015 =
* Bugfix : The bug with plugin menu duplicating was fixed.

= V1.29 - 24.09.2015 =
* Update : We updated all functionality for wordpress 4.3.1.

= V1.28 - 14.07.2015 =
* NEW : Ability to restore settings to defaults.
* Bugfix : The settings page url in email was fixed.

= V1.27 - 12.06.2015 =
* Update : We updated all functionality for wordpress 4.2.2.
* Update : BWS plugins section was updated.

= V1.26 - 07.05.2015 =
* Bugfix : Auto core update was fixed.
* Bugfix : Recipient email address setting was fixed.

= V1.25 - 28.04.2015 =
* Bugfix : Plugins work on multisite was changed. Managing settings is now available for the network administrator only.
* Update : We updated all functionality for wordpress 4.2.

= V1.24 - 26.02.2015 =
* Update : Plugin settings are updated.
* Update : We updated all functionality for wordpress 4.1.1.

= V1.23 - 08.01.2015 =
* Update : We updated all functionality for wordpress 4.1.

= V1.22 - 12.11.2014 =
* Update : BWS plugins section was updated.
* Bugfix : The incorrect link in emails is changed.

= V1.21 - 12.08.2014 =
* Update : The outdated function wp_update_core for WordPress V. 3.7 and later was replaced.
* Bugfix : Security Exploit was fixed.
* Budfix : We fixed the bug of core update.

= V1.20 - 15.07.2014 =
* Update : We updated all functionality for Email Queue plugin.

= V1.19 - 22.05.2014 =
* Update : We updated all functionality for wordpress 3.9.1.
* Bugfix : We fixed the bug of the wrong version in the report after the update.
* Update : The Ukrainian language file is updated.

= V1.18 - 11.04.2014 =
* Update : We updated all functionality for wordpress 3.8.2.

= V1.17 - 13.03.2014 =
* Budfix : Plugin optimization is done.
* Update : Screenshots and BWS menu were updated.

= V1.16 - 04.02.2014 =
* Update : We updated all functionality for wordpress 3.8.1.

= V1.15 - 16.12.2013 =
* Update : We updated all functionality for wordpress 3.8.
* NEW : A notice when changing settings on the plugin's settings page was added.

= V1.14 - 13.11.2013 =
* Bugfix : We fixed the bug of syntax error.

= V1.13 - 12.11.2013 =
* NEW : Add checking installed wordpress version.
* NEW : The Serbian language file is added to the plugin.
* Update : We updated all functionality for wordpress 3.7.1.
* Update : Activation of radio button or checkbox by clicking on its label.

= V1.12 - 22.10.2013 =
* Update : Screenshots and BWS menu were updated.

= V1.11 - 02.10.2013 =
* Update : We updated all functionality for wordpress 3.6.1.
* NEW : The Ukrainian language file is added to the plugin.

= V1.10 - 04.09.2013 =
* Update : We updated all functionality for wordpress 3.6.
* Update : Function for displaying BWS plugins section placed in a separate file and has own language files.

= V1.09 - 18.07.2013 =
* NEW : Added an ability to view and send system information by mail.
* Update : We updated all functionality for wordpress 3.5.2.

= V1.08 - 03.06.2013 =
* Update : BWS plugins section is updated.

= V1.07 - 16.04.2013 =
* NEW : Added html blocks.

= V1.06 - 08.04.2013 =
* Update : We updated the English language in the plugin.

= V1.05 - 25.02.2013 =
* Bugfix : The code refactoring.
* Update : Changed location of the pages.

= V1.04 - 13.02.2013 =
* NEW : Added sending a test email.
* NEW : Added Frequently Asked Questions.

= V1.03 - 06.02.2013 =
* Bugfix : The time bug is fixed.
* Update : Updated the email notification which is sent when new versions of the plugins or WordPress are available.

= V1.02 - 28.01.2013 =
* NEW : Add sending email when new versions of the plugins or WordPress are available.
* Bugfix : Bugs in admin menu are fixed.
* Update : Css-style is updated.
* Update : We updated all functionality for wordpress 3.5.1.

= V1.01 - 17.01.2013 =
* Bugfix : The code refactoring was performed.
* NEW : Css-style was added.

== Upgrade Notice ==

= V1.34 =
* The compatibility with new WordPress version updated.

= V1.33 =
We updated all functionality for wordpress 4.5.2. An error with checking inputs validation was fixed.

= V1.32 =
The bug with email notifications was fixed. Auto update was fixed.

= V1.31 =
Added the ability to update the themes. Added ability to choose what is necessary to search/update:  WordPress, themes or/and plugins.

= V1.30 =
The bug with plugin menu duplicating was fixed.

= V1.29 =
We updated all functionality for wordpress 4.3.1.

= V1.28 =
Ability to restore settings to defaults. The settings page url in email was fixed.

= V1.27 =
We updated all functionality for wordpress 4.2.2. BWS plugins section was updated.

= V1.26 =
Auto core update was fixed. Recipient email address setting was fixed.

= V1.25 =
Plugins work on multisite was changed. Managing settings is now available for the network administrator only. We updated all functionality for wordpress 4.2.

= V1.24 =
Plugin settings are updated. We updated all functionality for wordpress 4.1.1.

= V1.23 =
We updated all functionality for wordpress 4.1.

= V1.22 =
BWS plugins section was updated. The incorrect link in emails is changed.

= V1.21 =
The outdated function wp_update_core for WordPress V. 3.7 and later was replaced. Security Exploit was fixed. We fixed the bug of core update.

= V1.20 =
We updated all functionality for Email Queue plugin.

= V1.19 =
We updated all functionality for wordpress 3.9.1. We fixed the bug of the wrong version in the report after the update. The Ukrainian language file is updated.

= V1.18 =
We updated all functionality for wordpress 3.8.2.

= V1.17 =
Plugin optimization is done. Screenshots and BWS menu were updated.

= V1.16 =
We updated all functionality for wordpress 3.8.1.

= V1.15 =
We updated all functionality for wordpress 3.8. A notice when changing settings on the plugin's settings page was added.

= V1.14 =
We fixed the bug of syntax error.

= V1.13 =
Add checking installed wordpress version. The Serbian language file is added to the plugin. We updated all functionality for wordpress 3.7.1. Activation of radio button or checkbox by clicking on its label.

= V1.12 =
Screenshots and BWS menu were updated.

= V1.11 =
We updated all functionality for wordpress 3.6.1. The Ukrainian language file is added to the plugin.

= V1.10 =
We updated all functionality for wordpress 3.6. Function for displaying BWS plugins section placed in a separate file and has own language files

= V1.09 =
Added an ability to view and send system information by mail. We updated all functionality for wordpress 3.5.2.

= V1.08 =
BWS plugins section is updated.

= V1.07 =
Added html blocks.

= V1.06 =
We updated the English language in the plugin.

= V1.05 =
The code refactoring. Changed location of the pages.

= V1.04 =
Add sending a test email. Add Frequently Asked Questions.

= V1.03 =
The time bug is fixed. Updated the email notification which is sent when new versions of the plugins or WordPress are available.

= V1.02 =
Add sending email when new versions of plugins or WordPress are available. Bugs in the admin menu are fixed. Css-style is updated. We updated all functionality for wordpress 3.5.1.

= V1.01 =
The code refactoring. Css-style was added.
