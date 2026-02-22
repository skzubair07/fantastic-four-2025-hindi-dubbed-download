<?php
/**
 * Settings class.
 *
 * @package personal-auto-engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles settings registration.
 */
class PADE_Settings {

	/**
	 * Option key.
	 *
	 * @var string
	 */
	private const OPTION_KEY = 'pade_settings';

	/**
	 * Init settings.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public static function register(): void {
		register_setting(
			'pade_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Default config.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'manual_approval'        => 1,
			'daily_post_limit'       => 5,
			'openai_api_key'         => '',
			'openai_model'           => 'gpt-4o-mini',
			'openai_max_tokens'      => 250,
			'platforms'              => array(
				'telegram'  => 0,
				'pinterest' => 0,
				'facebook'  => 0,
				'twitter'   => 0,
				'linkedin'  => 0,
			),
			'platform_api_tokens'    => array(
				'telegram'  => '',
				'pinterest' => '',
				'facebook'  => '',
				'twitter'   => '',
				'linkedin'  => '',
			),
			'prompt_templates'       => array(
				'telegram'  => 'Write a concise Telegram caption for: {post_title}. Content: {post_content}',
				'pinterest' => 'Write a Pinterest pin caption for {post_title}. Include a call to action.',
				'facebook'  => 'Write a Facebook post for {post_title}. Content: {post_content}',
				'twitter'   => 'Write an X post under 240 chars for {post_title}.',
				'linkedin'  => 'Write a professional LinkedIn update for {post_title}.',
			),
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array<string, mixed> $input Raw settings.
	 * @return array<string, mixed>
	 */
	public static function sanitize( array $input ): array {
		$defaults = self::defaults();
		$settings = wp_parse_args( $input, $defaults );

		$settings['manual_approval']   = ! empty( $settings['manual_approval'] ) ? 1 : 0;
		$settings['daily_post_limit']  = max( 1, absint( $settings['daily_post_limit'] ) );
		$settings['openai_api_key']    = sanitize_text_field( (string) $settings['openai_api_key'] );
		$settings['openai_model']      = sanitize_text_field( (string) $settings['openai_model'] );
		$settings['openai_max_tokens'] = max( 1, absint( $settings['openai_max_tokens'] ) );

		foreach ( $defaults['platforms'] as $platform => $value ) {
			$settings['platforms'][ $platform ] = ! empty( $settings['platforms'][ $platform ] ) ? 1 : 0;
		}

		foreach ( $defaults['platform_api_tokens'] as $platform => $value ) {
			$settings['platform_api_tokens'][ $platform ] = sanitize_text_field( (string) $settings['platform_api_tokens'][ $platform ] );
		}

		foreach ( $defaults['prompt_templates'] as $platform => $value ) {
			$settings['prompt_templates'][ $platform ] = sanitize_textarea_field( (string) $settings['prompt_templates'][ $platform ] );
		}

		return $settings;
	}

	/**
	 * Get all settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get(): array {
		$value = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( $value, self::defaults() );
	}
}
