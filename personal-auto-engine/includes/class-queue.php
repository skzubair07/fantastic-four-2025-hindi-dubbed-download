<?php
/**
 * Queue engine.
 *
 * @package personal-auto-engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles queue lifecycle.
 */
class PADE_Queue {

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'publish_post', array( __CLASS__, 'enqueue_post' ), 10, 2 );
		add_action( 'pade_process_queue_event', array( __CLASS__, 'process_pending' ) );
	}

	/**
	 * Enqueue on publish.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public static function enqueue_post( int $post_id, WP_Post $post ): void {
		if ( 'post' !== $post->post_type ) {
			return;
		}

		$settings = PADE_Settings::get();
		$status   = ! empty( $settings['manual_approval'] ) ? 'pending' : 'approved';

		$payload = array(
			'post_id'  => $post_id,
			'platform' => 'telegram',
			'caption'  => '',
		);

		self::insert_queue_item( $post_id, $payload, $status );
	}

	/**
	 * Insert queue item.
	 *
	 * @param int                 $post_id Post ID.
	 * @param array<string,mixed> $payload Payload.
	 * @param string              $status Status.
	 * @return int
	 */
	public static function insert_queue_item( int $post_id, array $payload, string $status = 'pending' ): int {
		global $wpdb;

		$table = PADE_Database::queue_table();
		$now   = current_time( 'mysql' );

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (post_id, payload_json, status, retry_count, created_at, updated_at)
				 VALUES (%d, %s, %s, %d, %s, %s)",
				$post_id,
				wp_json_encode( $payload ),
				sanitize_key( $status ),
				0,
				$now,
				$now
			)
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Approve queue item.
	 *
	 * @param int $id Queue ID.
	 * @return void
	 */
	public static function approve( int $id ): void {
		self::update_status( $id, 'approved' );
	}

	/**
	 * Process approved/scheduled entries.
	 *
	 * @return void
	 */
	public static function process_pending(): void {
		global $wpdb;
		$table = PADE_Database::queue_table();

		$items = $wpdb->get_results(
			"SELECT * FROM {$table} WHERE status IN ('approved','scheduled') ORDER BY created_at ASC LIMIT 10"
		);

		if ( empty( $items ) ) {
			return;
		}

		foreach ( $items as $item ) {
			self::update_status( (int) $item->id, 'processing' );
			$payload           = json_decode( (string) $item->payload_json, true );
			$payload           = is_array( $payload ) ? $payload : array();
			$platform          = sanitize_key( $payload['platform'] ?? 'telegram' );
			$payload['post_id'] = (int) $item->post_id;

			if ( empty( $payload['caption'] ) ) {
				$payload['caption'] = PADE_AI_Engine::generate_caption( (int) $item->post_id, $platform );
			}

			$result = PADE_Router::dispatch( $platform, $payload );
			if ( ! empty( $result['success'] ) ) {
				self::update_status( (int) $item->id, 'completed' );
			} else {
				self::handle_failure( (int) $item->id, (int) $item->retry_count );
			}
		}
	}

	/**
	 * Add bulk image queue jobs.
	 *
	 * @param array<int,int> $attachment_ids Media attachment IDs.
	 * @param string         $platform Platform.
	 * @return int Number queued.
	 */
	public static function bulk_image_enqueue( array $attachment_ids, string $platform ): int {
		$settings = PADE_Settings::get();
		$limit    = absint( $settings['daily_post_limit'] );
		$count    = 0;

		foreach ( $attachment_ids as $attachment_id ) {
			if ( $count >= $limit ) {
				break;
			}

			if ( ! self::mark_image_if_available( (int) $attachment_id ) ) {
				continue;
			}

			$post_id = (int) get_post_field( 'post_parent', $attachment_id );
			$payload = array(
				'post_id'       => $post_id,
				'platform'      => sanitize_key( $platform ),
				'attachment_id' => (int) $attachment_id,
				'caption'       => PADE_AI_Engine::generate_caption( $post_id, $platform ),
			);

			self::insert_queue_item( $post_id, $payload, 'approved' );
			++$count;
		}

		return $count;
	}

	/**
	 * Get queue rows.
	 *
	 * @param int $limit Limit.
	 * @return array<int,object>
	 */
	public static function get_items( int $limit = 100 ): array {
		global $wpdb;
		$table = PADE_Database::queue_table();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d",
				$limit
			)
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Queue summary.
	 *
	 * @return array<string,int>
	 */
	public static function get_summary(): array {
		global $wpdb;
		$table = PADE_Database::queue_table();

		$rows = $wpdb->get_results( "SELECT status, COUNT(*) AS total FROM {$table} GROUP BY status", ARRAY_A );
		$out  = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$out[ sanitize_key( $row['status'] ) ] = (int) $row['total'];
			}
		}

		return $out;
	}

	/**
	 * Update queue status.
	 *
	 * @param int    $id Queue ID.
	 * @param string $status Status.
	 * @return void
	 */
	private static function update_status( int $id, string $status ): void {
		global $wpdb;
		$table = PADE_Database::queue_table();

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = %s, updated_at = %s WHERE id = %d",
				sanitize_key( $status ),
				current_time( 'mysql' ),
				$id
			)
		);
	}

	/**
	 * Retry handling.
	 *
	 * @param int $id Queue ID.
	 * @param int $retry Current retry count.
	 * @return void
	 */
	private static function handle_failure( int $id, int $retry ): void {
		global $wpdb;
		$table = PADE_Database::queue_table();

		if ( $retry >= 2 ) {
			self::update_status( $id, 'failed' );
			return;
		}

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET retry_count = retry_count + 1, status = %s, updated_at = %s WHERE id = %d",
				'scheduled',
				current_time( 'mysql' ),
				$id
			)
		);
	}

	/**
	 * Mark image used.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	private static function mark_image_if_available( int $attachment_id ): bool {
		global $wpdb;
		$table = PADE_Database::images_table();
		$now   = current_time( 'mysql' );

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE attachment_id = %d",
				$attachment_id
			)
		);

		if ( $existing ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET last_used_at = %s WHERE attachment_id = %d",
					$now,
					$attachment_id
				)
			);
			return false;
		}

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (attachment_id, last_used_at) VALUES (%d, %s)",
				$attachment_id,
				$now
			)
		);

		return true;
	}
}
