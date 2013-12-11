<?php
/**
 * Plugin Name: WordPress Hotkeys
 * Plugin URI:  http://mightyminnow.com
 * Description: Provides hotkeys to navigate the WordPress dashboard.
 * Version:     1.0.0
 * Author:      MIGHTYminnow
 * Author URI:  http://mightyminnow.com
 * License:     GPLv2+
 */

/**
 * TODO
 * Fix plugins (2) and better solution for update including number
 * Better way to simulate mouseove/hover than manually adding class?
 * Better way to reset if combo of mouse/key hover/off-hover (e.g. use key to hover, then mouse triggers off hover)
 * add ability/option to show hotkeys
 * language stuff i18n
 * add jQuery duplicate validation
 * doesn't work on appearance > menus for some reason :(
 */

// Definitions
define( 'WH_PLUGIN_NAME', 'WordPress Hotkeys' );

// Includes
require_once dirname( __FILE__ ) . '/lib/admin/admin.php';

/**
 * Loads text domain for internationalization
 *
 * @package WordPress Hotkeys
 * @since   1.0.0
 */
function wh_init() {

    // Load plugin text domain
    load_plugin_textdomain( 'wh', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

}
add_action( 'plugins_init', 'wh_init' );

/**
 * Enqueue required scripts & styles and pass PHP variables to jQuery file
 *
 * @package WordPress Hotkeys
 * @since   1.0.0
 */
function wh_admin_scripts() {
    
    // Admin menu items
    global $wh_menu_items;

    // Get options
    $options = get_option( 'wh-options' );

	// Include WP jQuery hotkeys functionality
	wp_enqueue_script( 'jquery-hotkeys' );

	// Include WH jQuery
    wp_enqueue_script( 'wordpress-hotkeys', plugins_url( '/lib/js/wordpress-hotkeys.js', __FILE__ ), array( 'jquery' ), '1.0.0', false );

	// Setup PHP variables to pass to jQuery
	$phpVars = array (
		'menuItems' => $wh_menu_items,
		'adminUrl' => get_admin_url(),
		'showHints' => $options[ 'show-hints' ],
		'closeHoverHotkey' => $options[ 'close-hover-hotkey' ],
	);

	// Pass menu items to jQuery
	wp_localize_script( 'wordpress-hotkeys', 'phpVars', $phpVars );

}
add_action( 'admin_enqueue_scripts', 'wh_admin_scripts' );

/**
 * Setup $menu_item array
 *
 * @package WordPress Hotkeys
 * @since   1.0.0
 */
function wh_menu_functionality() {
    
    // Admin menu items
    global $wh_menu_items;

    // Get admin menu items
	$wh_menu_items = wh_get_menu_items();

	// Add hotkey defaults
	$wh_menu_items = wh_hotkey_defaults( $wh_menu_items );

	// Setup PHP variables to pass to jQuery
	$phpVars = array (
		'menuItemss' => $wh_menu_items,
		'adminUrl' => get_admin_url(),
	);

	// Pass menu items to jQuery
	wp_localize_script( 'wordpress-hotkeys', 'phpVars', $phpVars );

}
add_action( 'admin_init', 'wh_menu_functionality', 9 );

/**
 * Setup menu_items[] array with admin top-level and sub-menu items
 *
 * @package WordPress Hotkeys
 * @since   1.0.0
 *
 * @return  array $wh_menu_items array populated with admin top-level and sub-menu items
 */
function wh_get_menu_items() {

	global $menu, $submenu, $wh_menu_items;

	$wh_menu_items = array();

	// Prevent breakage with AJAX when dragging widgets
	if ( !$menu )
		return false;
			
	// Top level menu items
	foreach ( $menu as $item ) {
		
		$top_name = rtrim( preg_replace( '/<span.*<\/span>/', '', $item[0] ) ); /* Remove all 'count' spans, e.g. Updates <span...>5</span> */

		$top_file = $item[2];

		// Set top level link
		if ( $top_name )
			$wh_menu_items[ $top_name ]['url'] = get_admin_menu_item_url( $item[2] );
	
		// Sub menu items
		foreach ( $submenu as $p_file => $submenu_item ) {

		
			if ( $top_file == $p_file ) {

				foreach ( $submenu_item as $submenu_item ) {
					
					$submenu_item_name = rtrim( preg_replace( '/<span.*<\/span>/', '', $submenu_item[0] ) ); /* Remove all 'count' spans, e.g. Updates <span...>5</span> */

					$sub_file = $submenu_item[2];

					$submenu_item_url = get_admin_menu_item_url( $sub_file );
					
					if ( $submenu_item_name )
						$wh_menu_items[ $top_name ]['sub_items'][$submenu_item_name ]['url'] = $submenu_item_url;

				}

			}

		}

	}
	
	return $wh_menu_items;

}

/**
 * Get the URL of an admin menu item
 *
 * @package WordPress Hotkeys
 * @since   1.0.0
 *
 * @param   string $menu_item_file admin menu item file
 *          - can be obtained via array key #2 for any item in the global $menu or $submenu array
 * @param   boolean $submenu_as_parent
 * 
 * @return  string URL of admin menu item, NULL if the menu item file can't be found in $menu or $submenu 
 */
function get_admin_menu_item_url( $menu_item_file, $submenu_as_parent = true ) {
	global $menu, $submenu, $self, $parent_file, $submenu_file, $plugin_page, $typenow;

	$admin_is_parent = false;
	$item = '';
	$submenu_item = '';
	$url = '';

	// 1. Check if top-level menu item
	foreach( $menu as $key => $menu_item ) {
		if ( array_keys( $menu_item, $menu_item_file, true ) ) {
			$item = $menu[ $key ];
		}

		if ( $submenu_as_parent && ! empty( $submenu_item ) ) {
			$menu_hook = get_plugin_page_hook( $submenu_item[2], $item[2] );
			$menu_file = $submenu_item[2];
		
			if ( false !== ( $pos = strpos( $menu_file, '?' ) ) )
				$menu_file = substr( $menu_file, 0, $pos );
			if ( ! empty( $menu_hook ) || ( ( 'index.php' != $submenu_item[2] ) && file_exists( WP_PLUGIN_DIR . "/$menu_file" ) && ! file_exists( ABSPATH . "/wp-admin/$menu_file" ) ) ) {
				$admin_is_parent = true;
				$url = 'admin.php?page=' . $submenu_item[2];
			} else {
				$url = $submenu_item[2];
			}
		}

		elseif ( ! empty( $item[2] ) && current_user_can( $item[1] ) ) {
			$menu_hook = get_plugin_page_hook( $item[2], 'admin.php' );
			$menu_file = $item[2];

			if ( false !== ( $pos = strpos( $menu_file, '?' ) ) )
				$menu_file = substr( $menu_file, 0, $pos );
			if ( ! empty( $menu_hook ) || ( ( 'index.php' != $item[2] ) && file_exists( WP_PLUGIN_DIR . "/$menu_file" ) && ! file_exists( ABSPATH . "/wp-admin/$menu_file" ) ) ) {
				$admin_is_parent = true;
				$url = 'admin.php?page=' . $item[2];
			} else {
				$url = $item[2];
			}
		}
	}

	// 2. Check if sub-level menu item
	if ( ! $item ) {
		$sub_item = '';
		foreach( $submenu as $top_file => $submenu_items ) {
					
			// Reindex $submenu_items
			$submenu_items = array_values( $submenu_items );

			foreach( $submenu_items as $key => $submenu_item ) {
				if ( array_keys( $submenu_item, $menu_item_file ) ) {
					$sub_item = $submenu_items[ $key ];
					break;
				}
			}					
			
			if ( ! empty( $sub_item ) )
				break;
		}

		// Get top-level parent item
		foreach( $menu as $key => $menu_item ) {
			if ( array_keys( $menu_item, $top_file, true ) ) {
				$item = $menu[ $key ];
				break;
			}
		}

				

		// If the $menu_item_file parameter doesn't match any menu item, return false
		if ( ! $sub_item )
			return false;

		// Get URL
		$menu_file = $item[2];

		if ( false !== ( $pos = strpos( $menu_file, '?' ) ) )
			$menu_file = substr( $menu_file, 0, $pos );

		// Handle current for post_type=post|page|foo pages, which won't match $self.
		$self_type = ! empty( $typenow ) ? $self . '?post_type=' . $typenow : 'nothing';
		$menu_hook = get_plugin_page_hook( $sub_item[2], $item[2] );
		
		$sub_file = $sub_item[2];
		if ( false !== ( $pos = strpos( $sub_file, '?' ) ) )
			$sub_file = substr($sub_file, 0, $pos);

		if ( ! empty( $menu_hook ) || ( ( 'index.php' != $sub_item[2] ) && file_exists( WP_PLUGIN_DIR . "/$sub_file" ) && ! file_exists( ABSPATH . "/wp-admin/$sub_file" ) ) ) {
			// If admin.php is the current page or if the parent exists as a file in the plugins or admin dir
			if ( ( ! $admin_is_parent && file_exists( WP_PLUGIN_DIR . "/$menu_file" ) && ! is_dir( WP_PLUGIN_DIR . "/{$item[2]}" ) ) || file_exists( $menu_file ) )
				$url = add_query_arg( array( 'page' => $sub_item[2] ), $item[2] );
			else
				$url = add_query_arg( array( 'page' => $sub_item[2] ), 'admin.php' );
		} else {
			$url = $sub_item[2];
		}
	}

	return esc_url( $url );

}

/**
 * Setup hotkey defaults on $wh_menu_items array
 *
 * @package WordPress Hotkeys
 * @since   1.0.0
 *
 * @param   array $wh_menu_items array of admin menu items
 * 
 * @return  array admin menu array with hotkey defaults added
 */
function wh_hotkey_defaults( $wh_menu_items ) {

	// General settings
	$general_options['show-hints']                                       = 0;
	$general_options['close-hover-hotkey']                               = 'esc';

	// Hotkeys
	// Dashboard
	$wh_menu_items['Dashboard']['default']                                  = 'd';
	$wh_menu_items['Dashboard']['sub_items']['Home']['default']             = 'h';
	$wh_menu_items['Dashboard']['sub_items']['Updates']['default']          = 'u';

	// Posts
	$wh_menu_items['Posts']['default']                                      = 'p';
	$wh_menu_items['Posts']['sub_items']['All Posts']['default']            = 'a';
	$wh_menu_items['Posts']['sub_items']['Add New']['default']              = 'n';
	$wh_menu_items['Posts']['sub_items']['Categories']['default']           = 'c';
	$wh_menu_items['Posts']['sub_items']['Tags']['default']                 = 't';

	// Media
	$wh_menu_items['Media']['default']                                      = 'm';
	$wh_menu_items['Media']['sub_items']['Library']['default']              = "l";
	$wh_menu_items['Media']['sub_items']['Add New']['default']              = "n";

	// Pages
	$wh_menu_items['Pages']['default']                                      = 'g';
	$wh_menu_items['Pages']['sub_items']['All Pages']['default']            = 'a';
	$wh_menu_items['Pages']['sub_items']['Add New']['default']              = 'n';

	// Comments
	$wh_menu_items['Comments']['default']                                   = 'c';

	// Appearance
	$wh_menu_items['Appearance']['default']                                 = 'a';
	$wh_menu_items['Appearance']['sub_items']['Themes']['default']          = 't';
	$wh_menu_items['Appearance']['sub_items']['Customize']['default']       = 'c';
	$wh_menu_items['Appearance']['sub_items']['Widgets']['default']         = 'w';
	$wh_menu_items['Appearance']['sub_items']['Menus']['default']           = 'm';
	$wh_menu_items['Appearance']['sub_items']['Header']['default']          = 'h';
	$wh_menu_items['Appearance']['sub_items']['Background']['default']      = 'b';
	$wh_menu_items['Appearance']['sub_items']['Editor']['default']          = 'e';

	// Plugins
	$wh_menu_items['Plugins']['default']                                    = 'n';
	$wh_menu_items['Plugins']['sub_items']['Installed Plugins']['default']  = 'i';
	$wh_menu_items['Plugins']['sub_items']['Add New']['default']            = 'n';
	$wh_menu_items['Plugins']['sub_items']['Editor']['default']             = 'e';

	// Users
	$wh_menu_items['Users']['default']                                      = 'u';
	$wh_menu_items['Users']['sub_items']['All Users']['default']            = 'a';
	$wh_menu_items['Users']['sub_items']['Add New']['default']              = 'n';
	$wh_menu_items['Users']['sub_items']['Your Profile']['default']         = 'y';

	// Tools
	$wh_menu_items['Tools']['default']                                      = 't';
	$wh_menu_items['Tools']['sub_items']['Available Tools']['default']      = 'a';
	$wh_menu_items['Tools']['sub_items']['Import']['default']               = 'i';
	$wh_menu_items['Tools']['sub_items']['Export']['default']               = 'e';

	// Settings
	$wh_menu_items['Settings']['default']                                   = 's';
	$wh_menu_items['Settings']['sub_items']['General']['default']           = 'g';
	$wh_menu_items['Settings']['sub_items']['Writing']['default']           = 'w';
	$wh_menu_items['Settings']['sub_items']['Reading']['default']           = 'r';
	$wh_menu_items['Settings']['sub_items']['Discussion']['default']        = 'd';
	$wh_menu_items['Settings']['sub_items']['Media']['default']             = 'm';
	$wh_menu_items['Settings']['sub_items']['Permalinks']['default']        = 'p';
	$wh_menu_items['Settings']['sub_items']['WordPress Hotkeys']['default'] = 'h';

	// Setup hotkey default options
	$reset = false;

	if ( isset( $_GET['wh-reset'] ) && wp_verify_nonce( $_GET['wh-nonce'], 'wh-nonce' ) && current_user_can( 'manage_options' ) ) {
		$reset = true;
	}

	wh_set_defaults( $wh_menu_items, $general_options, $reset );

	return $wh_menu_items;

}

/**
 * Update wh-options option with default hotkeys
 *
 * @package WordPress Hotkeys
 * @since   1.0.0
 *
 * @param   array &$wh_menu_items array of admin menu items (by reference)
 * @param   boolean $reset whether to entirely reset defaults (true) or just not-yet-existing options (false)
 */
function wh_set_defaults( &$wh_menu_items, $general_options, $reset = false ) {

	// Get hotkey options
	$options = get_option( 'wh-options' );

	// General defaults
	// Reset all defaults (if user clicks "Reset" button)
	foreach( $general_options as $option_name => $option ) {
		if ( $reset )
			$options[ $option_name ] = ! empty( $option ) ? $option : '';

		// Add default hotkey for new options
		elseif ( ! isset( $options[ $option_name ] ) )
			$options[ $option_name ] = ! empty( $option ) ? $option : '';
	}
	
	// Hotkey defaults
	foreach ( $wh_menu_items as $item_name => $item) {

		// Reset all defaults (if user clicks "Reset" button)
		if ( $reset )
			$options[ htmlspecialchars( $item_name ) ] = ! empty( $item['default'] ) ? $item['default'] : '';

		// Add default hotkey for new options
		elseif ( ! isset( $options[ htmlspecialchars( $item_name ) ] ) )
			$options[ htmlspecialchars( $item_name ) ] = ! empty( $item['default'] ) ? $item['default']: '';

		$wh_menu_items[ $item_name ]['hotkey'] = $options[ htmlspecialchars( $item_name ) ];

		// Sub menu items
		if ( !empty ( $item['sub_items'] ) ) {

			foreach( $item['sub_items'] as $sub_item_name => $sub_item ) {

				// Reset all defaults (if user clicks "Reset" button)
				if ( $reset )
					$options[ htmlspecialchars( $item_name . '-' . $sub_item_name ) ] = ! empty( $sub_item['default'] ) ? $sub_item['default'] : '';

				// Add default hotkey for new options
				elseif ( ! isset( $options[ htmlspecialchars( $item_name . '-' . $sub_item_name ) ] ) )
					$options[ htmlspecialchars( $item_name . '-' . $sub_item_name ) ] = ! empty( $sub_item['default'] ) ? $sub_item['default'] : '';

				// Set hotkey for $wh_menu_items submenu items
				$wh_menu_items[ $item_name ]['sub_items'][ $sub_item_name ]['hotkey'] = $options[ htmlspecialchars( $item_name . '-' . $sub_item_name ) ];
				
			}

		}

	}

	update_option( 'wh-options', $options);

	if ( $reset )
		wp_redirect( admin_url( 'options-general.php?page=wordpress-hotkeys' ) );
}