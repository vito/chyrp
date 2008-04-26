<?php
class Gregphoto_Defensio {
	protected $apiKey;
	protected $appUrl;
	protected $appType = 'blog';
	protected $baseUrl = 'http://api.defensio.com';
	protected $apiVersion = '1.1';
	protected $httpClient;
	protected static $methods = array(
		'validate-key' => array(
			'required_arguments' => array('owner-url'),
			'optional_arguments' => array(),
			'response' => array('status','message','api-version')
		),
		'announce-article' => array(
			'required_arguments' => array('owner-url','article-author','article-author-email','article-title','article-content','permalink'),
			'optional_arguments' => array(),
			'response' => array('status','message','api-version')
		),
		'audit-comment' => array(
			'required_arguments' => array('owner-url','user-ip','article-date','comment-author','comment-type'),
			'optional_arguments' => array('comment-content','comment-author-email','comment-author-url','permalink','referrer','user-logged-in','trusted-user','test-force'),
			'response' => array('status','message','api-version','signature','spam','spaminess')
		),
		'report-false-negatives' => array(
			'required_arguments' => array('owner-url','signatures'),
			'optional_arguments' => array(),
			'response' => array('status','message','api-version')
		),
		'report-false-positives' => array(
			'required_arguments' => array('owner-url','signatures'),
			'optional_arguments' => array(),
			'response' => array('status','message','api-version')
		),
		'get-stats' => array(
			'required_arguments' => array('owner-url'),
			'optional_arguments' => array(),
			'response' => array('status','message','api-version','accuracy','spam','ham','false-positives','false-negatives','learning','learning-message')
		)
	);

	/**
	 * Instantiate a Defensio object
	 *
	 * @param string $apiKey Defensio API key
	 * @param string $appUrl URL to application or blog
	 */
	public function __construct($apiKey,$appUrl) {
		$this->apiKey = $apiKey;
		$this->appUrl = $appUrl;
	}

	/**
	 * Set the Http client to use for POST requests.  If not called Gregphoto_Defensio_Adapter_Streams will be used
	 *
	 * @param Object $client A Gregphoto_Defensio_Adapter object such as Gregphoto_Defensio_Adapter_Streams, Gregphoto_Defensio_Adapter_Curl, or Gregphoto_Defensio_Adapter_ZendHttpClient
	 */
	public function setHttpClient($client) {
		$this->httpClient = $client;
	}

	protected function getHttpClient() {
		if(!isset($this->httpClient)) {
			require_once('Defensio/Adapter/Streams.php');
			$this->httpClient = new Gregphoto_Defensio_Adapter_Streams();
		}
		return $this->httpClient;
	}

	/**
	 * Checks whether API key and application URL are valid
	 *
	 * @param array $params Array of input parameters
	 * @return array Associative array containing Defensio response
	 */
	public function validate_key($params = array()) {
		return $this->makeApiCall('validate-key',$params);
	}

	/**
	 * Announce a new article to Defensio
	 *
	 * @param array $params Array of input parameters
	 * @return array Associative array containing Defensio response
	 */
	public function announce_article($params = array()) {
		return $this->makeApiCall('announce-article',$params);
	}

	/**
	 * Check with Defensio whether a message is spam and get its spaminess
	 *
	 * @param array $params Array of input parameters
	 * @return array Associative array containing Defensio response
	 */
	public function audit_comment($params = array()) {
		return $this->makeApiCall('audit-comment',$params);
	}

	/**
	 * Report false negative to Defensio
	 *
	 * @param array $params Array of input parameters
	 * @return array Associative array containing Defensio response
	 */
	public function report_false_negatives($params = array()) {
		return $this->makeApiCall('report-false-negatives',$params);
	}

	/**
	 * Report false positives to Defensio
	 *
	 * @param array $params Array of input parameters
	 * @return array Associative array containing Defensio response
	 */
	public function report_false_positives($params = array()) {
		return $this->makeApiCall('report-false-positives',$params);
	}

	/**
	 * Get statistics from Defensio
	 *
	 * @param array $params Array of input parameters
	 * @return array Associative array containing Defensio response
	 */
	public function get_stats($params = array()) {
		return $this->makeApiCall('get-stats',$params);
	}

	/**
	 * Get a list of valid Defensio actions
	 *
	 * @return array
	 */
	public static function getActions() {
		return array_keys(self::$methods);
	}

	/**
	 * Return a list of required/optional parameters and the output response parameters
	 *
	 * @param string $action The Defensio api action (e.g. audit-comment)
	 * @return array Associate array with required, optional, and response keys
	 */
	public static function getActionDetails($action) {
		return self::$methods[$action];
	}

	/**
	 * Set the type of application
	 *
	 * @param string $type Valid values are 'blog' or 'app'
	 */
	public function setAppType($type) {
		$validTypes = array('blog','app');
		if(in_array($type,$validTypes)) {
			$this->appType = $type;
		} else {
			throw new Exception("Type must be either 'blog' or 'app'");
		}
	}

	/**
	 * Method to make direct calls to Defensio.
	 *
	 * @param string $action Defensio api name (e.g. get-stats)
	 * @param array $params Parameters to pass as part of API call
	 * @param boolean $validate Whether or not to validate against required/optional params
	 * @return unknown
	 */
	public function makeApiCall($action,$params,$validate=true) {
		$params = $this->injectOwnerUrl($params);
		if($validate){
			$this->validateParams($action,$params);
		}
		$url = $this->buildUrl($action);
		$client = $this->getHttpClient();
		$response = $client->postRequest($url,$params);
		return $this->responseToArray($response);
	}

	protected function buildUrl($action) {
		$url = $this->baseUrl . '/' . $this->appType . '/' . $this->apiVersion . '/' . $action . '/' . $this->apiKey . '.xml';
		return $url;
	}

	protected function injectOwnerUrl($params) {
		if(!array_key_exists('owner-url',$params)) {
			$params['owner-url'] = $this->appUrl;
		}
		return $params;
	}

	protected function validateParams($action,$params) {
		$requiredParams = self::$methods[$action]['required_arguments'];
		foreach($requiredParams as $required) {
			if(!array_key_exists($required,$params)) {
				throw new Exception("{$required} is a required parameter for method {$action}");
			}
		}
		$paramKeys = array_keys($params);
		$supportedParams = array_merge(self::$methods[$action]['required_arguments'],self::$methods[$action]['optional_arguments']);
		foreach($paramKeys as $param) {
			if(!in_array($param,$supportedParams)) {
				throw new Exception("{$param} is not a supported parameter for method {$action}");
			}
		}
	}

	protected function responseToArray($response) {
		$responseArray = array();
		$xml = new DOMDocument();
		$xml->loadXML($response);
		$root = $xml->childNodes->item(0);
		if($root->hasChildNodes()){
			foreach($root->childNodes as $node){
				if($node->nodeType == XML_ELEMENT_NODE) {
					$responseArray[$node->nodeName] = $node->nodeValue;
				}
			}
		}
		return $responseArray;
	}
}