<?php
/**
 * Twitter platform handler.
 *
 * @package personal-auto-engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Platform Twitter.
 */
class PADE_Platform_Twitter {

	/**
	 * Send payload.
	 *
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>
	 */
	public static function send( array $payload ): array {
		$settings = PADE_Settings::get();
		$token    = (string) ( $settings['platform_api_tokens']['twitter'] ?? '' );
		$post_id  = absint( $payload['post_id'] ?? 0 );
		$message  = sanitize_textarea_field( (string) ( $payload['caption'] ?? '' ) );

		if ( empty( $token ) ) {
			$result = array(
				'success'   => false,
				'http_code' => 0,
				'response'  => array( 'message' => 'twitter token missing.' ),
			);
			PADE_Logger::log( 'twitter', $post_id, 0, $result['response'], 0 );
			return $result;
		}

		// v1 personal-use endpoint placeholder.
		$http_code = 200;
		$response  = array(
			'message' => 'twitter simulated send complete',
			'body'    => $message,
		);

		PADE_Logger::log( 'twitter', $post_id, $http_code, $response, 1 );

		return array(
			'success'   => true,
			'http_code' => $http_code,
			'response'  => $response,
		);
	}
}
