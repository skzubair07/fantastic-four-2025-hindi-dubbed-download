<?php
/**
 * Settings page.
 *
 * @package personal-auto-engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin settings UI.
 */
class PADE_Admin_Settings_Page {

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
	}

	/**
	 * Register menu.
	 *
	 * @return void
	 */
	public static function menu(): void {
		add_menu_page(
			__( 'PADE Settings', 'personal-auto-engine' ),
			__( 'PADE', 'personal-auto-engine' ),
			'manage_options',
			'pade-settings',
			array( __CLASS__, 'render' ),
			'dashicons-share',
			58
		);
	}

	/**
	 * Render page.
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = PADE_Settings::get();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Personal Auto Distribution Engine', 'personal-auto-engine' ); ?></h1>
			<h2 class="nav-tab-wrapper">
				<a href="#general" class="nav-tab nav-tab-active"><?php esc_html_e( 'General', 'personal-auto-engine' ); ?></a>
				<a href="#api" class="nav-tab"><?php esc_html_e( 'API Credentials', 'personal-auto-engine' ); ?></a>
				<a href="#ai" class="nav-tab"><?php esc_html_e( 'AI Settings', 'personal-auto-engine' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=pade-control-center' ) ); ?>" class="nav-tab"><?php esc_html_e( 'Diagnostics', 'personal-auto-engine' ); ?></a>
				<a href="#instructions" class="nav-tab"><?php esc_html_e( 'Instructions', 'personal-auto-engine' ); ?></a>
			</h2>
			<form action="options.php" method="post">
				<?php settings_fields( 'pade_settings_group' ); ?>
				<div id="general" style="margin-top:20px;">
					<h2><?php esc_html_e( 'General', 'personal-auto-engine' ); ?></h2>
					<label>
						<input type="checkbox" name="pade_settings[manual_approval]" value="1" <?php checked( ! empty( $settings['manual_approval'] ) ); ?> />
						<?php esc_html_e( 'Enable Manual Approval Mode', 'personal-auto-engine' ); ?>
					</label>
					<p>
						<label for="pade_daily_post_limit"><?php esc_html_e( 'Daily Post Limit', 'personal-auto-engine' ); ?></label>
						<input id="pade_daily_post_limit" type="number" min="1" name="pade_settings[daily_post_limit]" value="<?php echo esc_attr( (string) $settings['daily_post_limit'] ); ?>" />
					</p>
				</div>

				<div id="api" style="margin-top:20px;">
					<h2><?php esc_html_e( 'API Credentials', 'personal-auto-engine' ); ?></h2>
					<?php foreach ( $settings['platform_api_tokens'] as $platform => $token ) : ?>
						<p>
							<label for="pade_token_<?php echo esc_attr( $platform ); ?>"><?php echo esc_html( ucfirst( $platform ) ); ?> <?php esc_html_e( 'Token', 'personal-auto-engine' ); ?></label>
							<input id="pade_token_<?php echo esc_attr( $platform ); ?>" type="password" class="regular-text" name="pade_settings[platform_api_tokens][<?php echo esc_attr( $platform ); ?>]" value="<?php echo esc_attr( (string) $token ); ?>" />
						</p>
					<?php endforeach; ?>
				</div>

				<div id="ai" style="margin-top:20px;">
					<h2><?php esc_html_e( 'AI Settings', 'personal-auto-engine' ); ?></h2>
					<p>
						<label for="pade_openai_api_key"><?php esc_html_e( 'OpenAI API Key', 'personal-auto-engine' ); ?></label>
						<input id="pade_openai_api_key" type="password" class="regular-text" name="pade_settings[openai_api_key]" value="<?php echo esc_attr( (string) $settings['openai_api_key'] ); ?>" />
					</p>
					<p>
						<label for="pade_openai_model"><?php esc_html_e( 'Model', 'personal-auto-engine' ); ?></label>
						<input id="pade_openai_model" type="text" class="regular-text" name="pade_settings[openai_model]" value="<?php echo esc_attr( (string) $settings['openai_model'] ); ?>" />
					</p>
					<p>
						<label for="pade_openai_max_tokens"><?php esc_html_e( 'Max Tokens', 'personal-auto-engine' ); ?></label>
						<input id="pade_openai_max_tokens" type="number" min="1" name="pade_settings[openai_max_tokens]" value="<?php echo esc_attr( (string) $settings['openai_max_tokens'] ); ?>" />
					</p>
					<h3><?php esc_html_e( 'Platform Prompt Templates', 'personal-auto-engine' ); ?></h3>
					<?php foreach ( $settings['prompt_templates'] as $platform => $template ) : ?>
						<p>
							<label for="pade_prompt_<?php echo esc_attr( $platform ); ?>"><?php echo esc_html( ucfirst( $platform ) ); ?></label><br />
							<textarea id="pade_prompt_<?php echo esc_attr( $platform ); ?>" name="pade_settings[prompt_templates][<?php echo esc_attr( $platform ); ?>]" rows="4" cols="90"><?php echo esc_textarea( (string) $template ); ?></textarea>
						</p>
					<?php endforeach; ?>
					<p><?php esc_html_e( 'Placeholders: {post_title}, {post_content}, {platform_name}', 'personal-auto-engine' ); ?></p>
				</div>

				<div id="instructions" style="margin-top:20px;">
					<h2><?php esc_html_e( 'Instructions', 'personal-auto-engine' ); ?></h2>
					<p><?php esc_html_e( '1. Configure API credentials.', 'personal-auto-engine' ); ?></p>
					<p><?php esc_html_e( '2. Configure AI prompt templates by platform.', 'personal-auto-engine' ); ?></p>
					<p><?php esc_html_e( '3. Use Queue and Control Center pages for execution and diagnostics.', 'personal-auto-engine' ); ?></p>
				</div>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
