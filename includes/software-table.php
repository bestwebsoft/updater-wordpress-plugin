<?php
/**
 * Displays the content on the plugin 'Installed Software' page
 * @subpackage Updater
 * @since 1.35
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
if ( ! class_exists( 'Pdtr_Software_List' ) ) {
	class Pdtr_Software_List extends WP_List_Table {
		/**
		* Constructor of class
		*/
		function __construct() {
			parent::__construct( array(
				'singular'  => __( 'item', 'updater' ),
				'plural'    => __( 'items', 'updater' )
			) );
		}
		/**
		* Function to prepare data before display
		* @return void
		*/
		function prepare_items() {
			$columns = $this->get_columns();
			$hidden = $sortable = array();
			$this->_column_headers = array( $columns, $hidden, $sortable );

			$this->items = $this->software_list();
			$total_items = $this->items_count();
			$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page'    => $total_items
			) );
		}

		/**
		 * Get a list of columns.
		 * @return array list of columns and titles
		 */
		function get_columns() {
			$columns = array(
				'cb'    		=> '<input type="checkbox" />',
				'title'			=> __( 'Title', 'updater' ),
				'version'   	=> __( 'Version', 'updater' ),
				'auto_update' 	=> __( 'Auto Update', 'updater' ),
				'category'  	=> __( 'Category', 'updater' )
			);			
			return $columns;
		}
		/**
		 * Function to add action links to drop down menu before and after software list
		 * @return array of actions
		 */
		function get_bulk_actions() {
			$actions = array();
			$actions['update'] = __( 'Update Now', 'updater' );
			return $actions;
		}
		/**
		 * Fires when the default column output is displayed for a single row.
		 * @param      string    $column_name      The custom column's name.
		 * @param      array     $item             The cuurrent letter data.
		 * @return    void
		 */
		function column_default( $item, $column_name ) {
			switch ( $column_name ) {
				case 'version':
					$return = $item['version'];
					if ( ! empty( $item['new_version'] ) && $item['version'] != $item['new_version'] ) {
						$return .= '<div>' . __( 'New version is available.', 'updater' ) . ' ';
						
						if ( 'plugin' == $item['type'] )
							$link_part = '&pdtr_plugin_id=' . $item['wp_key'];
						elseif ( 'theme' == $item['type'] )
							$link_part = '&pdtr_theme_id=' . $item['wp_key'];
						else
							$link_part = '&pdtr_core_id=' . $item['wp_key'];

						if ( $item['type'] != 'core' ) {
							$return .= sprintf( '<a class="thickbox" href="%1$s">' . __( 'View %2$s details' ) . '</a>',
								$item['url'],
								$item['new_version']
							);

							$return .= ' ' . __( 'or', 'updater' ) . ' <a class="pdtr-update-now" href="' . wp_nonce_url( self_admin_url( 'admin.php?page=updater&pdtr_tab_action=update' . $link_part ), 'updater-update' . $item['wp_key'] ) . '">' . __( 'update now', 'updater' ) . '.</a>';
						} else {
							$return .= '<a href="' . wp_nonce_url( self_admin_url( 'admin.php?page=updater&pdtr_tab_action=update' . $link_part ), 'updater-update' . $item['wp_key'] ) . '">' . __( 'Update now.', 'updater' ) . '</a>';
						}
						$return .= '</div>';						
					}
					return $return;
				case 'category':
					if ( 'plugin' == $item['type'] )
						return __( 'Plugin', 'updater' );
					elseif ( 'theme' == $item['type'] )
						return __( 'Theme', 'updater' );
					else
						return __( 'Core', 'updater' );
				case 'auto_update':
					return __( 'Enabled', 'updater' );
				case 'title':
					return $item['name'];
				default:
					return print_r( $item, true ) ;
			}
		}
		/**
		 * Function to add column of checboxes
		 * @param     array     $item        The cuurrent letter data.
		 * @return    string                  with html-structure of <input type=['checkbox']>
		 */
		function column_cb( $item ) {
			if ( 'plugin' == $item['type'] )
				return sprintf( '<input type="checkbox" name="pdtr_plugin_id[]" value="%2s" />', $item['wp_key'], $item['wp_key'] );
			elseif ( 'theme' == $item['type'] )
				return sprintf( '<input type="checkbox" name="pdtr_theme_id[]" value="%2s" />', $item['wp_key'], $item['wp_key'] );
			else
				return sprintf( '<input type="checkbox" name="pdtr_core_id" value="%2s" />', $item['wp_key'], $item['wp_key'] );
		}
		/**
		 * Function to add action links to title column depenting on status page
		 * @param    array     $item           The current letter data.
		 * @return   string                     with action links
		 */
		function column_title( $item ) {
			/*pls */
			global $pdtr_options;
			if ( bws_hide_premium_options_check( $pdtr_options ) ) {
			/* pls*/
				return $item['name'];
			/*pls */
			} else {			
				$actions       = array();
				$actions['disable'] = '<a class="pdtr-disable" href="#">' . __( 'Disable Auto Update', 'updater' ) . ' (' . __( 'Available in Pro', 'updater' ) . ')</a>';
				return sprintf( '%1$s %2$s', $item['name'], $this->row_actions( $actions ) );
			}
			/* pls*/
		}

		/**
		 * Function to add necessary class and id to table row
		 * @param array $user with user data
		 * @return void
		 */
		function single_row( $item ) {
			$row_class = ( ! empty( $item['new_version'] ) && $item['version'] != $item['new_version'] ) ? 'pdtr-update-available' : '';
			echo '<tr data-type="' . $item['type'] . '" data-key="' . $item['wp_key'] . '" class="' . $row_class . '">';
				$this->single_row_columns( $item );
			echo "</tr>\n";
		}

		/**
		 * Function to get software list
		 * @return array list of letters
		 */
		function software_list() {
			/* Get information about WP core and installed plugins */
			$result_list_all = pdtr_processing_site();
			return $result_list_all;
		}

		/**
		 * Function to get number of all Software
		 * @return sting Software number
		 */
		function items_count() {
			return count( $this->items );
		}
	}
}

/**
 *
 * @return    array      $action_message
 */
if ( ! function_exists( 'pdtr_handle_action' ) ) {
	function pdtr_handle_action() {
		/* Get necessary action */
		/* bulk actions */
		$action = ( isset( $_POST['action'] ) && 'update' == $_POST['action'] ) ? 1 : 0;
		if ( ! $action )
			$action = ( isset( $_POST['action2'] ) && 'update' == $_POST['action2'] ) ? 1 : 0;
		
		if ( ! $action ) {
			/* action links */
			$action = ( isset( $_REQUEST['pdtr_tab_action'] ) && 'update' == $_REQUEST['pdtr_tab_action'] ) ? 1 : 0;
			
			$wp_key = '';
			if ( isset( $_REQUEST['pdtr_plugin_id'] ) )
				$wp_key = $_REQUEST['pdtr_plugin_id'];
			elseif ( isset( $_REQUEST['pdtr_theme_id'] ) )
				$wp_key = $_REQUEST['pdtr_theme_id'];
			elseif ( isset( $_REQUEST['pdtr_core_id'] ) )
				$wp_key = $_REQUEST['pdtr_core_id'];

			$nonce_action    = 'updater-update' . $wp_key;
			$nonce_query_arg = '_wpnonce';
		} else {
			$nonce_action    = plugin_basename( __FILE__ );
			$nonce_query_arg = 'pdtr_nonce_name';
		}

		if ( $action && check_admin_referer( $nonce_action, $nonce_query_arg ) ) {
			global $pdtr_options;

			$core_for_update = false;
			$plugin_list_for_update = $theme_list_for_update = array();

			if ( isset( $_REQUEST['pdtr_plugin_id'] ) ) {
				foreach ( (array)$_REQUEST['pdtr_plugin_id'] as $value ) {
					$plugin_list_for_update[] = $value;
				}
			}
			if ( isset( $_REQUEST['pdtr_theme_id'] ) ) {
				foreach ( (array)$_REQUEST['pdtr_theme_id'] as $value ) {
					$theme_list_for_update[] = $value;
				}
			}
			if ( isset( $_REQUEST['pdtr_core_id'] ) )
				$core_for_update = true;

			include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
			if ( false != $core_for_update )
				include_once( ABSPATH . 'wp-admin/includes/misc.php' );
			include_once( ABSPATH . 'wp-admin/includes/file.php' );
			include_once( ABSPATH . 'wp-admin/includes/update.php' );

			echo '<div class="update-php">';

			/* Update checked WP core */
			$core_result = false;
			if ( $core_for_update )
				$core_result = pdtr_update_core();
			/* Update checked plugins */
			if ( ! empty( $plugin_list_for_update ) )
				pdtr_update_plugin( $plugin_list_for_update );
			/* Update checked themes */
			if ( ! empty( $theme_list_for_update ) )
				pdtr_update_theme( $theme_list_for_update );

			if ( ! empty( $plugin_list_for_update ) || ! empty( $theme_list_for_update ) || $core_for_update ) {
				$message = "";
				/* Send mail if it's need */
				if ( 1 == $pdtr_options["send_mail_after_update"] ) {
					if ( $core_result || ! empty( $plugin_list_for_update ) || ! empty( $theme_list_for_update ) ) {
						echo '<h2>' . __( 'Sending Report...', 'updater' ) . '</h2>';
						$result_mail = pdtr_notification_after_update( $plugin_list_for_update, $theme_list_for_update, $core_for_update, $core_result );

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

						if ( $result_mail != true ) { ?>
							<p><?php printf( __( "Sorry, Your email could not be sent to %s.", 'updater' ), implode( ",", $emails ) ); ?></p>
						<?php } else { ?>
							<p><?php printf( __( "The email with the update results was sent to %s.", 'updater' ), implode( ",", $emails ) ); ?></p>
						<?php }
					}
				} ?>
				<p><a target="_parent" title="<?php _e( 'Go to the Updater page', 'updater' ); ?>" href="admin.php?page=updater"><?php _e( 'Go back to the Updater page', 'updater' ); ?></a></p>
			<?php }

			echo '</div>';

			if ( ! empty( $plugin_list_for_update ) || ! empty( $theme_list_for_update ) || $core_for_update ) {
				echo '</div>';
				include( ABSPATH . 'wp-admin/admin-footer.php' );
				echo '</div>';
				exit;
			}			
		}
	}
}

/**
 * Display list
 * @return void
 */
if ( ! function_exists( 'pdtr_display_table' ) ) {
	function pdtr_display_table() {		
		$pdtr_software_list = new Pdtr_Software_List();
		pdtr_handle_action(); ?>
		<form method="post" action="">
			<?php $pdtr_software_list->views();
			$pdtr_software_list->prepare_items();
			$pdtr_software_list->current_action();
			$pdtr_software_list->display();
			wp_nonce_field( plugin_basename( __FILE__ ), 'pdtr_nonce_name' ); ?>
		</form>
	<?php }
}