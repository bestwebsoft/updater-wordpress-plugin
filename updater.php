<?php
/*
Plugin Name: Updater
Plugin URI: http://bestwebsoft.com/products/
Description: This plugin allows you to update plugins and WP core in auto or manual mode.
Author: BestWebSoft
Version: 1.24
Author URI: http://bestwebsoft.com/
License: GPLv2 or later
*/

/*
	© Copyright 2015  BestWebSoft  ( http://support.bestwebsoft.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Create pages for the plugin */
if ( ! function_exists( 'pdtr_add_admin_menu' ) ) {
	function pdtr_add_admin_menu() {
		global $bstwbsftwppdtplgns_options, $bstwbsftwppdtplgns_added_menu;
		$bws_menu_info = get_plugin_data( plugin_dir_path( __FILE__ ) . "bws_menu/bws_menu.php" );
		$bws_menu_version = $bws_menu_info["Version"];
		$base = plugin_basename(__FILE__);

		if ( ! isset( $bstwbsftwppdtplgns_options ) ) {
			if ( is_multisite() ) {
				if ( ! get_site_option( 'bstwbsftwppdtplgns_options' ) )
					add_site_option( 'bstwbsftwppdtplgns_options', array() );
				$bstwbsftwppdtplgns_options = get_site_option( 'bstwbsftwppdtplgns_options' );
			} else {
				if ( ! get_option( 'bstwbsftwppdtplgns_options' ) )
					add_option( 'bstwbsftwppdtplgns_options', array() );
				$bstwbsftwppdtplgns_options = get_option( 'bstwbsftwppdtplgns_options' );
			}
		}

		if ( isset( $bstwbsftwppdtplgns_options['bws_menu_version'] ) ) {
			$bstwbsftwppdtplgns_options['bws_menu']['version'][ $base ] = $bws_menu_version;
			unset( $bstwbsftwppdtplgns_options['bws_menu_version'] );
			if ( is_multisite() )
				update_site_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
			else
				update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
			require_once( dirname( __FILE__ ) . '/bws_menu/bws_menu.php' );
		} else if ( ! isset( $bstwbsftwppdtplgns_options['bws_menu']['version'][ $base ] ) || $bstwbsftwppdtplgns_options['bws_menu']['version'][ $base ] < $bws_menu_version ) {
			$bstwbsftwppdtplgns_options['bws_menu']['version'][ $base ] = $bws_menu_version;
			if ( is_multisite() )
				update_site_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
			else
				update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
			require_once( dirname( __FILE__ ) . '/bws_menu/bws_menu.php' );
		} else if ( ! isset( $bstwbsftwppdtplgns_added_menu ) ) {
			$plugin_with_newer_menu = $base;
			foreach ( $bstwbsftwppdtplgns_options['bws_menu']['version'] as $key => $value ) {
				if ( $bws_menu_version < $value && is_plugin_active( $base ) ) {
					$plugin_with_newer_menu = $key;
				}
			}
			$plugin_with_newer_menu = explode( '/', $plugin_with_newer_menu );
			$wp_content_dir = defined( 'WP_CONTENT_DIR' ) ? basename( WP_CONTENT_DIR ) : 'wp-content';
			if ( file_exists( ABSPATH . $wp_content_dir . '/plugins/' . $plugin_with_newer_menu[0] . '/bws_menu/bws_menu.php' ) )
				require_once( ABSPATH . $wp_content_dir . '/plugins/' . $plugin_with_newer_menu[0] . '/bws_menu/bws_menu.php' );
			else
				require_once( dirname( __FILE__ ) . '/bws_menu/bws_menu.php' );	
			$bstwbsftwppdtplgns_added_menu = true;			
		}

		add_menu_page( 'BWS Plugins', 'BWS Plugins', 'manage_options', 'bws_plugins', 'bws_add_menu_render', plugins_url( 'images/px.png', __FILE__ ), 1001 );
		add_submenu_page( 'bws_plugins', 'Updater', 'Updater', 'manage_options', 'updater', 'pdtr_own_page' );
		add_submenu_page( 'updater', 'Updater', 'Updater', 'manage_options', 'updater-options', 'pdtr_settings_page' );
		add_submenu_page( 'updater', 'Updater', 'Updater', 'manage_options', 'updater-go-pro', 'pdtr_go_pro_page' );
	}
}

if ( ! function_exists ( 'pdtr_init' ) ) {
	function pdtr_init() {
		/* Internationalization */
		load_plugin_textdomain( 'updater', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		/* Function check if plugin is compatible with current WP version  */
		pdtr_version_check();
	}
}
if ( ! function_exists ( 'pdtr_admin_init' ) ) {
	function pdtr_admin_init() {
		global $bws_plugin_info, $pdtr_plugin_info;

		if ( ! $pdtr_plugin_info )
			$pdtr_plugin_info = get_plugin_data( __FILE__ );	

		if ( ! isset( $bws_plugin_info ) || empty( $bws_plugin_info ) )			
			$bws_plugin_info = array( 'id' => '84', 'version' => $pdtr_plugin_info["Version"] );

		/* Call register settings function */
		if ( isset( $_GET['page'] ) && ( "updater-options" == $_GET['page'] || "updater" == $_GET['page'] ) )
			pdtr_register_settings();
	}
}

/* Function check if plugin is compatible with current WP version  */
if ( ! function_exists ( 'pdtr_version_check' ) ) {
	function pdtr_version_check() {
		global $wp_version, $pdtr_plugin_info;
		$require_wp		=	"3.3"; /* Wordpress at least requires version */
		$plugin			=	plugin_basename( __FILE__ );
	 	if ( version_compare( $wp_version, $require_wp, "<" ) ) {
	 		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			if ( is_plugin_active( $plugin ) ) {
				deactivate_plugins( $plugin );
				$admin_url = ( function_exists( 'get_admin_url' ) ) ? get_admin_url( null, 'plugins.php' ) : esc_url( '/wp-admin/plugins.php' );
				if ( ! $pdtr_plugin_info )
					$pdtr_plugin_info = get_plugin_data( __FILE__, false );
				wp_die( "<strong>" . $pdtr_plugin_info['Name'] . " </strong> " . __( 'requires', 'updater' ) . " <strong>WordPress " . $require_wp . "</strong> " . __( 'or higher, that is why it has been deactivated! Please upgrade WordPress and try again.', 'updater') . "<br /><br />" . __( 'Back to the WordPress', 'updater') . " <a href='" . $admin_url . "'>" . __( 'Plugins page', 'updater') . "</a>." );
			}
		}
	}
}

/* Register settings function */
if ( ! function_exists( 'pdtr_register_settings' ) ) {
	function pdtr_register_settings() {
		global $pdtr_options, $pdtr_plugin_info, $pdtr_option_defaults;

		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}
		$from_email = 'wordpress@' . $sitename;

		$pdtr_option_defaults = array(
			'plugin_option_version' 		=> $pdtr_plugin_info["Version"],
			'pdtr_mode'						=> '0',
			'pdtr_send_mail_after_update'	=> '1',
			'pdtr_send_mail_get_update'		=> '1',
			'pdtr_time'						=> '12',
			'pdtr_to_email'					=> get_option( 'admin_email' ),
			'pdtr_from_name'				=> get_bloginfo( 'name' ),
			'pdtr_from_email'				=> $from_email
	  	);

	  	/* Install the option defaults */
		if ( ! get_option( 'pdtr_options' ) )
			add_option( 'pdtr_options', $pdtr_option_defaults );
		/* Get options from the database */
		$pdtr_options = get_option( 'pdtr_options' );

		/* Array merge incase this version has added new options */
		if ( ! isset( $pdtr_options['plugin_option_version'] ) || $pdtr_options['plugin_option_version'] != $pdtr_plugin_info["Version"] ) {
			if ( '' == $pdtr_options['pdtr_from_email'] )
				$pdtr_options['pdtr_from_email'] = $pdtr_option_defaults['pdtr_from_email'];
			if ( '' == $pdtr_options['pdtr_from_name'] )
				$pdtr_options['pdtr_from_name'] = $pdtr_option_defaults['pdtr_from_name'];
			if ( '' == $pdtr_options['pdtr_to_email'] )
				$pdtr_options['pdtr_to_email'] = $pdtr_option_defaults['pdtr_to_email'];
			if ( '' == $pdtr_options['pdtr_time'] )
				$pdtr_options['pdtr_time'] = '12';

	  		$pdtr_options = array_merge( $pdtr_option_defaults, $pdtr_options );
	  		$pdtr_options['plugin_option_version'] = $pdtr_plugin_info["Version"];
		  	update_option( 'pdtr_options', $pdtr_options );
		}
	}
}
/* End pdtr_register_settings */

/* Add time for cron viev */
if ( ! function_exists( 'pdtr_schedules' ) ) {
	function pdtr_schedules( $schedules ) {
		global $pdtr_options;
		if ( empty( $pdtr_options ) )
			$pdtr_options = get_option( 'pdtr_options' );
		$schedules_hours = ( '' != $pdtr_options['pdtr_time'] ) ? $pdtr_options['pdtr_time'] : 12;

	    $schedules['schedules_hours'] = array( 'interval' => $schedules_hours*60*60, 'display' => 'Every ' . $schedules_hours . ' hours' );
	    return $schedules;
	}
}

/* Function for display updater settings page in the BWS admin area */
if ( ! function_exists ( 'pdtr_settings_page' ) ) {
	function pdtr_settings_page() {
		global $pdtr_options, $wp_version, $pdtr_plugin_info, $pdtr_option_defaults;
		$options_error = $message =	"";

		/* Check mail */
		if ( isset( $_REQUEST["pdtr_form_check_mail"] ) && check_admin_referer( plugin_basename(__FILE__), 'pdtr_nonce_check_mail' ) ) {
			global $pdtr_core_plugin_list;			
			$pdtr_core_plugin_list = pdtr_processing_site();
			$plugin_upd_list		=	"";
			$core					=	false;
			
			if ( $pdtr_core_plugin_list["core"]["current"] != $pdtr_core_plugin_list["core"]["new"] )
				$core = true;
			
			if ( isset( $pdtr_core_plugin_list["plg_need_update"] ) ) {
				foreach ( $pdtr_core_plugin_list["plg_need_update"] as $key => $value ) {
					$plugin_upd_list[] = $key;
				}
			}

			if ( 1 == $pdtr_options["pdtr_send_mail_get_update"] || 1 == $pdtr_options["pdtr_send_mail_after_update"] ) {
				$result_mail = pdtr_notification_exist_update( $plugin_upd_list, $core, true );

				$email = ( "" != $pdtr_options["pdtr_to_email"] ) ? $pdtr_options["pdtr_to_email"] : get_option( 'admin_email' );

				if ( $result_mail != true )
					$message = __( "Sorry, your message could not be delivered to", 'updater' ) . ' ' . $email;
				else
					$message = __( "Test message is sent to", 'updater' ) . ' ' . $email;
			} else {
				$message = __( "Please check off the Send email options, save settings and try again", 'updater' );
			}
		}

		/* Save data for settings page */
		if ( isset( $_REQUEST["pdtr_form_submit"] ) && check_admin_referer( plugin_basename(__FILE__), 'pdtr_nonce_name' ) ) {
			$pdtr_options["pdtr_send_mail_after_update"]	=	isset( $_REQUEST["pdtr_send_mail_after_update"] ) ? 1 : 0;
			$pdtr_options["pdtr_send_mail_get_update"]		=	isset( $_REQUEST["pdtr_send_mail_get_update"] ) ? 1 : 0;
			$pdtr_options["pdtr_mode"]						=	$_REQUEST["pdtr_mode"];
			if ( isset( $_REQUEST["pdtr_time"] ) ) {
				if ( preg_match( "/^[0-9]{1,5}+$/", $_REQUEST['pdtr_time'] ) && "0" != $_REQUEST["pdtr_time"] )
					$pdtr_options["pdtr_time"] = $_REQUEST["pdtr_time"];
				else
					$options_error = __( "Please enter a time for search and/or update. A number of hours should be integer and it should not contain more than 5 digits. Settings are not saved", 'updater' );
			}
			/* If user enter receiver's email check if it correct. Save email if it pass the test */
			if ( isset( $_REQUEST["pdtr_to_email"] ) ) {
				if ( is_email( trim( $_REQUEST["pdtr_to_email"] ) ) )
					$pdtr_options["pdtr_to_email"] = trim( $_REQUEST["pdtr_to_email"] );
				else
					$options_error = __( "Please enter a valid recipient email. Settings are not saved", 'updater' );
			}
			$pdtr_options["pdtr_from_name"] = stripslashes( esc_html( $_REQUEST["pdtr_from_name"] ) );
			if ( '' == $pdtr_options['pdtr_from_name'] )
				$pdtr_options['pdtr_from_name'] = $pdtr_option_defaults['pdtr_from_name'];
			/*If user enter sender's email check if it correct. Save email if it pass the test */
			if ( isset( $_REQUEST["pdtr_from_email"] ) ) {
				if ( is_email( trim( $_REQUEST["pdtr_from_email"] ) ) )
					$pdtr_options["pdtr_from_email"] = trim( $_REQUEST["pdtr_from_email"] );
				else
					$options_error = __( "Please enter a valid sender email. Settings are not saved", 'updater' );
			}

			/* Update options in the database */
			update_option( 'pdtr_options', $pdtr_options );
			
			if ( "" == $options_error )
				$message = __( "All settings are saved", 'updater' );

		    /* Add or delete hook of auto/handle mode */
		    if ( wp_next_scheduled( 'pdtr_auto_hook' ) )
					wp_clear_scheduled_hook( 'pdtr_auto_hook' );
			if ( '0' != $pdtr_options["pdtr_mode"] || '0' != $pdtr_options["pdtr_send_mail_get_update"] ) {
				$time = ( '' != $pdtr_options['pdtr_time'] ) ? time()+$pdtr_options['pdtr_time']*60*60 : time()+12*60*60;
				wp_schedule_event( $time, 'schedules_hours', 'pdtr_auto_hook' );
			}
		} /* Display form on the setting page */ ?> 
		<div class="wrap">
			<div class="icon32 icon32-bws" id="icon-options-general"></div>
			<h2><?php _e( 'Updater | Settings', 'updater' ); ?></h2>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab<?php if ( isset( $_GET['page'] ) && 'updater' == $_GET['page'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=updater"><?php _e( 'Tools', 'updater' ); ?></a>
				<a class="nav-tab<?php if ( isset( $_GET['page'] ) && 'updater-options' == $_GET['page'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=updater-options"><?php _e( 'Settings', 'updater' ); ?></a>
				<a class="bws_plugin_menu_pro_version nav-tab" href="http://bestwebsoft.com/products/updater/" target="_blank" title="<?php _e( 'This setting is available in Pro version', 'updater' ); ?>"><?php _e( 'User guide', 'updater' ); ?></a>
				<a class="nav-tab" href="http://bestwebsoft.com/products/updater/faq/" target="_blank"><?php _e( 'FAQ', 'updater' ); ?></a>
				<a class="nav-tab bws_go_pro_tab<?php if ( isset( $_GET['page'] ) && 'updater-go-pro' == $_GET['page'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=updater-go-pro"><?php _e( 'Go PRO', 'updater' ); ?></a>
			</h2>
			<div class="error"><p><strong><?php _e( 'We strongly recommend that you backup your website and the WordPress database before updating! We are not responsible for the site work after updates', 'updater' ); ?></strong></p></div>
			<div class="updated fade" <?php if ( ! ( isset( $_REQUEST["pdtr_form_submit"] ) || isset( $_REQUEST["pdtr_form_check_mail"] ) ) || "" != $options_error || "" == $message ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
			<div class="error" <?php if ( "" == $options_error ) echo "style=\"display:none\""; ?>><p><strong><?php echo $options_error; ?></strong></p></div>
			<div id="pdtr_settings_notice" class="updated fade" style="display:none"><p><strong><?php _e( "Notice:", 'updater' ); ?></strong> <?php _e( "The plugin's settings have been changed. In order to save them please don't forget to click the 'Save Changes' button.", 'updater' ); ?></p></div>
			<form id="pdtr_settings_form" method="post" action="admin.php?page=updater-options">
			  	<table class="pdtr_settings form-table">
					<tbody>
						<tr valign="top">
							<th scope="row">
								<?php _e( 'Select the plugin mode', 'updater' ); ?>
							</th>
							<td colspan="2">
								<label><input type="radio" name="pdtr_mode" value="0" <?php if ( 0 == $pdtr_options["pdtr_mode"] ) echo "checked=\"checked\""; ?> /> <?php _e( 'Manual mode', 'updater' ); ?></label><br />
								<label><input type="radio" name="pdtr_mode" value="1" <?php if ( 1 == $pdtr_options["pdtr_mode"] ) echo "checked=\"checked\""; ?> /> <?php _e( 'Auto mode', 'updater' ); ?></label>
							</td>
						</tr>
						<tr valign="top">
							<th><?php _e( 'Notify when new versions of plugins or WordPress are available', 'updater' ); ?></th>
							<td colspan="2">
								<input type="checkbox" name="pdtr_send_mail_get_update" value="1" <?php if ( 1 == $pdtr_options["pdtr_send_mail_get_update"] ) echo "checked=\"checked\""; ?> />
							</td>
						</tr>
						<tr valign="top">
							<th><?php _e( 'Send email after updating the plugins or WordPress', 'updater' ); ?></th>
							<td colspan="2">
								<input type="checkbox" name="pdtr_send_mail_after_update" value="1" <?php if ( '1' == $pdtr_options["pdtr_send_mail_after_update"] ) echo "checked=\"checked\""; ?> />
							</td>
						</tr>
						<tr valign="top">
							<th><?php _e( 'How often should the plugin search for or/and update plugins and WordPress?', 'updater' ); ?></th>
							<td colspan="2">
								<input type="number" name="pdtr_time" value="<?php echo $pdtr_options["pdtr_time"]; ?>" min="1" /> <?php _e( 'hours', 'updater' ); ?>
								<br />
								<span class="pdtr_span">(<?php _e( 'It should be integer and it should not contain more than 5 digits.', 'updater' ); ?>)</span>
							</td>
						</tr>
						<tr valign="top">
							<th><?php _e( 'Recipient email address (To:)', 'updater' ); ?></th>
							<td colspan="2">
								<input type="email" name="pdtr_to_email" value="<?php echo $pdtr_options["pdtr_to_email"]; ?>" />
							</td>
						</tr>
						<tr valign="top">
							<th><?php _e( "'FROM' field", 'updater' ); ?></th>
							<td style="width: 200px; vertical-align: top;">
								<div><?php _e( "Name", 'updater' ); ?></div>
								<div><input type="text" name="pdtr_from_name" value="<?php echo $pdtr_options["pdtr_from_name"]; ?>" /></div>
							</td>
							<td>
								<div><?php _e( "Email", 'updater' ); ?></div>
								<div>
									<input type="email" name="pdtr_from_email" value="<?php echo $pdtr_options["pdtr_from_email"]; ?>" />
								</div>
								<span class="pdtr_span">(<?php _e( 'If this option is changed, email messages may be moved to the spam folder or email delivery failures may occur.', 'updater' ); ?>)</span>
							</td>
						</tr>
					</tbody>
				</table>				
				<p class="submit" id="submit">
					<input type="hidden" name="pdtr_form_submit" value="submit" />
					<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'updater' ); ?>" />
					<?php wp_nonce_field( plugin_basename( __FILE__ ), 'pdtr_nonce_name' ); ?>
				</p>				
			</form>
			<h4><?php _e( "Send a test email message", 'updater' ); ?></h4>
			<form method="post" action="admin.php?page=updater-options">
				<input type="hidden" name="pdtr_form_check_mail" value="submit" />
				<p><?php _e( "Here You can make sure that your settings are correct and the email can be delivered.", 'updater' ); ?></p>
				<input type="submit" class="button" value="<?php _e( 'Check email sending', 'updater' ); ?>" />
				<?php wp_nonce_field( plugin_basename( __FILE__ ), 'pdtr_nonce_check_mail' ); ?>
			</form>
			<div class="bws_pro_version_bloc">
				<div class="bws_pro_version_table_bloc">	
					<div class="bws_table_bg"></div>											
					<table class="form-table bws_pro_version">
						<tr valign="top">
							<th><?php _e( 'Disable auto WP core update', 'updater' ); ?></th>
							<td>
								<input type="checkbox" disabled name="pdtrpr_disable_auto_core_update" value="1" />
							</td>
						</tr>
						<tr>
							<th><?php _e( 'Make backup before updating', 'updater' ); ?></th>
							<td>
								<input type="checkbox" disabled value="1" />
								<input type="button" disabled class="button" value="<?php _e( 'Test making the backup', 'updater' ); ?>" style="margin-left: 115px;"/>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'Backup all folders', 'updater' ); ?></th>
							<td><input disabled type="checkbox" value="1" /></td>
						</tr>
						<tr>
							<th><?php _e( 'Backup all tables in database', 'updater' ); ?></th>
							<td><input disabled type="checkbox" value="1" /></td>
						</tr>
						<tr>
							<th><?php _e( 'Delete test backup after testing', 'updater' ); ?></th>
							<td><input disabled type="checkbox" value="1" /></td>
						</tr>
						<tr>
							<th scope="row" colspan="2">
								* <?php _e( 'If you upgrade to Pro version all your settings will be saved.', 'updater' ); ?>
							</th>
						</tr>					
					</table>	
				</div>
				<div class="bws_pro_version_tooltip">
					<div class="bws_info">
						<?php _e( 'Unlock premium options by upgrading to a PRO version.', 'updater' ); ?> 
						<a href="http://bestwebsoft.com/products/updater/?k=347ed3784e3d2aeb466e546bfec268c0&pn=84&v=<?php echo $pdtr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Updater Pro"><?php _e( 'Learn More', 'updater' ); ?></a>				
					</div>
					<a class="bws_button" href="http://bestwebsoft.com/products/updater/buy/?k=347ed3784e3d2aeb466e546bfec268c0&pn=84&v=<?php echo $pdtr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Updater Pro">
						<?php _e( 'Go', 'updater' ); ?> <strong>PRO</strong>
					</a>
					<div class="clear"></div>
				</div>
			</div>
			<div class="bws-plugin-reviews">
				<div class="bws-plugin-reviews-rate">
					<?php _e( 'If you enjoy our plugin, please give it 5 stars on WordPress', 'updater' ); ?>: 
					<a href="http://wordpress.org/support/view/plugin-reviews/updater/" target="_blank" title="Updater reviews"><?php _e( 'Rate the plugin', 'updater' ); ?></a>
				</div>
				<div class="bws-plugin-reviews-support">
					<?php _e( 'If there is something wrong about it, please contact us', 'updater' ); ?>: 
					<a href="http://support.bestwebsoft.com">http://support.bestwebsoft.com</a>
				</div>
			</div>
		</div>
	<?php }
}
/* End function pdtr_settings_page */

/* Function for processing the site */
if ( ! function_exists ( 'pdtr_processing_site' ) ) {
	function pdtr_processing_site() {
		global $wp_version;
		$pdtr_core_plugin_list = array();
		/* Include file for get plugins */
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		/* Add the list of installed plugins */
		$wp_list_table 						=	apply_filters( 'all_plugins', get_plugins() );
		$pdtr_core_plugin_list["plg_list"]	=	$wp_list_table;

		/* Add the list of plugins, that need to be update */
		$update_plugins	=	get_site_transient( 'update_plugins' );
		$plugins		=	array();
		if ( ! empty( $update_plugins->response ) ) {
			foreach ( $update_plugins->response as $file => $value ) {
				$value = get_object_vars( $value );
				$plugins[ $file ] = $value;
			}
			$pdtr_core_plugin_list["plg_need_update"] = $plugins;
		}
		/* Add current core version and the latest version of core */
		$core = get_site_transient( 'update_core' );
		if ( ! empty( $core->updates ) )
			$pdtr_core_plugin_list["core"] = array( "current" => $wp_version, "new" => $core->updates[0]->current );
		return $pdtr_core_plugin_list;
	}
}
/* End function pdtr_processing_site */

/* Function for display updater page in the Tools admin area */
if ( ! function_exists ( 'pdtr_own_page' ) ) {
	function pdtr_own_page() {
		global $pdtr_options, $wp_version, $pdtr_plugin_info, $pdtr_core_plugin_list;
		$core = false;

		if ( 0 < get_option( 'gmt_offset' ) )
			$gmt = 'UTC+' . get_option( 'gmt_offset' );
		elseif ( 0 == get_option( 'gmt_offset' ) )
			$gmt = 'UTC';
		else
			$gmt = 'UTC' . get_option( 'gmt_offset' );

		/* Get information about WP core and installed plugins from the website */
		$pdtr_core_plugin_list = pdtr_processing_site();
		/* Update plugins and WP if they checked and show the results */
		if ( ( isset( $_REQUEST["checked_core"] ) || isset( $_REQUEST["checked_plugin"] ) ) && check_admin_referer( plugin_basename(__FILE__), 'pdtr_nonce_name' ) ) { ?>
			<div class="wrap"><div class="icon32 icon32-bws" id="icon-options-general"></div>
			<?php echo '<h2>' . __( 'Updater', 'updater' ) . '</h2>';
			if ( isset( $_REQUEST["checked_core"] ) )
				$core = pdtr_update_core();  /* Update the WP core */
			if ( isset( $_REQUEST["checked_plugin"] ) ) {
				$plugins = (array) $_REQUEST["checked_plugin"];
				pdtr_update_plugin( $plugins );	/* Update plugins */
			} else {
				$plugins = "";
			} ?>
			<p><a target="_parent" title="<?php _e( 'Go back to the Updater page', 'updater' ); ?>" href="admin.php?page=updater"><?php _e( 'Return to the Updater page', 'updater' ); ?></a></p>
			<?php /* Send mail if it's need */
			if ( 1 == $pdtr_options["pdtr_send_mail_after_update"] ) {
				$result_mail = pdtr_notification_after_update( $plugins, $core );

				$email = ( "" != $pdtr_options["pdtr_to_email"] ) ? $pdtr_options["pdtr_to_email"] : get_option( 'admin_email' );

				if ( true != $result_mail )
					echo '<p>' . __( "Sorry, your message could not be delivered to", 'updater' ) . ' ' . $email . '</p>';
				else
					echo '<p>' . __( "The email message with the update results is sent to", 'updater' ) . ' ' . $email . '</p>';
			}
			if ( '3.2' <= $wp_version )
				include( ABSPATH . 'wp-admin/admin-footer.php' );
			echo '</div>';
			exit;
		} ?>
		<div class="wrap">
			<div class="icon32 icon32-bws" id="icon-options-general"></div>
			<h2><?php _e( 'Updater | Tools', 'updater' ); ?></h2>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab<?php if ( isset( $_GET['page'] ) && 'updater' == $_GET['page'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=updater"><?php _e( 'Tools', 'updater' ); ?></a>
				<a class="nav-tab<?php if ( isset( $_GET['page'] ) && 'updater-options' == $_GET['page'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=updater-options"><?php _e( 'Settings', 'updater' ); ?></a>
				<a class="bws_plugin_menu_pro_version nav-tab" href="http://bestwebsoft.com/products/updater/" target="_blank" title="<?php _e( 'This setting is available in Pro version', 'updater' ); ?>"><?php _e( 'User guide', 'updater' ); ?></a>
				<a class="nav-tab" href="http://bestwebsoft.com/products/updater/faq/" target="_blank"><?php _e( 'FAQ', 'updater' ); ?></a>
				<a class="nav-tab bws_go_pro_tab<?php if ( isset( $_GET['page'] ) && 'updater-go-pro' == $_GET['page'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=updater-go-pro"><?php _e( 'Go PRO', 'updater' ); ?></a>
			</h2>
			<div class="error"><p><strong><?php _e( 'We strongly recommend that you backup your website and the WordPress database before updating! We are not responsible for the site work after updates', 'updater' ); ?></strong></p></div>
			<div class="bws_pro_version_bloc">
				<div class="bws_table_bg"></div>											
				<div class="bws_pro_version">
					<p>
						<img class="pdtr_img" src="<?php echo plugins_url( 'images/unlock.png' , __FILE__ );?>" alt=""/> - <?php _e( "the element will be updated", 'updater' ); ?><br/>
						<img class="pdtr_img" src="<?php echo plugins_url( 'images/lock.png' , __FILE__ );?>" alt=""/> - <?php _e( "the element will not be updated", 'updater' ); ?><br/>
					</p>
					<p>
						<input disabled type="submit" class="button" value="<?php _e( "Update information", 'updater' ); ?>" /> <?php _e( 'Latest update was', 'updater' ) . ' ' . current_time('mysql') . ' ' . $gmt; ?>
					</p>
					<p>
						<input disabled type="checkbox" value="1" /> 
						<?php _e( 'Updater Pro will display, check and update all plugins (Not just the active ones)', 'updater' ); ?>
					</p>
					<p>* <?php _e( 'If you upgrade to Pro version all your settings will be saved.', 'updater' ); ?></p>
				</div>
				<div class="bws_pro_version_tooltip">
					<div class="bws_info">
						<?php _e( 'Unlock premium options by upgrading to a PRO version.', 'updater' ); ?> 
						<a href="http://bestwebsoft.com/products/updater/?k=347ed3784e3d2aeb466e546bfec268c0&pn=84&v=<?php echo $pdtr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Updater Pro"><?php _e( 'Learn More', 'updater' ); ?></a>				
					</div>
					<a class="bws_button" href="http://bestwebsoft.com/products/updater/buy/?k=347ed3784e3d2aeb466e546bfec268c0&pn=84&v=<?php echo $pdtr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Updater Pro">
						<?php _e( 'Go', 'updater' ); ?> <strong>PRO</strong>
					</a>	
					<div class="clear"></div>					
				</div>
			</div>
			<div class="clear"></div>
			<form method="post" action="admin.php?page=updater" enctype="multipart/form-data">
				<table class="wp-list-table widefat pdtr" cellspacing="0">
					<thead>
						<tr>
							<th class="plugin-title check-column"><?php _e( 'WP Core / Plugins', 'updater' ); ?></th>
							<th id="cb" class="manage-column check-column" scope="col">
								<input type="checkbox">
								<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>
							</th>
						</tr>
					</thead>
					<tbody id="the-list">
						<tr>
							<td class="plugin-title"><strong><?php _e( 'WordPress Version', 'updater' ); ?></strong></td>
							<?php $message_update	=	"";
							$version		=	$pdtr_core_plugin_list["core"]["current"];
							if ( isset( $pdtr_core_plugin_list["core"]["new"] ) ) {
								if ( $version != $pdtr_core_plugin_list["core"]["new"] ) {
									$message_update = __( 'Update to', 'updater' ) . ' ' . $pdtr_core_plugin_list["core"]["new"];
								}
							} ?>
							<td class="manage-column check-column" <?php if ( "" != $message_update ) echo "style=\"background:#e89b92\""; ?> >
								<div <?php if ( "" != $message_update ) echo "class=\"update-message\""; ?>>
									<div class="pdtr_left">
										<img class="pdtr_img" src="<?php echo plugins_url( 'images/unlock.png' , __FILE__ );?>" alt="" />
										<?php echo __( 'Version', 'updater' ) . ' ' . $version; ?>
									</div>
									<?php if ( "" != $message_update ) { ?>
										<div class="pdtr_right">
											<input type='checkbox' value='1' name='checked_core' />
											<strong><?php echo $message_update; ?></strong>
										</div>
									<?php } ?>
								</div>
							</td>
						</tr>
						<?php if ( empty( $pdtr_core_plugin_list["plg_list"] ) ) { ?>
							<tr>
								<th><?php _e( 'No plugins found', 'updater' ); ?></th>
							</tr>
						<?php } else {
							foreach ( $pdtr_core_plugin_list["plg_list"] as $plg_key => $value ) { ?>
								<tr>
									<td class="plugin-title"><strong><?php echo $pdtr_core_plugin_list["plg_list"][ $plg_key ]["Name"]; ?></strong></td>
									<?php $message_update	=	"";
									$version		=	$pdtr_core_plugin_list["plg_list"][ $plg_key ]["Version"];
									if ( isset( $pdtr_core_plugin_list["plg_need_update"] ) ) {
										foreach ( $pdtr_core_plugin_list["plg_need_update"] as $file => $plugin_up ) {
											if ( $plg_key == $file ) {
												if ( $version != $plugin_up["new_version"] ) {
													$message_update = __( 'Update to', 'updater' ) . ' ' . $plugin_up["new_version"];
												}
											}
										}
									} ?>
									<td class="manage-column check-column" <?php if ( "" != $message_update ) echo "style=\"background:#e89b92\""; ?>>
										<div <?php if ( "" != $message_update ) echo "class=\"update-message\""; ?>>
											<div class="pdtr_left">
												<img class="pdtr_img" src="<?php echo plugins_url( 'images/unlock.png' , __FILE__ );?>" alt="" />
												<?php echo __( 'Version', 'updater' ) . " " . $version; ?>
											</div>
											<?php if ( "" != $message_update ) { ?>
												<div class="pdtr_right">
													<input type='checkbox' name='checked_plugin[]' value='<?php echo $plg_key; ?>' />
													<strong><?php echo $message_update; ?></strong>
												</div>
											<?php } ?>
										</div>
									</td>
								</tr>
							<?php }
						} ?>
					</tbody>
				</table>
				<input type="hidden" name="pdtr_form_submit" value="submit" />
				<p class="submit" id="submit">
					<input type="submit" class="button-primary" name="pdtr_submit" value="<?php _e( 'Update', 'updater' ); ?>" />
				</p>
				<?php wp_nonce_field( plugin_basename( __FILE__ ), 'pdtr_nonce_name' ); ?>
			</form>
			<div class="bws-plugin-reviews">
				<div class="bws-plugin-reviews-rate">
					<?php _e( 'If you enjoy our plugin, please give it 5 stars on WordPress', 'updater' ); ?>: 
					<a href="http://wordpress.org/support/view/plugin-reviews/updater/" target="_blank" title="Updater reviews"><?php _e( 'Rate the plugin', 'updater' ); ?></a><br/>
				</div>
				<div class="bws-plugin-reviews-support">
					<?php _e( 'If there is something wrong about it, please contact us', 'updater' ); ?>: 
					<a href="http://support.bestwebsoft.com">http://support.bestwebsoft.com</a>
				</div>
			</div>
		</div>
	<?php }
}
/* End function pdtr_own_page */

/* Function for display updater settings page in the BWS admin area */
if ( ! function_exists ( 'pdtr_go_pro_page' ) ) {
	function pdtr_go_pro_page() {
		global $wp_version, $pdtr_plugin_info, $bstwbsftwppdtplgns_options;
		$error = $message = "";

		/* GO PRO */
		$bws_license_key = ( isset( $_POST['bws_license_key'] ) ) ? stripslashes( esc_html( trim( $_POST['bws_license_key'] ) ) ) : "";

		if ( isset( $_POST['bws_license_submit'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'bws_license_nonce_name' ) ) {
			if ( '' != $bws_license_key ) { 
				if ( strlen( $bws_license_key ) != 18 ) {
					$error = __( "Wrong license key", 'updater' );
				} else {
					$bws_license_plugin = stripslashes( esc_html( $_POST['bws_license_plugin'] ) );	
					if ( isset( $bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['count'] ) && $bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['time'] < ( time() + (24 * 60 * 60) ) ) {
						$bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['count'] = $bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['count'] + 1;
					} else {
						$bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['count'] = 1;
						$bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['time'] = time();
					}	

					/* download Pro */
					if ( ! function_exists( 'get_plugins' ) )
						require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

					$all_plugins = get_plugins();
					
					if ( ! array_key_exists( $bws_license_plugin, $all_plugins ) ) {
						$current = get_site_transient( 'update_plugins' );
						if ( is_array( $all_plugins ) && !empty( $all_plugins ) && isset( $current ) && is_array( $current->response ) ) {
							$to_send = array();
							$to_send["plugins"][ $bws_license_plugin ] = array();
							$to_send["plugins"][ $bws_license_plugin ]["bws_license_key"] = $bws_license_key;
							$to_send["plugins"][ $bws_license_plugin ]["bws_illegal_client"] = true;
							$options = array(
								'timeout' => ( ( defined('DOING_CRON') && DOING_CRON ) ? 30 : 3 ),
								'body' => array( 'plugins' => serialize( $to_send ) ),
								'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ) );
							$raw_response = wp_remote_post( 'http://bestwebsoft.com/wp-content/plugins/paid-products/plugins/update-check/1.0/', $options );

							if ( is_wp_error( $raw_response ) || 200 != wp_remote_retrieve_response_code( $raw_response ) ) {
								$error = __( "Something went wrong. Try again later. If the error will appear again, please, contact us <a href=http://support.bestwebsoft.com>BestWebSoft</a>. We are sorry for inconvenience.", 'updater' );
							} else {
								$response = maybe_unserialize( wp_remote_retrieve_body( $raw_response ) );
								if ( is_array( $response ) && !empty( $response ) ) {
									foreach ( $response as $key => $value ) {
										if ( "wrong_license_key" == $value->package ) {
											$error = __( "Wrong license key", 'updater' ); 
										} elseif ( "wrong_domain" == $value->package ) {
											$error = __( "This license key is bind to another site", 'updater' );
										} elseif ( "you_are_banned" == $value->package ) {
											$error = __( "Unfortunately, you have exceeded the number of available tries per day. Please, upload the plugin manually.", 'updater' );
										} elseif ( "time_out" == $value->package ) {
											$error = __( "Unfortunately, Your license has expired. To continue getting top-priority support and plugin updates you should extend it in your", 'updater_pro' ) . ' <a href="http://bestwebsoft.com/wp-admin/admin.php?page=bws_plugins_client_area">Client area</a>';
										}
									}
									if ( '' == $error ) {									
										$bstwbsftwppdtplgns_options[ $bws_license_plugin ] = $bws_license_key;

										$url = 'http://bestwebsoft.com/wp-content/plugins/paid-products/plugins/downloads/?bws_first_download=' . $bws_license_plugin . '&bws_license_key=' . $bws_license_key . '&download_from=5';
										$uploadDir = wp_upload_dir();
											$zip_name = explode( '/', $bws_license_plugin );
											$received_content = file_get_contents( $url );
											if ( ! $received_content ) {
												$error = __( "Failed to download the zip archive. Please, upload the plugin manually", 'updater' );
											} else {
												if ( is_writable( $uploadDir["path"] ) ) {
													$file_put_contents = $uploadDir["path"] . "/" . $zip_name[0] . ".zip";
												    if ( file_put_contents( $file_put_contents, $received_content ) ) {
												    	@chmod( $file_put_contents, octdec( 755 ) );
												    	if ( class_exists( 'ZipArchive' ) ) {
															$zip = new ZipArchive();
															if ( $zip->open( $file_put_contents ) === TRUE ) {
																$zip->extractTo( WP_PLUGIN_DIR );
																$zip->close();
															} else {
																$error = __( "Failed to open the zip archive. Please, upload the plugin manually", 'updater' );
															}
														} elseif ( class_exists( 'Phar' ) ) {
															$phar = new PharData( $file_put_contents );
															$phar->extractTo( WP_PLUGIN_DIR );
														} else {
															$error = __( "Your server does not support either ZipArchive or Phar. Please, upload the plugin manually", 'updater' );
														}
														@unlink( $file_put_contents );
													} else {
														$error = __( "Failed to download the zip archive. Please, upload the plugin manually", 'updater' );
													}
												} else {
													$error = __( "UploadDir is not writable. Please, upload the plugin manually", 'updater' );
												}
											}

										/* activate Pro */
										if ( file_exists( WP_PLUGIN_DIR . '/' . $zip_name[0] ) ) {			
											if ( is_multisite() && is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
												/* if multisite and free plugin is network activated */
												$active_plugins = get_site_option( 'active_sitewide_plugins' );
												$active_plugins[ $bws_license_plugin ] = time();
												update_site_option( 'active_sitewide_plugins', $active_plugins );
											} else {
												/* activate on a single blog */
												$active_plugins = get_option( 'active_plugins' );
												array_push( $active_plugins, $bws_license_plugin );
												update_option( 'active_plugins', $active_plugins );
											}
											$pro_plugin_is_activated = true;
										} elseif ( '' == $error ) {
											$error = __( "Failed to download the zip archive. Please, upload the plugin manually", 'updater' );
										}																				
									}
								} else {
									$error = __( "Something went wrong. Try again later or upload the plugin manually. We are sorry for inconvenience.", 'updater' ); 
				 				}
				 			}
			 			}
					} else {
						/* activate Pro */
						if ( ! is_plugin_active( $bws_license_plugin ) ) {
							if ( is_multisite() && is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
								/* if multisite and free plugin is network activated */
								$network_wide = true;
							} else {
								/* activate on a single blog */
								$network_wide = false;
							}
							activate_plugin( $bws_license_plugin, NULL, $network_wide );
							$pro_plugin_is_activated = true;
						}						
					}
					if ( is_multisite() )
						update_site_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
					else
						update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
		 		}
		 	} else {
	 			$error = __( "Please, enter Your license key", 'updater' );
	 		}
	 	}
		/* Display form on the setting page */ ?> 
		<div class="wrap">
			<div class="icon32 icon32-bws" id="icon-options-general"></div>
			<h2><?php _e( 'Updater | Go PRO', 'updater' ); ?></h2>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab<?php if ( isset( $_GET['page'] ) && 'updater' == $_GET['page'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=updater"><?php _e( 'Tools', 'updater' ); ?></a>
				<a class="nav-tab<?php if ( isset( $_GET['page'] ) && 'updater-options' == $_GET['page'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=updater-options"><?php _e( 'Settings', 'updater' ); ?></a>
				<a class="bws_plugin_menu_pro_version nav-tab" href="http://bestwebsoft.com/products/updater/" target="_blank" title="<?php _e( 'This setting is available in Pro version', 'updater' ); ?>"><?php _e( 'User guide', 'updater' ); ?></a>
				<a class="nav-tab" href="http://bestwebsoft.com/products/updater/faq/" target="_blank"><?php _e( 'FAQ', 'updater' ); ?></a>
				<a class="nav-tab bws_go_pro_tab<?php if ( isset( $_GET['page'] ) && 'updater-go-pro' == $_GET['page'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=updater-go-pro"><?php _e( 'Go PRO', 'updater' ); ?></a>
			</h2>
			<div class="updated fade" <?php if ( ! isset( $_REQUEST["bws_license_submit"] ) || "" != $error || "" == $message ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
			<div class="error" <?php if ( "" == $error ) echo "style=\"display:none\""; ?>><p><strong><?php echo $error; ?></strong></p></div>
			<?php if ( isset( $pro_plugin_is_activated ) && true === $pro_plugin_is_activated ) { ?>
				<script type="text/javascript">
					window.setTimeout( function() {
					    window.location.href = 'admin.php?page=updater-pro';
					}, 5000 );
				</script>				
				<p><?php _e( "Congratulations! The PRO version of the plugin is successfully download and activated.", 'updater' ); ?></p>
				<p>
					<?php _e( "Please, go to", 'updater' ); ?> <a href="admin.php?page=updater-pro"><?php _e( 'the setting page', 'updater' ); ?></a> 
					(<?php _e( "You will be redirected automatically in 5 seconds.", 'updater' ); ?>)
				</p>
			<?php } else { ?>
				<form method="post" action="admin.php?page=updater-go-pro">
					<p>
						<?php _e( 'You can download and activate', 'updater' ); ?> 
						<a href="http://bestwebsoft.com/products/updater/?k=347ed3784e3d2aeb466e546bfec268c0&pn=84&v=<?php echo $pdtr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Updater Pro">PRO</a> 
						<?php _e( 'version of this plugin by entering Your license key.', 'updater' ); ?><br />
						<span class="pdtr_span">
							<?php _e( 'You can find your license key on your personal page Client area, by clicking on the link', 'updater' ); ?> 
							<a href="http://bestwebsoft.com/wp-login.php">http://bestwebsoft.com/wp-login.php</a> 
							<?php _e( '(your username is the email you specify when purchasing the product).', 'updater' ); ?>
						</span>
					</p>
					<?php if ( isset( $bstwbsftwppdtplgns_options['go_pro']['updater-pro/updater_pro.php']['count'] ) &&
						'5' < $bstwbsftwppdtplgns_options['go_pro']['updater-pro/updater_pro.php']['count'] &&
						$bstwbsftwppdtplgns_options['go_pro']['updater-pro/updater_pro.php']['time'] < ( time() + ( 24 * 60 * 60 ) ) ) { ?>
						<p>
							<input disabled="disabled" type="text" name="bws_license_key" value="<?php echo $bws_license_key; ?>" />
							<input disabled="disabled" type="submit" class="button-primary" value="<?php _e( 'Activate', 'updater' ); ?>" />
						</p>
						<p><?php _e( "Unfortunately, you have exceeded the number of available tries per day. Please, upload the plugin manually.", 'updater' ); ?></p>
					<?php } else { ?>
						<p>
							<input type="text" name="bws_license_key" value="<?php echo $bws_license_key; ?>" />
							<input type="hidden" name="bws_license_plugin" value="updater-pro/updater_pro.php" />
							<input type="hidden" name="bws_license_submit" value="submit" />
							<input type="submit" class="button-primary" value="<?php _e( 'Activate', 'updater' ); ?>" />
							<?php wp_nonce_field( plugin_basename(__FILE__), 'bws_license_nonce_name' ); ?>
						</p>
					<?php } ?>
				</form>
			<?php } ?>
		</div>
	<?php }
}
/* End function pdtr_go_pro_page */

/* Function for updating plugins */
if ( ! function_exists ( 'pdtr_update_plugin' ) ) {
	function pdtr_update_plugin( $plugins_list ) {
		/* Include files for using class Plugin_Upgrader */
		include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		include_once( ABSPATH . 'wp-admin/includes/file.php' );
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		include_once( ABSPATH . 'wp-admin/includes/update.php' );
		echo '<h3>' . __( 'Updating plugins...', 'updater' ) . '</h3>';
		/* Update plugins */
		if ( "" != $plugins_list ) {
			$plugins_list = array_map( 'urldecode', $plugins_list );
			$upgrader = new Plugin_Upgrader( new Bulk_Plugin_Upgrader_Skin() );
			$upgrader->bulk_upgrade( $plugins_list );
		}
	}
}
/* End function pdtr_update_plugin */

/* Function for updating WP core */
if ( ! function_exists ( 'pdtr_update_core' ) ) {
	function pdtr_update_core() {
		global $wp_filesystem, $wp_version;
		echo '<h3>' . __( 'Updating WordPress...', 'updater' ) . '</h3>';
		/* Include files for correct update */
		include_once( ABSPATH . 'wp-admin/includes/misc.php' );
		include_once( ABSPATH . 'wp-admin/includes/file.php' );

		$url	=	wp_nonce_url( 'update-core.php?action=do-core-upgrade', 'upgrade-core' );
		if ( false === ( $credentials = request_filesystem_credentials( $url, '', false, ABSPATH ) ) )
			return;

		$url	=	wp_nonce_url( 'admin.php?page=updater', 'upgrade-core' );
		if ( false === ( $credentials = request_filesystem_credentials( $url, '', false, ABSPATH ) ) )
			return;

		$from_api	=	get_site_transient( 'update_core' );
		$updates	=	$from_api->updates;
		/* get latest WP version */
		$update	=	$updates[0];

		if ( ! WP_Filesystem( $credentials, ABSPATH ) ) {
			request_filesystem_credentials( $url, '', true, ABSPATH ); /* Failed to connect, Error and request again */
			return false;
		}

		if ( $wp_filesystem->errors->get_error_code() ) {
			foreach ( $wp_filesystem->errors->get_error_messages() as $message )
				show_message( $message );
			return false;
		}
		if ( '3.7' > $wp_version )
			$result = @wp_update_core( $update, 'show_message' );
		else {
			add_filter( 'update_feedback', 'show_message' );
			include ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			$upgrader = new Core_Upgrader();
			$result = @$upgrader->upgrade( $update );
		}

		if ( is_wp_error( $result ) ) {
			show_message( $result );
			if ( 'up_to_date' != $result->get_error_code() )
				show_message( __( 'Update Failed', 'updater' ) );
			return false;
		}

		show_message( __( 'WordPress updated successfully!', 'updater' ) );
		/* Check version and set option 'update_core' */
		wp_version_check();

		return true;
	}
}
/* End function pdtr_update_core */

/* Function for sending email after update */
if ( ! function_exists ( 'pdtr_notification_after_update' ) ) {
	function pdtr_notification_after_update( $plugins_list, $core ) {
		global $pdtr_options;

		$pdtr_core_plugin_list = pdtr_processing_site();
		$subject	=	esc_html__( 'The Updater plugin made the updates at the site', 'updater' ) .  ' ' . esc_attr( get_bloginfo( 'name', 'display' ) );
		$message	=	'<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
						<body>
						<h3>' . __( 'Hello!', 'updater' ) . '</h3>' .
						esc_html__( 'The Updater plugin is being run on your website', 'updater' ) . ' <a href=' . home_url() . '>' . esc_attr( get_bloginfo( 'name', 'display' ) ) . '</a>.<br/><br/>' ;
		
		if ( "" != $plugins_list ) {
			$message .= '<strong> - ' . __( 'These plugins were updated:', 'updater' ) . '</strong><ul>';
			foreach ( $plugins_list as $key => $value ) {
				$name = explode( "/", $value );
				$message .= '<li>' . $name[0] . ' - ' . __( 'to the version', 'updater' ) . ' ' . $pdtr_core_plugin_list["plg_list"][ $value ]["Version"] . ';</li>';
			}
			$message .= '</ul><br/>';
		}

		if ( true === $core ) {
			$message .= '<strong> - ' . __( 'WordPress was updated to the version', 'updater' ) . ' ' . $pdtr_core_plugin_list["core"]["new"] . '.</strong><br/><br/>';
		}

		$message .= __( 'If you want to change the plugin mode or other settings you should go here:', 'updater' ) .
				' <a href=' . home_url() . '/wp-admin/admin.php?page=updater-options> ' . __( 'the Updater settings page on your website.', 'updater' ) . '</a>
				<br/><br/>----------------------------------------<br/><br/>' .
				esc_html__( 'Thanks for using the plugin', 'updater' ) . ' <a href="http://bestwebsoft.com/products/updater/">Updater</a>!</body></html>';
		
		$email = ( "" != $pdtr_options["pdtr_to_email"] ) ? $pdtr_options["pdtr_to_email"] : get_option( 'admin_email' );	
		$email = apply_filters( 'auto_updater_notification_email_address', $email );
		
		$from_email = ( "" != $pdtr_options["pdtr_from_email"] ) ? $pdtr_options["pdtr_from_email"] : $email;		
		$from_name = ( "" != $pdtr_options["pdtr_from_name"] ) ? htmlspecialchars_decode( $pdtr_options["pdtr_from_name"] ) : esc_attr( get_bloginfo( 'name', 'display' ) );
		
		$headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
		add_filter( 'wp_mail_content_type', create_function( '', 'return "text/html";' ) );
		
		if ( ! function_exists( 'is_plugin_active' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		if ( ( is_plugin_active( 'email-queue/email-queue.php' ) || is_plugin_active( 'email-queue-pro/email-queue.php-pro' ) ) && mlq_if_mail_plugin_is_in_queue( plugin_basename( __FILE__ ) ) ) {
			/* if email-queue plugin is active and this plugin's "in_queue" status is 'ON' */
			global $mlq_mail_result;
			do_action( 'pdtr_get_mail_data', plugin_basename( __FILE__ ), $email, $subject, $message, $headers );
			/* return $mail_result = true if email-queue has successfully inserted mail in its DB; in other case - return false */
			return $mail_result = $mlq_mail_result;
		} else {
			if ( '10' == has_filter( 'wp_mail_from_name', 'cntctfrm_email_name_filter' ) ) {
				remove_filter( 'wp_mail_from_name', 'cntctfrm_email_name_filter' );
				$mail_result = wp_mail( $email, $subject, $message, $headers );
				add_filter( 'wp_mail_from_name', 'cntctfrm_email_name_filter', 10, 1 );
			} else {
				$mail_result = wp_mail( $email, $subject, $message, $headers );
			}
		}

		return $mail_result;
	}
}
/* End function pdtr_notification_after_update */

/**
 * Function that is used by email-queue plugin to check for compatibility
 * @return void 
 */
if ( ! function_exists( 'pdtr_check_for_compatibility_with_mlq' ) ) {
	function pdtr_check_for_compatibility_with_mlq() {
		return false;
	}
}

/* Function for sending email if exist update */
if ( ! function_exists ( 'pdtr_notification_exist_update' ) ) {
	function pdtr_notification_exist_update( $plugins_list, $core, $test = false ) {
		global $pdtr_options, $pdtr_core_plugin_list;
		/* Get information about WP core and installed plugins from the website */
		$subject	=	esc_html__( 'Check for updates on', 'updater' ) . ' ' . esc_attr( get_bloginfo( 'name', 'display' ) );
		$message	=	'<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
					<body>
					<h3>' . __( 'Hello!', 'updater' ) . '</h3>' .
					esc_html__( 'The Updater plugin is being run on your website', 'updater' ) . ' <a href=' . home_url() . '>' . esc_attr( get_bloginfo( 'name', 'display' ) ) . '</a>.';
		
		if ( ( "" != $plugins_list ) || ( false != $core ) )
			$message .= ' ' . __( 'The files that need update are:', 'updater' ) . '<br/><br/>' ;
		else
			$message .= '.' . '<br/><br/>' ;

		if ( "" != $plugins_list ) {
			$message .= '<strong> - ' . __( 'These plugins can be updated:', 'updater' ) . '</strong><ul>';
			foreach ( $plugins_list as $key => $value ) {
				$name = explode( "/", $value );
				$message .= '<li>' . $name[0] . ' - ' . __( 'to the version', 'updater' ) . ' ' . $pdtr_core_plugin_list["plg_need_update"][ $value ]["new_version"] .
						 ' ('. __( 'the current version is', 'updater' ) . ' ' . $pdtr_core_plugin_list["plg_list"][ $value ]["Version"] . ');</li>';
			}
			$message .= '</ul>';
		}

		if ( true === $core ) {
			$message .= '<strong> - ' . __( 'WordPress can be updated to the version', 'updater' ) . ' ' . $pdtr_core_plugin_list["core"]["new"] .
					 ' ('. __( 'the current version is', 'updater' ) . ' ' . $pdtr_core_plugin_list["core"]["current"]. ').</strong><br/>';
		}

		if ( false === $test ) {
			if ( 0 == $pdtr_options["pdtr_mode"] ) {
				$message .= '<br/>' . __( 'Please use this link to update:', 'updater' ) . ' <a href=' . home_url() . '/wp-admin/admin.php?page=updater' . '> ' . __( 'the Updater page on your website.', 'updater' ) . '</a>';
			} else {
				$message .= '<br/>' . __( 'The Updater plugin starts updating these files.', 'updater' );
			}
		} elseif ( ( "" != $plugins_list ) || ( false != $core ) ) {
			$message .= '<br/>' . __( 'Please use this link to update:', 'updater' ) . ' <a href=' . home_url() . '/wp-admin/admin.php?page=updater' . '> ' . __( 'the Updater page on your website.', 'updater' ) . '</a>';
		}

		if ( ( "" == $plugins_list ) && ( false == $core ) ) {
			$message .= __( 'Congratulations! Your plugins and WordPress have the latest updates!', 'updater' );
		}

		$message .= '<br/><br/>' . __( 'If you want to change type of mode for the plugin or other settings you should go here:', 'updater' ) .
				' <a href=' . home_url() . '/wp-admin/admin.php?page=updater-options> ' . __( 'the Updater settings page on your website.', 'updater' ) . '</a>
				<br/><br/>----------------------------------------<br/><br/>' .
				esc_html__( 'Thanks for using the plugin', 'updater' ) . ' <a href="http://bestwebsoft.com/products/updater/">Updater</a>!</body></html>';
		
		$email = ( "" != $pdtr_options["pdtr_to_email"] ) ? $pdtr_options["pdtr_to_email"] : get_option( 'admin_email' );
		$email = apply_filters( 'auto_updater_notification_email_address', $email );
		
		$from_email = ( "" != $pdtr_options["pdtr_from_email"] ) ? $pdtr_options["pdtr_from_email"] : $email;		
		$from_name = ( "" != $pdtr_options["pdtr_from_name"] ) ? htmlspecialchars_decode( $pdtr_options["pdtr_from_name"] ) : esc_attr( get_bloginfo( 'name', 'display' ) );
		
		$headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
		add_filter( 'wp_mail_content_type', create_function( '', 'return "text/html";' ) );

		if ( ! function_exists( 'is_plugin_active' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		if ( ( is_plugin_active( 'email-queue/email-queue.php' ) || is_plugin_active( 'email-queue-pro/email-queue.php-pro' ) ) && mlq_if_mail_plugin_is_in_queue( plugin_basename( __FILE__ ) ) ) {
			/* if email-queue plugin is active and this plugin's "in_queue" status is 'ON' */
			global $mlq_mail_result;
			do_action( 'pdtr_get_mail_data', plugin_basename( __FILE__ ), $email, $subject, $message, $headers );
			/* return $mail_result = true if email-queue has successfully inserted mail in its DB; in other case - return false */
			$mail_result = $mlq_mail_result;
		} else {
			$mail_result = wp_mail( $email, $subject, $message, $headers );
		}
		return $mail_result;
	}
}
/* End function pdtr_notification_exist_update */

/* Add css-file to the plugin */
if ( ! function_exists ( 'pdtr_admin_head' ) ) {
	function pdtr_admin_head() {
		if ( isset( $_GET['page'] ) && ( "updater-options" == $_GET['page'] || "updater" == $_GET['page'] ) ) {
			wp_enqueue_style( 'pdtr_stylesheet', plugins_url( 'css/style.css', __FILE__ ) );
			wp_enqueue_script( 'pdtr_script', plugins_url( 'js/script.js' , __FILE__ ) );
		}
	}
}

/* Function that update all plugins and WP core. It will be executed every hour if enabled auto mode */
if ( ! function_exists ( 'pdtr_auto_function' ) ) {
	function pdtr_auto_function() {
		global $pdtr_options, $pdtr_core_plugin_list;
		$plugin_upd_list		=	"";
		$core					=	false;
		$pdtr_core_plugin_list	=	pdtr_processing_site();

		if ( empty( $pdtr_options ) )
			$pdtr_options =	get_option( 'pdtr_options' );
		
		if ( $pdtr_core_plugin_list["core"]["current"] != $pdtr_core_plugin_list["core"]["new"] )
			$core = true;

		if ( isset( $pdtr_core_plugin_list["plg_need_update"] ) ) {
			foreach ( $pdtr_core_plugin_list["plg_need_update"] as $key => $value) {
				$plugin_upd_list[] = $key;
			}
		}

		if ( 1 == $pdtr_options["pdtr_send_mail_get_update"] ) {
			if ( false != $test ) {
				pdtr_notification_exist_update( $plugin_upd_list, $core, true );
			} elseif ( ( "" != $plugin_upd_list ) || ( false != $core ) ) {
				pdtr_notification_exist_update( $plugin_upd_list, $core );
			}
		}

		if ( 1 == $pdtr_options["pdtr_mode"] ) {
			/* If WP core need to be update */
			if ( true === $core )
				$core = pdtr_update_core(); // update the WP core
			/* Update the list of plugins */
			if ( "" != $plugin_upd_list ) {
				pdtr_update_plugin( $plugin_upd_list );
			}
			/* Send mail */
			if ( 1 == $pdtr_options["pdtr_send_mail_after_update"] ) {
				if ( ( "" != $plugin_upd_list ) || ( false != $core ) ) {
					pdtr_notification_after_update( $plugin_upd_list, $core );
				}
			}
		}

		wp_clear_scheduled_hook( 'pdtr_auto_hook' );

		$time = ( '' != $pdtr_options['pdtr_time'] ) ? time()+$pdtr_options['pdtr_time']*60*60 : time()+12*60*60;
		wp_schedule_event( $time, 'schedules_hours', 'pdtr_auto_hook' );
	}
}
/* End function pdtr_auto_function */

/* Add link 'Settings' */
if ( ! function_exists( 'pdtr_plugin_action_links' ) ) {
	function pdtr_plugin_action_links( $links, $file ) {
		if ( ! is_network_admin() ) {
			/* Static so we don't call plugin_basename on every plugin row */
			static $this_plugin;
			if ( ! $this_plugin )
				$this_plugin = plugin_basename( __FILE__ );
			if ( $file == $this_plugin ) {
				$settings_link = '<a href="admin.php?page=updater-options">' . __( 'Settings', 'updater' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
}
/* End function pdtr_plugin_action_links */

/* Register plugin links */
if ( ! function_exists( 'pdtr_register_plugin_links' ) ) {
	function pdtr_register_plugin_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file == $base ) {
			if ( ! is_network_admin() )
				$links[]	=	'<a href="admin.php?page=updater-options">' . __( 'Settings', 'updater' ) . '</a>';
			$links[]	=	'<a href="http://wordpress.org/plugins/updater/faq/" target="_blank">' . __( 'FAQ', 'updater' ) . '</a>';
			$links[]	=	'<a href="http://support.bestwebsoft.com">' . __( 'Support', 'updater' ) . '</a>';
		}
		return $links;
	}
}
/* End function pdtr_register_plugin_links */

if ( ! function_exists ( 'pdtr_plugin_banner' ) ) {
	function pdtr_plugin_banner() {
		global $hook_suffix;
		if ( 'plugins.php' == $hook_suffix ) {
			global $pdtr_plugin_info;
			$banner_array = array(
				array( 'gglnltcs_hide_banner_on_plugin_page', 'bws-google-analytics/bws-google-analytics.php', '1.6.2' ),
				array( 'htccss_hide_banner_on_plugin_page', 'htaccess/htaccess.php', '1.6.3' ),
				array( 'sbscrbr_hide_banner_on_plugin_page', 'subscriber/subscriber.php', '1.1.8' ),
				array( 'lmtttmpts_hide_banner_on_plugin_page', 'limit-attempts/limit-attempts.php', '1.0.2' ),
				array( 'sndr_hide_banner_on_plugin_page', 'sender/sender.php', '0.5' ),
				array( 'srrl_hide_banner_on_plugin_page', 'user-role/user-role.php', '1.4' ),
				array( 'pdtr_hide_banner_on_plugin_page', 'updater/updater.php', '1.12' ),
				array( 'cntctfrmtdb_hide_banner_on_plugin_page', 'contact-form-to-db/contact_form_to_db.php', '1.2' ),
				array( 'cntctfrmmlt_hide_banner_on_plugin_page', 'contact-form-multi/contact-form-multi.php', '1.0.7' ),
				array( 'gglmps_hide_banner_on_plugin_page', 'bws-google-maps/bws-google-maps.php', '1.2' ),
				array( 'fcbkbttn_hide_banner_on_plugin_page', 'facebook-button-plugin/facebook-button-plugin.php', '2.29' ),
				array( 'twttr_hide_banner_on_plugin_page', 'twitter-plugin/twitter.php', '2.34' ),
				array( 'pdfprnt_hide_banner_on_plugin_page', 'pdf-print/pdf-print.php', '1.7.1' ),
				array( 'gglplsn_hide_banner_on_plugin_page', 'google-one/google-plus-one.php', '1.1.4' ),
				array( 'gglstmp_hide_banner_on_plugin_page', 'google-sitemap-plugin/google-sitemap-plugin.php', '2.8.4' ),
				array( 'cntctfrmpr_for_ctfrmtdb_hide_banner_on_plugin_page', 'contact-form-pro/contact_form_pro.php', '1.14' ),
				array( 'cntctfrm_for_ctfrmtdb_hide_banner_on_plugin_page', 'contact-form-plugin/contact_form.php', '3.62' ),
				array( 'cntctfrm_hide_banner_on_plugin_page', 'contact-form-plugin/contact_form.php', '3.47' ),
				array( 'cptch_hide_banner_on_plugin_page', 'captcha/captcha.php', '3.8.4' ),
				array( 'gllr_hide_banner_on_plugin_page', 'gallery-plugin/gallery-plugin.php', '3.9.1' )
			);

			if ( ! function_exists( 'is_plugin_active' ) )
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	
			$all_plugins = get_plugins();
			$this_banner = 'pdtr_hide_banner_on_plugin_page';
			foreach ( $banner_array as $key => $value ) {
				if ( $this_banner == $value[0] ) {
					global $wp_version, $bstwbsftwppdtplgns_cookie_add;
					if ( ! isset( $bstwbsftwppdtplgns_cookie_add ) ) {
						echo '<script type="text/javascript" src="' . plugins_url( 'js/c_o_o_k_i_e.js', __FILE__ ) . '"></script>';
						$bstwbsftwppdtplgns_cookie_add = true;
					} ?>
					<script type="text/javascript">
						(function($) {
							$(document).ready( function() {
								var hide_message = $.cookie( "pdtr_hide_banner_on_plugin_page" );
								if ( hide_message == "true" ) {
									$( ".pdtr_message" ).css( "display", "none" );
								} else {
									$( ".pdtr_message" ).css( "display", "block" );
								};
								$( ".pdtr_close_icon" ).click( function() {
									$( ".pdtr_message" ).css( "display", "none" );
									$.cookie( "pdtr_hide_banner_on_plugin_page", "true", { expires: 32 } );
								});
							});
						})(jQuery);
					</script>
					<div class="updated" style="padding: 0; margin: 0; border: none; background: none;">
						<div class="pdtr_message bws_banner_on_plugin_page" style="display: none;">
							<img class="pdtr_close_icon close_icon" title="" src="<?php echo plugins_url( 'images/close_banner.png', __FILE__ ); ?>" alt=""/>
							<div class="button_div">
								<a class="button" target="_blank" href="http://bestwebsoft.com/products/updater/?k=0b6882b0c99c2776d06c375dc22b5869&pn=84&v=<?php echo $pdtr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>"><?php _e( 'Learn More', 'updater' ); ?></a>
							</div>
							<div class="text"><?php
								_e( 'It’s time to upgrade your', 'updater' ); ?> <strong>Updater plugin</strong> <?php _e( 'to', 'updater' ); ?> <strong>PRO</strong> <?php _e( 'version!', 'updater' ); ?><br />
								<span><?php _e( 'Extend standard plugin functionality with new great options.', 'updater' ); ?></span>
							</div>
							<div class="icon">
								<img title="" src="<?php echo plugins_url( 'images/banner.png', __FILE__ ); ?>" alt="" />
							</div>
						</div>
					</div>
					<?php break;
				}
				if ( isset( $all_plugins[ $value[1] ] ) && $all_plugins[ $value[1] ]["Version"] >= $value[2] && is_plugin_active( $value[1] ) && ! isset( $_COOKIE[ $value[0] ] ) ) {
					break;
				}
			}    
		}
	}
}

/* Function for delete hook and options */
if ( ! function_exists ( 'pdtr_deactivation' ) ) {
	function pdtr_deactivation() {
		/* Delete hook if it exist */
		wp_clear_scheduled_hook( 'pdtr_auto_hook' );
	}
}

/* Function for delete options */
if ( ! function_exists ( 'pdtr_uninstall' ) ) {
	function pdtr_uninstall() {
		delete_option( 'pdtr_options' );
		delete_site_option( 'pdtr_options' );
	}
}

add_action( 'admin_menu', 'pdtr_add_admin_menu' );
add_action( 'init', 'pdtr_init' );
add_action( 'admin_init', 'pdtr_admin_init' );
/* Add css-file to the plugin */
add_action( 'admin_enqueue_scripts', 'pdtr_admin_head' );

/* Additional links on the plugin page */
add_filter( 'plugin_action_links', 'pdtr_plugin_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'pdtr_register_plugin_links', 10, 2 );
/* Add time for cron viev */
add_filter( 'cron_schedules', 'pdtr_schedules' );
/* Function that update all plugins and WP core in auto mode. */
add_action( 'pdtr_auto_hook', 'pdtr_auto_function' );

add_action( 'admin_notices', 'pdtr_plugin_banner' );

/* When deactivate plugin */
register_deactivation_hook( __FILE__, 'pdtr_deactivation' );
/* When uninstall plugin */
register_uninstall_hook( __FILE__, 'pdtr_uninstall' );
?>