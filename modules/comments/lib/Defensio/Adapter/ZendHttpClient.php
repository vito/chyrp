<?php
class Gregphoto_Defensio_Adapter_ZendHttpClient {
	/**
	 * @var Zend_Http_Client
	 */
	protected $client;

	/**
	 * Instantiate the Gregphoto_Defensio_Adapter_ZendHttpClient class and optionally set Zend_Http_Client options
	 *
	 * @param array $options An optional array of Zend_Http_Client options (e.g. 'timeout', 'keepalive', etc)
	 */
	public function __construct($options = array()) {
		$this->client = new Zend_Http_Client($options);
	}

	/**
	 * Send a post request to the provided URL with the provided parameters
	 *
	 * @param string $url
	 * @param array $params Name/Value pairs for POST body
	 * @return string The body of the http response
	 */
	public function postRequest($url,$params) {
		$this->client->setUri($url);
		foreach($params as $param=>$val) {
			$this->client->setParameterPost($param,$val);
		}
		$this->client->setHeaders(array(
            'Host' => 'api.defensio.com',
            'Content-Type' => 'application/x-www-form-urlencoded'
		));
		$response = $this->client->request('POST');
		if($response->getStatus() == 401) {
			throw new Exception("Received 401 from Defensio: API key is invalid");
		}
		return $response->getBody();
	}
}