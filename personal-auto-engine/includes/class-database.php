<?php
/**
 * Database class.
 *
 * @package personal-auto-engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages custom tables.
 */
class PADE_Database {

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'maybe_upgrade' ) );
	}

	/**
	 * Create tables on activation.
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::create_tables();
		PADE_Scheduler::schedule();
	}

	/**
	 * Table for queue.
	 *
	 * @return string
	 */
	public static function queue_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'pade_queue';
	}

	/**
	 * Table for logs.
	 *
	 * @return string
	 */
	public static function logs_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'pade_logs';
	}

	/**
	 * Optional image tracking table.
	 *
	 * @return string
	 */
	public static function images_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'pade_images';
	}

	/**
	 * Keep tables synced with version.
	 *
	 * @return void
	 */
	public static function maybe_upgrade(): void {
		$installed = get_option( 'pade_db_version', '' );
		if ( PADE_VERSION !== $installed ) {
			self::create_tables();
			update_option( 'pade_db_version', PADE_VERSION );
		}
	}

	/**
	 * Create plugin tables.
	 *
	 * @return void
	 */
	private static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$queue   = self::queue_table();
		$logs    = self::logs_table();
		$images  = self::images_table();

		$sql_queue = "CREATE TABLE {$queue} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT UNSIGNED NOT NULL,
			payload_json LONGTEXT NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			retry_count INT NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY status_idx (status),
			KEY post_idx (post_id)
		) {$charset};";

		$sql_logs = "CREATE TABLE {$logs} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			timestamp DATETIME NOT NULL,
			platform VARCHAR(50) NOT NULL,
			post_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			http_code INT NOT NULL DEFAULT 0,
			success_flag TINYINT(1) NOT NULL DEFAULT 0,
			raw_response LONGTEXT NOT NULL,
			PRIMARY KEY (id),
			KEY platform_idx (platform),
			KEY time_idx (timestamp)
		) {$charset};";

		$sql_images = "CREATE TABLE {$images} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			attachment_id BIGINT UNSIGNED NOT NULL,
			last_used_at DATETIME NULL,
			PRIMARY KEY (id),
			UNIQUE KEY attachment_idx (attachment_id)
		) {$charset};";

		dbDelta( $sql_queue );
		dbDelta( $sql_logs );
		dbDelta( $sql_images );
	}
}
