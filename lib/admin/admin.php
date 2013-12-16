<?php

/**
 * Creates admin settings page
 *
 * @package WordPress Hotkeys
 * @since   1.0
 */
function wh_do_settings_page() {

	// Create admin menu item
	add_options_page( WH_PLUGIN_NAME, 'WordPress Hotkeys', 'manage_options', 'wordpress-hotkeys', 'wh_output_settings');

}
add_action( 'admin_menu', 'wh_do_settings_page' );

/**
 * Outputs settings page with form
 *
 * @package WordPress Hotkeys
 * @since   1.0
 */
function wh_output_settings() { ?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2><?php echo WH_PLUGIN_NAME; ?></h2>
		<form method="post" action="options.php" class="wh-form">
			<style>
				.wh-form tr:first-child .warning {
					border-color: red;
				}

				.wh-form .warning {
					border-color: orange;
				}


			</style>
			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
				<a type="reset" name="wh-reset" id="wh-reset-top" class="button button-primary wh-reset" href="<?php echo admin_url( 'options-general.php?page=wordpress-hotkeys&wh-reset=true&wh-nonce='. wp_create_nonce( 'wh-nonce' ) ); ?>" onClick="return whConfirmReset()"><?php _e( 'Reset Defaults', 'wordpress-hotkeys' ); ?></a>
			</p>
			<?php settings_fields( 'wordpress-hotkeys' ); ?>
			<h2>General Settings</h2>
		    <?php do_settings_sections( 'general-settings' ); ?>
		    <br />
		    <h2>Hotkeys</h2>
		    <?php do_settings_sections( 'wordpress-hotkeys' ); ?>
			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
				<a type="reset" name="wh-reset" id="wh-reset-bottom" class="button button-primary wh-reset" href="<?php echo admin_url( 'options-general.php?page=wordpress-hotkeys&wh-reset=true&wh-nonce='. wp_create_nonce( 'wh-nonce' ) ); ?>" onClick="return whConfirmReset()"><?php _e( 'Reset Defaults', 'wordpress-hotkeys' ); ?></a>
			</p>
			
		</form>
	</div>
<?php }

/**
 * Registers plugin settings
 *
 * @package WordPress Hotkeys
 * @since   1.0
 */
function wh_register_settings() {

	global $wh_menu_items;
			
	register_setting( 'wh-settings-group', 'wh-settings-group', 'wh-settings-validate' );

	// 1. General Options
	add_settings_section(
		'wh-general-settings',
		'',
		'',
		'general-settings'
	);

	// Show hotkey hints
	$fields[] = array (
		'id' => 'show-hints',
		'title' => __( 'Show hotkey hints', 'wordpress-hotkeys' ),
		'callback' => 'wh_output_fields',
		'page' => 'general-settings',
		'section' => 'wh-general-settings',
		'args' => array( 
			'type' => 'checkbox',
			'validation' => 'wp_kses_post',
		)
	);

	// Exit hotkey
	$fields[] = array (
		'id' => 'close-hover-hotkey',
		'title' => __( 'Hotkey to close hover menu', 'wordpress-hotkeys' ),
		'callback' => 'wh_output_fields',
		'page' => 'general-settings',
		'section' => 'wh-general-settings',
		'args' => array( 
			'type' => 'text',
			'validation' => 'wp_kses_post',
		)
	);

	// 2. Do hotkey settings for each admin menu item
	$duplicate = '';
	$sub_duplicates = '';

	// Check for duplicates
	foreach ( $wh_menu_items as $item_file => $item) {
				
		// Top level
		if ( $item['name'] && $item['hotkey'] )
			$hotkeys[ $item_file ] = $item['hotkey'];

		// Sub level
		if ( empty( $item['sub_items'] ) )
			continue;

		foreach ( $item['sub_items'] as $sub_item_file => $sub_item ) {

			if ( $sub_item['name'] && $sub_item['hotkey'] )
				$sub_hotkeys[ $item_file ][ $sub_item_file ] = $sub_item['hotkey'];

		}

		if ( isset( $sub_hotkeys[ $item_file ] ) && wh_get_keys_for_duplicates( $sub_hotkeys[ $item_file ] ) )
			$sub_duplicates[ $item_file ] = wh_get_keys_for_duplicates( $sub_hotkeys[ $item_file ] );
	
	}

	// Top level duplicates array
	$duplicates = wh_get_keys_for_duplicates( $hotkeys );

	if ( $duplicates || $sub_duplicates )
		add_action( 'admin_notices', 'wh_admin_notice' );
			

	// Output actual fields
	foreach ( $wh_menu_items as $item_file => $item) {	
				
		if ( empty( $item['name'] ) )
			continue;

		$item_name = $item['name'];

		// Menu item setting sections
		add_settings_section(
			'wh-settings-section-' . $item_name,
			$item_name,
			'',
			'wordpress-hotkeys'
		);

		// Add duplicate arg if two of the same hotkey exist
		$duplicate = false;
		if ( in_array( $item_file, $duplicates ) )
			$duplicate = true;

		// Top level menu items
		$fields[] = array (
			'id' => $item_file,
			'title' => $item_name,
			'callback' => 'wh_output_fields',
			'page' => 'wordpress-hotkeys',
			'section' => 'wh-settings-section-' . $item_name,
			'args' => array( 
				'type' => 'text',
				'validation' => 'wp_kses_post',
				'level' => 'top',
				'duplicate' => $duplicate,
			)
		);


		// Sub menu items
		if ( !empty ( $item['sub_items'] ) ) {

			foreach( $item['sub_items'] as $sub_item_file => $sub_item ) {

				if ( empty( $sub_item['name'] ) )
					continue;

				$sub_item_name = $sub_item['name'];

				// Add duplicate arg if two of the same hotkey exist
				$duplicate = false;
				if ( isset( $sub_duplicates[ $item_file ] ) && in_array( $sub_item_file, $sub_duplicates[ $item_file ] ) )
					$duplicate = true;

				$fields[] = array (
					'id' => $item_file. '-' . $sub_item_file,
					'title' => $sub_item_name,
					'callback' => 'wh_output_fields',
					'page' => 'wordpress-hotkeys',
					'section' => 'wh-settings-section-' . $item_name,
					'args' => array( 
						'type' => 'text',
						'validation' => 'wp_kses_post',
						'duplicate' => $duplicate,
					)
				);

			}

		}

	}

	foreach ( $fields as $field )
		wh_register_settings_field( $field['id'], $field['title'], $field['callback'], $field['page'], $field['section'], $field );

	// Register settings
	register_setting( 'wordpress-hotkeys', 'wh-options' );

}
add_action( 'admin_init', 'wh_register_settings' );

/**
 * Adds and registers settings field
 *
 * @package WordPress Hotkeys
 * @since   1.0		
 */	
function wh_register_settings_field( $id, $title, $callback, $page, $section, $field ) {

	// Add settings field	
	add_settings_field( $id, $title, $callback, $page, $section, $field );

	// Register setting with appropriate validation
	$validation = !empty( $field['args']['validation'] ) ? $field['args']['validation'] : '';

}

function wh_output_fields( $field ) {

	// Get hotkey options
	$options = get_option( 'wh-options' );

	$value = isset( $options[ htmlspecialchars( $field['id'] ) ] ) ? $options[ htmlspecialchars( $field['id'] ) ] : '';
	
	// Get necessary input args
	$type = $field['args']['type'];

	// Output form elements
	switch( $type ) {

		// Text fields
		case 'text':
			// Check if this hotkey has a duplicate
			$class = '';
			if ( isset( $field['args']['duplicate'] ) && $field['args']['duplicate'] )
				$class = ' class="warning" ';

			echo '<input name="wh-options[' . htmlspecialchars( $field['id'] ) . ']" id="' . $field['id'] . '" type="' . $type . '" value="' . $value . '"' . $class . '/>';
			
			// Indicate top-level menu items
			if ( isset( $field['args']['level'] ) )
				echo ' [top level]';

			break;

		// Checkbox
		case 'checkbox':
			echo '<input name="wh-options[' . $field['id'] . ']" id="' . $field['id'] . '" type="hidden" value="0"' . checked( get_option( 'wh-options' )[ $field['id'] ], 1, false ) . '" />';
			echo '<input name="wh-options[' . $field['id'] . ']" id="' . $field['id'] . '" type="' . $type . '" value="1"' . checked( get_option( 'wh-options' )[ $field['id'] ], 1, false ) . '" />';
			break;

	}
	
	// After text
	if ( !empty( $field['args']['after_text'] ) )
		echo ' <em>' . $field['args']['after_text'] . '</em>';

	// Description
	if ( !empty( $field['args']['description'] ) )
		echo '<br /><em>' . $field['args']['description'] . "</em>\n";

}

function wh_get_keys_for_duplicates( $array ) {

	$counts = array_count_values( $array );
	
	$filtered = array_filter( $counts, function( $value ) {
	    return $value != 1;
	});

	return array_keys( array_intersect( $array, array_keys( $filtered ) ) );

}

function wh_admin_notice() { ?>
	<div class="error">
		<p><?php printf( __( '<b>There are duplicate hotkeys.</b> Please visit the %sWordPress Hotkeys settings page%s to fix this issue.', 'wordpress-hotkeys' ), '<a href="options-general.php?page=wordpress-hotkeys&settings-updated=true">', '</a>' ); ?></p>
		<p>Top level duplicates are indicated with red.<br />
			Sub-level duplicates are indicated with orange.</p>
	</div>
<?php }