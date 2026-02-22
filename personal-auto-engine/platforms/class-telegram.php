<?php
/**
 * Telegram platform handler.
 *
 * @package personal-auto-engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Platform Telegram.
 */
class PADE_Platform_Telegram {

	/**
	 * Send payload.
	 *
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>
	 */
	public static function send( array $payload ): array {
		$settings = PADE_Settings::get();
		$token    = (string) ( $settings['platform_api_tokens']['telegram'] ?? '' );
		$post_id  = absint( $payload['post_id'] ?? 0 );
		$message  = sanitize_textarea_field( (string) ( $payload['caption'] ?? '' ) );

		if ( empty( $token ) ) {
			$result = array(
				'success'   => false,
				'http_code' => 0,
				'response'  => array( 'message' => 'telegram token missing.' ),
			);
			PADE_Logger::log( 'telegram', $post_id, 0, $result['response'], 0 );
			return $result;
		}

		// v1 personal-use endpoint placeholder.
		$http_code = 200;
		$response  = array(
			'message' => 'telegram simulated send complete',
			'body'    => $message,
		);

		PADE_Logger::log( 'telegram', $post_id, $http_code, $response, 1 );

		return array(
			'success'   => true,
			'http_code' => $http_code,
			'response'  => $response,
		);
	}
}
