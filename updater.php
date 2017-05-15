<?php
/*
Plugin Name: Updater by BestWebSoft
Plugin URI: https://bestwebsoft.com/products/wordpress/plugins/updater/
Description: Automatically check and update WordPress website core with all installed plugins and themes to the latest versions.
Author: BestWebSoft
Text Domain: updater
Domain Path: /languages
Version: 1.36
Author URI: https://bestwebsoft.com/
License: GPLv2 or later
*/

/*
	© Copyright 2017  BestWebSoft  ( https://support.bestwebsoft.com )

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
		global $submenu, $pdtr_plugin_info, $wp_version;

		$tools = add_menu_page( 'Updater', 'Updater', 'manage_options', 'updater', 'pdtr_settings_page', 'none' );
		add_submenu_page( 'updater', 'Updater', __( 'Installed Software', 'updater' ), 'manage_options', 'updater', 'pdtr_settings_page' );		
		$settings = add_submenu_page( 'updater', 'Updater', __( 'Settings', 'updater' ), 'manage_options', 'updater-options', 'pdtr_settings_page' );

		add_submenu_page( 'updater', 'BWS Panel', 'BWS Panel', 'manage_options', 'pdtr-bws-panel', 'bws_add_menu_render' );

		/*pls */
		if ( isset( $submenu['updater'] ) )
			$submenu['updater'][] = array( 
				'<span style="color:#d86463"> ' . __( 'Upgrade to Pro', 'updater' ) . '</span>',
				'manage_options',
				'https://bestwebsoft.com/products/wordpress/plugins/updater/?k=347ed3784e3d2aeb466e546bfec268c0pn&pn=84&v=' . $pdtr_plugin_info["Version"] . '&wp_v=' . $wp_version );
		/* pls*/
		add_action( 'load-' . $settings, 'pdtr_add_tabs' );
		add_action( 'load-' . $tools, 'pdtr_add_tabs' );
	}
}

if ( ! function_exists( 'pdtr_plugins_loaded' ) ) {
	function pdtr_plugins_loaded() {
		/* Internationalization */
		load_plugin_textdomain( 'updater', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

if ( ! function_exists( 'pdtr_init' ) ) {
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
		bws_wp_min_version_check( plugin_basename( __FILE__ ), $pdtr_plugin_info, '3.9' );
	}
}

if ( ! function_exists( 'pdtr_admin_init' ) ) {
	function pdtr_admin_init() {
		global $bws_plugin_info, $pdtr_plugin_info;

		if ( empty( $bws_plugin_info ) )
			$bws_plugin_info = array( 'id' => '84', 'version' => $pdtr_plugin_info["Version"] );

		/* Call register settings function */
		if ( isset( $_GET['page'] ) && ( "updater-options" == $_GET['page'] || $_REQUEST['page'] == 'updater' ) )
			pdtr_register_settings();
	}
}

/* Register settings function */
if ( ! function_exists( 'pdtr_register_settings' ) ) {
	function pdtr_register_settings() {
		global $pdtr_options, $pdtr_plugin_info;

		if ( is_multisite() ) {
			if ( ! get_site_option( 'pdtr_options' ) ) {
				$options_default = pdtr_get_options_default();
				add_site_option( 'pdtr_options', $options_default );
			}
		} else {
			/* Install the option defaults */
			if ( ! get_option( 'pdtr_options' ) ) {
				$options_default = pdtr_get_options_default();
				add_option( 'pdtr_options', $options_default );
			}
		}

		/* Get options from the database */
		$pdtr_options = is_multisite() ? get_site_option( 'pdtr_options' ) : get_option( 'pdtr_options' );

		/* Array merge incase this version has added new options */
		if ( ! isset( $pdtr_options['plugin_option_version'] ) || $pdtr_options['plugin_option_version'] != $pdtr_plugin_info["Version"] ) {

			$options_default = pdtr_get_options_default();
			$pdtr_options = array_merge( $options_default, $pdtr_options );
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

if ( ! function_exists( 'pdtr_get_options_default' ) ) {
	function pdtr_get_options_default() {
		global $pdtr_plugin_info;

		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}
		$from_email = 'wordpress@' . $sitename;

		$options_default = array(
			'plugin_option_version' 	=>	$pdtr_plugin_info["Version"],
			'first_install'				=>	strtotime( "now" ),
			'display_settings_notice'	=>	1,
			'suggest_feature_banner'	=>	1,
			'mode'						=>	0,
			'send_mail_after_update'	=>	1,
			'send_mail_get_update'		=>	1,
			'time'						=>	12,
			'to_email'					=>	get_option( 'admin_email' ),
			'to_email_type'				=> 'custom',
			'from_name'					=>	get_bloginfo( 'name' ),
			'from_email'				=>	$from_email,			
			'update_core'				=>	1,
			'update_plugin'				=>	1,
			'update_theme'				=>	1
		);
		return $options_default;
	}
}

if ( ! function_exists( 'pdtr_activation' ) ) {
	function pdtr_activation() {
		global $pdtr_options;
		/* Get options from the database */
		pdtr_register_settings();
		if ( ! empty( $pdtr_options ) && ( 0 != $pdtr_options["mode"] || 0 != $pdtr_options["send_mail_get_update"] ) ) {
			$time = ( ! empty( $pdtr_options['time'] ) ) ? time() + $pdtr_options['time']*60*60 : time() + 12*60*60;
			wp_schedule_event( $time, 'pdtr_schedules_hours', 'pdtr_auto_hook' );
		}

		/* When deactivate plugin */
		register_deactivation_hook( __FILE__, 'pdtr_deactivation' );
		/* When uninstall plugin */
		register_uninstall_hook( __FILE__, 'pdtr_uninstall' );
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
if ( ! function_exists( 'pdtr_settings_page' ) ) {
	function pdtr_settings_page() {
		global $pdtr_plugin_info; ?>
		<div class="wrap" id="pdtr_wrap">			
			<?php if ( 'updater-options' == $_GET['page'] ) { /* Showing settings tab */
				require_once( dirname( __FILE__ ) . '/includes/class-pdtr-settings.php' );
				$page = new Pdtr_Settings_Tabs( plugin_basename( __FILE__ ) ); ?>
				<h1>Updater <?php _e( 'Settings', 'updater' ); ?></h1>
				<?php $page->display_content();
			} else { ?>
				<h1><?php _e( 'Software', 'updater' ); ?></h1>
				<div class="error notice inline">
					<p><strong><?php _e( 'We strongly recommend you to backup your website and WordPress database before updating! We are not responsible for the site running after updates.', 'updater' ); ?></strong></p>
				</div>
				<?php require_once( dirname( __FILE__ ) . '/includes/software-table.php' );
				pdtr_display_table();
				/*pls */
				bws_plugin_reviews_block( $pdtr_plugin_info['Name'], 'updater' );
				/* pls*/
			} ?>
		</div>
	<?php }
}

/* Function for processing the site */
if ( ! function_exists( 'pdtr_processing_site' ) ) {
	function pdtr_processing_site() {
		global $wp_version, $pdtr_options;

		$updater_list = array();
		/* Include file for get plugins */
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		

		/* Add the list of plugins, that need to be update */
		if ( 1 == $pdtr_options["update_plugin"] ) {
			/* Add the list of installed plugins */
			$wp_list_table_plugins = apply_filters( 'all_plugins', get_plugins() );

			foreach ( $wp_list_table_plugins as $key => $value ) {
				$updater_list[ $key ] = array(
					'type'			=> 'plugin',
					'wp_key'		=> $key,
					'name'			=> $value["Name"],
					'version'		=> $value['Version'],
					'new_version' 	=> ''
				);
			}

			$update_plugins	= get_site_transient( 'update_plugins' );

			if ( ! empty( $update_plugins->response ) ) {
				foreach ( $update_plugins->response as $file => $value ) {
					$value = get_object_vars( $value );
					if ( isset( $updater_list[ $file ] ) ) {
						$updater_list[ $file ]["new_version"] = $value["new_version"];
						if ( ! empty( $value["slug"] ) )
							$updater_list[ $file ]['url'] = esc_url( self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $value["slug"] . '&section=changelog&TB_iframe=true&width=772&height=930' ) );
					}
				}
			}
		}

		/* Add the list of installed themes */
		if ( 1 == $pdtr_options["update_theme"] ) {
			$wp_list_table_themes = apply_filters( 'all_themes', wp_get_themes() );
			foreach ( $wp_list_table_themes as $key => $value ) {
				$updater_list[ $key ] = array(
					'type'			=> 'theme',
					'wp_key'		=> $key,
					'name'			=> $value["Name"],
					'version'		=> $value['Version'],
					'new_version' 	=> ''
				);
			}
			/* Add the list of themes, that need to be update */
			$update_themes 	= get_site_transient( 'update_themes' );
			if ( ! empty( $update_themes->response ) ) {
				foreach ( $update_themes->response as $file => $value ) {
					if ( isset( $updater_list[ $file ] ) ) {
						$updater_list[ $file ]['new_version'] = $value["new_version"];
						if ( ! empty( $value['url'] ) )
							$updater_list[ $file ]['url'] = esc_url( add_query_arg( array( 'TB_iframe' => 'true', 'width' => 1024, 'height' => 800 ), $value['url'] ) );
					}
				}
			}
		}

		/* Add current core version and the latest version of core */
		if ( 1 == $pdtr_options["update_core"] ) {
			$core = get_site_transient( 'update_core' );
			if ( ! empty( $core->updates ) )
				$updater_list['core'] = array(
					'type'			=> 'core',
					'wp_key'		=> 'wp_core',
					'name'			=> 'WordPress',
					'version'		=> $wp_version,
					'new_version' 	=> $core->updates[0]->current
				);
		}
		
		return $updater_list;
	}
}

/* Function for updating plugins */
if ( ! function_exists( 'pdtr_update_plugin' ) ) {
	function pdtr_update_plugin( $plugins_list, $automode = false ) {
		/* Update plugins */
		if ( ! empty( $plugins_list ) ) {
			/* Include files for using class Plugin_Upgrader */
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			if ( ! class_exists( 'Bulk_Plugin_Upgrader_Skin' ) )
				include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader-skins.php' );

			$plugins_list = array_map( 'urldecode', $plugins_list );

			if ( ! $automode ) {
				echo '<h2>' . __( 'Updating Plugins...', 'updater' ) . '</h2>';
			}

			$upgrader = new Plugin_Upgrader( new Bulk_Plugin_Upgrader_Skin() );
			$upgrader->bulk_upgrade( $plugins_list );

			if ( ! $automode )
				iframe_footer();
		}
	}
}

/* Function for updating theme */
if ( ! function_exists( 'pdtr_update_theme' ) ) {
	function pdtr_update_theme( $themes_list, $automode = false ) {
		/*  Update themes */
		if ( ! empty( $themes_list ) ) {
			/* Include files for using class Plugin_Upgrader */
			include_once( ABSPATH . 'wp-admin/includes/theme.php' );
			if ( ! class_exists( 'Bulk_Theme_Upgrader_Skin' ) )
				include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader-skins.php' );
			
			$themes_list = array_map( 'urldecode', $themes_list );
		
			if ( ! $automode ) {
				echo '<h2>' . __( 'Updating Themes...', 'updater' ) . '</h2>';
			}
			
			$theme_upgrader = new Theme_Upgrader( new Bulk_Theme_Upgrader_Skin() );
			$theme_upgrader->bulk_upgrade( $themes_list );

			if ( ! $automode )
				iframe_footer();
		}
	}
}

/* Function for updating WP core */
if ( ! function_exists( 'pdtr_update_core' ) ) {
	function pdtr_update_core( $automode = false ) {
		global $wp_filesystem, $wp_version;

		if ( ! $automode )
			echo '<h2>' . __( 'Updating WordPress...', 'updater' ) . '</h2>';

		$url = wp_nonce_url( 'update-core.php?action=do-core-upgrade', 'upgrade-core' );
		if ( false === ( $credentials = request_filesystem_credentials( $url, '', false, ABSPATH ) ) )
			return false;

		$url = wp_nonce_url( 'admin.php?page=updater-options', 'upgrade-core' );
		if ( false === ( $credentials = request_filesystem_credentials( $url, '', false, ABSPATH ) ) )
			return false;

		$from_api	= get_site_transient( 'update_core' );
		$updates	= $from_api->updates;
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

/* Function for sending email after update */
if ( ! function_exists( 'pdtr_notification_after_update' ) ) {
	function pdtr_notification_after_update( $plugins_list, $themes_list, $core_for_update, $core_result ) {
		global $pdtr_options, $wp_version;

		$updater_list = pdtr_processing_site();

		$subject = sprintf( __( 'Updating on %s', 'updater' ), esc_attr( get_bloginfo( 'name', 'display' ) ) );

		$message = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
			<body>
			<h3>' . __( 'Hello!', 'updater' ) . '</h3>' .
			sprintf( __( 'Updater plugin is run on your website %s.', 'updater' ), '<a href=' . home_url() . '>' . esc_attr( get_bloginfo( 'name', 'display' ) ) . '</a>' ) .
			'<br/><br/>';

		if ( ! empty( $plugins_list ) ) {
			$message .= '<strong>' . __( 'Plugin(s)', 'updater' ) . ':</strong><ul>';
			foreach ( $plugins_list as $value ) {
				$name = explode( "/", $value );
				$message .= '<li>' . $name[0] . ' - ' . sprintf( __( 'updated to the version %s', 'updater' ), $updater_list[ $value ]["version"] ) . ';</li>';
			}
			$message .= '</ul><br/>';
		}

		if ( ! empty( $themes_list ) ) {
			$message .= '<strong>' . __( 'Theme(s)', 'updater' ) . ':</strong><ul>';
			foreach ( $themes_list as $value ) {
				$name = explode( "/", $value );
				$message .= '<li>' . $name[0] . ' - ' . sprintf( __( 'updated to the version %s', 'updater' ), $updater_list[ $value ]["version"] ) . ';</li>';
			}
			$message .= '</ul><br/>';
		}
		if ( $core_for_update ) {
			$message .= '<strong>' . __( 'WordPress', 'updater' ) . ':</strong><ul><li>';
			if ( true === $core_result ) {
				$message .= __( 'Version', 'updater' ) . ' ' . $wp_version . '.';
			} else {
				$message .= __( "WordPress can’t be updated on your website.", 'updater' );
			}
			$message .= '</li></ul><br/>';
		}

		$message .= sprintf( __( 'If you want to change the type of the updating mode or other settings, please go to the %s.', 'updater' ), '<a href=' . network_admin_url( 'admin.php?page=updater-options' ) . '>' . __( 'plugin settings page', 'updater' ) . '</a>' ) .
			'<br/><br/>----------------------------------------<br/><br/>' .
			sprintf( __( 'Thanks for using %s!', 'updater' ), '<a href="https://bestwebsoft.com/products/wordpress/plugins/updater/">Updater</a>' ) . '</body></html>';

		if ( 'default' == $pdtr_options["to_email_type"] ) {
			$emails = array();
			foreach ( $pdtr_options["to_email"] as $userlogin ) {
				$user = get_user_by( 'login', $userlogin );
				if ( false !== $user )
					$emails[] = $user->user_email;
			}
		} else {
			if ( preg_match( '|,|', $pdtr_options["to_email"] ) ) {
				$emails = explode( ',', $pdtr_options["to_email"] );
			} else {
				$emails = array();
				$emails[] = $pdtr_options["to_email"];
			}
		}

		$headers = 'From: ' . $pdtr_options["from_name"] . ' <' . $pdtr_options["from_email"] . ">\n" . 
			'Content-type: text/html; charset=utf-8' . "\n";

		if ( ! function_exists( 'is_plugin_active' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'email-queue/email-queue.php' ) && mlq_if_mail_plugin_is_in_queue( plugin_basename( __FILE__ ) ) ) {
			foreach ( $emails as $email ) {			
				/* if email-queue plugin is active and this plugin's "in_queue" status is 'ON' */
				global $mlq_mail_result;
				do_action( 'pdtr_get_mail_data', plugin_basename( __FILE__ ), $email, $subject, $message, $headers );
				/* return $mail_result = true if email-queue has successfully inserted mail in its DB; in other case - return false */
				$mail_result = $mlq_mail_result;
				if ( ! $mail_result )
					break;
			}
		} else {
			$mail_result = wp_mail( $emails, $subject, $message, $headers );
		}

		return $mail_result;
	}
}

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
if ( ! function_exists( 'pdtr_notification_exist_update' ) ) {
	function pdtr_notification_exist_update( $plugins_list, $themes_list, $core, $test = false ) {
		global $pdtr_options, $pdtr_updater_list;

		$subject = sprintf( __( 'Check for Updates on %s', 'updater' ), esc_attr( get_bloginfo( 'name', 'display' ) ) );
		$message = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
					<body>
					<h3>' . __( 'Hello!', 'updater' ) . '</h3>' .
					sprintf( __( 'Updater plugin is run on your website %s.', 'updater' ), ' <a href=' . home_url() . '>' . esc_attr( get_bloginfo( 'name', 'display' ) ) . '</a>' );

		if ( ! empty( $themes_list ) || ! empty( $plugins_list ) || false != $core )
			$message .= ' ' . __( 'The following files need to be updated:', 'updater' );

		$message .= '<br/><br/>';

		if ( ! empty( $plugins_list ) ) {
			$message .= '<strong>' . __( 'Plugin(s)', 'updater' ) . ':</strong><ul>';
			foreach ( $plugins_list as $value ) {
				$name = explode( "/", $value );
				$message .= '<li>' . $pdtr_updater_list[ $value ]['name'] . ' - ' . sprintf( __( 'to the version %s', 'updater' ), $pdtr_updater_list[ $value ]["new_version"] ) .
						 ' ('. sprintf( __( 'the current version is %s', 'updater' ), $pdtr_updater_list[ $value ]["version"] ) . ');</li>';
			}
			$message .= '</ul>';
		}

		if ( ! empty( $themes_list ) ) {
			$message .= '<strong>' . __( 'Theme(s)', 'updater' ) . ':</strong><ul>';
			foreach ( $themes_list as $value ) {
				$name = explode( "/", $value );
				$message .= '<li>' . $pdtr_updater_list[ $value ]['name'] . ' - ' . sprintf( __( 'to the version %s', 'updater' ), $pdtr_updater_list[ $value ]["new_version"] ) .
						 ' (' . sprintf( __( 'the current version is %s', 'updater' ), $pdtr_updater_list[ $value ]["version"] ) . ');</li>';
			}
			$message .= '</ul>';
		}

		if ( true === $core ) {
			$message .= '<strong>' . __( 'WordPress', 'updater' ) . ':</strong><ul><li>' . sprintf( __( 'Version %s is available', 'updater' ), $pdtr_updater_list['core']["new_version"] ) . ' (' . sprintf( __( 'the current version is %s', 'updater' ), $pdtr_updater_list["core"]["version"] ) . ').</li></ul>';
		}

		if ( false === $test ) {
			if ( 0 == $pdtr_options["mode"] ) {
				$message .= '<br/>' . __( 'To start the updating, please follow the link', 'updater' ) . ' - <a href=' . network_admin_url( 'admin.php?page=updater' ) . '>' . __( 'Updater page on your website', 'updater' ) . '</a>.';
			} else {
				$message .= '<br/>' . __( 'Updater plugin starts updating these files.', 'updater' );
			}
		} elseif ( ! empty( $themes_list ) || ! empty( $plugins_list ) || false != $core ) {
			$message .= '<br/>' . __( 'To start the updating, please follow the link', 'updater' ) . ' - <a href=' . network_admin_url( 'admin.php?page=updater' ) . '>' . __( 'Updater page on your website', 'updater' ) . '</a>.';
		}

		if ( empty( $themes_list ) && empty( $plugins_list ) && false == $core ) {
			$message .= __( 'Congratulations! Your plugins, themes and WordPress have the latest versions!', 'updater' );
		}

		$message .= '<br/><br/>' .
			sprintf( __( 'If you want to change the type of the updating mode or other settings, please go to the %s.', 'updater' ), '<a href=' . network_admin_url( 'admin.php?page=updater-options' ) . '>' . __( 'plugin settings page', 'updater' ) . '</a>' ) .
			'<br/><br/>----------------------------------------<br/><br/>' .
			sprintf( __( 'Thanks for using %s!', 'updater' ), '<a href="https://bestwebsoft.com/products/wordpress/plugins/updater/">Updater</a>' ) . '</body></html>';

		if ( 'default' == $pdtr_options["to_email_type"] ) {
			$emails = array();
			foreach ( $pdtr_options["to_email"] as $userlogin ) {
				$user = get_user_by( 'login', $userlogin );
				if ( false !== $user )
					$emails[] = $user->user_email;
			}
		} else {
			if ( preg_match( '|,|', $pdtr_options["to_email"] ) ) {
				$emails = explode( ',', $pdtr_options["to_email"] );
			} else {
				$emails = array();
				$emails[] = $pdtr_options["to_email"];
			}
		}

		$headers = 'From: ' . $pdtr_options["from_name"] . ' <' . $pdtr_options["from_email"] . ">\n" . 
			'Content-type: text/html; charset=utf-8' . "\n";

		if ( ! function_exists( 'is_plugin_active' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		if ( is_plugin_active( 'email-queue/email-queue.php' ) && mlq_if_mail_plugin_is_in_queue( 'updater/updater.php' ) ) {
			foreach ( $emails as $email ) {
				/* if email-queue plugin is active and this plugin's "in_queue" status is 'ON' */
				global $mlq_mail_result;
				do_action( 'pdtr_get_mail_data', 'updater/updater.php', $email, $subject, $message, $headers );
				/* return $mail_result = true if email-queue has successfully inserted mail in its DB; in other case - return false */
				$mail_result = $mlq_mail_result;
				if ( ! $mail_result )
					break;
			} 
		} else {
			$mail_result = wp_mail( $emails, $subject, $message, $headers );
		}
		return $mail_result;
	}
}
/* End function pdtr_notification_exist_update */

/* Add css-file to the plugin */
if ( ! function_exists( 'pdtr_admin_head' ) ) {
	function pdtr_admin_head() {
		global $hook_suffix;

		wp_enqueue_style( 'pdtr_style', plugins_url( 'css/style.css', __FILE__ ) );

		if ( isset( $_REQUEST['page'] ) && ( $_REQUEST['page'] == 'updater-options' || $_REQUEST['page'] == 'updater' ) ) {
			wp_enqueue_script( 'jquery' );
			add_thickbox();

			if ( ( isset( $_POST['action'] ) && 'update' == $_POST['action'] ) || ( isset( $_POST['action2'] ) && 'update' == $_POST['action2'] ) || ( isset( $_POST['pdtr_tab_action'] ) && 'update' == $_POST['pdtr_tab_action'] ) )
				wp_enqueue_script( 'updates' );

			wp_enqueue_script( 'pdtr_script', plugins_url( 'js/script.js', __FILE__ ) );

			bws_enqueue_settings_scripts();
		} elseif ( $hook_suffix == 'plugin-install.php' ) {
			wp_enqueue_script( 'pdtr_script', plugins_url( 'js/script.js', __FILE__ ) );
		}
	}
}

if ( ! function_exists( 'pdtr_admin_body_class' ) ) {
	function pdtr_admin_body_class( $classes ) {
		if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'updater' ) {
			/* add this class for correct styles of TB_iframe */
			return $classes . ' plugins-php ';
		}
		return $classes;
	}
}

/* Function that update all plugins and WP core. It will be executed every hour if enabled auto mode */
if ( ! function_exists( 'pdtr_auto_function' ) ) {
	function pdtr_auto_function() {
		global $pdtr_options, $pdtr_updater_list;
		$plugin_update_list = $theme_update_list = array();
		$core = $core_result = false;

		if ( empty( $pdtr_options ) )
			$pdtr_options = is_multisite() ? get_site_option( 'pdtr_options' ) : get_option( 'pdtr_options' ) ;

		$pdtr_updater_list	= pdtr_processing_site();

		if ( 1 == $pdtr_options["update_core"] && $pdtr_updater_list["core"]["version"] != $pdtr_updater_list["core"]["new_version"] )
			$core = true;

		if ( 1 == $pdtr_options["update_plugin"] || 1 == $pdtr_options["update_theme"] ) {
			foreach ( $pdtr_updater_list as $key => $value ) {
				if ( 'plugin' == $value['type'] && ! empty( $value['new_version'] ) && 1 == $pdtr_options["update_plugin"] )
					$plugin_update_list[] = $key;
				elseif ( 'theme' == $value['type'] && ! empty( $value['new_version'] ) && 1 == $pdtr_options["update_theme"] )
					$theme_update_list[] = $key;
			}
		}

		if ( 1 == $pdtr_options["send_mail_get_update"] && ( ! empty( $theme_update_list ) || ! empty( $plugin_update_list ) || false != $core ) ) {
			pdtr_notification_exist_update( $plugin_update_list, $theme_update_list, $core );
		}

		if ( 1 == $pdtr_options["mode"] ) {
			include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
			if ( false != $core )
				include_once( ABSPATH . 'wp-admin/includes/misc.php' );
			include_once( ABSPATH . 'wp-admin/includes/file.php' );
			include_once( ABSPATH . 'wp-admin/includes/update.php' );

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
				pdtr_notification_after_update( $plugin_update_list, $theme_update_list, $core, $core_result );
			}
		}

		wp_clear_scheduled_hook( 'pdtr_auto_hook' );

		$time = ( ! empty( $pdtr_options['time'] ) ) ? time() + $pdtr_options['time']*60*60 : time() + 12*60*60;
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
			$links[]	=	'<a href="https://support.bestwebsoft.com/hc/en-us/sections/200538859" target="_blank">' . __( 'FAQ', 'updater' ) . '</a>';
			$links[]	=	'<a href="https://support.bestwebsoft.com">' . __( 'Support', 'updater' ) . '</a>';
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

		if ( ! array_key_exists( 'updater/updater_pro.php', $all_plugins ) && ! array_key_exists( 'updater-plus/updater-plus.php', $all_plugins ) ) {
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

add_filter( 'admin_body_class', 'pdtr_admin_body_class' );

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