<?php
class Gregphoto_Defensio_Adapter_Curl {
	protected $handle;
	protected $defaultCurlOptions = array(
		CURLOPT_POST => true,
		CURLOPT_RETURNTRANSFER => true
	);

	/**
	 * Instantiate the Gregphoto_Defensio_Adapter_Curl class and optionally set Curl options
	 *
	 * @param array $options An optional array of Curl options (e.g. 'CURLOPT_CONNECTTIMEOUT')
	 */
	public function __construct($options = array()) {
		if(!extension_loaded('curl')) {
			throw new Exception('The PHP CURL extension is not available so the CURL adapter cannot be used.');
		}
		$this->handle = curl_init();
		$this->setOptions($this->defaultCurlOptions);
		if(count($options) > 0) {
			$this->setOptions($options);
		}
	}

	/**
	 * Send a post request to the provided URL with the provided parameters
	 *
	 * @param string $url
	 * @param array $params Name/Value pairs for POST body
	 * @return string The body of the http response
	 */
	public function postRequest($url,$params) {
		$this->setOptions(array(
			CURLOPT_URL => $url,
			CURLOPT_POSTFIELDS => http_build_query($params)
		));
		$response = curl_exec($this->handle);
		switch(curl_getinfo($this->handle,CURLINFO_HTTP_CODE)) {
			case 401:
				throw new Exception("Received 401 from Defensio: API key is invalid.");
			case 408:
				throw new Exception("Request timed out.");
		}
		return $response;
	}

	protected function setOptions($options) {
		foreach($options as $option=>$value) {
			curl_setopt($this->handle,$option,$value);
		}
	}

	public function __destruct() {
		curl_close($this->handle);
	}
}
?>