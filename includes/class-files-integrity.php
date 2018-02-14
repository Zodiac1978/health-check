<?php

/**
 * Check all core files against the checksums provided by WordPress API.
 *
 * @package Health Check
 */

/**
 * Class Files_Integrity
 */
class Files_Integrity {

	/**
	 * Gathers checksums from WordPress API and cross checks the core files in the current installation.
	 *
	 * @return void
	 */
	static function run_files_integrity_check() {

		$checksums = Files_Integrity::call_checksum_api();

		$files = Files_Integrity::parse_checksum_results( $checksums );

		Files_Integrity::create_the_response( $files );

	}

	/**
	* Calls the WordPress API on the checksums endpoint
	*
	* @uses get_bloginfo()
	* @uses get_locale()
	* @uses ABSPATH
	* @uses wp_remote_get()
	* @uses get_bloginfo()
	*
	* @return array
	*/
	static function call_checksum_api() {
		// Setup variables.
		$wpversion = get_bloginfo( 'version' );
		$wplocale  = get_locale();

		// Setup API Call.
		$checksumapi = wp_remote_get( 'https://api.wordpress.org/core/checksums/1.0/?version=' . $wpversion . '&locale=' . $wplocale );

		// Encode the API response body.
		$checksumapibody = json_decode( wp_remote_retrieve_body( $checksumapi ), true );

		return $checksumapibody;
	}

	/**
	* Parses the results from the WordPress API call
	*
	* @uses file_exists()
	* @uses md5_file()
	* @uses ABSPATH
	*
	* @param array $checksums
	*
	* @return array
	*/
	static function parse_checksum_results( $checksums ) {
		$filepath = ABSPATH;
		$files    = array();
		// Parse the results.
		foreach ( $checksums['checksums'] as $file => $checksum ) {
			// Check the files.
			if ( file_exists( $filepath . $file ) && md5_file( $filepath . $file ) !== $checksum ) {
				$reason = esc_html__( 'Content changed', 'health-check' );
				array_push( $files, array( $file, $reason ) );
			} elseif ( ! file_exists( $filepath . $file ) ) {
				$reason = esc_html__( 'File not found', 'health-check' );
				array_push( $files, array( $file, $reason ) );
			}
		}
		return $files;
	}

	/**
	* Generates the response
	*
	* @uses wp_send_json_success()
	* @uses wp_die()
	* @uses ABSPATH
	*
	* @param null|array $files
	*
	* @return array
	*/
	static function create_the_response( $files ) {
		$filepath = ABSPATH;
		$output   = '';

		if ( empty( $files ) ) {
			$output .= '<div class="notice notice-success inline"><p>';
			$output .= esc_html__( 'All files passed the check. Everything seems to be ok!', 'health-check' );
			$output .= '</p></div>';

			$response = array(
				'message' => $output,
			);

			wp_send_json_success( $response );

			wp_die();
		} else {
			$output .= '<div class="notice notice-error inline"><p>';
			$output .= __( 'It appears that some files have been tampered with. Please either update WordPress or manually replace the files you see on the list and run the <code>File Integrity</code> check again.', 'health-check' );
			$output .= '</p></div><table class="widefat striped file-integrity-table"><thead><tr><th>';
			$output .= esc_html__( 'Status', 'health-check' );
			$output .= '</th><th>';
			$output .= esc_html__( 'File', 'health-check' );
			$output .= '</th><th>';
			$output .= esc_html__( 'Reason', 'health-check' );
			$output .= '</th></tr></thead><tfoot><tr><td>';
			$output .= esc_html__( 'Status', 'health-check' );
			$output .= '</td><td>';
			$output .= esc_html__( 'File', 'health-check' );
			$output .= '</td><td>';
			$output .= esc_html__( 'Reason', 'health-check' );
			$output .= '</td></tr></tfoot><tbody>';
			foreach ( $files as $tampered ) {
				$output .= '<tr>';
				$output .= '<td><span class="error"></span></td>';
				$output .= '<td>' . esc_attr( $filepath ) . esc_attr( $tampered[0] ) . '</td>';
				$output .= '<td>' . esc_attr( $tampered[1] ) . '</td>';
				$output .= '</tr>';
			}
			$output .= '</tbody>';
			$output .= '</table>';

			$response = array(
				'message' => $output,
			);

			wp_send_json_success( $response );

			wp_die();
		}
	}

}
