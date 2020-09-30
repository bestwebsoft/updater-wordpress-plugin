<?php
/*
Plugin Name: Updater by BestWebSoft
Plugin URI: https://bestwebsoft.com/products/wordpress/plugins/updater/
Description: Automatically check and update WordPress website core with all installed plugins and themes to the latest versions.
Author: BestWebSoft
Text Domain: updater
Domain Path: /languages
Version: 1.45
Author URI: https://bestwebsoft.com/
License: GPLv2 or later
*/

/*
	© Copyright 2020  BestWebSoft  ( https://support.bestwebsoft.com )

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

		/* pls */
		if ( isset( $submenu['updater'] ) ) {
			$submenu['updater'][] = array(
				'<span style="color:#d86463"> ' . __( 'Upgrade to Pro', 'updater' ) . '</span>',
				'manage_options',
				'https://bestwebsoft.com/products/wordpress/plugins/updater/?k=347ed3784e3d2aeb466e546bfec268c0pn&pn=84&v=' . $pdtr_plugin_info["Version"] . '&wp_v=' . $wp_version );
		}
		/* pls */
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
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$pdtr_plugin_info = get_plugin_data( __FILE__ );
		}

		/* Function check if plugin is compatible with current WP version */
		bws_wp_min_version_check( plugin_basename( __FILE__ ), $pdtr_plugin_info, '4.5' );
	}
}

if ( ! function_exists( 'pdtr_admin_init' ) ) {
	function pdtr_admin_init() {
		global $bws_plugin_info, $pdtr_plugin_info, $pdtr_options, $pagenow;

		if ( empty( $bws_plugin_info ) ) {
			$bws_plugin_info = array( 'id' => '84', 'version' => $pdtr_plugin_info["Version"] );
		}
		/* Call register settings function */
		if ( isset( $_GET['page'] ) && ( "updater-options" == $_GET['page'] || $_REQUEST['page'] == 'updater' ) ) {
			pdtr_register_settings();
		}
		if ( 'plugins.php' == $pagenow ) {
			/* Install the option defaults */
			if ( function_exists( 'bws_plugin_banner_go_pro' ) ) {
				pdtr_register_settings();
			    bws_plugin_banner_go_pro( $pdtr_options, $pdtr_plugin_info, 'pdtr', 'updater', '0b6882b0c99c2776d06c375dc22b5869', '84', 'updater' );
			}
		}
	}
}

/* Register settings function */
if ( ! function_exists( 'pdtr_register_settings' ) ) {
	function pdtr_register_settings() {
		global $pdtr_options, $pdtr_plugin_info;
		$db_version = '1.40';

		$is_multisite = is_multisite();

		if ( empty( $pdtr_plugin_info ) ) {
		    if ( ! function_exists( 'get_plugin_data' ) ) {
		        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		    }
		    $pdtr_plugin_info = get_plugin_data( __FILE__ );
		}

		if ( $is_multisite ) {
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
		$pdtr_options = $is_multisite ? get_site_option( 'pdtr_options' ) : get_option( 'pdtr_options' );

		if ( ! isset( $pdtr_options['plugin_option_version'] ) || $pdtr_options['plugin_option_version'] != $pdtr_plugin_info["Version"] ) {

			$options_default = pdtr_get_options_default();
			$pdtr_options = array_merge( $options_default, $pdtr_options );
			$pdtr_options['plugin_option_version'] = $pdtr_plugin_info["Version"];
			/* show pro features */
			$pdtr_options['hide_premium_options'] = array();
			$update_option = true;

		}

		/* Update tables when update plugin and tables changes*/
		if ( ! isset( $pdtr_options['plugin_db_version'] ) || $pdtr_options['plugin_db_version'] != $db_version ) {
			pdtr_drop_table();
			pdtr_create_table();
			pdtr_processing_site();
			$pdtr_options['plugin_db_version'] = $db_version;
			$update_option = true;
		}

		if ( isset( $update_option ) ) {
			if ( $is_multisite ) {
				update_site_option( 'pdtr_options', $pdtr_options );
			} else {
				update_option( 'pdtr_options', $pdtr_options );
			}
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

		$email_message_new_update_default =  __( 'Hello!', 'updater' ) . "\n" . __( 'Updater plugin is run on your website', 'updater' ) . " {SITE_NAME}.\n\n" . __( 'The following files need to be updated:', 'updater' ) . "\n\n{UPDATE_LIST}\n\n"
            . sprintf( __( 'If you want to change the type of the updating mode or other settings, please go to the %s.', 'updater' ), '<a href=' . network_admin_url( 'admin.php?page=updater-options' ) . '>' . __( 'plugin settings page', 'updater' ) . '</a>' )
            . "\n\n----------------------------------------\n\n" .  sprintf( __( 'Thanks for using %s!', 'updater' ), '<a href=' . esc_url('https://bestwebsoft.com/products/wordpress/plugins/updater/' ) . '>' . "Updater" . '</a>' );
        $email_message_complete_update_default = __( 'Hello!', 'updater' ) . "\n" . __( 'Updater plugin is run on your website', 'updater' ) . " {SITE_NAME}.\n\n{UPDATE_LIST}\n\n"
            . sprintf( __( 'If you want to change the type of the updating mode or other settings, please go to the %s.', 'updater' ), '<a href=' . network_admin_url( 'admin.php?page=updater-options' ) . '>' . __( 'plugin settings page', 'updater' ) . '</a>' )
            . "\n\n----------------------------------------\n\n" .  sprintf( __( 'Thanks for using %s!', 'updater' ), '<a href=' . esc_url('https://bestwebsoft.com/products/wordpress/plugins/updater/' ) . '>' . "Updater" . '</a>' );

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
			'update_theme'				=>	1,
			'update_language'			=>	1,
			'email_subject_update'      => __( 'Check for Updates on', 'updater' ) . " {SITE_NAME}",
			'email_subject_complete'    => __( 'Updating on', 'updater' ) . " {SITE_NAME}",
			'email_message_update'      => $email_message_new_update_default,
			'email_message_complete'    => $email_message_complete_update_default,
		);
		return $options_default;
	}
}

/* Create TABLE in db if it isn't exist */
if ( ! function_exists( 'pdtr_create_table' ) ) {
	function pdtr_create_table() {
		global $wpdb;
		$sql = "CREATE TABLE IF NOT EXISTS `" . $wpdb->base_prefix . "updater_list` (
			`id` int NOT NULL AUTO_INCREMENT,
			`type` ENUM('theme', 'plugin', 'core', 'language'),
			`name` tinytext NOT NULL,
			`wp_key` tinytext NOT NULL,
			`time` datetime NOT NULL,
			`version` tinytext NOT NULL,
			`new_version` tinytext NOT NULL,
			`url` tinytext NOT NULL,
			`block` tinyint(1) NOT NULL DEFAULT '0',
			UNIQUE KEY id (id)
		);";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
}

/* Drope TABLE in db if it is exist */
if ( ! function_exists( 'pdtr_drop_table' ) ) {
	function pdtr_drop_table() {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->base_prefix . "updater_list`" );
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

		pdtr_create_table();
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
		if ( empty( $pdtr_options ) ) {
			$pdtr_options =  is_multisite() ? get_site_option( 'pdtr_options' ) : get_option( 'pdtr_options' ) ;
		}
		$schedules_hours = ( ! empty( $pdtr_options['time'] ) ) ? $pdtr_options['time'] : 12;

		$schedules['pdtr_schedules_hours'] = array( 'interval' => $schedules_hours*60*60, 'display' => 'Every ' . $schedules_hours . ' hours' );
		return $schedules;
	}
}

/* Function for display updater settings page in the BWS admin area */
if ( ! function_exists( 'pdtr_settings_page' ) ) {
	function pdtr_settings_page() {
		global $pdtr_plugin_info, $pdtr_options; ?>
		<div class="wrap" id="pdtr_wrap">
			<?php if ( 'updater-options' == $_GET['page'] ) { /* Showing settings tab */
				if ( ! class_exists( 'Bws_Settings_Tabs' ) )
					require_once( dirname( __FILE__ ) . '/bws_menu/class-bws-settings.php' );
				require_once( dirname( __FILE__ ) . '/includes/class-pdtr-settings.php' );
				$page = new Pdtr_Settings_Tabs( plugin_basename( __FILE__ ) ); ?>
				<h1><?php _e( 'Updater Settings', 'updater' ); ?></h1>
				<?php $page->display_content();
			} else { ?>
				<h1><?php _e( 'Installed Software', 'updater' ); ?></h1>
				<div class="error notice inline">
					<p><strong><?php _e( 'We strongly recommend you to backup your website and WordPress database before updating! We are not responsible for the site running after updates.', 'updater' ); ?></strong></p>
				</div>
				<?php require_once( dirname( __FILE__ ) . '/includes/software-table.php' );
				pdtr_display_table();
				/* pls */
				bws_plugin_reviews_block( $pdtr_plugin_info['Name'], 'updater' );
				/* pls */
			} ?>
		</div>
	<?php }
}

/* Function for processing the site ( get plugins, get themes, core and translations ) */
if ( ! function_exists( 'pdtr_processing_site' ) ) {
	function pdtr_processing_site() {
		global $wp_version, $pdtr_options, $wpdb;
		$updater_list = array();

		if ( empty( $pdtr_options ) )
			pdtr_register_settings();

		$wpdb->query( "TRUNCATE " . $wpdb->base_prefix . "updater_list" );

		/* Include file for get plugins */
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		/* Add the list of plugins, that need to be update */
		if ( 1 == $pdtr_options["update_plugin"] ) {
			/* Add the list of installed plugins */
			$wp_list_table_plugins = apply_filters( 'all_plugins', get_plugins() );

			$updater_list["plugin_list"] = $wp_list_table_plugins;

			foreach ( $wp_list_table_plugins as $key => $value ) {
				$wpdb->insert( $wpdb->base_prefix . "updater_list",
					array(
						'time'			=> current_time( 'mysql' ),
						'name'			=> $value["Name"],
						'type' 			=> 'plugin',
						'wp_key'		=> $key,
						'version'		=> $value["Version"]
					),
					array( '%s', '%s', '%s', '%s', '%s' )
				);
			}
		}

		/* Add the list of installed themes */
		if ( 1 == $pdtr_options["update_theme"] ) {
			$wp_list_table_themes = apply_filters( 'all_themes', wp_get_themes() );

			$updater_list[ "theme_list" ] = $wp_list_table_themes;

			foreach ( $wp_list_table_themes as $key => $value ) {
				$wpdb->insert( $wpdb->base_prefix . "updater_list",
					array(
						'time'			=> current_time( 'mysql' ),
						'name'			=> $value["Name"],
						'type' 			=> 'theme',
						'wp_key'		=> $key,
						'version'		=> $value["Version"]
					),
					array( '%s', '%s', '%s', '%s', '%s' )
				);
			}
		}

		/* Chack if languages need to be update. If yes, add the languages list. */
		if ( ! empty( $pdtr_options["update_language"] ) ) {
			$language_list = wp_get_translation_updates();
			$pdtr_updater_list["language"] = array();
			if ( ! empty( $language_list ) ) {
				foreach ( $language_list as $key => $value ) {
					$language_code = $language_list[ $key ] -> language;
					$language_code = substr( $language_code, 0, 2 );
					if ( ! in_array( $language_code, $pdtr_updater_list["language"] ) ) {
						$pdtr_updater_list["language"][] = $language_code;
					}
				}
				$wp_list_table_languages = $pdtr_updater_list["language"];
				$pdtr_lang_codes = pdtr_lang_codes();

				foreach ( $wp_list_table_languages as $key => $value ) {
					$wpdb->insert( $wpdb->base_prefix . "updater_list",
						array(
							'time'			=> current_time( 'mysql' ),
							'name'			=> $value,
							'type' 			=> 'language',
							'wp_key'		=> $pdtr_lang_codes[ $value ],
						),
						array( '%s', '%s', '%s', '%s' )
					);
				}

			}
		}

		/* Functions for getting update */
		require_once( ABSPATH . 'wp-includes/update.php' );
		if ( defined( 'WP_INSTALLING' ) ) {
			return false;
		}
		if ( 1 == $pdtr_options["update_core"] ) {
			wp_version_check();
		}
		if ( 1 == $pdtr_options["update_plugin"] ) {
			wp_update_plugins();
		}
		if ( 1 == $pdtr_options["update_theme"] ) {
			wp_update_themes();
		}

		/* Add the list of plugins, that need to be updated */
		if ( 1 == $pdtr_options["update_plugin"] ) {

			$update_plugins	= get_site_transient( 'update_plugins' );

			if ( ! empty( $update_plugins->response ) ) {
				foreach ( $update_plugins->response as $file => $value ) {
					$value = get_object_vars( $value );
					if ( isset( $wp_list_table_plugins[ $file ] ) ) {
						$wp_list_table_plugins[ $file ][ "new_version" ] = $value["new_version"];
						if ( ! empty( $value["slug"] ) ) {
							$url = esc_url( self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $value["slug"] . '&section=changelog&TB_iframe=true&width=772&height=930' ) );
						} else {
							$url = NULL;
						}
						$wpdb->update(
							$wpdb->base_prefix . "updater_list",
							array(
								'new_version'	=> $value["new_version"],
								'url'			=> $url
							),
							array( 'wp_key' 	 => $file )
						);
					}
				}
			}
		}

			/* Add the list of themes, that need to be update */
		if ( 1 == $pdtr_options["update_theme"] ) {
			$update_themes 	= get_site_transient( 'update_themes' );
			if ( ! empty( $update_themes->response ) ) {
				foreach ( $update_themes->response as $file => $value ) {
					if ( isset( $wp_list_table_themes[ $file ] ) ) {
						$wp_list_table_themes[ $file ]['new_version'] = $value["new_version"];
						if ( ! empty( $value['url'] ) ) {
							$url = esc_url( add_query_arg( array( 'TB_iframe' => 'true', 'width' => 1024, 'height' => 800 ), $value['url'] ) );
						} else {
							$url = NULL;
						}
						$wpdb->update(
							$wpdb->base_prefix . "updater_list",
							array(
								'new_version'	=> $value["new_version"],
								'url'			=> $url
							),
							array( 'wp_key' 	 => $file )
						);
					}
				}
			}
		}

		/* Add current core version and the latest version of core */
		if ( 1 == $pdtr_options["update_core"] ) {
			$core = get_site_transient( 'update_core' );
			$wp_version_new = ( ! empty( $core->updates ) && $core->updates[0]->current != $wp_version ) ? $core->updates[0]->current : '';
			$updater_list["core"] = array(
				"current" => $wp_version,
				"new" => $wp_version_new
			);

			$wpdb->insert( $wpdb->base_prefix . "updater_list",
				array(
					'time'				=> current_time( 'mysql' ),
					'name'				=> 'WordPress',
					'type'				=> 'core',
					'wp_key'			=> 'wp_core',
					'version'			=> $wp_version,
					'new_version' 		=> $wp_version_new
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		}

		return $updater_list;
	}
}

/* Function for updating plugins */
if ( ! function_exists( 'pdtr_update_plugin' ) ) {
	function pdtr_update_plugin( $plugins_list, $automode = false ) {
			if ( empty( $pdtr_options ) ) {
				pdtr_register_settings();
			}

			/* Include files for using class Plugin_Upgrader */
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			if ( ! class_exists( 'Bulk_Plugin_Upgrader_Skin' ) ) {
				include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader-skins.php' );
			}

			if ( ! $automode ) {
				echo '<h2>' . __( 'Updating Plugins...', 'updater' ) . '</h2>';
			}

			$plugins_list = array_map( 'urldecode', $plugins_list );
			$upgrader = new Plugin_Upgrader( new Bulk_Plugin_Upgrader_Skin() );
			$upgrader->bulk_upgrade( $plugins_list );

			if ( ! $automode ) {
				iframe_footer();
			}

	}
}

/* Function for updating theme */
if ( ! function_exists( 'pdtr_update_theme' ) ) {
	function pdtr_update_theme( $themes_list, $automode = false ) {
			if ( empty( $pdtr_options ) ) {
				pdtr_register_settings();
			}

			/* Include files for using class Plugin_Upgrader */
			include_once( ABSPATH . 'wp-admin/includes/theme.php' );
			if ( ! class_exists( 'Bulk_Theme_Upgrader_Skin' ) ) {
				include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader-skins.php' );
			}

			if ( ! $automode ) {
				echo '<h2>' . __( 'Updating Themes...', 'updater' ) . '</h2>';
			}

			$themes_list = array_map( 'urldecode', $themes_list );
			$theme_upgrader = new Theme_Upgrader( new Bulk_Theme_Upgrader_Skin() );
			$theme_upgrader->bulk_upgrade( $themes_list );

			if ( ! $automode ) {
				iframe_footer();
			}

	}
}

/* Function for updating languages */
if ( ! function_exists( 'pdtr_update_language' ) ) {
	function pdtr_update_language( $language_list , $automode = false ) {
		global $pdtr_need_backup;
		/* Include files for using class Plugin_Upgrader */
		if ( ! class_exists( 'Language_Pack_Upgrader' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/class-language-pack-upgrader.php' );
		}
		if ( ! class_exists( 'Language_Pack_Upgrader_Skin' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/class-language-pack-upgrader-skin.php' );
		}
		/* Backup if it need and if user check it */
		if ( true === $pdtr_need_backup ) {
			$backup_result = pdtr_backup_wrap( $automode );
			if ( ! $backup_result ) {
				return;
			}
		}
		if ( ! $automode ) {
			echo '<h2>' . __( 'Updating Translations...', 'updater' ) . '</h2>';
		}
		$language_list = wp_get_translation_updates();
		$skin = new Language_Pack_Upgrader_Skin( array(
			'skip_header_footer' => true,
		) );
		$language_upgrader = new Language_Pack_Upgrader( $skin );
		include_once( ABSPATH . 'wp-admin/includes/misc.php' );
		$language_upgrader->bulk_upgrade( $language_list );
		if ( ! $automode ) {
			iframe_footer();
		}
	}
}

/* Function for updating WP core */
if ( ! function_exists( 'pdtr_update_core' ) ) {
	function pdtr_update_core( $automode = false ) {
		global $wp_filesystem, $wp_version;
		if ( empty( $pdtr_options ) ) {
			pdtr_register_settings();
		}

		if ( ! $automode ) {
			echo '<h2>' . __( 'Updating WordPress...', 'updater' ) . '</h2>';
		}

		$url = wp_nonce_url( 'update-core.php?action=do-core-upgrade', 'upgrade-core' );
		if ( false === ( $credentials = request_filesystem_credentials( $url, '', false, ABSPATH ) ) ) {
			return false;
		}

		$url = wp_nonce_url( 'admin.php?page=updater-options', 'upgrade-core' );
		if ( false === ( $credentials = request_filesystem_credentials( $url, '', false, ABSPATH ) ) ) {
			return false;
		}

		$from_api	= get_site_transient( 'update_core' );
		$updates	= $from_api->updates;
		/* get latest WP version */
		$update	=	$updates[0];

		if ( ! WP_Filesystem( $credentials, ABSPATH ) ) {
			request_filesystem_credentials( $url, '', true, ABSPATH ); /* Failed to connect, Error and request again */
			return false;
		}

		if ( $wp_filesystem->errors->get_error_code() ) {
			foreach ( $wp_filesystem->errors->get_error_messages() as $message ) {
				show_message( $message );
			}
			return false;
		}

		add_filter( 'update_feedback', 'show_message' );
		include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		$upgrader = new Core_Upgrader();

		if ( '4.1' > $wp_version ) {
			$result = @$upgrader->upgrade( $update );
		} else {
			$result = @$upgrader->upgrade( $update, array( 'allow_relaxed_file_ownership' => true ) );
		}

		if ( is_wp_error( $result ) ) {
			if ( ! $automode ) {
				show_message( $result );
				if ( 'up_to_date' != $result->get_error_code() ) {
					show_message( __( 'Update Failed', 'updater' ) );
				}
			}
			return false;
		}
		if ( ! $automode ) {
			show_message( __( 'WordPress was updated successfully!', 'updater' ) );
		}

		/* Check version and set option 'update_core' */
		wp_version_check();

		return true;
	}
}

/* Function for sending email after update */
if ( ! function_exists( 'pdtr_notification_after_update' ) ) {
	function pdtr_notification_after_update( $plugins_list, $themes_list, $core_for_update, $core_result, $languages ) {
		global $pdtr_options, $wp_version, $wpdb;
		$have_error_plugin = $have_error_theme = false;
		$have_updating_plugins = $have_updating_themes = $language_result = array();

		$updater_list = pdtr_processing_site();
		$subject = $pdtr_options['email_subject_complete'];
		$message = $pdtr_options['email_message_complete'];
		$plugins_list_update = '';


		if ( ! empty( $plugins_list ) ) {
			foreach ( $plugins_list as $key => $value ) {
				$versions = $wpdb->get_row( "SELECT `version`, `new_version` FROM `" . $wpdb->base_prefix . "updater_list` WHERE `wp_key` like '" . $value . "'", ARRAY_A );
				$plugins_result[ $value ] = ( $versions["version"] < $versions["new_version"] && ( ! empty( $versions["new_version"] ) ) ) ? false : true;
			}
			foreach ( $plugins_result as $plugin_value ) {
				if ( false != $plugin_value ) {
					$have_updating_plugins = true;
				} else {
					$have_error_plugin = true;
				}
			}
		}

		if ( ! empty( $themes_list ) ) {
			foreach ( $themes_list as $key => $value ) {
				$versions = $wpdb->get_row( "SELECT `version`, `new_version` FROM `" . $wpdb->base_prefix . "updater_list` WHERE `wp_key` like '" . $value . "'", ARRAY_A );
				$themes_result[ $value ] = ( $versions["version"] < $versions["new_version"] && ( ! empty( $versions["new_version"] ) ) ) ? false : true;
			}
			foreach ( $themes_result as $theme_value ) {
				if ( false != $theme_value ) {
					$have_updating_themes = true;
				} else {
					$have_error_theme = true;
				}
			}
		}

		if ( ! empty( $languages ) ) {
			foreach ( $languages as $language ) {
				$data = $wpdb->get_row( "SELECT `wp_key` FROM `" . $wpdb->base_prefix . "updater_list` WHERE `wp_key` = '" . $language . "'", ARRAY_A );
				if ( ! empty( $data ) ) {
					$language_result[] = $data;
				}
			}
		}		

		/* Errors */
		if ( ( false == $core_result && true == $core_for_update ) || true == $have_error_plugin  || true == $have_error_theme || ! empty( $language_result ) ) {

			$plugins_list_update .= __( "The following files can't be updated", 'updater' ) . ':<br/><br/>';

			if ( true == $have_error_plugin ) {
				$plugins_list_update .= '<strong>' . __( 'Plugin(s)', 'updater' ) . ':</strong><ul>';
				foreach ( $plugins_result as $key => $value ) {
				    if ( true != $value ) {
						$name = explode( "/", $key );

						$plugins_list_update .= '<li><span style="color:#d52721">' . $name[0] . ' - ' . __( 'failed update', 'updater' ) . ' (' . sprintf( __( 'the current version is %s', 'updater' ), $updater_list["plugin_list"][ $key ]["Version"] ) . ');</span></li>';
					}
				}
				$plugins_list_update .= '</ul><br/>';
			}

			if ( true == $have_error_theme ) {
				$plugins_list_update .= '<strong>' . __( 'Theme(s)', 'updater' ) . ':</strong><ul>';
				foreach ( $themes_result as $key => $value ) {
						if ( true != $value ) {
							$name = explode( "/", $key );

							$plugins_list_update .= '<li><span style="color:#d52721">' . $name[0] . ' - ' . __( 'failed update', 'updater' ) . ' (' . sprintf( __( 'the current version is %s', 'updater' ), $updater_list["theme_list"][ $key ]["Version"] ) . ');</span></li>';
						}
				}
				$plugins_list_update .= '</ul><br/>';
			}

			if ( false == $core_result && true == $core_for_update ) {
				$plugins_list_update .= '<strong>' . __( 'WordPress', 'updater' ) . ':</strong><ul>
					<li><span style="color:#d52721">' . __( "WordPress can’t be updated on your website.", 'updater' ) . '</span></li>
					</ul><br/>';
			}

			if ( ! empty( $language_result ) ) {
				$plugins_list_update .= '<strong>' . __( 'Translations', 'updater' ) . ':</strong><ul>';
				foreach ( $language_result as $language ) {
					$plugins_list_update .= '<li><span style="color:#d52721">' . $language . ' - ' . __( "failed update.", 'updater' ) . '</span></li>
					</ul><br/>';
				}
			}
		}

		if ( $have_updating_plugins || $have_updating_themes || ( true == $core_result && true == $core_for_update ) || empty( $language_result ) ) {

			$plugins_list_update .= __( 'The following files were updated successfully', 'updater' ) . ':<br/><br/>';

			if ( $have_updating_plugins ) {
				$plugins_list_update .= '<strong>' . __( 'Plugin(s)', 'updater' ) . ':</strong><ul>';
				foreach ( $plugins_result as $key => $value ) {
					if ( false != $value ) {
						$name = explode( "/", $key );
						$$plugins_list_update .= '<li><span style="color:#179247">' . $name[0] . ' - ' . sprintf( __( 'updated to the version %s', 'updater' ), $updater_list["plugin_list"][ $key ]["Version"] ) . ';</span></li>';
					}
				}
				$plugins_list_update .= '</ul><br/>';
			}

			if ( $have_updating_themes ) {
				$plugins_list_update .= '<strong>' . __( 'Theme(s)', 'updater' ) . ':</strong><ul>';
				foreach ( $themes_result as $key => $value ) {
					if ( false != $value ) {
						$name = explode( "/", $key );
						$plugins_list_update .= '<li><span style="color:#179247">' . $name[0] . ' - ' . sprintf( __( 'updated to the version %s', 'updater' ), $updater_list["theme_list"][ $key ]["Version"] ) . ';</span></li>';
					}
				}
				$plugins_list_update .= '</ul><br/>';
			}

				if ( true == $core_result && true == $core_for_update ) {
					$plugins_list_update .= '<strong>' . __( 'WordPress', 'updater' ) . ':</strong><ul>
					<li><span style="color:#179247">' . __( 'Version', 'updater' ) . ' ' . $wp_version . '.</span></li>
					</ul><br/>';
				}

			if ( ! empty( $language_result ) ) {
				$plugins_list_update .= '<strong>' . __( 'Translations', 'updater' ) . ':</strong><ul>';
				foreach ( $languages as $language ) {
					$plugins_list_update .= '<li><span style="color:#179247">' . $language . ' - ' . __( "updated successfully.", 'updater' ) . '</span></li>
					</ul><br/>';
				}
			}
		} 
		
		$plugins_list_update .= '</body></html>';

		$subject = str_replace(
			array( '{SITE_NAME}' ),
			array( get_bloginfo( 'name' ) ),
			$subject
		);

		$message = str_replace(
			array( '{SITE_NAME}', '{SITE_URL}', '{UPDATE_LIST}' ),
			array( get_bloginfo( 'name' ), get_bloginfo( 'url' ), $plugins_list_update ),
			$message
		);

		if ( 'default' == $pdtr_options["to_email_type"] ) {
			$emails = array();
			foreach ( $pdtr_options["to_email"] as $userlogin ) {
				$user = get_user_by( 'login', $userlogin );
				if ( false !== $user ) {
					$emails[] = $user->user_email;
				}
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
		$mail_result = wp_mail( $emails, $subject, htmlspecialchars_decode( nl2br( $message ) ), $headers );
		return $mail_result;
	}
}

/* Function for sending email if exist update */
if ( ! function_exists( 'pdtr_notification_exist_update' ) ) {
	function pdtr_notification_exist_update( $plugins_list, $themes_list, $core, $languages, $test = false ) {
		global $pdtr_options, $wpdb;
		pdtr_processing_site();
		$subject = $pdtr_options['email_subject_update'];		
		$message = $pdtr_options['email_message_update'];		
		$plugins_list_update = '';
		
		if ( ! empty( $plugins_list ) ) {
			$plugins_list_update .= '<strong>' . __( 'Plugin(s)', 'updater' ) . ':</strong><ul>';
			foreach ( $plugins_list as $value ) {
				$plugin = $wpdb->get_row( "SELECT `version`, `new_version`, `name` FROM `" . $wpdb->prefix . "updater_list` WHERE `wp_key` = '" . $value . "'", ARRAY_A );
				$plugins_list_update .= '<li>' . $plugin["name"] . ' - ' . sprintf( __( 'to the version %s', 'updater' ), $plugin["new_version"] ) .
						 ' ('. sprintf( __( 'the current version is %s', 'updater' ), $plugin["version"] ) . ');</li>';
			}
			$plugins_list_update .= '</ul>';
		}

		if ( ! empty( $themes_list ) ) {
			$plugins_list_update .= '<strong>' . __( 'Theme(s)', 'updater' ) . ':</strong><ul>';
			foreach ( $themes_list as $value ) {
				$theme = $wpdb->get_row( "SELECT `version`, `new_version`, `name` FROM `" . $wpdb->prefix . "updater_list` WHERE `wp_key` = '" . $value . "'", ARRAY_A );
				$plugins_list_update .= '<li>' . $theme["name"] . ' - ' . sprintf( __( 'to the version %s', 'updater' ), $theme["new_version"] ) .
							' ('. sprintf( __( 'the current version is %s', 'updater' ), $theme["version"] ) . ');</li>';
			}
			$plugins_list_update .= '</ul>';
		}		

		if ( true === $core ) {
			$core_version = $wpdb->get_row( "SELECT `version`, `new_version` FROM `" . $wpdb->prefix . "updater_list` WHERE `wp_key` = `wp_core`", ARRAY_A );
			$plugins_list_update .= '<strong>' . __( 'WordPress', 'updater' ) . ':</strong><ul><li>' . sprintf( __( 'Version %s is available', 'updater' ), $core_version["new_version"] ) . ' (' . sprintf( __( 'the current version is %s', 'updater' ), $core_version["version"] ) . ').</li></ul>';
		}

		if ( ! empty( $languages ) ) {
			$plugins_list_update .= '<strong>' . __( 'Translations', 'updater' ) . ':</strong><ul>';
			foreach ( $languages as $language ) {
				$plugins_list_update .= '<li>' . $language . '</li>';
			}
			$plugins_list_update .= '</ul>';
		}

		if ( false === $test ) {
			if ( 0 == $pdtr_options["mode"] ) {
				$plugins_list_update .= '<br/>' . __( 'To start the updating, please follow the link', 'updater' ) . ' - <a href=' . network_admin_url( 'admin.php?page=updater' ) . '>' . __( 'Updater page on your website', 'updater' ) . '</a>.';
			} else {
				$plugins_list_update .= '<br/>' . __( 'Updater plugin starts updating these files.', 'updater' );
			}
		} elseif ( ! empty( $themes_list ) || ! empty( $plugins_list ) || false != $core || ! empty( $languages ) ) {
			$plugins_list_update .= '<br/>' . __( 'To start the updating, please follow the link', 'updater' ) . ' - <a href=' . network_admin_url( 'admin.php?page=updater' ) . '>' . __( 'Updater page on your website', 'updater' ) . '</a>.';
		}

		if ( empty( $themes_list ) && empty( $plugins_list ) && false == $core && empty( $languages ) ) {
			$plugins_list_update .= __( 'Your files are all up to date.', 'updater' );
		}

		$plugins_list_update .= '</body></html>';

		$subject = str_replace(
			array( '{SITE_NAME}' ),
			array( get_bloginfo( 'name' ) ),
			$subject
		);

		$message = str_replace(
			array( '{SITE_NAME}', '{SITE_URL}', '{UPDATE_LIST}' ),
			array( get_bloginfo( 'name' ), get_bloginfo( 'url' ), $plugins_list_update ),
			$message
		);

		if ( 'default' == $pdtr_options["to_email_type"] ) {
			$emails = array();
			foreach ( $pdtr_options["to_email"] as $userlogin ) {
				$user = get_user_by( 'login', $userlogin );
				if ( false !== $user ) {
					$emails[] = $user->user_email;
				}
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

		$mail_result = wp_mail( $emails, $subject,  htmlspecialchars_decode( nl2br( $message ) ), $headers );
		return $mail_result;
	}
}
/* End function pdtr_notification_exist_update */

/* Return an array of language codes */
if ( ! function_exists( 'pdtr_lang_codes' ) ) {
	function pdtr_lang_codes() {
		$pdtr_lang_codes = array(
            'ab' => 'Abkhazian',
            'aa' => 'Afar',
            'af' => 'Afrikaans',
            'ak' => 'Akan',
            'sq' => 'Albanian',
            'am' => 'Amharic',
            'ar' => 'Arabic',
            'an' => 'Aragonese',
            'hy' => 'Armenian',
            'as' => 'Assamese',
            'av' => 'Avaric',
            'ae' => 'Avestan',
            'ay' => 'Aymara',
            'az' => 'Azerbaijani',
            'bm' => 'Bambara',
            'ba' => 'Bashkir',
            'eu' => 'Basque',
            'be' => 'Belarusian',
            'bn' => 'Bengali',
            'bh' => 'Bihari',
            'bi' => 'Bislama',
            'bs' => 'Bosnian',
            'br' => 'Breton',
            'bg' => 'Bulgarian',
            'my' => 'Burmese',
            'ca' => 'Catalan; Valencian',
            'ch' => 'Chamorro',
            'ce' => 'Chechen',
            'ny' => 'Chichewa; Chewa; Nyanja',
            'zh' => 'Chinese',
            'cu' => 'Church Slavic; Old Slavonic; Church Slavonic; Old Bulgarian; Old Church Slavonic',
            'cv' => 'Chuvash',
            'km' => 'Central Khmer',
            'kw' => 'Cornish',
            'co' => 'Corsican',
            'cr' => 'Cree',
            'hr' => 'Croatian',
            'cs' => 'Czech',
            'da' => 'Danish',
            'dv' => 'Divehi; Dhivehi; Maldivian',
            'nl' => 'Dutch; Flemish',
            'dz' => 'Dzongkha',
            'en' => 'English',
            'eo' => 'Esperanto',
            'et' => 'Estonian',
            'ee' => 'Ewe',
            'fo' => 'Faroese',
            'fj' => 'Fijjian',
            'fi' => 'Finnish',
            'fr' => 'French',
            'ff' => 'Fulah',
            'gd' => 'Gaelic; Scottish Gaelic',
            'gl' => 'Galician',
            'lg' => 'Ganda',
            'ka' => 'Georgian',
            'de' => 'German',
            'el' => 'Greek, Modern',
            'gn' => 'Guarani',
            'gu' => 'Gujarati',
            'ht' => 'Haitian; Haitian Creole',
            'ha' => 'Hausa',
            'he' => 'Hebrew',
            'hz' => 'Herero',
            'hi' => 'Hindi',
            'ho' => 'Hiri Motu',
            'hu' => 'Hungarian',
            'is' => 'Icelandic',
            'io' => 'Ido',
            'ig' => 'Igbo',
            'id' => 'Indonesian',
            'ie' => 'Interlingue',
            'ia' => 'Interlingua (International Auxiliary Language Association)',
            'iu' => 'Inuktitut',
            'ik' => 'Inupiaq',
            'ga' => 'Irish',
            'it' => 'Italian',
            'ja' => 'Japanese',
            'jv' => 'Javanese',
            'kl' => 'Kalaallisut; Greenlandic',
            'kn' => 'Kannada',
            'kr' => 'Kanuri',
            'ks' => 'Kashmiri',
            'kk' => 'Kazakh',
            'ki' => 'Kikuyu; Gikuyu',
            'rw' => 'Kinyarwanda',
            'ky' => 'Kirghiz; Kyrgyz',
            'kv' => 'Komi',
            'kg' => 'Kongo',
            'ko' => 'Korean',
            'kj' => 'Kuanyama; Kwanyama',
            'ku' => 'Kurdish',
            'lo' => 'Lao',
            'la' => 'Latin',
            'lv' => 'Latvian',
            'li' => 'Limburgan; Limburger; Limburgish',
            'ln' => 'Lingala',
            'lt' => 'Lithuanian',
            'lu' => 'Luba-Katanga',
            'lb' => 'Luxembourgish; Letzeburgesch',
            'mk' => 'Macedonian',
            'mg' => 'Malagasy',
            'ms' => 'Malay',
            'ml' => 'Malayalam',
            'mt' => 'Maltese',
            'gv' => 'Manx',
            'mi' => 'Maori',
            'mr' => 'Marathi',
            'mh' => 'Marshallese',
            'mo' => 'Moldavian',
            'mn' => 'Mongolian',
            'na' => 'Nauru',
            'nv' => 'Navajo; Navaho',
            'nr' => 'Ndebele, South; South Ndebele',
            'nd' => 'Ndebele, North; North Ndebele',
            'ng' => 'Ndonga',
            'ne' => 'Nepali',
            'se' => 'Northern Sami',
            'no' => 'Norwegian',
            'nn' => 'Norwegian Nynorsk; Nynorsk, Norwegian',
            'nb' => 'Norwegian Bokmål; Bokmål, Norwegian',
            'oc' => 'Occitan, Provençal',
            'oj' => 'Ojibwa',
            'or' => 'Oriya',
            'om' => 'Oromo',
            'os' => 'Ossetian; Ossetic',
            'pi' => 'Pali',
            'pa' => 'Panjabi; Punjabi',
            'fa' => 'Persian',
            'pl' => 'Polish',
            'pt' => 'Portuguese',
            'ps' => 'Pushto',
            'qu' => 'Quechua',
            'ro' => 'Romanian',
            'rm' => 'Romansh',
            'rn' => 'Rundi',
            'ru' => 'Russian',
            'sm' => 'Samoan',
            'sg' => 'Sango',
            'sa' => 'Sanskrit',
            'sc' => 'Sardinian',
            'sr' => 'Serbian',
            'sn' => 'Shona',
            'ii' => 'Sichuan Yi',
            'sd' => 'Sindhi',
            'si' => 'Sinhala; Sinhalese',
            'sk' => 'Slovak',
            'sl' => 'Slovenian',
            'so' => 'Somali',
            'st' => 'Sotho, Southern',
            'es' => 'Spanish; Castilian',
            'su' => 'Sundanese',
            'sw' => 'Swahili',
            'ss' => 'Swati',
            'sv' => 'Swedish',
            'tl' => 'Tagalog',
            'ty' => 'Tahitian',
            'tg' => 'Tajik',
            'ta' => 'Tamil',
            'tt' => 'Tatar',
            'te' => 'Telugu',
            'th' => 'Thai',
            'bo' => 'Tibetan',
            'ti' => 'Tigrinya',
            'to' => 'Tonga (Tonga Islands)',
            'ts' => 'Tsonga',
            'tn' => 'Tswana',
            'tr' => 'Turkish',
            'tk' => 'Turkmen',
            'tw' => 'Twi',
            'ug' => 'Uighur; Uyghur',
            'uk' => 'Ukrainian',
            'ur' => 'Urdu',
            'uz' => 'Uzbek',
            've' => 'Venda',
            'vi' => 'Vietnamese',
            'vo' => 'Volapük',
            'wa' => 'Walloon',
            'cy' => 'Welsh',
            'fy' => 'Western Frisian',
            'wo' => 'Wolof',
            'xh' => 'Xhosa',
            'yi' => 'Yiddish',
            'yo' => 'Yoruba',
            'za' => 'Zhuang; Chuang',
            'zu' => 'Zulu',
        );
		return $pdtr_lang_codes;
	}
}

/* Add css-file to the plugin */
if ( ! function_exists( 'pdtr_admin_head' ) ) {
	function pdtr_admin_head() {
		global $hook_suffix;

		wp_enqueue_style( 'pdtr_style', plugins_url( 'css/style.css', __FILE__ ) );

		if ( isset( $_REQUEST['page'] ) && ( $_REQUEST['page'] == 'updater-options' || $_REQUEST['page'] == 'updater' ) ) {
			wp_enqueue_script( 'jquery' );
			add_thickbox();

			if ( ( isset( $_POST['action'] ) && 'update' == $_POST['action'] ) || ( isset( $_POST['action2'] ) && 'update' == $_POST['action2'] ) || ( isset( $_POST['pdtr_tab_action'] ) && 'update' == $_POST['pdtr_tab_action'] ) ) {
				wp_enqueue_script( 'updates' );
			}

			wp_enqueue_script( 'pdtr_script', plugins_url( 'js/script.js', __FILE__ ) );

			bws_enqueue_settings_scripts();
            bws_plugins_include_codemirror();
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
		global $pdtr_options, $wpdb;
		$plugin_update_list = $theme_update_list = $languages = array();
		$core = $core_result = false;
		$language_list_for_update = wp_get_translation_updates();

		if ( empty( $pdtr_options ) ) {
			$pdtr_options = is_multisite() ? get_site_option( 'pdtr_options' ) : get_option( 'pdtr_options' );
		}

		$updater_list_time = $wpdb->get_var( "SELECT `time` FROM `" . $wpdb->base_prefix . "updater_list` LIMIT 1;" );
		if ( empty( $updater_list_time ) || 3600 <= ( strtotime( current_time( 'mysql' ) ) - strtotime( $updater_list_time ) ) ) {
			pdtr_processing_site();
		}

		$result_list = $wpdb->get_results( "SELECT `wp_key`, `type` FROM `" . $wpdb->base_prefix . "updater_list` WHERE `new_version` != '' OR `type` = 'language'", ARRAY_A );
		if ( $result_list ) {
			foreach ( $result_list as $key => $value ) {

				if ( "plugin" == $value["type"] && 1 == $pdtr_options["update_plugin"] ) {
					$plugin_update_list[] = $value["wp_key"];
				} elseif ( "theme" == $value["type"] && 1 == $pdtr_options["update_theme"] ) {
					$theme_update_list[] = $value["wp_key"];
				} elseif ( "core" == $value["type"] && 1 == $pdtr_options["update_core"] ) {
					$core = true;
				} elseif ( "language" == $value["type"] && 1 == $pdtr_options["update_language"] ) {
					$languages[] = $value["wp_key"];
				}
			}
		} else {
			wp_clear_scheduled_hook( 'pdtr_auto_hook' );
			$time = ( ! empty( $pdtr_options['time'] ) ) ? time() + $pdtr_options['time']*60*60 : time() + 12*60*60;
			wp_schedule_event( $time, 'pdtr_schedules_hours', 'pdtr_auto_hook' );
		    return;
        }

		if ( 1 == $pdtr_options["send_mail_get_update"] ) {
			pdtr_notification_exist_update( $plugin_update_list, $theme_update_list, $core, $languages );
		}

        include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
        if ( false != $core ) {
            include_once( ABSPATH . 'wp-admin/includes/misc.php' );
        }
        include_once( ABSPATH . 'wp-admin/includes/file.php' );
        include_once( ABSPATH . 'wp-admin/includes/update.php' );

        if ( 1 == $pdtr_options['mode'] ) { /* if auto update on */
            /* If WP core need to be update */
            if ( false != $core ) {
                $core_result = pdtr_update_core( true ); /* update the WP core */
            }
            /* Update the list of plugins */
            if ( ! empty( $plugin_update_list ) ) {
                pdtr_update_plugin( $plugin_update_list, true );
            }
            /* Update the list of themes */
            if ( ! empty( $theme_update_list ) ) {
                pdtr_update_theme( $theme_update_list, true );
            }
            /* Update the list of languages */
            if ( ! empty( $language_list_for_update ) && $pdtr_options["update_language"] ) {
                pdtr_update_language( $language_list_for_update, true );
            }
            /* Send mail */
            if ( 1 == $pdtr_options["send_mail_after_update"] && ( ! empty( $theme_update_list ) || ! empty( $plugin_update_list ) || false != $core || ! empty( $languages ) ) ) {
                pdtr_notification_after_update( $plugin_update_list, $theme_update_list, $core, $core_result, $languages );
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
			if ( ! $this_plugin ) {
				$this_plugin = plugin_basename( __FILE__ );
			}
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
			if ( ! is_multisite() || is_network_admin() ) {
				$links[]	=	'<a href="admin.php?page=updater-options">' . __( 'Settings', 'updater' ) . '</a>';
			}
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

			/* show banner go settings pls*/
			bws_plugin_banner_to_settings( $pdtr_plugin_info, 'pdtr_options', 'updater', 'admin.php?page=updater-options' );

			if ( is_multisite() && ! is_network_admin() && is_admin() ) { ?>
				<div class="update-nag"><strong><?php _e( 'Notice:', 'updater' ); ?></strong>
					<?php if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
						_e( 'Due to the peculiarities of the multisite work, Updater plugin has only', 'updater' ); ?> <a target="_blank" href="<?php echo network_admin_url( 'admin.php?page=updater-options' ); ?>"><?php _e( 'Network settings page', 'updater' ); ?></a>
					<?php } else {
						_e( 'Due to the peculiarities of the multisite work, Updater plugin has the network settings page only and it should be Network Activated.', 'updater' ); ?> <a target="_blank" href="<?php echo network_admin_url( 'plugins.php' ); ?>"><?php _e( 'Activate Updater for Network', 'updater' ); ?></a>
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
		global $wpdb;
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		$all_plugins = get_plugins();

		if ( ! array_key_exists( 'updater-pro/updater_pro.php', $all_plugins ) && ! array_key_exists( 'updater-plus/updater-plus.php', $all_plugins ) ) {
			delete_option( 'pdtr_options' );
			delete_site_option( 'pdtr_options' );
			$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->base_prefix . "updater_list`" );
		}

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );
		bws_delete_plugin( plugin_basename( __FILE__ ) );
	}
}

/* When activate plugin */
register_activation_hook( __FILE__, 'pdtr_activation' );

if ( function_exists( 'is_multisite' ) ) {
	if ( is_multisite() ) {
		add_action( 'network_admin_menu', 'pdtr_add_admin_menu' );
	} else {
		add_action( 'admin_menu', 'pdtr_add_admin_menu' );
	}
}
add_action( 'init', 'pdtr_init' );
add_action( 'admin_init', 'pdtr_admin_init' );

add_action( 'plugins_loaded', 'pdtr_plugins_loaded' );
/* Add css-file to the plugin */
add_action( 'admin_enqueue_scripts', 'pdtr_admin_head' );

add_filter( 'admin_body_class', 'pdtr_admin_body_class' );

add_action( 'admin_footer', 'pdtr_processing_site' );

/* Additional links on the plugin page */
add_filter( 'plugin_action_links', 'pdtr_plugin_action_links', 10, 2 );
if ( function_exists( 'is_multisite' ) ) {
	if ( is_multisite() ) {
		add_filter( 'network_admin_plugin_action_links', 'pdtr_plugin_action_links', 10, 2 );
	}
}
add_filter( 'plugin_row_meta', 'pdtr_register_plugin_links', 10, 2 );
/* Add time for cron viev */
add_filter( 'cron_schedules', 'pdtr_schedules' );
/* Function that update all plugins, themes and WP core in auto mode. */
add_action( 'pdtr_auto_hook', 'pdtr_auto_function' );

add_action( 'admin_notices', 'pdtr_plugin_banner' );
