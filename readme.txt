=== Updater ===
Contributors: bestwebsoft
Donate link: https://www.2checkout.com/checkout/purchase?sid=1430388&quantity=10&product_id=13
Tags: plugin, core, wordpress, update
Requires at least: 3.0
Tested up to: 3.6.1
Stable tag: 1.11
License: GPLv2 or later

This plugin allows you to update plugins and WordPress core.

== Description ==

This plugin updates WordPress core and the plugins to the recent versions. You can also use the auto mode or manual mode for updating and set email notifications.
There is also a premium version of the plugin with more useful features available.

<a href="http://wordpress.org/extend/plugins/updater/faq/" target="_blank">FAQ</a>
<a href="http://support.bestwebsoft.com" target="_blank">Support</a>

= User Guide =

1. After downloading and activating the plugin 'Updater', you should go to the page 'Updater' => 'Options' in the 'BWS Plugins' menu, where the plugin settings are available.
2. On this page you should choose the plugin mode and edit email notifications settings.
3. If you want to update your plugins or WP core in the manual mode you should go to the page 'Updater' in the 'Tools' menu. If WordPress or some of your plugins needs update it will be highlighted in red. Check it and click 'update'.

= Translation =

* Russian (ru_RU)
* Ukrainian (uk)

If you would like to create your own language pack or update the existing one, you can send <a href="http://codex.wordpress.org/Translating_WordPress" target="_blank">the text of PO and MO files</a> for <a href="http://support.bestwebsoft.com" target="_blank">BestWebSoft</a> and we'll add it to the plugin. You can download the latest version of the program for work with PO and MO files  <a href="http://www.poedit.net/download.php" target="_blank">Poedit</a>.

= Technical support =

Dear users, our plugins are available for free download. If you have any questions or recommendations regarding the functionality of our plugins (existing options, new options, current issues), please feel free to contact us. Please note that we accept requests in English only. All messages in another languages won't be accepted.

If you notice any bugs in the plugin's work, you can notify us about it and we'll investigate and fix the issue then. Your request should contain URL of the website, issues description and WordPress admin panel credentials.
Moreover we can customize the plugin according to your requirements. It's a paid service (as a rule it costs $40, but the price can vary depending on the amount of the necessary changes and their complexity). Please note that we could also include this or that feature (developed for you) in the next release and share with the other users then. 
We can fix some things for free for the users who provide translation of our plugin into their native language (this should be a new translation of a certain plugin, you can check available translations on the official plugin page).

== Installation ==

1. Upload the `updater` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin via the 'Plugins' menu in WordPress.
3. Plugin settings are located in 'BWS Plugins', 'Updater', 'options'.

== Frequently Asked Questions ==

= The plugin doesn't send any emails =

Please follow the instructions below:
1. Please check the plugin settings '/wp-admin/admin.php?page=updater-options'
2. The plugin sends email notifications if any updates are available. 
3. Some email messages can be marked as spam on the server. Enter email address ('Sender's name and email address'), for example, gmail account or similar. If the message is approved (sent), this means that the email filtering is configured on your server.
4. Are you getting any notifications about new users registration? If no, perhaps you didn't configure email sending. In this case You should install an extra plugin called WP-mail-SMTP and configure it.

= The plugin stopped sending emails after some time. What's the problem? =

The plugin sends email notification if any updates are available. If there are no updates available, you won't get anything.

= The time of sending does not match what I have specified (or default value). =

The function for sending emails and updating is triggered when the site is active (when someone visits your site.) If there is no activity, it will run when the first activity appears. So the time can be shifted.

= How often will the plugin search for updates or/and update plugins and WordPress if I did not specify the time? =

Updater does it every 12 hours by default. In auto mode Updater updates plugins or WP if any updates are available.

= I want the plugin to search for updates or/and update plugins and WordPress every 0.5 hours =

It's impossible. The number of hours should be integer and it should not include more than 5 digits.

== Screenshots ==

1. Updater Options page.
2. Updater page in the BWS admin area.

== Changelog ==

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