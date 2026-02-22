<?php
/**
 * Queue page.
 *
 * @package personal-auto-engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Queue admin page.
 */
class PADE_Admin_Queue_Page {

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_pade_approve_queue', array( __CLASS__, 'approve' ) );
		add_action( 'admin_post_pade_process_now', array( __CLASS__, 'process_now' ) );
	}

	/**
	 * Menu registration.
	 *
	 * @return void
	 */
	public static function menu(): void {
		add_submenu_page(
			'pade-settings',
			__( 'PADE Queue', 'personal-auto-engine' ),
			__( 'Queue', 'personal-auto-engine' ),
			'manage_options',
			'pade-queue',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Approve request.
	 *
	 * @return void
	 */
	public static function approve(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized request.', 'personal-auto-engine' ) );
		}

		check_admin_referer( 'pade_approve_queue' );
		$id = isset( $_POST['queue_id'] ) ? absint( wp_unslash( $_POST['queue_id'] ) ) : 0;
		if ( $id > 0 ) {
			PADE_Queue::approve( $id );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=pade-queue' ) );
		exit;
	}

	/**
	 * Process queue now.
	 *
	 * @return void
	 */
	public static function process_now(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized request.', 'personal-auto-engine' ) );
		}

		check_admin_referer( 'pade_process_now' );
		PADE_Queue::process_pending();
		wp_safe_redirect( admin_url( 'admin.php?page=pade-queue&processed=1' ) );
		exit;
	}

	/**
	 * Render queue page.
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$rows = PADE_Queue::get_items();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'PADE Queue', 'personal-auto-engine' ); ?></h1>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="pade_process_now" />
				<?php wp_nonce_field( 'pade_process_now' ); ?>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Process Queue Now', 'personal-auto-engine' ); ?></button>
			</form>
			<table class="widefat striped" style="margin-top:20px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ID', 'personal-auto-engine' ); ?></th>
						<th><?php esc_html_e( 'Post ID', 'personal-auto-engine' ); ?></th>
						<th><?php esc_html_e( 'Status', 'personal-auto-engine' ); ?></th>
						<th><?php esc_html_e( 'Retries', 'personal-auto-engine' ); ?></th>
						<th><?php esc_html_e( 'Payload', 'personal-auto-engine' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'personal-auto-engine' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td colspan="6"><?php esc_html_e( 'Queue is empty.', 'personal-auto-engine' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $rows as $row ) : ?>
							<tr>
								<td><?php echo esc_html( (string) $row->id ); ?></td>
								<td><?php echo esc_html( (string) $row->post_id ); ?></td>
								<td><?php echo esc_html( (string) $row->status ); ?></td>
								<td><?php echo esc_html( (string) $row->retry_count ); ?></td>
								<td><textarea rows="2" cols="50" readonly><?php echo esc_textarea( (string) $row->payload_json ); ?></textarea></td>
								<td>
									<?php if ( 'pending' === $row->status ) : ?>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
											<input type="hidden" name="action" value="pade_approve_queue" />
											<input type="hidden" name="queue_id" value="<?php echo esc_attr( (string) $row->id ); ?>" />
											<?php wp_nonce_field( 'pade_approve_queue' ); ?>
											<button type="submit" class="button"><?php esc_html_e( 'Approve', 'personal-auto-engine' ); ?></button>
										</form>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
