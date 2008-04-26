<?php
class Gregphoto_Defensio_Adapter_Streams {
	protected $options = array();

	/**
	 * Instantiate the Gregphoto_Defensio_Adapter_Streams class and optionally set POST options
	 *
	 * @param array $options An optional array of Zend_Http_Client options (e.g. 'timeout', 'proxy', etc)
	 */
	public function __construct($options = array()) {
		if(!ini_get('allow_url_fopen')) {
			throw new Exception("PHP setting allow_url_fopen is not enabled so the Stream adapter cannot be used.");
		}
		$this->options = $options;
	}

	/**
	 * Send a post request to the provided URL with the provided parameters
	 *
	 * @param string $url
	 * @param array $params Name/Value pairs for POST body
	 * @return string The body of the http response
	 */
	public function postRequest($url,$params) {
		$opts = array('http' =>
			array_merge(
				array(
					'method'  => 'POST',
					'header'  => 'Content-type: application/x-www-form-urlencoded',
					'content' => http_build_query($params)
				),
				$this->options
			)
		);
		if(!$response = @file_get_contents($url, false, stream_context_create($opts))){
			throw new Exception("Invalid API key or unable to make connection to Defensio.  Use Defensio::validate_key to check validity of API key.");
		}
		return $response;
	}
}