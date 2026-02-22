<?php
/**
 * Control center page.
 *
 * @package personal-auto-engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Diagnostics dashboard.
 */
class PADE_Admin_Control_Center_Page {

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_pade_test_platform', array( __CLASS__, 'test_platform' ) );
	}

	/**
	 * Register submenu.
	 *
	 * @return void
	 */
	public static function menu(): void {
		add_submenu_page(
			'pade-settings',
			__( 'PADE Control Center', 'personal-auto-engine' ),
			__( 'Control Center', 'personal-auto-engine' ),
			'manage_options',
			'pade-control-center',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Handle API test.
	 *
	 * @return void
	 */
	public static function test_platform(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized request.', 'personal-auto-engine' ) );
		}

		check_admin_referer( 'pade_test_platform' );
		$platform = isset( $_POST['platform'] ) ? sanitize_key( wp_unslash( $_POST['platform'] ) ) : '';

		$payload = array(
			'post_id'  => 0,
			'caption'  => 'PADE API test message',
			'platform' => $platform,
		);

		PADE_Router::dispatch( $platform, $payload );
		wp_safe_redirect( admin_url( 'admin.php?page=pade-control-center&test=1' ) );
		exit;
	}

	/**
	 * Render diagnostics page.
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$summary = PADE_Queue::get_summary();
		$logs    = PADE_Logger::recent_logs( 50 );
		$cron    = wp_next_scheduled( 'pade_process_queue_event' );
		$last_ai = (string) get_option( 'pade_last_ai_response', '' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'PADE Diagnostics Control Center', 'personal-auto-engine' ); ?></h1>
			<ul>
				<li><strong><?php esc_html_e( 'PHP Version:', 'personal-auto-engine' ); ?></strong> <?php echo esc_html( PHP_VERSION ); ?></li>
				<li><strong><?php esc_html_e( 'Memory Limit:', 'personal-auto-engine' ); ?></strong> <?php echo esc_html( (string) ini_get( 'memory_limit' ) ); ?></li>
				<li><strong><?php esc_html_e( 'WP_DEBUG:', 'personal-auto-engine' ); ?></strong> <?php echo esc_html( defined( 'WP_DEBUG' ) && WP_DEBUG ? 'true' : 'false' ); ?></li>
				<li><strong><?php esc_html_e( 'Cron Status:', 'personal-auto-engine' ); ?></strong> <?php echo esc_html( $cron ? gmdate( 'Y-m-d H:i:s', (int) $cron ) : __( 'Not scheduled', 'personal-auto-engine' ) ); ?></li>
			</ul>

			<h2><?php esc_html_e( 'Queue Health Summary', 'personal-auto-engine' ); ?></h2>
			<p><?php echo esc_html( wp_json_encode( $summary ) ?: '{}' ); ?></p>

			<h2><?php esc_html_e( 'API Test Buttons', 'personal-auto-engine' ); ?></h2>
			<?php foreach ( array( 'telegram', 'pinterest', 'facebook', 'twitter', 'linkedin' ) as $platform ) : ?>
				<form style="display:inline-block;margin-right:8px;" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="pade_test_platform" />
					<input type="hidden" name="platform" value="<?php echo esc_attr( $platform ); ?>" />
					<?php wp_nonce_field( 'pade_test_platform' ); ?>
					<button type="submit" class="button"><?php echo esc_html( ucfirst( $platform ) ); ?></button>
				</form>
			<?php endforeach; ?>

			<h2><?php esc_html_e( 'Last AI Response Preview', 'personal-auto-engine' ); ?></h2>
			<textarea rows="6" cols="120" readonly><?php echo esc_textarea( $last_ai ); ?></textarea>

			<h2><?php esc_html_e( 'Last 50 Logs', 'personal-auto-engine' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Timestamp', 'personal-auto-engine' ); ?></th>
						<th><?php esc_html_e( 'Platform', 'personal-auto-engine' ); ?></th>
						<th><?php esc_html_e( 'Post ID', 'personal-auto-engine' ); ?></th>
						<th><?php esc_html_e( 'HTTP Code', 'personal-auto-engine' ); ?></th>
						<th><?php esc_html_e( 'Success', 'personal-auto-engine' ); ?></th>
						<th><?php esc_html_e( 'Raw Response', 'personal-auto-engine' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $logs ) ) : ?>
						<tr><td colspan="6"><?php esc_html_e( 'No logs available.', 'personal-auto-engine' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $logs as $log ) : ?>
							<tr>
								<td><?php echo esc_html( (string) $log->timestamp ); ?></td>
								<td><?php echo esc_html( (string) $log->platform ); ?></td>
								<td><?php echo esc_html( (string) $log->post_id ); ?></td>
								<td><?php echo esc_html( (string) $log->http_code ); ?></td>
								<td><?php echo esc_html( (int) $log->success_flag ? __( 'Yes', 'personal-auto-engine' ) : __( 'No', 'personal-auto-engine' ) ); ?></td>
								<td><textarea rows="3" cols="60" readonly><?php echo esc_textarea( (string) $log->raw_response ); ?></textarea></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
