<?php
/**
 * Displays the content on the plugin settings page
 */

require_once( dirname( dirname( __FILE__ ) ) . '/bws_menu/class-bws-settings.php' );

if ( ! class_exists( 'Pdtr_Settings_Tabs' ) ) {
	class Pdtr_Settings_Tabs extends Bws_Settings_Tabs {
		/**
		 * Constructor.
		 *
		 * @access public
		 *
		 * @see Bws_Settings_Tabs::__construct() for more information on default arguments.
		 *
		 * @param string $plugin_basename
		 */
		public function __construct( $plugin_basename ) {
			global $pdtr_options, $pdtr_plugin_info;

			$tabs = array(
				'settings' 		=> array( 'label' => __( 'Settings', 'updater' ) ),
				'misc' 			=> array( 'label' => __( 'Misc', 'updater' ) ),
				/*pls */
				'license'		=> array( 'label' => __( 'License Key', 'updater' ) )
				/* pls*/
			);

			parent::__construct( array(
				'plugin_basename' 	 => $plugin_basename,
				'plugins_info'		 => $pdtr_plugin_info,
				'prefix' 			 => 'pdtr',
				'default_options' 	 => pdtr_get_options_default(),
				'options' 			 => $pdtr_options,
				'is_network_options' => is_multisite(),
				'tabs' 				 => $tabs,
				/*pls */
				'wp_slug'			 => 'updater',				
				'doc_link'			 => 'https://docs.google.com/document/d/1UHXGDpOJ2dZrJpPGHmH_i4U3ph50M1L2WuKC583RmTY/',
				'pro_page' 			 => 'admin.php?page=updater-pro-options',
				'bws_license_plugin' => 'updater-pro/updater_pro.php',
				'link_key' 			 => '347ed3784e3d2aeb466e546bfec268c0pn',
				'link_pn' 			 => '84'
				/* pls*/
			) );

			add_filter( get_parent_class( $this ) . '_additional_restore_options', array( $this, 'additional_restore_options' ) );
			/*pls */
			add_filter( get_parent_class( $this ) . '_additional_misc_options_affected', array( $this, 'additional_misc_options_affected' ) );
			/* pls*/	
		}

		/**
		 * Save plugin options to the database
		 * @access public
		 * @param  void
		 * @return array    The action results
		 */
		public function save_options() {
			
			$this->options["update_core"] 				= ( isset( $_REQUEST["pdtr_update_core"] ) ) ? 1 : 0;
			$this->options["update_plugin"] 			= ( isset( $_REQUEST["pdtr_update_plugin"] ) ) ? 1 : 0;
			$this->options["update_theme"] 				= ( isset( $_REQUEST["pdtr_update_theme"] ) ) ? 1 : 0;
			$this->options["mode"]						= ( isset( $_REQUEST["pdtr_mode"] ) ) ? 1 : 0;
			$this->options["check_all"]					= ( isset( $_REQUEST["pdtr_check_all"] ) ) ? 1 : 0;			

			if ( preg_match( "/^[0-9]{1,5}+$/", $_REQUEST['pdtr_time'] ) && "0" != intval( $_REQUEST["pdtr_time"] ) )
				$this->options["time"] = intval( $_REQUEST["pdtr_time"] );
			else
				$this->options["time"] = $this->default_options["time"];
			
			$this->options["send_mail_get_update"]		= ( isset( $_REQUEST["pdtr_send_mail_get_update"] ) ) ? 1 : 0;
			$this->options["send_mail_after_update"] 	= ( isset( $_REQUEST["pdtr_send_mail_after_update"] ) ) ? 1 : 0;

			/* If user enter Receiver's email check if it correct. Save email if it pass the test. */
			$this->options['to_email_type'] = esc_attr( $_REQUEST["pdtr_to_email_type"] );

			if ( 'default' == $this->options['to_email_type'] ) {
				if ( ! empty( $_REQUEST["pdtr_to_email_default"] ) ) {
					$this->options["to_email"] = $_REQUEST["pdtr_to_email_default"];
				} else {
					$error = __( "Please select a recipient email. Settings are not saved.", 'updater' );
				}
			} else {
				if ( ! empty( $_REQUEST["pdtr_to_email"] ) ) {
					if ( preg_match( '|,|', $_REQUEST["pdtr_to_email"] ) ) {
						$emails = explode( ',', $_REQUEST["pdtr_to_email"] );
					} else {
						$emails[0] = $_REQUEST["pdtr_to_email"];
					}
					foreach ( $emails as $email ) {
						if ( ! is_email( trim( $email ) ) ) {
							$error = __( "Please enter a valid recipient email. Settings are not saved.", 'updater' );
							break;
						}
					}
					$this->options["to_email"] = trim( $_REQUEST["pdtr_to_email"] );
				} else {
					$error = __( "Please enter a valid recipient email. Settings are not saved.", 'updater' );
				}
			}

			$this->options["from_name"] = stripslashes( esc_html( $_REQUEST["pdtr_from_name"] ) );
			if ( empty( $this->options['from_email'] ) )
				$this->options['from_email'] = $this->default_options['from_email'];

			/* If user enter Sender's email check if it correct. Save email if it pass the test. */
			if ( is_email( trim( $_REQUEST["pdtr_from_email"] ) ) || empty( $_REQUEST["pdtr_from_email"] ) )
				$this->options["from_email"] = trim( $_REQUEST["pdtr_from_email"] );
			else
				$error = __( "Please enter a valid sender email. Settings are not saved.", 'updater' );

			/* Add or delete hook of auto/handle mode */
			if ( wp_next_scheduled( 'pdtr_auto_hook' ) )
				wp_clear_scheduled_hook( 'pdtr_auto_hook' );

			if ( '0' != $this->options["mode"] || '0' != $this->options["send_mail_get_update"] ) {
				$time = time() + $this->options['time']*60*60;
				wp_schedule_event( $time, 'pdtr_schedules_hours', 'pdtr_auto_hook' );
			}

			if ( empty( $this->options["update_core"] ) && empty( $this->options["update_plugin"] ) && empty( $this->options["update_theme"] ) )
				$error = __( "Please select at least one option in the 'Check & Update' section. Settings are not saved.", 'updater' );

			/* Update options in the database */
			if ( empty( $error ) ) {
				$this->options = apply_filters( 'pdtr_before_save_options', $this->options );
				if ( $this->is_multisite )
					update_site_option( 'pdtr_options', $this->options );
				else
					update_option( 'pdtr_options', $this->options );
				$message = __( 'Settings saved.', 'updater' );
			}

			return compact( 'message', 'notice', 'error' );
		}

		/**
		 *
		 */
		public function tab_settings() { ?>
			<h3 class="bws_tab_label"><?php _e( 'Updater Settings', 'updater' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>			
			<table class="form-table pdtr_settings_form">
				<tr>
					<th scope="row"><?php _e( 'Check & Update', 'updater' ); ?></th>
					<td>
						<fieldset>
							<label><input type="checkbox" name="pdtr_update_core" value="1" <?php checked( 1, $this->options["update_core"] ); ?> /> <?php _e( 'WordPress', 'updater' ); ?></label>
							<br />
							<label><input type="checkbox" name="pdtr_update_plugin" value="1" <?php checked( 1, $this->options["update_plugin"] ); ?> /> <?php _e( 'Plugins', 'updater' ); ?></label>
							<br />
							<label><input type="checkbox" name="pdtr_update_theme" value="1" <?php checked( 1, $this->options["update_theme"] ); ?> /> <?php _e( 'Themes', 'updater' ); ?></label>
						</fieldset>
					</td>
				</tr>
			</table>
			<!-- pls -->
			<?php if ( ! $this->hide_pro_tabs ) { ?>
				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'updater' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<table class="form-table bws_pro_version">
							<tr>
								<th scope="row"><?php _e( 'Update Inactive Plugins & Themes', 'updater' ); ?></th>
								<td>
									<label>
										<input disabled="disabled" checked="checked" type="checkbox" name="pdtr_check_all" value="1" />
										<span class="bws_info"><?php _e( 'Enable to update inactive plugins and themes.', 'updater' ); ?></span>
									</label>
								</td>
							</tr>
						</table>
					</div>
					<?php $this->bws_pro_block_links(); ?>
				</div>
			<?php } ?>
			<!-- end pls -->
			<table class="form-table pdtr_settings_form">
				<tr>
					<th scope="row"><?php _e( 'Auto Update', 'updater' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="pdtr_mode" value="1" <?php checked( 1, $this->options["mode"] ); ?> />
							<span class="bws_info"><?php _e( 'Enable to update software automatically.', 'updater' ); ?></span>
						</label>
					</td>
				</tr>				
				<tr>
					<th><?php _e( 'Search & Install Updates Every', 'updater' ); ?></th>
					<td>
						<input type="number" name="pdtr_time" class="small-text" value="<?php echo $this->options["time"]; ?>" min="1" max="99999" /> <?php _e( 'hours', 'updater' ); ?>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Receive Email Notifications When', 'updater' ); ?></th>
					<td>
						<fieldset>
							<label><input type="checkbox" name="pdtr_send_mail_get_update" value="1" <?php checked( 1, $this->options["send_mail_get_update"] ); ?> /> <?php _e( 'New updates are available', 'updater' ); ?></label>
							<br>
							<label><input type="checkbox" name="pdtr_send_mail_after_update" value="1" <?php checked( 1, $this->options["send_mail_after_update"] ); ?> /> <?php _e( 'Update is completed', 'updater' ); ?></label>
						</fieldset>
					</td>
				</tr>
				<tr class="pdtr_email_settings">
					<th><?php _e( 'Send Email Notifications to', 'updater' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="radio" name="pdtr_to_email_type" value="default" <?php checked( 'default', $this->options["to_email_type"] ); ?> class="bws_option_affect" data-affect-show=".pdtr_to_email_default" data-affect-hide=".pdtr_to_email_custom" /> 
								<?php _e( 'Default', 'updater' ); ?>
							</label>
							<div class="pdtr_to_email_default">
								<select name="pdtr_to_email_default[]" multiple="multiple">
									<option disabled><?php _e( "Select a username", 'updater' ); ?></option>
									<?php $userslogin = get_users( 'blog_id=' . $GLOBALS['blog_id'] . '&role=administrator' );
									foreach ( $userslogin as $value ) {
										if ( isset( $value->data ) ) {
											if ( $value->data->user_email != '' ) { ?>
												<option value="<?php echo $value->data->user_login; ?>" <?php if ( 'default' == $this->options["to_email_type"] && in_array( $value->data->user_login, $this->options["to_email"] ) ) echo 'selected'; ?>><?php echo $value->data->user_login; ?></option>
											<?php }
										} else {
											if ( $value->user_email != '' ) { ?>
												<option value="<?php echo $value->user_login; ?>" <?php if ( 'default' == $this->options["to_email_type"] && in_array( $value->user_login, $this->options["to_email"] ) ) echo 'selected'; ?>><?php echo $value->user_login; ?></option>
											<?php }
										}
									} ?>
								</select>
							</div>
							<br>
							<label>
								<input type="radio" name="pdtr_to_email_type" value="custom" <?php checked( 'custom', $this->options["to_email_type"] ); ?> class="bws_option_affect" data-affect-show=".pdtr_to_email_custom" data-affect-hide=".pdtr_to_email_default" />
								<?php _e( 'Custom', 'updater' ); ?>
							</label>
							<div class="pdtr_to_email_custom">
								<textarea name="pdtr_to_email"><?php if ( 'custom' == $this->options["to_email_type"] ) echo $this->options["to_email"]; ?></textarea>
								<div class="bws_info"><?php _e( 'You can specify several emails, separating them by commas.', 'updater' ); ?></div>
							</div>
						</fieldset>
						<div class="bws_info"><?php _e( 'Select an existing administrator or a custom email.', 'updater' ); ?></div>
					</td>
				</tr>
				<tr class="pdtr_email_settings">
					<th><?php _e( "Send Email Notifications from", 'updater' ); ?></th>
					<td>
						<p><?php _e( "Name", 'updater' ); ?></p>
						<input type="text" name="pdtr_from_name" maxlength="250" value="<?php echo $this->options["from_name"]; ?>" />
						<p><?php _e( "Email", 'updater' ); ?></p>
						<input type="email" name="pdtr_from_email" maxlength="250" value="<?php echo $this->options["from_email"]; ?>" />
						<div class="bws_info"><?php _e( "Note: Email notifications may be marked as spam or email delivery failures may occur if you'll change this option.", 'updater' ); ?></div>
					</td>
				</tr>
				<?php do_action( 'pdtr_settings_page_action', $this->options ); ?>
			</table>
			<!-- pls -->
			<?php if ( ! $this->hide_pro_tabs ) { ?>
				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'updater' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<table class="form-table bws_pro_version">
							<tr>
								<th><?php _e( 'Backup', 'updater' ); ?></th>
								<td>
									<input disabled type="checkbox" name="pdtr_make_backup" value="1" />
									<span class="bws_info"><?php _e( 'Enable to automatically create a backup before each update (recommended).', 'updater' ); ?></span>
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Backup Rotation', 'updater' ); ?></th>
								<td>
									<select disabled name="pdtr_backups_count">
										<option value="4">4</option>
									</select>
									<div class="bws_info"><?php _e( 'The maximum number of stored backups. When maximum limit is reached the oldest backup will be automatically deleted.', 'updater' ); ?></div>
								</td>
							</tr>				
							<tr>
								<th><?php _e( 'Backup Custom', 'updater' ); ?></th>
								<td>
									<fieldset>
										<label><input disabled type="checkbox" name="pdtr_backup_all_files" value="1" /> <?php _e( 'Folders', 'updater' ); ?></label>
										<br/>
										<label><input disabled type="checkbox" name="pdtr_backup_all_db" value="1" /> <?php _e( 'Tables in database', 'updater' ); ?></label>
									</fieldset>
								</td>
							</tr>			
							<tr>
								<th><?php _e( 'Test Backup', 'updater' ); ?></th>
								<td>
									<input disabled type="checkbox" name="pdtr_test_backup_delete" value="1" /> 
									<span class="bws_info"><?php _e( "Enable to delete test backup when it's finished.", 'updater' ); ?></span>
									<p>
										<input disabled type="button" class="button button-secondary bws_no_bind_notice" name="pdtr_test_backup" value="<?php _e( 'Backup Now', 'updater' ); ?>"/>
									</p>
								</td>
							</tr>				
							<tr>
								<th><?php _e( 'Envato API', 'updater' ); ?></th>
								<td>
									<input disabled type="text" name="pdtr_envato_token" class="widefat" value="" autocomplete="off">
									<div class="bws_info"><?php _e( 'Insert your Envato API Personal Token to enable automatic updates of your installed products.', 'updater' ); ?> <a href="https://build.envato.com/create-token/?purchase:download=t&amp;purchase:verify=t&amp;purchase:list=t" target="_blank"><?php _e( 'Learn More', 'updater' ); ?></a></div>
								</td>
							</tr>
						</table>
					</div>
					<?php $this->bws_pro_block_links(); ?>
				</div>
			<?php } ?>
			<!-- end pls -->
		<?php }
		
		/**
		 * Custom content for Misc tab
		 * @access public
		 */
		public function additional_misc_options_affected() {
			if ( ! $this->hide_pro_tabs ) { ?>
				</table>
					<div class="bws_pro_version_bloc">
						<div class="bws_pro_version_table_bloc">
							<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'updater' ); ?>"></button>
							<div class="bws_table_bg"></div>
							<table class="form-table bws_pro_version">
								<tr>
									<th><?php _e( 'Skip Minor WordPress Updates', 'updater' ); ?></th>
									<td>
										<input disabled="disabled" type="checkbox" name="pdtr_disable_auto_core_update" value="1" /> <span class="bws_info"><?php _e( 'Enable to turn off automatic update of WordPress minor versions. This will not have an impact on plugin’s workability.', 'updater' ); ?></span>
									</td>
								</tr>
							</table>
						</div>
						<?php $this->bws_pro_block_links(); ?>
					</div>
				<table class="form-table">
			<?php }
		}

		/**
		 * Custom functions for "Restore plugin options to defaults"
		 * @access public
		 */
		public function additional_restore_options( $default_options ) {
			wp_clear_scheduled_hook( 'pdtr_auto_hook' );
			wp_schedule_event( time() + $default_options['time']*60*60, 'pdtr_schedules_hours', 'pdtr_auto_hook' );			
			return $default_options;
		}
	}
}