<?php
/*
Plugin Name: Updater by BestWebSoft
Plugin URI: http://bestwebsoft.com/products/
Description: This plugin allows you to update plugins and WP core in auto or manual mode.
Author: BestWebSoft
Text Domain: updater
Domain Path: /languages
Version: 1.29
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
		bws_add_general_menu( plugin_basename( __FILE__ ) );
		add_submenu_page( 'bws_plugins', 'Updater', 'Updater', 'manage_options', 'updater-options', 'pdtr_settings_page' );
	}
}

if ( ! function_exists( 'pdtr_plugins_loaded' ) ) {
	function pdtr_plugins_loaded() {
		/* Internationalization */
		load_plugin_textdomain( 'updater', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

if ( ! function_exists ( 'pdtr_init' ) ) {
	function pdtr_init() {
		global $pdtr_plugin_info;
		
		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );

		if ( empty( $pdtr_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) )
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$pdtr_plugin_info = get_plugin_data( __FILE__ );
		}

		/* Function check if plugin is compatible with current WP version */
		bws_wp_min_version_check( plugin_basename( __FILE__ ), $pdtr_plugin_info, '3.8', '3.3' );
	}
}

if ( ! function_exists ( 'pdtr_admin_init' ) ) {
	function pdtr_admin_init() {
		global $bws_plugin_info, $pdtr_plugin_info;

		if ( ! isset( $bws_plugin_info ) || empty( $bws_plugin_info ) )			
			$bws_plugin_info = array( 'id' => '84', 'version' => $pdtr_plugin_info["Version"] );
		
		/* Call register settings function */
		if ( isset( $_GET['page'] ) && "updater-options" == $_GET['page'] )
			pdtr_register_settings();
	}
}

/* Register settings function */
if ( ! function_exists( 'pdtr_register_settings' ) ) {
	function pdtr_register_settings() {
		global $pdtr_options, $pdtr_plugin_info, $pdtr_option_defaults, $wpdb;

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
			'pdtr_from_email'				=> $from_email,
			'first_install'					=>	strtotime( "now" ),
			'display_settings_notice'		=> 1
	  	);
		if ( is_multisite() ) {
			if ( ! get_site_option( 'pdtr_options' ) ) {
				if ( get_option( 'pdtr_options' ) )
					$pdtr_option_defaults = array_merge( $pdtr_option_defaults, get_option( 'pdtr_options' ) );
				else {
					$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
						foreach ( $blogids as $blog_id ) {
							switch_to_blog( $blog_id );				
							if ( get_option( 'pdtr_options' ) ) {
								$pdtr_option_defaults = array_merge( $pdtr_option_defaults, get_option( 'pdtr_options' ) );
								break;
							}
						}
						restore_current_blog();
				}
				add_site_option(  'pdtr_options', $pdtr_option_defaults );
			}
		} else {
			/* Install the option defaults */
			if ( ! get_option( 'pdtr_options' ) )
				add_option( 'pdtr_options', $pdtr_option_defaults );
		}
	  	/* Get options from the database */
		$pdtr_options = is_multisite() ? get_site_option( 'pdtr_options' ) : get_option( 'pdtr_options' );

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

			$pdtr_option_defaults['display_settings_notice'] = 0;

	  		$pdtr_options = array_merge( $pdtr_option_defaults, $pdtr_options );
	  		$pdtr_options['plugin_option_version'] = $pdtr_plugin_info["Version"];
	  		if ( is_multisite() )
	  			update_site_option( 'pdtr_options', $pdtr_options );
	  		else
		  		update_option( 'pdtr_options', $pdtr_options );
		}
	}
}
/* End pdtr_register_settings */

if ( ! function_exists( 'pdtr_activation' ) ) {
	function pdtr_activation() {
		global $pdtr_options;
	  	/* Get options from the database */
		$pdtr_options = is_multisite() ? get_site_option( 'pdtr_options' ) : get_option( 'pdtr_options' );
		if ( ! empty( $pdtr_options ) && ( '0' != $pdtr_options["pdtr_mode"] || '0' != $pdtr_options["pdtr_send_mail_get_update"] ) ) {
			$time = ( '' != $pdtr_options['pdtr_time'] ) ? time()+$pdtr_options['pdtr_time']*60*60 : time()+12*60*60;
			wp_schedule_event( $time, 'schedules_hours', 'pdtr_auto_hook' );
		}
	}
}

/* Add time for cron viev */
if ( ! function_exists( 'pdtr_schedules' ) ) {
	function pdtr_schedules( $schedules ) {
		global $pdtr_options;
		if ( empty( $pdtr_options ) )
			$pdtr_options =  is_multisite() ? get_site_option( 'pdtr_options' ) : get_option( 'pdtr_options' ) ;
		$schedules_hours = ( '' != $pdtr_options['pdtr_time'] ) ? $pdtr_options['pdtr_time'] : 12;

	    $schedules['schedules_hours'] = array( 'interval' => $schedules_hours*60*60, 'display' => 'Every ' . $schedules_hours . ' hours' );
	    return $schedules;
	}
}

/* Function for display updater settings page in the BWS admin area */
if ( ! function_exists ( 'pdtr_settings_page' ) ) {
	function pdtr_settings_page() {
		global $pdtr_options, $wp_version, $pdtr_plugin_info, $pdtr_option_defaults;
		$error = $message =	"";

		if ( ! isset( $_GET['action'] ) ) {
			$core = '';

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
				<p><a target="_parent" title="<?php _e( 'Go back to the Updater page', 'updater' ); ?>" href="admin.php?page=updater-options"><?php _e( 'Return to the Updater page', 'updater' ); ?></a></p>
				<?php /* Send mail if it's need */
				if ( 1 == $pdtr_options["pdtr_send_mail_after_update"] ) {
					$result_mail = pdtr_notification_after_update( $plugins, $core );

					if ( "" != $pdtr_options["pdtr_to_email"] ) 
						$email = $pdtr_options["pdtr_to_email"];
					else { 
						$email = is_multisite() ? get_site_option( 'admin_email' ) : get_option( 'admin_email' );
					}

					if ( true != $result_mail )
						echo '<p>' . __( "Sorry, your message could not be delivered to", 'updater' ) . ' ' . $email . '</p>';
					else
						echo '<p>' . __( "The email message with the update results is sent to", 'updater' ) . ' ' . $email . '</p>';
				}
				if ( '3.2' <= $wp_version )
					include( ABSPATH . 'wp-admin/admin-footer.php' );
				echo '</div>';
				exit;
			}
		}

		if ( isset( $_GET['action'] ) && $_GET['action'] == 'settings' ) {

			/* Check mail */
			if ( isset( $_REQUEST["pdtr_form_check_mail"] ) && check_admin_referer( plugin_basename(__FILE__), 'pdtr_nonce_check_mail' ) ) {
				global $pdtr_core_plugin_list;			
				$pdtr_core_plugin_list = pdtr_processing_site();
				$plugin_upd_list = $core = "";
				
				if ( $pdtr_core_plugin_list["core"]["current"] != $pdtr_core_plugin_list["core"]["new"] )
					$core = true;
				
				if ( isset( $pdtr_core_plugin_list["plg_need_update"] ) ) {
					foreach ( $pdtr_core_plugin_list["plg_need_update"] as $key => $value ) {
						$plugin_upd_list[] = $key;
					}
				}

				if ( 1 == $pdtr_options["pdtr_send_mail_get_update"] || 1 == $pdtr_options["pdtr_send_mail_after_update"] ) {
					$result_mail = pdtr_notification_exist_update( $plugin_upd_list, $core, true );

					if ( "" != $pdtr_options["pdtr_to_email"] ) 
						$email = $pdtr_options["pdtr_to_email"];
					else { 
						$email = is_multisite() ? get_site_option( 'admin_email' ) : get_option( 'admin_email' );
					}

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
						$error = __( "Please enter a time for search and/or update. A number of hours should be integer and it should not contain more than 5 digits. Settings are not saved", 'updater' );
				}
				/* If user enter receiver's email check if it correct. Save email if it pass the test */
				if ( isset( $_REQUEST["pdtr_to_email"] ) ) {
					if ( is_email( trim( $_REQUEST["pdtr_to_email"] ) ) )
						$pdtr_options["pdtr_to_email"] = trim( $_REQUEST["pdtr_to_email"] );
					else
						$error = __( "Please enter a valid recipient email. Settings are not saved", 'updater' );
				}
				$pdtr_options["pdtr_from_name"] = stripslashes( esc_html( $_REQUEST["pdtr_from_name"] ) );
				if ( '' == $pdtr_options['pdtr_from_name'] )
					$pdtr_options['pdtr_from_name'] = $pdtr_option_defaults['pdtr_from_name'];
				/*If user enter sender's email check if it correct. Save email if it pass the test */
				if ( isset( $_REQUEST["pdtr_from_email"] ) ) {
					if ( is_email( trim( $_REQUEST["pdtr_from_email"] ) ) )
						$pdtr_options["pdtr_from_email"] = trim( $_REQUEST["pdtr_from_email"] );
					else
						$error = __( "Please enter a valid sender email. Settings are not saved", 'updater' );
				}

				/* Update options in the database */
				if ( is_multisite() )
					update_site_option( 'pdtr_options', $pdtr_options );
				else	
					update_option( 'pdtr_options', $pdtr_options );
				
				if ( "" == $error )
					$message = __( "All settings are saved", 'updater' );

			    /* Add or delete hook of auto/handle mode */
			    if ( wp_next_scheduled( 'pdtr_auto_hook' ) )
					wp_clear_scheduled_hook( 'pdtr_auto_hook' );

				if ( '0' != $pdtr_options["pdtr_mode"] || '0' != $pdtr_options["pdtr_send_mail_get_update"] ) {
					$time = ( '' != $pdtr_options['pdtr_time'] ) ? time()+$pdtr_options['pdtr_time']*60*60 : time()+12*60*60;
					wp_schedule_event( $time, 'schedules_hours', 'pdtr_auto_hook' );
				}
			} 

			/* Add restore function */
			if ( isset( $_REQUEST['bws_restore_confirm'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'bws_settings_nonce_name' ) ) {
				$pdtr_options = $pdtr_option_defaults;
				if ( is_multisite() )
					update_site_option( 'pdtr_options', $pdtr_options );
				else	
					update_option( 'pdtr_options', $pdtr_options );
				wp_clear_scheduled_hook( 'pdtr_auto_hook' );
				wp_schedule_event( time()+$pdtr_options['pdtr_time']*60*60, 'schedules_hours', 'pdtr_auto_hook' );
				$message = __( 'All plugin settings were restored.', 'updater' );
			}		
		}

		/* GO PRO */
		if ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) {			
			$go_pro_result = bws_go_pro_tab_check( plugin_basename(__FILE__) );
			if ( ! empty( $go_pro_result['error'] ) )
				$error = $go_pro_result['error'];
		}

		/* Display form on the setting page */ ?> 
		<div class="wrap">
			<h2>Updater</h2>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab<?php if ( ! isset( $_GET['action'] ) ) echo ' nav-tab-active'; ?>" href="admin.php?page=updater-options"><?php _e( 'Tools', 'updater' ); ?></a>
				<a class="nav-tab<?php if ( isset( $_GET['action'] ) && 'settings' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=updater-options&action=settings"><?php _e( 'Settings', 'updater' ); ?></a>
				<a class="bws_plugin_menu_pro_version nav-tab" href="http://bestwebsoft.com/products/updater/" target="_blank" title="<?php _e( 'This setting is available in Pro version', 'updater' ); ?>"><?php _e( 'User guide', 'updater' ); ?></a>
				<a class="nav-tab" href="http://bestwebsoft.com/products/updater/faq/" target="_blank"><?php _e( 'FAQ', 'updater' ); ?></a>
				<a class="nav-tab bws_go_pro_tab<?php if ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=updater-options&action=go_pro"><?php _e( 'Go PRO', 'updater' ); ?></a>
			</h2>
			<div class="error"><p><strong><?php _e( 'We strongly recommend that you backup your website and the WordPress database before updating! We are not responsible for the site work after updates', 'updater' ); ?></strong></p></div>
			<div class="updated fade" <?php if ( "" != $error || "" == $message ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
			<div class="error" <?php if ( "" == $error ) echo "style=\"display:none\""; ?>><p><strong><?php echo $error; ?></strong></p></div>
			<?php if ( ! isset( $_GET['action'] ) ) { ?>
				<div class="bws_pro_version_bloc">
					<div class="bws_table_bg"></div>											
					<div class="bws_pro_version">
						<p>
							<img class="pdtr_img" src="<?php echo plugins_url( 'images/unlock.png' , __FILE__ );?>" alt=""/> - <?php _e( "the element will be updated", 'updater' ); ?><br/>
							<img class="pdtr_img" src="<?php echo plugins_url( 'images/lock.png' , __FILE__ );?>" alt=""/> - <?php _e( "the element will not be updated", 'updater' ); ?><br/>
						</p>
						<p>
							<input disabled type="submit" class="button" value="<?php _e( "Update information", 'updater' ); ?>" /> <?php echo __( 'Latest update was', 'updater' ) . ' ' . current_time('mysql') . ' ' . $gmt; ?>
						</p>
						<p>
							<input disabled checked type="checkbox" value="1" /> 
							<?php _e( 'Updater Pro will display, check and update all plugins (Not just the active ones)', 'updater' ); ?>
						</p>
						<p>* <?php _e( 'If you upgrade to Pro version all your settings will be saved.', 'updater' ); ?></p>
					</div>
					<div class="bws_pro_version_tooltip">
						<div class="bws_info">
							<?php _e( 'Unlock premium options by upgrading to Pro version', 'updater' ); ?> 
						</div>
						<a class="bws_button" href="http://bestwebsoft.com/products/updater/?k=347ed3784e3d2aeb466e546bfec268c0&pn=84&v=<?php echo $pdtr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Updater Pro"><?php _e( 'Learn More', 'updater' ); ?></a>	
						<div class="clear"></div>					
					</div>
				</div>
				<div class="clear"></div>
				<form method="post" action="" enctype="multipart/form-data">
					<table class="wp-list-table widefat pdtr" cellspacing="0">
						<thead>
							<tr>
								<th class="plugin-title check-column"><?php _e( 'WP Core / Plugins', 'updater' ); ?></th>
								<th id="cb" class="manage-column check-column" scope="col">
									<label><input type="checkbox" /> <?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?></label>
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
								<tr><th><?php _e( 'No plugins found', 'updater' ); ?></th></tr>
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
			<?php } elseif ( isset( $_GET['action'] ) && 'settings' == $_GET['action'] ) {
				bws_show_settings_notice();
				if ( isset( $_REQUEST['bws_restore_default'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'bws_settings_nonce_name' ) ) {
					bws_form_restore_default_confirm( plugin_basename( __FILE__ ) );
				} else { ?>
					<form class="bws_form" method="post" action="">
					  	<table class="pdtr_settings form-table">
							<tbody>
								<tr valign="top">
									<th scope="row">
										<?php _e( 'Plugin mode', 'updater' ); ?>
									</th>
									<td colspan="2">
										<label><input type="radio" name="pdtr_mode" value="0" <?php if ( 0 == $pdtr_options["pdtr_mode"] ) echo "checked=\"checked\""; ?> /> <?php _e( 'Manual', 'updater' ); ?></label><br />
										<label><input type="radio" name="pdtr_mode" value="1" <?php if ( 1 == $pdtr_options["pdtr_mode"] ) echo "checked=\"checked\""; ?> /> <?php _e( 'Auto', 'updater' ); ?></label>
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e( 'Send email when new versions are available', 'updater' ); ?></th>
									<td colspan="2">
										<input type="checkbox" name="pdtr_send_mail_get_update" value="1" <?php if ( 1 == $pdtr_options["pdtr_send_mail_get_update"] ) echo "checked=\"checked\""; ?> />
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e( 'Send email after updating', 'updater' ); ?></th>
									<td colspan="2">
										<input type="checkbox" name="pdtr_send_mail_after_update" value="1" <?php if ( 1 == $pdtr_options["pdtr_send_mail_after_update"] ) echo "checked=\"checked\""; ?> />
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e( 'Search/update frequency', 'updater' ); ?></th>
									<td colspan="2">
										<input type="number" name="pdtr_time" value="<?php echo $pdtr_options["pdtr_time"]; ?>" min="1" max="99999" /> <?php _e( 'hours', 'updater' ); ?>
										<br />
										<span class="bws_info">(<?php _e( 'It should be integer and it should not contain more than 5 digits.', 'updater' ); ?>)</span>
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e( 'Recipient email address (To:)', 'updater' ); ?></th>
									<td colspan="2">
										<input type="email" name="pdtr_to_email" maxlength="250" value="<?php echo $pdtr_options["pdtr_to_email"]; ?>" />
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e( "'FROM' field", 'updater' ); ?></th>
									<td style="width: 200px; vertical-align: top;">
										<div><?php _e( "Name", 'updater' ); ?></div>
										<div><input type="text" name="pdtr_from_name" maxlength="250" value="<?php echo $pdtr_options["pdtr_from_name"]; ?>" /></div>
									</td>
									<td>
										<div><?php _e( "Email", 'updater' ); ?></div>
										<div>
											<input type="email" name="pdtr_from_email" maxlength="250" value="<?php echo $pdtr_options["pdtr_from_email"]; ?>" />
										</div>
										<span class="bws_info">(<?php _e( 'If this option is changed, email messages may be moved to the spam folder or email delivery failures may occur.', 'updater' ); ?>)</span>
									</td>
								</tr>
							</tbody>
						</table>
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
										<th></th>
										<td>
											<input disabled type="checkbox" value="1" /> <?php _e( 'Backup all folders', 'updater' ); ?><br/>
											<input disabled type="checkbox" value="1" /> <?php _e( 'Backup all tables in database', 'updater' ); ?><br/>
											<input disabled type="checkbox" value="1" /> <?php _e( 'Delete test backup after testing', 'updater' ); ?>
										</td>
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
									<?php _e( 'Unlock premium options by upgrading to Pro version', 'updater' ); ?> 
								</div>
								<a class="bws_button" href="http://bestwebsoft.com/products/updater/?k=347ed3784e3d2aeb466e546bfec268c0&pn=84&v=<?php echo $pdtr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="Updater Pro"><?php _e( 'Learn More', 'updater' ); ?></a>
								<div class="clear"></div>
							</div>
						</div>				
						<p class="submit" id="submit">
							<input type="hidden" name="pdtr_form_submit" value="submit" />
							<input id="bws-submit-button" type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'updater' ); ?>" />
							<?php wp_nonce_field( plugin_basename( __FILE__ ), 'pdtr_nonce_name' ); ?>
						</p>				
					</form>
					<h4><?php _e( "Send a test email message", 'updater' ); ?></h4>
					<form method="post" action="">
						<input type="hidden" name="pdtr_form_check_mail" value="submit" />
						<p><?php _e( "Here You can make sure that your settings are correct and the email can be delivered.", 'updater' ); ?></p>
						<input type="submit" class="button" value="<?php _e( 'Check email sending', 'updater' ); ?>" />
						<?php wp_nonce_field( plugin_basename( __FILE__ ), 'pdtr_nonce_check_mail' ); ?>
					</form>				
					<h4><?php _e( "Restore settings", 'updater' ); ?></h4>
					<?php bws_form_restore_default_settings( plugin_basename( __FILE__ ) );
				}
			} elseif ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) {
				bws_go_pro_tab( $pdtr_plugin_info, plugin_basename( __FILE__ ), 'updater-options&action=go_pro', 'updater-pro', 'updater-pro/updater_pro.php', 'updater', '347ed3784e3d2aeb466e546bfec268c0', '84', isset( $go_pro_result['pro_plugin_is_activated'] ) );
			}
			bws_plugin_reviews_block( $pdtr_plugin_info['Name'], 'updater' ); ?>
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
	function pdtr_update_core( $auto_mode = false ) {
		global $wp_filesystem, $wp_version;

		if ( ! $auto_mode )
			echo '<h3>' . __( 'Updating WordPress...', 'updater' ) . '</h3>';
		/* Include files for correct update */
		include_once( ABSPATH . 'wp-admin/includes/misc.php' );
		include_once( ABSPATH . 'wp-admin/includes/file.php' );
		include_once( ABSPATH . 'wp-admin/includes/update.php' );

		$url	=	wp_nonce_url( 'update-core.php?action=do-core-upgrade', 'upgrade-core' );
		if ( false === ( $credentials = request_filesystem_credentials( $url, '', false, ABSPATH ) ) )
			return false;

		$url	=	wp_nonce_url( 'admin.php?page=updater-options', 'upgrade-core' );
		if ( false === ( $credentials = request_filesystem_credentials( $url, '', false, ABSPATH ) ) )
			return false;

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
			include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
			$upgrader = new Core_Upgrader();
			if ( '4.1' > $wp_version )
				$result = @$upgrader->upgrade( $update );
			else				
				$result = @$upgrader->upgrade( $update, array( 'allow_relaxed_file_ownership' => true ) );
		}

		if ( is_wp_error( $result ) ) {
			if ( ! $auto_mode ) {
				show_message( $result );
				if ( 'up_to_date' != $result->get_error_code() )
					show_message( __( 'Update Failed', 'updater' ) );
			}
			return false;
		}
		if ( ! $auto_mode )
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
		$network = is_multisite() ? 'network/' : '';
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
		} elseif ( false === $core ) {
			$message .= '<strong> - ' . __( "We couldn't update WordPress Core in the site", 'updater' ) . '.</strong><br/><br/>';
		}

		$message .= __( 'If you want to change the plugin mode or other settings you should go here:', 'updater' ) .
				' <a href=' . admin_url( '/' ) . $network . 'admin.php?page=updater-options&action=settings> ' . __( 'the Updater settings page on your website.', 'updater' ) . '</a>
				<br/><br/>----------------------------------------<br/><br/>' .
				esc_html__( 'Thanks for using the plugin', 'updater' ) . ' <a href="http://bestwebsoft.com/products/updater/">Updater</a>!</body></html>';
		
		if ( "" != $pdtr_options["pdtr_to_email"] ) 
			$email = $pdtr_options["pdtr_to_email"];
		else { 
			$email = is_multisite() ? get_site_option( 'admin_email' ) : get_option( 'admin_email' );
		}

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
			$mail_result = wp_mail( $email, $subject, $message, $headers );
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
		$network = is_multisite() ? 'network/' : '';
		$subject	=	esc_html__( 'Check for updates on', 'updater' ) . ' ' . esc_attr( get_bloginfo( 'name', 'display' ) );
		$message	=	'<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
					<body>
					<h3>' . __( 'Hello!', 'updater' ) . '</h3>' .
					esc_html__( 'The Updater plugin is being run on your website', 'updater' ) . ' <a href=' . home_url() . '>' . esc_attr( get_bloginfo( 'name', 'display' ) ) . '</a>.';
		
		if ( ( "" != $plugins_list ) || ( false != $core ) )
			$message .= ' ' . __( 'The files that need update are:', 'updater' );
		
		$message .= '<br/><br/>';

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
					 ' (' . __( 'the current version is', 'updater' ) . ' ' . $pdtr_core_plugin_list["core"]["current"] . ').</strong><br/>';
		}

		if ( false === $test ) {
			if ( 0 == $pdtr_options["pdtr_mode"] ) {
				$message .= '<br/>' . __( 'Please use this link to update:', 'updater' ) . ' <a href=' . admin_url( '/' ) . $network . 'admin.php?page=updater-options' . '> ' . __( 'the Updater page on your website.', 'updater' ) . '</a>';
			} else {
				$message .= '<br/>' . __( 'The Updater plugin starts updating these files.', 'updater' );
			}
		} elseif ( ( "" != $plugins_list ) || ( false != $core ) ) {
			$message .= '<br/>' . __( 'Please use this link to update:', 'updater' ) . ' <a href=' . admin_url( '/' ) . $network . 'admin.php?page=updater-options' . '> ' . __( 'the Updater page on your website.', 'updater' ) . '</a>';
		}

		if ( ( "" == $plugins_list ) && ( false == $core ) ) {
			$message .= __( 'Congratulations! Your plugins and WordPress have the latest updates!', 'updater' );
		}

		$message .= '<br/><br/>' . __( 'If you want to change type of mode for the plugin or other settings you should go here:', 'updater' ) .
				' <a href=' . admin_url( '/' ) . $network . 'admin.php?page=updater-options&action=settings> ' . __( 'the Updater settings page on your website.', 'updater' ) . '</a>
				<br/><br/>----------------------------------------<br/><br/>' .
				esc_html__( 'Thanks for using the plugin', 'updater' ) . ' <a href="http://bestwebsoft.com/products/updater/">Updater</a>!</body></html>';

		if ( "" != $pdtr_options["pdtr_to_email"] ) 
			$email = $pdtr_options["pdtr_to_email"];
		else { 
			$email = is_multisite() ? get_site_option( 'admin_email' ) : get_option( 'admin_email' );
		}

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
		if ( isset( $_GET['page'] ) && "updater-options" == $_GET['page'] ) {
			wp_enqueue_style( 'pdtr_stylesheet', plugins_url( 'css/style.css', __FILE__ ) );
		}
	}
}

/* Function that update all plugins and WP core. It will be executed every hour if enabled auto mode */
if ( ! function_exists ( 'pdtr_auto_function' ) ) {
	function pdtr_auto_function() {
		global $pdtr_options, $pdtr_core_plugin_list;
		$plugin_upd_list = $core =	"";
		$pdtr_core_plugin_list	=	pdtr_processing_site();

		if ( empty( $pdtr_options ) )
			$pdtr_options = is_multisite() ? get_site_option( 'pdtr_options' ) : get_option( 'pdtr_options' ) ;
		
		if ( $pdtr_core_plugin_list["core"]["current"] != $pdtr_core_plugin_list["core"]["new"] )
			$core = true;

		if ( isset( $pdtr_core_plugin_list["plg_need_update"] ) ) {
			foreach ( $pdtr_core_plugin_list["plg_need_update"] as $key => $value) {
				$plugin_upd_list[] = $key;
			}
		}

		if ( 1 == $pdtr_options["pdtr_send_mail_get_update"] && ( "" != $plugin_upd_list || '' != $core ) ) {
			pdtr_notification_exist_update( $plugin_upd_list, $core );
		}

		if ( 1 == $pdtr_options["pdtr_mode"] ) {			
			/* If WP core need to be update */
			if ( '' != $core )
				$core_result = pdtr_update_core( true ); /* update the WP core */
			/* Update the list of plugins */
			if ( "" != $plugin_upd_list ) {
				pdtr_update_plugin( $plugin_upd_list );
			}
			/* Send mail */
			if ( 1 == $pdtr_options["pdtr_send_mail_after_update"] && ( "" != $plugin_upd_list || '' != $core ) ) {
				pdtr_notification_after_update( $plugin_upd_list, $core_result );
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
		if ( ! is_multisite() || is_network_admin() ) {
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
			if ( ! is_multisite() || is_network_admin() )
				$links[]	=	'<a href="admin.php?page=updater-options">' . __( 'Settings', 'updater' ) . '</a>';
			$links[]	=	'<a href="http://wordpress.org/plugins/updater/faq/" target="_blank">' . __( 'FAQ', 'updater' ) . '</a>';
			$links[]	=	'<a href="http://support.bestwebsoft.com">' . __( 'Support', 'updater' ) . '</a>';
		}
		return $links;
	}
}
/* End function pdtr_register_plugin_links */

if ( ! function_exists( 'pdtr_plugin_banner' ) ) {
	function pdtr_plugin_banner() {
		global $hook_suffix;
		if ( 'plugins.php' == $hook_suffix ) {
			global $pdtr_plugin_info, $pdtr_options;
			if ( isset( $pdtr_options['first_install'] ) && strtotime( '-1 week' ) > $pdtr_options['first_install'] )
				bws_plugin_banner( $pdtr_plugin_info, 'pdtr', 'updater', '0b6882b0c99c2776d06c375dc22b5869', '84', '//ps.w.org/updater/assets/icon-128x128.png' );    
			
			bws_plugin_banner_to_settings( $pdtr_plugin_info, 'pdtr_options', 'updater', 'admin.php?page=updater-options' );

			if ( is_multisite() && ! is_network_admin() && is_admin() ) { ?>
				<div class="update-nag"><strong><?php _e( 'Notice:', 'updater' ); ?></strong> 
					<?php if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
						_e( 'Due to the peculiarities of the multisite work, Updater plugin has only', 'updater' ); ?> <a target="_blank" href="<?php echo network_admin_url( 'admin.php?page=updater-options' ); ?>"><?php _e( 'Network settings page', 'updater' ); ?></a>
					<?php } else {
						_e( 'Due to the peculiarities of the multisite work, Updater plugin has the network settings page only and it should be Network Activated. Please', 'updater' ); ?> <a target="_blank" href="<?php echo network_admin_url( 'plugins.php' ); ?>"><?php _e( 'Activate Updater for Network', 'updater' ); ?></a>
					<?php } ?>
				</div>	
			<?php }
		}
	}
}

/* Function for delete hook and options */
if ( ! function_exists( 'pdtr_deactivation' ) ) {
	function pdtr_deactivation() {
		/* Delete hook if it exist */
		wp_clear_scheduled_hook( 'pdtr_auto_hook' );
	}
}

/* Function for delete options */
if ( ! function_exists( 'pdtr_uninstall' ) ) {
	function pdtr_uninstall() {
		delete_option( 'pdtr_options' );
		delete_site_option( 'pdtr_options' );
	}
}

/* When deactivate plugin */
register_activation_hook( __FILE__, 'pdtr_activation' );

if ( function_exists( 'is_multisite' ) ) {
	if ( is_multisite() )
		add_action( 'network_admin_menu', 'pdtr_add_admin_menu' );
	else
		add_action( 'admin_menu', 'pdtr_add_admin_menu' );
}
add_action( 'init', 'pdtr_init' );
add_action( 'admin_init', 'pdtr_admin_init' );

add_action( 'plugins_loaded', 'pdtr_plugins_loaded' );
/* Add css-file to the plugin */
add_action( 'admin_enqueue_scripts', 'pdtr_admin_head' );

/* Additional links on the plugin page */
add_filter( 'plugin_action_links', 'pdtr_plugin_action_links', 10, 2 );
if ( function_exists( 'is_multisite' ) ) {
	if ( is_multisite() )
		add_filter( 'network_admin_plugin_action_links', 'pdtr_plugin_action_links', 10, 2 );
}
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