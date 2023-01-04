<?php

class eTranslation_Service {
	private $username;
	private $password;
	static $api_url   = 'https://webgate.ec.europa.eu/etranslation/si/';
	static $error_map = array(
		-20000 => 'Source language not specified',
		-20001 => 'Invalid source language',
		-20002 => 'Target language(s) not specified',
		-20003 => 'Invalid target language(s)',
		-20004 => 'DEPRECATED',
		-20005 => 'Caller information not specified',
		-20006 => 'Missing application name',
		-20007 => 'Application not authorized to access the service',
		-20008 => 'Bad format for ftp address',
		-20009 => 'Bad format for sftp address',
		-20010 => 'Bad format for http address',
		-20011 => 'Bad format for email address',
		-20012 => 'Translation request must be text type, document path type or document base64 type and not several at a time',
		-20013 => 'Language pair not supported by the domain',
		-20014 => 'Username parameter not specified',
		-20015 => 'Extension invalid compared to the MIME type',
		-20016 => 'DEPRECATED',
		-20017 => 'Username parameter too long',
		-20018 => 'Invalid output format',
		-20019 => 'Institution parameter too long',
		-20020 => 'Department number too long',
		-20021 => 'Text to translate too long',
		-20022 => 'Too many FTP destinations',
		-20023 => 'Too many SFTP destinations',
		-20024 => 'Too many HTTP destinations',
		-20025 => 'Missing destination',
		-20026 => 'Bad requester callback protocol',
		-20027 => 'Bad error callback protocol',
		-20028 => 'Concurrency quota exceeded',
		-20029 => 'Document format not supported',
		-20030 => 'Text to translate is empty',
		-20031 => 'Missing text or document to translate',
		-20032 => 'Email address too long',
		-20033 => 'Cannot read stream',
		-20034 => 'Output format not supported',
		-20035 => 'Email destination tag is missing or empty',
		-20036 => 'HTTP destination tag is missing or empty',
		-20037 => 'FTP destination tag is missing or empty',
		-20038 => 'SFTP destination tag is missing or empty',
		-20039 => 'Document to translate tag is missing or empty',
		-20040 => 'Format tag is missing or empty',
		-20041 => 'The content is missing or empty',
		-20042 => 'Source language defined in TMX file differs from request',
		-20043 => 'Source language defined in XLIFF file differs from request',
		-20044 => 'Output format is not available when quality estimate is requested. It should be blank or \'xslx\'',
		-20045 => 'Quality estimate is not available for text snippet',
		-20046 => 'Document too big (>20Mb)',
		-20047 => 'Quality estimation not available',
		-40010 => 'Too many segments to translate',
		-80004 => 'Cannot store notification file at specified FTP address',
		-80005 => 'Cannot store notification file at specified SFTP address',
		-80006 => 'Cannot store translated file at specified FTP address',
		-80007 => 'Cannot store translated file at specified SFTP address',
		-90000 => 'Cannot connect to FTP',
		-90001 => 'Cannot retrieve file at specified FTP address',
		-90002 => 'File not found at specified address on FTP',
		-90007 => 'Malformed FTP address',
		-90012 => 'Cannot retrieve file content on SFTP',
		-90013 => 'Cannot connect to SFTP',
		-90014 => 'Cannot store file at specified FTP address',
		-90015 => 'Cannot retrieve file content on SFTP',
		-90016 => 'Cannot retrieve file at specified SFTP address',
	);

	public function __construct( $username, $password ) {
		$this->username = $username;
		$this->password = $password;
	}

	public function send_translate_document_request( $sourceLanguage, $targetLanguage, $document, $domain = 'GEN', $id = '' ) {
		$translationRequest = array(
			'documentToTranslateBase64' => $document,
			'sourceLanguage'            => strtoupper( $sourceLanguage ),
			'targetLanguages'           => array(
				strtoupper( $targetLanguage ),
			),
			'errorCallback'             => get_rest_url() . 'etranslation/v1/error_callback/' . $id,
			'callerInformation'         => array(
				'application' => $this->username,
			),
			'destinations'              => array(
				'httpDestinations' => array(
					get_rest_url() . 'etranslation/v1/document/destination/' . $id,
				),
			),
			'domain'                    => $domain,
		);

		$post     = json_encode( $translationRequest );
		$response = $this->send_etranslation_request(
			self::$api_url . 'translate',
			'POST',
			$post,
			array(
				'Content-Type'   => 'application/json',
				'Content-Length' => strlen( $post ),
			)
		);

		$http_status = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$request_id  = is_numeric( $body ) ? (int) $body : null;

		if ( $http_status != 200 || $request_id < 0 ) {
			$message = self::$error_map[ $request_id ] ?? $body;
			$err     = is_wp_error( $response ) ? $response->get_error_message() : $body;
			error_log( "Invalid response from eTranslation: $err [status: $http_status, message: $message]" );
		}

		return array(
			'response' => $http_status,
			'body'     => $body,
		);
	}

	public function get_available_domain_language_pairs() {
		$response    = $this->send_etranslation_request( self::$api_url . 'get-domains', 'GET' );
		$http_status = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		if ( $http_status != 200 ) {
			error_log( "Error retrieving domains from eTranslation: $body [status: $http_status]" );
		}

		return array(
			'response' => $http_status,
			'body'     => json_decode( $body ),
		);
	}

	private function send_etranslation_request( $url, $method, $body = null, $headers = array() ) {
		$auth_value = $this->get_digest_auth_header_value( $url, $method );
		if ( $auth_value ) {
			$headers['Authorization'] = $auth_value;
			$args                     = array(
				'method'    => $method,
				'sslverify' => false,
				'redirects' => 5,
				'headers'   => $headers,
			);
			if ( $body ) {
				$args['body'] = $body;
			}
			return wp_remote_request( $url, $args );
		} else {
			return new WP_Error( 401, 'Failed to retrieve digest auth header' );
		}
	}

	private function get_digest_auth_header_value( $request_url, $method ) {
		$response = wp_remote_post( $request_url );
		$header   = wp_remote_retrieve_header( $response, 'WWW-Authenticate' );

		$retries = 2;
		while ( empty( $header ) && $retries > 0 ) {
			// request failed, retry.
			$response = wp_remote_post( $request_url );
			$header   = wp_remote_retrieve_header( $response, 'WWW-Authenticate' );
			$retries--;
		}
		if ( empty( $header ) ) {
			return false;
		}

		/*
		* Parses the 'www-authenticate' header for nonce, realm and other values.
		*/
		preg_match_all( '#(([\w]+)=["]?([^\s"]+))#', $header, $matches );
		$server_bits = array();
		foreach ( $matches[2] as $i => $key ) {
			$server_bits[ $key ] = $matches[3][ $i ];
		}

		$server_bits['realm'] = $server_bits['realm'] . ' Realm via Digest Authentication';
		$nc                   = '00000001';
		$path                 = parse_url( $request_url, PHP_URL_PATH );
		$client_nonce         = uniqid();
		$ha1                  = md5( $this->username . ':' . $server_bits['realm'] . ':' . $this->password );
		$ha2                  = md5( $method . ':' . $path );
		// The order of this array matters, because it affects resulting hashed val
		$response_bits = array(
			$ha1,
			$server_bits['nonce'],
			$nc,
			$client_nonce,
			$server_bits['qop'],
			$ha2,
		);

		$digest_header_values = array(
			'username'  => '"' . $this->username . '"',
			'realm'     => '"' . $server_bits['realm'] . '"',
			'nonce'     => '"' . $server_bits['nonce'] . '"',
			'uri'       => '"' . $path . '"',
			'algorithm' => '"MD5"',
			'qop'       => $server_bits['qop'],
			'nc'        => $nc,
			'cnonce'    => '"' . $client_nonce . '"',
			'response'  => '"' . md5( implode( ':', $response_bits ) ) . '"',
		);
		$digest_header        = 'Digest ';
		foreach ( $digest_header_values as $key => $value ) {
			$digest_header .= $key . '=' . $value . ', ';
		}
		$digest_header = rtrim( $digest_header, ', ' );
		return $digest_header;
	}

}
