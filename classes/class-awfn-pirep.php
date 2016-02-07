<?php

/**
 * Class AwfnPirep
 *
 * This class retrieves PIREPS from a specified timeframe and builds the HTML output
 *
 * @package     Aviation Weather from NOAA
 * @subpackage  Pirep
 * @since       0.4.0
 */
class AwfnPirep extends Awfn {

	/**
	 * AwfnPirep constructor.
	 *
	 * Builds URL for Awfn::load_xml()
	 *
	 * @param      $lat
	 * @param      $lng
	 * @param int  $distance
	 * @param int  $hours
	 * @param bool $show
	 *
	 * @since 0.4.0
	 */
	public function __construct( $lat, $lng, $distance = 100, $hours = 1, $show = true ) {
		$this->show     = (bool) $show;
		self::$log_name = 'AircraftReport';
		$base           = 'https://aviationweather.gov/adds/dataserver_current/httpparam?dataSource=aircraftreports&requestType=retrieve';
		$base .= '&format=xml&radialDistance=%d;%f,%f&hoursBeforeNow=%d';
		$this->url = sprintf( $base, $distance, $lng, $lat, $hours );

		parent::__construct();

	}

	/**
	 * Iterates through SimpleXMLElement to retrieve pireps
	 *
	 * data should include at least one pirep by the time it arrives here.
	 *
	 * @since 0.4.0
	 */
	public function decode_data() {

		if ( $this->xmlData ) {
			foreach ( $this->xmlData as $report ) {
				$this->data[] = (string) $report->raw_text;
			}
		} else {
//			$this->log->debug( 'No pirep data' );
		}
	}

	/**
	 * Builds HTML output for display on front-end
	 *
	 * @since 0.4.0
	 */
	public function build_display() {

		if ( $this->data ) {

			$count = count( $this->data );

			$count_display = sprintf( '<span class="awfn-min">(%d)</span>', $count );

			$this->display_data = '<section id="pirep"><header>';
			$this->display_data .= sprintf( _n( 'Pirep %s', 'Pireps %s', $count, Adds_Weather_Widget::get_widget_slug() ), $count_display );
			$this->display_data .= '<span class="fa fa-sort-desc"></span></header><section id="all-pireps">';

			foreach ( $this->data as $pirep ) {
				$this->display_data .= sprintf( '<article class="pirep">%s</article>', $pirep );
			}
			$this->display_data .= '</section></section>';
		} else {
			$this->display_data = '<article class="no-pirep">' . __( 'No PIREPS found', Adds_Weather_Widget::get_widget_slug() ) .
			                      '</article>';
		}
	}
}