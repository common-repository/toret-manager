<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Toret_Manager_Api_Calls {

	/**
	 * Create curl post request
	 *
	 * @param string $slug
	 * @param string $endpoint
	 * @param string $access_token
	 * @param string $json
	 * @param string $module
	 *
	 * @return mixed
	 */
	public static function CreatePostRequest( string $slug, string $endpoint, string $access_token, string $json, string $module = 'product' ) {
		$url      = TORET_MANAGER_API_URL . $endpoint;
		$response = wp_remote_post( $url, array(
				'method'  => 'POST',
				'headers' => [
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/json',
				],
				'body'    => $json,
			)
		);

		if ( is_wp_error( $response ) ) {
			return 'none';
		}

		return self::processCallResponse( $slug, $response, $endpoint );
	}

	/**
	 * Get API access token
	 *
	 * @param string $slug
	 * @param string $api_key
	 * @param string $user_hash
	 *
	 * @return string
	 */
	public function login( string $slug, string $api_key, string $user_hash ): string {
		$url = TORET_MANAGER_API_URL . Toret_Manager_Api::TORET_MANAGER_ENDPOINT_LOGIN;

		$post_data = array( 'userHash' => $user_hash, 'apiKey' => $api_key );

		if ( TORET_MANAGER_LOG_API ) {
			$log = array(
				'module'    => 'ApiCall',
				'submodule' => Toret_Manager_Api::TORET_MANAGER_ENDPOINT_LOGIN,
				'context'   => __( 'API call request', 'toret-manager' ),
				'log'       => wp_json_encode( $post_data ),
			);
			trman_log( $slug, $log );
		}

		$response = wp_remote_post( $url, array(
				'method'  => 'POST',
				'headers' => [
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode( $post_data ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return 'none';
		}

		return self::processCallResponse( $slug, $response, Toret_Manager_Api::TORET_MANAGER_ENDPOINT_LOGIN );
	}

	/**
	 * Process API call response
	 *
	 * @param string $slug
	 * @param $response
	 * @param string $endpoint
	 *
	 * @return mixed
	 */
	static function processCallResponse( string $slug, $response, string $endpoint ) {
		$httpcode = wp_remote_retrieve_response_code( $response );
		$body     = wp_remote_retrieve_body( $response );

		if ( TORET_MANAGER_LOG_API ) {
			$log = array(
				'module'    => 'ApiCall',
				'submodule' => $endpoint,
				'context'   => __( 'API call response', 'toret-manager' ),
				'log'       => 'HTTP code: ' . $httpcode . ' /// ' . $body,
			);
			trman_log( $slug, $log );
		}

		if ( $httpcode >= 503 ) {
			$log = array(
				'module'    => 'ApiCall',
				'submodule' => $endpoint,
				'context'   => __( 'API call failed with http code:  ', 'toret-manager' ) . $httpcode,
				'log'       => 'Server internal error',
			);
			trman_log( $slug, $log );

			return 'none';
		}

		if ( $body ) {

			$response = json_decode( $body );

			if ( ! empty( $response ) ) {
				$code   = $response->code;
				$status = $response->status;
				$data   = $response->data;

				if ( $code == '200' ) {
					if ( $endpoint == Toret_Manager_Api::TORET_MANAGER_ENDPOINT_LOGIN ) {
						return $data->token;
					} else {
						return $data;
					}
				} else {

					$log = array(
						'module'    => 'ApiCall',
						'submodule' => $endpoint,
						'context'   => __( 'API call failed with status:  ', 'toret-manager' ) . $status . __( ' and message: ', 'toret-manager' ) . $data->type,
						'log'       => wp_json_encode( $data ),
					);
					trman_log( $slug, $log );

					if ( $code == 404 || $code == 400 ) {
						return $code;
					}

				}
			}

		}

		return 'none';
	}

}