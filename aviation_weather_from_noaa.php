<?php
/**
 * Plugin Name: Aviation Weather from NOAA
 * Plugin URI:  http://plugins.machouinard.com/adds
 * Description: Aviation weather data from NOAA's Aviation Digital Data Service (ADDS)
 * Version:     0.1.0
 * Author:      Mark Chouinard
 * Author URI:  http://machouinard.com
 * License:     GPLv2+
 * Text Domain: machouinard_adds
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2013 Mark Chouinard (email : mark@chouinard.me)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using grunt-wp-plugin
 * Copyright (c) 2013 10up, LLC
 * https://github.com/10up/grunt-wp-plugin
 */

// Useful global constants
define( 'MACHOUINARD_ADDS_VERSION', '0.1.0' );
define( 'MACHOUINARD_ADDS_URL',     plugin_dir_url( __FILE__ ) );
define( 'MACHOUINARD_ADDS_PATH',    dirname( __FILE__ ) . '/' );

/**
 * Default initialization for the plugin:
 * - Registers the default textdomain.
 */
function machouinard_adds_init() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'machouinard_adds' );
	load_textdomain( 'machouinard_adds', WP_LANG_DIR . '/machouinard_adds/machouinard_adds-' . $locale . '.mo' );
	load_plugin_textdomain( 'machouinard_adds', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	add_action( 'admin_menu', 'machouinard_adds_admin_settings' );
}

/**
 * Activate the plugin
 */
function machouinard_adds_activate() {
	// First load the init scripts in case any rewrite functionality is being loaded
	machouinard_adds_init();

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'machouinard_adds_activate' );

/**
 * Deactivate the plugin
 * Uninstall routines should be in uninstall.php
 */
function machouinard_adds_deactivate() {

}
register_deactivation_hook( __FILE__, 'machouinard_adds_deactivate' );

// Wireup actions
add_action( 'init', 'machouinard_adds_init' );
// At this point, I'm only using the widget.  Will add functionality to the plugin at a later date.
// Will use plugin at least for weather notifications, storm warnings, etc...
// add_action( 'init', 'machouinard_adds_init' );
add_action( 'widgets_init', 'machouinard_adds_register_widget' );
// Wireup filters

// Wireup shortcodes
// 
//
// Setup the settings menu option - for future use
function machouinard_adds_admin_settings() {
	add_options_page(
		__( 'ADDS Weather Settings', 'machouinard_adds' ),
		__( 'ADDS Weather Settings', 'machouinard_adds' ),
		'manage_options',
		'machouinard_adds_settings',
		'machouinard_adds_settings_page'
	);
}

// Setup the settings page - for future use
function machouinard_adds_settings_page(){

}

function machouinard_adds_register_widget() {
	register_widget( 'machouinard_adds_weather_widget' );
}


class machouinard_adds_weather_widget extends WP_Widget {

	protected $pireps;

	function machouinard_adds_weather_widget() {
		$machouinard_options = array(
			'classname' => 'machouinard_adds_widget_class',
			'description' => __( 'Displays METAR info from NOAA\'s Aviation Digital Data Service', 'machouinard_adds' )
		);
		$this->WP_Widget( 'machouinard_adds_weather_widget', 'ADDS Weather Info', $machouinard_options );
	}

	function form( $instance ) {
		// displays the widget form in the admin dashboard
		$defaults = array( 'icao' => 'KZZV', 'hours' => 2 );
		$instance = wp_parse_args(  (array) $instance, $defaults );
		$icao = $instance['icao'];
		$hours = $instance['hours'];
		?>
		<label for="<?php echo $this->get_field_name( 'icao' ); ?>"><?php _e('ICAO', 'machouinard_adds'); ?></label>
		<input class="widefat" name="<?php echo $this->get_field_name( 'icao' ); ?>" type="text" value="<?php echo esc_attr( $icao ); ?>" />
		<label for="<?php echo $this->get_field_name( 'hours' ); ?>">Hours before now</label>
		<select name="<?php echo $this->get_field_name( 'hours' ); ?>" id="<?php echo $this->get_field_id('hours'); ?>" class="widefat">
		<?php
		for( $x = 1; $x < 7; $x++) {
			echo '<option value="' . $x . '" id="' . $x . '"', $hours == $x ? ' selected="selected"' : '', '>', $x, '</option>';
		}
		?>
	</select>
		<?php
	}

	function update ( $new_instance, $old_instance ) {
		// process widget options to save
		$ptrn = '~[-\s,.;:]+~';
		$instance = $old_instance;
		$instance['icao'] = strtoupper(strip_tags( $new_instance['icao'] ));
		// $icao_arr = preg_split($ptrn, $instance['icao']);
		$instance['icao'] = implode(',', preg_split($ptrn, $instance['icao']));
		$instance['hours'] = strip_tags( $new_instance['hours'] );
		return $instance;
	}

	function widget ( $args, $instance ) {
		$icao = empty( $instance['icao'] ) ? '' : strtoupper($instance['icao']);
		$hours = empty( $instance['hours'] ) ? '&nbsp;' : $instance['hours'];
		$wx = $this->get_metar( $icao, $hours );
		arsort($wx);
		extract( $args );
		echo $before_widget;
		// echo '<pre>';
		// print_r($wx);
		// echo '</pre>';

		if( !empty($wx['metar'])) {
			echo '<p>';
			printf( _n('Most recent report for %s in the past hour', 'Most recent report for %s in the past %d hours', $hours, 'machouinard_adds' ), $icao, $hours );
			echo "</p>";
			foreach( $wx as $type=>$info ){
				echo '<strong>' . strtoupper($type) . "</strong><br />";
				if( is_array( $info )){
					foreach ($info as $key => $value) {
						if( !empty( $value)){
						echo  $value . "<br />\n";
						}
					}
				} else {
				echo $info . "<br />\n";
			}
			}
		}
		echo $after_widget;
	}

	function get_metar( $icao, $hours ) {
		$metar_url = "http://www.aviationweather.gov/adds/dataserver_current/httpparam?dataSource=metars&requestType=retrieve&format=xml&stationString={$icao}&hoursBeforeNow={$hours}";
		$tafs_url = "http://aviationweather.gov/adds/dataserver_current/httpparam?dataSource=tafs&requestType=retrieve&format=xml&hoursBeforeNow={$hours}&mostRecent=true&stationString={$icao}";
		$tafs_url = "http://www.aviationweather.gov/adds/dataserver_current/httpparam?dataSource=tafs&requestType=retrieve&format=xml&stationString={$icao}&hoursBeforeNow={$hours}";
		$xml['metar'] = simplexml_load_file($metar_url);
		$xml['taf'] = simplexml_load_file($tafs_url);
		$wx['taf'] = $xml['taf']->data->TAF[0]->raw_text;
		for( $i = 1; $i <= count($xml['metar']); $i++){
			$wx['metar'][$i] = $xml['metar']->data->METAR[$i]->raw_text;
		}
		// echo '<pre>';
		// print_r($xml['metar']);
		// echo '</pre>';
		// die();
		
		// if(isset($xml['metar']->errors->error)){
		// 	echo $xml['metar']->errors->error;
		// } 
		return $wx;
	}

}
