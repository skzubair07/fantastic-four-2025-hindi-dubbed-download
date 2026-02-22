<?php
/**
 * Router class.
 *
 * @package personal-auto-engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Routes payload to platform classes.
 */
class PADE_Router {

	/**
	 * Dispatch payload to platform.
	 *
	 * @param string               $platform Platform key.
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>
	 */
	public static function dispatch( string $platform, array $payload ): array {
		$map = array(
			'telegram'  => 'PADE_Platform_Telegram',
			'pinterest' => 'PADE_Platform_Pinterest',
			'facebook'  => 'PADE_Platform_Facebook',
			'twitter'   => 'PADE_Platform_Twitter',
			'linkedin'  => 'PADE_Platform_Linkedin',
		);

		$class = $map[ $platform ] ?? '';
		if ( empty( $class ) || ! class_exists( $class ) ) {
			return array(
				'success'   => false,
				'http_code' => 0,
				'response'  => array( 'message' => 'Unknown platform.' ),
			);
		}

		return $class::send( $payload );
	}
}
