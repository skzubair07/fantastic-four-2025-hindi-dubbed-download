<?php
/**
 * Logger class.
 *
 * @package personal-auto-engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central logger.
 */
class PADE_Logger {

	/**
	 * Log API response.
	 *
	 * @param string $platform Platform name.
	 * @param int    $post_id Post ID.
	 * @param int    $http_code HTTP status.
	 * @param mixed  $raw_response Raw API response.
	 * @param int    $success_flag Success flag.
	 * @return void
	 */
	public static function log( string $platform, int $post_id, int $http_code, $raw_response, int $success_flag ): void {
		global $wpdb;

		$table   = PADE_Database::logs_table();
		$json    = wp_json_encode( $raw_response );
		$payload = ( false === $json ) ? wp_json_encode( array( 'message' => 'Failed to encode response.' ) ) : $json;

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (timestamp, platform, post_id, http_code, success_flag, raw_response)
				 VALUES (%s, %s, %d, %d, %d, %s)",
				current_time( 'mysql' ),
				sanitize_key( $platform ),
				$post_id,
				$http_code,
				$success_flag,
				$payload
			)
		);

		update_option( 'pade_last_ai_response', (string) $payload );
	}

	/**
	 * Get recent logs.
	 *
	 * @param int $limit Number of logs.
	 * @return array<int, object>
	 */
	public static function recent_logs( int $limit = 50 ): array {
		global $wpdb;
		$table = PADE_Database::logs_table();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY timestamp DESC LIMIT %d",
				$limit
			)
		);

		return is_array( $rows ) ? $rows : array();
	}
}
