<?php
/**
 * Scheduler class.
 *
 * @package personal-auto-engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles WP-Cron scheduling.
 */
class PADE_Scheduler {

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'cron_schedules', array( __CLASS__, 'add_schedule' ) );
		self::schedule();
	}

	/**
	 * Add 5-min schedule.
	 *
	 * @param array<string,array<string,mixed>> $schedules Existing schedules.
	 * @return array<string,array<string,mixed>>
	 */
	public static function add_schedule( array $schedules ): array {
		$schedules['pade_every_five_minutes'] = array(
			'interval' => 300,
			'display'  => __( 'Every 5 Minutes (PADE)', 'personal-auto-engine' ),
		);

		return $schedules;
	}

	/**
	 * Schedule queue processing.
	 *
	 * @return void
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( 'pade_process_queue_event' ) ) {
			wp_schedule_event( time() + 60, 'pade_every_five_minutes', 'pade_process_queue_event' );
		}
	}

	/**
	 * Clear events.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'pade_process_queue_event' );
	}
}
