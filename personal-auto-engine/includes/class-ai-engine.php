<?php
/**
 * AI engine.
 *
 * @package personal-auto-engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles OpenAI requests.
 */
class PADE_AI_Engine {

	/**
	 * Generate text for platform.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $platform Platform name.
	 * @return string
	 */
	public static function generate_caption( int $post_id, string $platform ): string {
		$settings = PADE_Settings::get();
		$post     = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		$template = $settings['prompt_templates'][ $platform ] ?? 'Write a social post for {post_title}.';
		$prompt   = str_replace(
			array( '{post_title}', '{post_content}', '{platform_name}' ),
			array( $post->post_title, wp_strip_all_tags( (string) $post->post_content ), $platform ),
			$template
		);

		$api_key = (string) $settings['openai_api_key'];
		if ( empty( $api_key ) ) {
			return wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 30 );
		}

		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'    => $settings['openai_model'],
						'messages' => array(
							array(
								'role'    => 'user',
								'content' => $prompt,
							),
						),
						'max_tokens' => absint( $settings['openai_max_tokens'] ),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			PADE_Logger::log( 'ai', $post_id, 0, $response->get_error_message(), 0 );
			return wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 30 );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		$text = $data['choices'][0]['message']['content'] ?? '';

		PADE_Logger::log( 'ai', $post_id, $code, $data, $code >= 200 && $code < 300 ? 1 : 0 );

		if ( empty( $text ) ) {
			return wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 30 );
		}

		return sanitize_textarea_field( $text );
	}
}
