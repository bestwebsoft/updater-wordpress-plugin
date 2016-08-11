<?php
/*
Plugin Name: Updater by BestWebSoft
Plugin URI: http://bestwebsoft.com/products/updater/
Description: Automatically check and update WordPress website core with all installed plugins and themes to the latest versions.
Author: BestWebSoft
Text Domain: updater
Domain Path: /languages
Version: 1.34
Author URI: http://bestwebsoft.com/
License: GPLv2 or later
*/

/*
	© Copyright 2016  BestWebSoft  ( http://support.bestwebsoft.com )

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
		bws_general_menu();
		$settings = add_submenu_page( 'bws_panel', 'Updater', 'Updater', 'manage_options', 'updater-options', 'pdtr_settings_page' );
		add_action( 'load-' . $settings, 'pdtr_add_tabs' );
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
		bws_wp_min_version_check( plugin_basename( __FILE__ ), $pdtr_plugin_info, '3.8' );
	}
}

if ( ! function_exists ( 'pdtr_admin_init' ) ) {
	function pdtr_admin_init() {
		global $bws_plugin_info, $pdtr_plugin_info;

		if ( empty( $bws_plugin_info ) )
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
			'plugin_option_version' 	=>	$pdtr_plugin_info["Version"],
			'first_install'				=>	strtotime( "now" ),
			'display_settings_notice'	=>	1,
			'suggest_feature_banner'	=>	1,
			'mode'						=>	0,
			'send_mail_after_update'	=>	1,
			'send_mail_get_update'		=>	1,
			'time'						=>	12,
			'to_email'					=>	get_option( 'admin_email' ),
			'from_name'					=>	get_bloginfo( 'name' ),
			'from_email'				=>	$from_email,			
			'update_core'				=>	1,
			'update_plugin'				=>	1,
			'update_theme'				=>	1			
		);
		if ( is_multisite() ) {
			if ( ! get_site_option( 'pdtr_options' ) )
				add_site_option( 'pdtr_options', $pdtr_option_defaults );
		} else {
			/* Install the option defaults */
			if ( ! get_option( 'pdtr_options' ) )
				add_option( 'pdtr_options', $pdtr_option_defaults );
		}
		/* Get options from the database */
		$pdtr_options = is_multisite() ? get_site_option( 'pdtr_options' ) : get_option( 'pdtr_options' );

		/* Array merge incase this version has added new options */
		if ( ! isset( $pdtr_options['plugin_option_version'] ) || $pdtr_options['plugin_option_version'] != $pdtr_plugin_info["Version"] ) {
			
			/*pls
    		* @since 1.33
    		* @todo remove after 01.01.2017
    		*/
			$pdtr_option_defaults['display_settings_notice'] = 0;
			foreach ( $pdtr_options as $key => $value ) {
				if ( ! empty( $pdtr_options['pdtr_' . $key ] ) ) {
					$pdtr_options[ $key ] = $pdtr_options['pdtr_' . $key ];
					unset( $pdtr_options['pdtr_' . $key ] );
				}
			}
			if ( isset( $pdtr_options['option_update_core'] ) ) {
				$pdtr_options['update_core'] = $pdtr_options['option_update_core'];
				unset( $pdtr_options['option_update_core'] );
			}
			if ( isset( $pdtr_options['option_update_plugin'] ) ) {
				$pdtr_options['update_plugin'] = $pdtr_options['option_update_plugin'];
				unset( $pdtr_options['option_update_plugin'] );
			}
			if ( isset( $pdtr_options['option_update_theme'] ) ) {
				$pdtr_options['update_theme'] = $pdtr_options['option_update_theme'];
				unset( $pdtr_options['option_update_theme'] );
			}
			/* end @todo pls*/

			$pdtr_options = array_merge( $pdtr_option_defaults, $pdtr_options );
			$pdtr_options['plugin_option_version'] = $pdtr_plugin_info["Version"];
			/* show pro features */
			$pdtr_options['hide_premium_options'] = array();

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
		pdtr_register_settings();
		if ( ! empty( $pdtr_options ) && ( 0 != $pdtr_options["mode"] || 0 != $pdtr_options["send_mail_get_update"] ) ) {
			$time = ( ! empty( $pdtr_options['time'] ) ) ? time() + $pdtr_options['time']*60*60 : time() + 12*60*60;
			wp_schedule_event( $time, 'pdtr_schedules_hours', 'pdtr_auto_hook' );
		}
	}
}

/* Add time for cron viev */
if ( ! function_exists( 'pdtr_schedules' ) ) {
	function pdtr_schedules( $schedules ) {
		global $pdtr_options;
		if ( empty( $pdtr_options ) )
			$pdtr_options =  is_multisite() ? get_site_option( 'pdtr_options' ) : get_option( 'pdtr_options' ) ;
		$schedules_hours = ( ! empty( $pdtr_options['time'] ) ) ? $pdtr_options['time'] : 12;

		$schedules['pdtr_schedules_hours'] = array( 'interval' => $schedules_hours*60*60, 'display' => 'Every ' . $schedules_hours . ' hours' );
		return $schedules;
	}
}

/* Function for display updater settings page in the BWS admin area */
if ( ! function_exists ( 'pdtr_settings_page' ) ) {
	function pdtr_settings_page() {
		global $pdtr_options, $wp_version, $pdtr_plugin_info, $pdtr_option_defaults;
		$error = $message =	$core = '';

		if ( ! isset( $_GET['action'] ) ) {
			if ( 0 < get_option( 'gmt_offset' ) )
				$gmt = 'UTC+' . get_option( 'gmt_offset' );
			elseif ( 0 == get_option( 'gmt_offset' ) )
				$gmt = 'UTC';
			else
				$gmt = 'UTC' . get_option( 'gmt_offset' );

			/* Get information about WP core and installed plugins from the website */
			$pdtr_updater_list = pdtr_processing_site();
			/* Update plugins and WP if they checked and show the results */
			if ( ( isset( $_REQUEST["checked_core"] ) || isset( $_REQUEST["checked_plugin"] ) || isset( $_REQUEST["checked_theme"] ) ) && check_admin_referer( plugin_basename(__FILE__), 'pdtr_nonce_name' ) ) { ?>
				<?php echo '<h1>' . __( 'Updater', 'updater' ) . '</h1>';
				if ( isset( $_REQUEST["checked_core"] ) )
					$core = pdtr_update_core();  /* Update the WP core */
				if ( isset( $_REQUEST["checked_plugin"] ) ) {
					$plugins = (array) $_REQUEST["checked_plugin"];
					pdtr_update_plugin( $plugins );	/* Update plugins */
				} else {
					$plugins = "";
				}
				if ( isset( $_REQUEST["checked_theme"] ) ) {
					$themes = (array) $_REQUEST["checked_theme"];
					pdtr_update_theme( $themes );	/* Update themes */
				} else {
					$themes = "";
				} ?>
				<p><a target="_parent" title="<?php _e( 'Go back to the Updater page', 'updater' ); ?>" href="admin.php?page=updater-options"><?php _e( 'Return to the Updater page', 'updater' ); ?></a></p>
				<?php /* Send mail if it's need */
				if ( 1 == $pdtr_options["send_mail_after_update"] ) {
					$result_mail = pdtr_notification_after_update( $plugins, $themes, $core );

					if ( ! empty( $pdtr_options["to_email"] ) )
						$email = $pdtr_options["to_email"];
					else
						$email = is_multisite() ? get_site_option( 'admin_email' ) : get_option( 'admin_email' );

					if ( true != $result_mail )
						echo '<p>' . __( "Sorry, your message could not be sent to", 'updater' ) . ' ' . $email . '</p>';
					else
						echo '<p>' . __( "The email message with the update results is sent to", 'updater' ) . ' ' . $email . '</p>';
				}
				include( ABSPATH . 'wp-admin/admin-footer.php' );
				echo '</div>';
				exit;
			}
		}

		if ( isset( $_GET['action'] ) && $_GET['action'] == 'settings' ) {

			/* Check mail - sending test emails */
			if ( isset( $_REQUEST["pdtr_form_check_mail"] ) && check_admin_referer( plugin_basename(__FILE__), 'pdtr_nonce_check_mail' ) ) {
				global $pdtr_updater_list;
				$pdtr_updater_list = pdtr_processing_site();
				$plugin_update_list = $theme_update_list = '';

				if ( $pdtr_updater_list["core"]["current"] != $pdtr_updater_list["core"]["new"] )
					$core = true;

				if ( isset( $pdtr_updater_list["plugin_need_update"] ) ) {
					foreach ( $pdtr_updater_list["plugin_need_update"] as $key => $value ) {
						$plugin_update_list[] = $key;
					}
				}

				if ( isset( $pdtr_updater_list["theme_need_update"] ) ) {
					foreach ( $pdtr_updater_list["theme_need_update"] as $key => $value ) {
						$theme_update_list[] = $key;
					}
				}

				if ( 1 == $pdtr_options["send_mail_get_update"] || 1 == $pdtr_options["send_mail_after_update"] ) {
					$result_mail = pdtr_notification_exist_update( $plugin_update_list, $theme_update_list, $core, true );

					if ( ! empty( $pdtr_options["to_email"] ) )
						$email = $pdtr_options["to_email"];
					else
						$email = is_multisite() ? get_site_option( 'admin_email' ) : get_option( 'admin_email' );

					if ( $result_mail != true )
						$message = __( "Sorry, your message could not be sent to", 'updater' ) . ' ' . $email;
					else
						$message = __( "Test message was sent to", 'updater' ) . ' ' . $email;
				} else {
					$message = __( "Please check off the Send email options, save settings and try again", 'updater' );
				}
			}

			/* Save data for settings page */
			if ( isset( $_REQUEST["pdtr_form_submit"] ) && check_admin_referer( plugin_basename(__FILE__), 'pdtr_nonce_name' ) ) {
				if ( isset( $_POST['bws_hide_premium_options'] ) ) {
					$hide_result = bws_hide_premium_options( $pdtr_options );
					$pdtr_options = $hide_result['options'];
				}

				$pdtr_options["send_mail_after_update"]		= isset( $_REQUEST["pdtr_send_mail_after_update"] ) ? 1 : 0;
				$pdtr_options["send_mail_get_update"]		= isset( $_REQUEST["pdtr_send_mail_get_update"] ) ? 1 : 0;
				$pdtr_options["mode"]						= $_REQUEST["pdtr_mode"];
				$pdtr_options["update_core"] 				= ( isset( $_REQUEST["pdtr_checkbox_update_core"] ) ) ? 1 : 0;
				$pdtr_options["update_plugin"] 				= ( isset( $_REQUEST["pdtr_checkbox_update_plugin"] ) ) ? 1 : 0;
				$pdtr_options["update_theme"] 				= ( isset( $_REQUEST["pdtr_checkbox_update_theme"] ) ) ? 1 : 0;
				if ( isset( $_REQUEST["pdtr_time"] ) ) {
					if ( preg_match( "/^[0-9]{1,5}+$/", $_REQUEST['pdtr_time'] ) && 0 != intval( $_REQUEST["pdtr_time"] ) )
						$pdtr_options["time"] = intval( $_REQUEST["pdtr_time"] );
					else
						$error = __( "Please enter a time for search and/or update. A number of hours should be integer and it should not contain more than 5 digits. Settings are not saved", 'updater' );
				}
				/* If user enter receiver's email check if it correct. Save email if it pass the test */
				if ( isset( $_REQUEST["pdtr_to_email"] ) ) {
					if ( is_email( trim( $_REQUEST["pdtr_to_email"] ) ) )
						$pdtr_options["to_email"] = trim( $_REQUEST["pdtr_to_email"] );
					else
						$error = __( "Please enter a valid recipient email. Settings are not saved", 'updater' );
				}

				$pdtr_options["from_name"] = stripslashes( esc_html( $_REQUEST["pdtr_from_name"] ) );
				if ( empty( $pdtr_options['from_name'] ) )
					$pdtr_options['from_name'] = $pdtr_option_defaults['pdtr_from_name'];
				/*If user enter sender's email check if it correct. Save email if it pass the test */
				if ( isset( $_REQUEST["pdtr_from_email"] ) ) {
					if ( is_email( trim( $_REQUEST["pdtr_from_email"] ) ) )
						$pdtr_options["from_email"] = trim( $_REQUEST["pdtr_from_email"] );
					else
						$error = __( "Please enter a valid sender email. Settings are not saved", 'updater' );
				}

				if ( empty( $pdtr_options["update_core"] ) && empty( $pdtr_options["update_plugin"] ) && empty( $pdtr_options["update_theme"] ) )
					$error = __( "Please select at least one option in the Search/Update section. Settings are not saved", 'updater' );

				if ( empty( $error ) ) {
					$pdtr_options = apply_filters( 'pdtr_before_save_options', $pdtr_options );
					/* Update options in the database */
					if ( is_multisite() )
						update_site_option( 'pdtr_options', $pdtr_options );
					else
						update_option( 'pdtr_options', $pdtr_options );
					$message = __( "All settings are saved", 'updater' );
				}

				/* Add or delete hook of auto/handle mode */
				if ( wp_next_scheduled( 'pdtr_auto_hook' ) )
					wp_clear_scheduled_hook( 'pdtr_auto_hook' );

				if ( 0 != $pdtr_options["mode"] || 0 != $pdtr_options["send_mail_get_update"] ) {
					$time = ( ! empty( $pdtr_options['time'] ) ) ? time()+$pdtr_options['time']*60*60 : time()+12*60*60;
					wp_schedule_event( $time, 'pdtr_schedules_hours', 'pdtr_auto_hook' );
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
				wp_schedule_event( time() + $pdtr_options['time']*60*60, 'pdtr_schedules_hours', 'pdtr_auto_hook' );
				$message = __( 'All plugin settings were restored.', 'updater' );
			}
		}

		/*pls GO PRO */
		if ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) {
			$go_pro_result = bws_go_pro_tab_check( plugin_basename(__FILE__), 'pdtr_options', is_multisite() );
			if ( ! empty( $go_pro_result['error'] ) )
				$error = $go_pro_result['error'];
			elseif ( ! empty( $go_pro_result['message'] ) )
				$message = $go_pro_result['message'];
		}

		$bws_hide_premium_options_check = bws_hide_premium_options_check( $pdtr_options );

		/* Display form on the setting page  pls*/ ?>
		<div class="wrap">
			<h1>Updater</h1>
			<ul class="subsubsub pdtr_how_to_use">
				<li><a href="https://docs.google.com/document/d/1UHXGDpOJ2dZrJpPGHmH_i4U3ph50M1L2WuKC583RmTY/edit" target="_blank"><?php _e( 'How to Use Step-by-step Instruction', 'updater' ); ?></a></li>
			</ul>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab<?php if ( ! isset( $_GET['action'] ) ) echo ' nav-tab-active'; ?>" href="admin.php?page=updater-options"><?php _e( 'Tools', 'updater' ); ?></a>
				<a class="nav-tab<?php if ( isset( $_GET['action'] ) && 'settings' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=updater-options&amp;action=settings"><?php _e( 'Settings', 'updater' ); ?></a>
				<!-- pls -->
				<?php if ( ! $bws_hide_premium_options_check ) { ?>
					<a class="bws_plugin_menu_pro_version nav-tab" href="http://bestwebsoft.com/products/updater/" target="_blank" title="<?php _e( 'This setting is available in Pro version', 'updater' ); ?>"><?php _e( 'User guide', 'updater' ); ?></a>
				<?php } ?>
				<a class="nav-tab bws_go_pro_tab<?php if ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=updater-options&amp;action=go_pro"><?php _e( 'Go PRO', 'updater' ); ?></a>
				<!-- end pls -->
			</h2>
			<div class="error below-h2"><p><strong><?php _e( 'We strongly recommend that you backup your website and the WordPress database before updating! We are not responsible for the site work after updates', 'updater' ); ?></strong></p></div>
			<div class="updated fade below-h2" <?php if ( ( ! empty( $error ) ) || ( empty( $message ) ) ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
			<div class="error below-h2" <?php if ( empty( $error ) ) echo "style=\"display:none\""; ?>><p><strong><?php echo $error; ?></strong></p></div>
			<?php if ( ! empty( $hide_result['message'] ) ) { ?>
				<div class="updated fade below-h2"><p><strong><?php echo $hide_result['message']; ?></strong></p></div>
			<?php }
			if ( ! isset( $_GET['action'] ) ) {
				/*pls hide banner */
				if ( ! $bws_hide_premium_options_check ) { ?>
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
								<input disabled checked type="checkbox" name="pdtr_check_all_plugins"  value="1" />
								<?php _e( 'Updater Pro will display, check, and update all plugins/themes (Not just the active ones)', 'updater' ); ?>
							</p>
							<p>* <?php _e( 'If you upgrade to Pro version all your settings will be saved.', 'updater' ); ?></p>
						</div>
						<div class="bws_pro_version_tooltip">
							<div class="bws_info">
								<?php _e( 'Unlock premium options by upgrading to Pro version', 'updater' ); ?>
							</div>
							<a class="bws_button" href="http://bestwebsoft.com/products/updater/?k=347ed3784e3d2aeb466e546bfec268c0pn=84&amp;v=<?php echo $pdtr_plugin_info["Version"]; ?>&amp;wp_v=<?php echo $wp_version; ?>" target="_blank" title="Updater Pro"><?php _e( 'Learn More', 'updater' ); ?></a>
							<div class="clear"></div>
						</div>
					</div>
				<?php } /* end banner pls*/ ?>
				<div class="clear"></div>
				<form method="post" action="" enctype="multipart/form-data">
					<table class="wp-list-table widefat pdtr" cellspacing="0">
						<?php if ( 1 == $pdtr_options["update_core"] ) { ?>
							<thead class="hide-if-no-js">
								<tr>
									<th class="check-column"></th>
									<th id="cb" class="manage-column column-cb check-column" scope="col">
										<label for="cb-update-all"><input id="cb-update-all" type="checkbox" />&emsp;<?php _e( 'Update all', 'updater' ); ?></label>
									</th>
								</tr>
							</thead>
						<?php } ?>
						<tbody id="the-list">
							<?php if ( 1 == $pdtr_options["update_core"] ) { ?>
								<tr>
									<td><?php _e( 'WordPress Version', 'updater' ); ?></td>
									<?php $message_update = "";
									$version = $pdtr_updater_list["core"]["current"];
									if ( isset( $pdtr_updater_list["core"]["new"] ) ) {
										if ( $version != $pdtr_updater_list["core"]["new"] ) {
											$message_update = __( 'Update to', 'updater' ) . ' ' . $pdtr_updater_list["core"]["new"];
										}
									} ?>
									<td class="manage-column check-column" <?php if ( ! empty( $message_update ) ) echo "style=\"background:#e89b92\""; ?> >
										<div <?php if ( ! empty( $message_update ) ) echo "class=\"update-message\""; ?>>
											<div class="pdtr_left">
												<img class="pdtr_img" src="<?php echo plugins_url( 'images/unlock.png' , __FILE__ );?>" alt="" />
												<?php echo __( 'Version', 'updater' ) . ' ' . $version; ?>
											</div>
											<?php if ( ! empty( $message_update ) ) { ?>
												<div class="pdtr_right">
													<input type='checkbox' value='1' name='checked_core' />
													<strong><?php echo $message_update; ?></strong>
												</div>
											<?php } ?>
										</div>
									</td>
								</tr>
							<?php }
							if ( 1 == $pdtr_options["update_plugin"] ) { ?>
									<tr class="pdtr-caption">
										<th class="check-column"><strong><?php _e( 'Plugins', 'updater' ); ?></strong></th>
										<th id="cb" class="manage-column check-column" scope="col">
											<label><input type="checkbox" class="hide-if-no-js pdtr-check-plugin-all"></label>
										</th>
									</tr>
								<?php if ( empty( $pdtr_updater_list["plugin_list"] ) ) { ?>
									<tr><th><?php _e( 'No plugins found', 'updater' ); ?></th></tr>
								<?php } else {
									foreach ( $pdtr_updater_list["plugin_list"] as $plugin_key => $value ) { ?>
										<tr>
											<td><?php echo $pdtr_updater_list["plugin_list"][ $plugin_key ]["Name"]; ?></td>
											<?php $message_update = "";
											$version = $pdtr_updater_list["plugin_list"][ $plugin_key ]["Version"];
											if ( isset( $pdtr_updater_list["plugin_need_update"] ) ) {
												foreach ( $pdtr_updater_list["plugin_need_update"] as $file => $plugin_update ) {
													if ( $plugin_key == $file ) {
														if ( $version != $plugin_update["new_version"] ) {
															$message_update = __( 'Update to', 'updater' ) . ' ' . $plugin_update["new_version"];
														}
													}
												}
											} ?>
											<td class="manage-column check-column" <?php if ( ! empty( $message_update ) ) echo "style=\"background:#e89b92\""; ?>>
												<div <?php if ( ! empty( $message_update ) ) echo "class=\"update-message\""; ?>>
													<div class="pdtr_left">
														<img class="pdtr_img" src="<?php echo plugins_url( 'images/unlock.png' , __FILE__ );?>" alt="" />
														<?php echo __( 'Version', 'updater' ) . " " . $version; ?>
													</div>
													<?php if ( ! empty( $message_update ) ) { ?>
														<div class="pdtr_right">
															<input type='checkbox' name='checked_plugin[]' value='<?php echo $plugin_key; ?>' />
															<strong><?php echo $message_update; ?></strong>
														</div>
													<?php } ?>
												</div>
											</td>
										</tr>
									<?php }
								}
							}
							if ( 1 == $pdtr_options["update_theme"] ) { ?>
									<tr class="pdtr-caption">
										<th class="check-column"><strong><?php _e( 'Themes', 'updater' ); ?></strong></th>
										<th id="cb" class="manage-column check-column" scope="col">
											<label><input type="checkbox" class="hide-if-no-js pdtr-check-theme-all"></label>
										</th>
									</tr>
								<?php if ( empty( $pdtr_updater_list["theme_list"] ) ) { ?>
									<tr><th><?php _e( 'No themes found', 'updater' ); ?></th></tr>
								<?php } else {
									foreach ( $pdtr_updater_list["theme_list"] as $theme_key => $value ) { ?>
										<tr>
											<td><?php echo $pdtr_updater_list["theme_list"][ $theme_key ]["Name"]; ?></td>
											<?php $message_update = "";
											$version = $pdtr_updater_list["theme_list"][ $theme_key ]["Version"];
											if ( isset( $pdtr_updater_list["theme_need_update"] ) ) {
												foreach ( $pdtr_updater_list["theme_need_update"] as $file => $theme_update ) {
													if ( $theme_key == $file ) {
														if ( $version != $theme_update["new_version"] ) {
															$message_update = __( 'Update to', 'updater' ) . ' ' . $theme_update["new_version"];
														}
													}
												}
											} ?>
											<td class="manage-column check-column" <?php if ( ! empty( $message_update ) ) echo "style=\"background:#e89b92\""; ?>>
												<div <?php if ( ! empty( $message_update ) ) echo "class=\"update-message\""; ?>>
													<div class="pdtr_left">
														<img class="pdtr_img" src="<?php echo plugins_url( 'images/unlock.png' , __FILE__ );?>" alt="" />
														<?php echo __( 'Version', 'updater' ) . " " . $version; ?>
													</div>
													<?php if ( ! empty( $message_update ) ) { ?>
														<div class="pdtr_right">
															<input type='checkbox' name='checked_theme[]' value='<?php echo $theme_key; ?>' />
															<strong><?php echo $message_update; ?></strong>
														</div>
													<?php } ?>
												</div>
											</td>
										</tr>
									<?php }
								}
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
										<?php _e( 'Mode', 'updater' ); ?>
									</th>
									<td colspan="2"><fieldset>
										<label><input type="radio" name="pdtr_mode" value="0" <?php if ( 0 == $pdtr_options["mode"] ) echo "checked=\"checked\""; ?> /> <?php _e( 'Manual', 'updater' ); ?></label><br />
										<label><input type="radio" name="pdtr_mode" value="1" <?php if ( 1 == $pdtr_options["mode"] ) echo "checked=\"checked\""; ?> /> <?php _e( 'Auto', 'updater' ); ?></label>
									</fieldset></td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( 'Search/update', 'updater' ); ?></th>
									<td colspan="2"><fieldset>
										<label><input type="checkbox" name="pdtr_checkbox_update_core" value="1" <?php if ( 1 == $pdtr_options["update_core"] ) echo 'checked="checked"'; ?> /> <?php _e( 'WordPress', 'updater' ); ?></label><br />
										<label><input type="checkbox" name="pdtr_checkbox_update_plugin" value="1" <?php if ( 1 == $pdtr_options["update_plugin"] ) echo 'checked="checked"'; ?> /> <?php _e( 'Plugins', 'updater' ); ?></label><br />
										<label><input type="checkbox" name="pdtr_checkbox_update_theme" value="1" <?php if ( 1 == $pdtr_options["update_theme"] ) echo 'checked="checked"'; ?> /> <?php _e( 'Themes', 'updater' ); ?></label>
									</fieldset></td>
								</tr>
								<tr valign="top">
									<th><?php _e( 'Search/update frequency', 'updater' ); ?></th>
									<td colspan="2">
										<input type="number" name="pdtr_time" value="<?php echo $pdtr_options["time"]; ?>" min="1" max="99999" /> <?php _e( 'hours', 'updater' ); ?>
										<br />
										<span class="bws_info">(<?php _e( 'It should be integer and it should not contain more than 5 digits.', 'updater' ); ?>)</span>
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e( 'Send email when new versions are available', 'updater' ); ?></th>
									<td colspan="2">
										<input type="checkbox" name="pdtr_send_mail_get_update" value="1" <?php if ( 1 == $pdtr_options["send_mail_get_update"] ) echo "checked=\"checked\""; ?> />
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e( 'Send email after updating', 'updater' ); ?></th>
									<td colspan="2">
										<input type="checkbox" name="pdtr_send_mail_after_update" value="1" <?php if ( 1 == $pdtr_options["send_mail_after_update"] ) echo "checked=\"checked\""; ?> />
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e( 'Recipient email address (To:)', 'updater' ); ?></th>
									<td colspan="2">
										<input type="email" name="pdtr_to_email" maxlength="250" value="<?php echo $pdtr_options["to_email"]; ?>" />
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e( "'FROM' field", 'updater' ); ?></th>
									<td style="width: 200px; vertical-align: top;">
										<div><?php _e( "Name", 'updater' ); ?></div>
										<div><input type="text" name="pdtr_from_name" maxlength="250" value="<?php echo $pdtr_options["from_name"]; ?>" /></div>
									</td>
									<td>
										<div><?php _e( "Email", 'updater' ); ?></div>
										<div>
											<input type="email" name="pdtr_from_email" maxlength="250" value="<?php echo $pdtr_options["from_email"]; ?>" />
										</div>
										<span class="bws_info">(<?php _e( 'If this option is changed, email messages may be moved to the spam folder or email delivery failures may occur.', 'updater' ); ?>)</span>
									</td>
								</tr>
								<?php do_action( 'pdtr_settings_page_action', $pdtr_options ); ?>
							</tbody>
						</table>
						<?php /*pls hide banner */
						if ( ! $bws_hide_premium_options_check ) { ?>
							<div class="bws_pro_version_bloc">
								<div class="bws_pro_version_table_bloc">
									<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'updater' ); ?>"></button>
									<div class="bws_table_bg"></div>
									<table class="form-table bws_pro_version">										
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
										<tr valign="top">
											<th><?php _e( 'Disable auto WordPress update', 'updater' ); ?></th>
											<td>
												<input type="checkbox" disabled name="pdtr_disable_auto_core_update" value="1" />
												<br /><span class="bws_info">( <?php _e( 'Disable updates for WordPress minor versions', 'updater' ); ?> )</span>
											</td>
										</tr>
										<tr valign="top">
											<th><?php _e( 'Envato compatibility', 'updater' ); ?></th>
											<td colspan="2">
												<p class="description"><?php _e( 'Generate your Envato API Personal Token', 'updater' ); ?> <?php _e( 'and insert it below.', 'updater' ); ?></p>
												<input type="text" name="pdtr_envato_token" class="widefat" value="" autocomplete="off" disabled />
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
									<a class="bws_button" href="http://bestwebsoft.com/products/updater/?k=347ed3784e3d2aeb466e546bfec268c0&pn=84&v=<?php echo $pdtr_plugin_info["Version"]; ?>wp_v=<?php echo $wp_version; ?>" target="_blank" title="Updater Pro"><?php _e( 'Learn More', 'updater' ); ?></a>
									<div class="clear"></div>
								</div>
							</div>
						<?php } /* end banner pls*/ ?>
						<p class="submit" id="submit">
							<input type="hidden" name="pdtr_form_submit" value="submit" />
							<input id="bws-submit-button" type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'updater' ); ?>" />
							<?php wp_nonce_field( plugin_basename( __FILE__ ), 'pdtr_nonce_name' ); ?>
						</p>
					</form>
					<h3><?php _e( "Send a test email", 'updater' ); ?></h3>
					<form method="post" action="">
						<input type="hidden" name="pdtr_form_check_mail" value="submit" />
						<p><?php _e( "Here You can make sure that your settings are correct and the email can be delivered.", 'updater' ); ?></p>
						<input type="submit" class="button" value="<?php _e( 'Check email sending', 'updater' ); ?>" />
						<?php wp_nonce_field( plugin_basename( __FILE__ ), 'pdtr_nonce_check_mail' ); ?>
					</form>
					<h3><?php _e( "Restore settings", 'updater' ); ?></h3>
					<?php bws_form_restore_default_settings( plugin_basename( __FILE__ ) );
				}	
			} /*pls go pro tab */ elseif ( isset( $_GET['action'] ) && 'go_pro' == $_GET['action'] ) {
				bws_go_pro_tab_show( $bws_hide_premium_options_check, $pdtr_plugin_info, plugin_basename( __FILE__ ), 'updater-options&action=go_pro', 'updater-pro-options', 'updater-pro/updater_pro.php', 'updater', '347ed3784e3d2aeb466e546bfec268c0', '84', isset( $go_pro_result['pro_plugin_is_activated'] ) );
			}			
			bws_plugin_reviews_block( $pdtr_plugin_info['Name'], 'updater' ); /* show reviews pls*/ ?>
		</div>
	<?php }
}
/* End function pdtr_settings_page */

/* Function for processing the site */
if ( ! function_exists ( 'pdtr_processing_site' ) ) {
	function pdtr_processing_site() {
		global $wp_version;
		$pdtr_updater_list = array();
		/* Include file for get plugins */
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		/* Add the list of installed plugins */
		$wp_list_table_plugins = apply_filters( 'all_plugins', get_plugins() );
		$pdtr_updater_list["plugin_list"]	= $wp_list_table_plugins;

		/* Add the list of plugins, that need to be update */
		$update_plugins	= get_site_transient( 'update_plugins' );
		$plugins		= array();
		if ( ! empty( $update_plugins->response ) ) {
			foreach ( $update_plugins->response as $file => $value ) {
				$value = get_object_vars( $value );
				$plugins[ $file ] = $value;
			}
			$pdtr_updater_list["plugin_need_update"] = $plugins;
		}

		/* Add the list of installed themes */
		$wp_list_table_themes = apply_filters( 'all_themes', wp_get_themes() );
		$pdtr_updater_list["theme_list"]	= $wp_list_table_themes;
		/* Add the list of themes, that need to be update */
		$update_themes 	= get_site_transient( 'update_themes' );
		$themes			= array();
		if ( ! empty( $update_themes->response ) ) {
			foreach ( $update_themes->response as $file => $value ) {
				$themes[ $file ] = $value;
			}
			$pdtr_updater_list["theme_need_update"] = $themes;
		}

		/* Add current core version and the latest version of core */
		$core = get_site_transient( 'update_core' );
		if ( ! empty( $core->updates ) )
			$pdtr_updater_list["core"] = array( "current" => $wp_version, "new" => $core->updates[0]->current );
		return $pdtr_updater_list;
	}
}
/* End function pdtr_processing_site */

/* Function for updating plugins */
if ( ! function_exists ( 'pdtr_update_plugin' ) ) {
	function pdtr_update_plugin( $plugins_list, $automode = false ) {
		/* Update plugins */
		if ( ! empty( $plugins_list ) ) {
			/* Include files for using class Plugin_Upgrader */
			include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
			include_once( ABSPATH . 'wp-admin/includes/file.php' );
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			include_once( ABSPATH . 'wp-admin/includes/update.php' );
			if ( ! class_exists( 'Bulk_Plugin_Upgrader_Skin' ) )
				include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader-skins.php' );
			echo '<h2>' . __( 'Updating plugins...', 'updater' ) . '</h2>';		
			$plugins_list = array_map( 'urldecode', $plugins_list );

			if ( ! $automode )
				iframe_header();

			$upgrader = new Plugin_Upgrader( new Bulk_Plugin_Upgrader_Skin() );
			$upgrader->bulk_upgrade( $plugins_list );

			if ( ! $automode )
				iframe_footer();
		}
	}
}
/* End function pdtr_update_plugin */

/* Function for updating theme */
if ( ! function_exists ( 'pdtr_update_theme' ) ) {
	function pdtr_update_theme( $themes_list, $automode = false ) {
		/*  Update themes */
		if ( ! empty( $themes_list ) ) {
			/* Include files for using class Plugin_Upgrader */
			include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
			include_once( ABSPATH . 'wp-admin/includes/file.php' );
			include_once( ABSPATH . 'wp-admin/includes/theme.php' );
			include_once( ABSPATH . 'wp-admin/includes/update.php' );
			if ( ! class_exists( 'Bulk_Theme_Upgrader_Skin' ) )
				include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader-skins.php' );
			
			echo '<h2>' . __( 'Updating themes...', 'updater' ) . '</h2>';
			$themes_list = array_map( 'urldecode', $themes_list );
		
			if ( ! $automode )
				iframe_header();
			
			$theme_upgrader = new Theme_Upgrader( new Bulk_Theme_Upgrader_Skin() );
			$theme_upgrader->bulk_upgrade( $themes_list );

			if ( ! $automode )
				iframe_footer();
		}
	}
}
/* End function pdtr_update_theme */

/* Function for updating WP core */
if ( ! function_exists ( 'pdtr_update_core' ) ) {
	function pdtr_update_core( $automode = false ) {
		global $wp_filesystem, $wp_version;

		if ( ! $automode )
			echo '<h2>' . __( 'Updating WordPress...', 'updater' ) . '</h2>';
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

		add_filter( 'update_feedback', 'show_message' );
		include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		$upgrader = new Core_Upgrader();
		if ( '4.1' > $wp_version )
			$result = @$upgrader->upgrade( $update );
		else
			$result = @$upgrader->upgrade( $update, array( 'allow_relaxed_file_ownership' => true ) );

		if ( is_wp_error( $result ) ) {
			if ( ! $automode ) {
				show_message( $result );
				if ( 'up_to_date' != $result->get_error_code() )
					show_message( __( 'Update Failed', 'updater' ) );
			}
			return false;
		}
		if ( ! $automode )
			show_message( __( 'WordPress was updated successfully!', 'updater' ) );
		/* Check version and set option 'update_core' */
		wp_version_check();

		return true;
	}
}
/* End function pdtr_update_core */

/* Function for sending email after update */
if ( ! function_exists ( 'pdtr_notification_after_update' ) ) {
	function pdtr_notification_after_update( $plugins_list, $themes_list, $core ) {
		global $pdtr_options;

		$pdtr_updater_list = pdtr_processing_site();
		$network = is_multisite() ? 'network/' : '';
		$subject	=	esc_html__( 'The Updater plugin made the updates at the site', 'updater' ) .  ' ' . esc_attr( get_bloginfo( 'name', 'display' ) );
		$message	=	'<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
						<body>
						<h3>' . __( 'Hello!', 'updater' ) . '</h3>' .
						esc_html__( 'The Updater plugin is being run on your website', 'updater' ) . ' <a href=' . home_url() . '>' . esc_attr( get_bloginfo( 'name', 'display' ) ) . '</a>.<br/><br/>' ;

		if ( ! empty( $plugins_list ) ) {
			$message .= '<strong> - ' . __( 'These plugins were updated:', 'updater' ) . '</strong><ul>';
			foreach ( $plugins_list as $key => $value ) {
				$name = explode( "/", $value );
				$message .= '<li>' . $name[0] . ' - ' . __( 'to the version', 'updater' ) . ' ' . $pdtr_updater_list["plugin_list"][ $value ]["Version"] . ';</li>';
			}
			$message .= '</ul><br/>';
		}

		if ( ! empty( $themes_list ) ) {
			$message .= '<strong> - ' . __( 'These themes were updated:', 'updater' ) . '</strong><ul>';
			foreach ( $themes_list as $key => $value ) {
				$name = explode( "/", $value );
				$message .= '<li>' . $name[0] . ' - ' . __( 'to the version', 'updater' ) . ' ' . $pdtr_updater_list["theme_list"][ $value ]["Version"] . ';</li>';
			}
			$message .= '</ul><br/>';
		}

		if ( true === $core ) {
			$message .= '<strong> - ' . __( 'WordPress was updated to the version', 'updater' ) . ' ' . $pdtr_updater_list["core"]["new"] . '.</strong><br/><br/>';
		} elseif ( false === $core ) {
			$message .= '<strong> - ' . __( "We couldn't update WordPress Core in the site", 'updater' ) . '.</strong><br/><br/>';
		}

		$message .= __( 'If you want to change the plugin mode or other settings you should go here:', 'updater' ) .
				' <a href=' . admin_url( '/' ) . $network . 'admin.php?page=updater-options&action=settings> ' . __( 'the Updater settings page on your website.', 'updater' ) . '</a>
				<br/><br/>----------------------------------------<br/><br/>' .
				esc_html__( 'Thanks for using the plugin', 'updater' ) . ' <a href="http://bestwebsoft.com/products/updater/">Updater</a>!</body></html>';

		if ( ! empty( $pdtr_options["to_email"] ) )
			$email = $pdtr_options["to_email"];
		else {
			$email = is_multisite() ? get_site_option( 'admin_email' ) : get_option( 'admin_email' );
		}

		$from_email = ( ! empty( $pdtr_options["from_email"] ) ) ? $pdtr_options["from_email"] : $email;
		$from_name = ( ! empty( $pdtr_options["from_name"] ) ) ? htmlspecialchars_decode( $pdtr_options["from_name"] ) : esc_attr( get_bloginfo( 'name', 'display' ) );

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
	function pdtr_notification_exist_update( $plugins_list, $themes_list, $core, $test = false ) {
		global $pdtr_options, $pdtr_updater_list;
		/* Get information about WP core and installed plugins from the website */
		$network = is_multisite() ? 'network/' : '';
		$subject	=	esc_html__( 'Check for updates on', 'updater' ) . ' ' . esc_attr( get_bloginfo( 'name', 'display' ) );
		$message	=	'<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
					<body>
					<h3>' . __( 'Hello!', 'updater' ) . '</h3>' .
					esc_html__( 'The Updater plugin is being run on your website', 'updater' ) . ' <a href=' . home_url() . '>' . esc_attr( get_bloginfo( 'name', 'display' ) ) . '</a>.';

		if ( ! empty( $themes_list ) || ! empty( $plugins_list ) || false != $core )
			$message .= ' ' . __( 'The files that need update are:', 'updater' );

		$message .= '<br/><br/>';

		if ( ! empty( $plugins_list ) ) {
			$message .= '<strong> - ' . __( 'These plugins can be updated:', 'updater' ) . '</strong><ul>';
			foreach ( $plugins_list as $key => $value ) {
				$name = explode( "/", $value );
				$message .= '<li>' . $name[0] . ' - ' . __( 'to the version', 'updater' ) . ' ' . $pdtr_updater_list["plugin_need_update"][ $value ]["new_version"] .
						 ' ('. __( 'the current version is', 'updater' ) . ' ' . $pdtr_updater_list["plugin_list"][ $value ]["Version"] . ');</li>';
			}
			$message .= '</ul>';
		}

		if ( ! empty( $themes_list ) ) {
			$message .= '<strong> - ' . __( 'These themes can be updated:', 'updater' ) . '</strong><ul>';
			foreach ( $themes_list as $key => $value ) {
				$name = explode( "/", $value );
				$message .= '<li>' . $name[0] . ' - ' . __( 'to the version', 'updater' ) . ' ' . $pdtr_updater_list["theme_need_update"][ $value ]["new_version"] .
						 ' ('. __( 'the current version is', 'updater' ) . ' ' . $pdtr_updater_list["theme_list"][ $value ]["Version"] . ');</li>';
			}
			$message .= '</ul>';
		}

		if ( true === $core ) {
			$message .= '<strong> - ' . __( 'WordPress can be updated to the version', 'updater' ) . ' ' . $pdtr_updater_list["core"]["new"] .
					 ' (' . __( 'the current version is', 'updater' ) . ' ' . $pdtr_updater_list["core"]["current"] . ').</strong><br/>';
		}

		if ( false === $test ) {
			if ( 0 == $pdtr_options["mode"] ) {
				$message .= '<br/>' . __( 'Please use this link to update:', 'updater' ) . ' <a href=' . admin_url( '/' ) . $network . 'admin.php?page=updater-options' . '> ' . __( 'the Updater page on your website.', 'updater' ) . '</a>';
			} else {
				$message .= '<br/>' . __( 'The Updater plugin starts updating these files.', 'updater' );
			}
		} elseif ( ! empty( $themes_list ) || ! empty( $plugins_list ) || false != $core ) {
			$message .= '<br/>' . __( 'Please use this link to update:', 'updater' ) . ' <a href=' . admin_url( '/' ) . $network . 'admin.php?page=updater-options' . '> ' . __( 'the Updater page on your website.', 'updater' ) . '</a>';
		}

		if ( empty( $themes_list ) && empty( $plugins_list ) && false == $core ) {
			$message .= __( 'Congratulations! Your plugins, themes and WordPress have the latest updates!', 'updater' );
		}

		$message .= '<br/><br/>' . __( 'If you want to change type of mode for the plugin or other settings you should go here:', 'updater' ) .
				' <a href=' . admin_url( '/' ) . $network . 'admin.php?page=updater-options&action=settings> ' . __( 'the Updater settings page on your website.', 'updater' ) . '</a>
				<br/><br/>----------------------------------------<br/><br/>' .
				esc_html__( 'Thanks for using the plugin', 'updater' ) . ' <a href="http://bestwebsoft.com/products/updater/">Updater</a>!</body></html>';

		if ( ! empty( $pdtr_options["to_email"] ) )
			$email = $pdtr_options["to_email"];
		else
			$email = is_multisite() ? get_site_option( 'admin_email' ) : get_option( 'admin_email' );

		$from_email = ( ! empty( $pdtr_options["from_email"] ) ) ? $pdtr_options["from_email"] : $email;
		$from_name = ( ! empty( $pdtr_options["from_name"] ) ) ? htmlspecialchars_decode( $pdtr_options["from_name"] ) : esc_attr( get_bloginfo( 'name', 'display' ) );

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
		if ( isset( $_GET['page'] ) && 'updater-options' == $_GET['page'] ) {
			wp_enqueue_script( 'jquery' );
			if ( isset( $_REQUEST["checked_core"] ) || isset( $_REQUEST["checked_plugin"] ) || isset( $_REQUEST["checked_theme"] ) )
				wp_enqueue_script( 'updates' );
			wp_enqueue_style( 'pdtr_stylesheet', plugins_url( 'css/style.css', __FILE__ ) );
			wp_enqueue_script( 'pdtr_script', plugins_url( 'js/script.js', __FILE__ ) );
		}
	}
}

/* Function that update all plugins and WP core. It will be executed every hour if enabled auto mode */
if ( ! function_exists ( 'pdtr_auto_function' ) ) {
	function pdtr_auto_function() {
		global $pdtr_options, $pdtr_updater_list;
		$plugin_update_list = $theme_update_list = array();
		$core = false;

		if ( empty( $pdtr_options ) )
			$pdtr_options = is_multisite() ? get_site_option( 'pdtr_options' ) : get_option( 'pdtr_options' ) ;

		$pdtr_updater_list	=	pdtr_processing_site();

		if ( $pdtr_updater_list["core"]["current"] != $pdtr_updater_list["core"]["new"] && 1 == $pdtr_options["update_core"] )
			$core = true;

		if ( isset( $pdtr_updater_list["plugin_need_update"] ) && 1 == $pdtr_options["update_plugin"] ) {
			foreach ( $pdtr_updater_list["plugin_need_update"] as $key => $value) {
				$plugin_update_list[] = $key;
			}
		}

		if ( isset( $pdtr_updater_list["theme_need_update"] ) && 1 == $pdtr_options["update_theme"] ) {
			foreach ( $pdtr_updater_list["theme_need_update"] as $key => $value) {
				$theme_update_list[] = $key;
			}
		}

		if ( 1 == $pdtr_options["send_mail_get_update"] && ( ! empty( $theme_update_list ) || ! empty( $plugin_update_list ) || false != $core ) ) {
			pdtr_notification_exist_update( $plugin_update_list, $theme_update_list, $core );
		}

		if ( 1 == $pdtr_options["mode"] ) {
			/* If WP core need to be update */
			if ( false != $core )
				$core_result = pdtr_update_core( true ); /* update the WP core */
			/* Update the list of plugins */
			if ( ! empty( $plugin_update_list ) ) {
				pdtr_update_plugin( $plugin_update_list, true );
			}
			/* Update the list of themes */
			if ( ! empty( $theme_update_list ) ) {
				pdtr_update_theme( $theme_update_list, true );
			}
			/* Send mail */
			if ( 1 == $pdtr_options["send_mail_after_update"] && ( ! empty( $theme_update_list ) || ! empty( $plugin_update_list ) ||  false != $core ) ) {
				pdtr_notification_after_update( $plugin_update_list, $theme_update_list, $core_result );
			}
		}

		wp_clear_scheduled_hook( 'pdtr_auto_hook' );

		$time = ( ! empty( $pdtr_options['time'] ) ) ? time()+$pdtr_options['time']*60*60 : time()+12*60*60;
		wp_schedule_event( $time, 'pdtr_schedules_hours', 'pdtr_auto_hook' );
	}
}
/* End function pdtr_auto_function */

/* add help tab  */
if ( ! function_exists( 'pdtr_add_tabs' ) ) {
	function pdtr_add_tabs() {
		$screen = get_current_screen();
		$args = array(
			'id' 			=> 'pdtr',
			'section' 		=> '200538859'
		);
		bws_help_tab( $screen, $args );
	}
}

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
			$links[]	=	'<a href="http://bestwebsoft.com/products/updater/faq/" target="_blank">' . __( 'FAQ', 'updater' ) . '</a>';
			$links[]	=	'<a href="http://support.bestwebsoft.com">' . __( 'Support', 'updater' ) . '</a>';
		}
		return $links;
	}
}
/* End function pdtr_register_plugin_links */

if ( ! function_exists( 'pdtr_plugin_banner' ) ) {
	function pdtr_plugin_banner() {
		global $hook_suffix, $pdtr_plugin_info;
		if ( 'plugins.php' == $hook_suffix ) {
			/*pls show banner go pro */
			global $pdtr_options;
			if ( empty( $pdtr_options ) )
				$pdtr_options = is_multisite() ? get_site_option( 'pdtr_options' ) : get_option( 'pdtr_options' );
			if ( isset( $pdtr_options['first_install'] ) && strtotime( '-1 week' ) > $pdtr_options['first_install'] )
				bws_plugin_banner( $pdtr_plugin_info, 'pdtr', 'updater', '0b6882b0c99c2776d06c375dc22b5869', '84', '//ps.w.org/updater/assets/icon-128x128.png' );
			
			/* show banner go settings pls*/
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
		if ( isset( $_REQUEST['page'] ) && 'updater-options' == $_REQUEST['page'] ) {
			bws_plugin_suggest_feature_banner( $pdtr_plugin_info, 'pdtr_options', 'updater' );
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
		if ( ! function_exists( 'get_plugins' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$all_plugins = get_plugins();

		if ( ! array_key_exists( 'updater-pro/updater_pro.php', $all_plugins ) && ! array_key_exists( 'updater-plus/updater-plus.php', $all_plugins ) ) {
			delete_option( 'pdtr_options' );
			delete_site_option( 'pdtr_options' );
		}

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );
		bws_delete_plugin( plugin_basename( __FILE__ ) );
	}
}

/* When activate plugin */
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
/* Function that update all plugins, themes and WP core in auto mode. */
add_action( 'pdtr_auto_hook', 'pdtr_auto_function' );

add_action( 'admin_notices', 'pdtr_plugin_banner' );

/* When deactivate plugin */
register_deactivation_hook( __FILE__, 'pdtr_deactivation' );
/* When uninstall plugin */
register_uninstall_hook( __FILE__, 'pdtr_uninstall' );
