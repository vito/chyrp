<?php
/**
 *
 * Defensio PHP4 class
 *
 **

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU Lesser General Public License as published by
  the Free Software Foundation; either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Lesser General Public License for more details.

  You should have received a copy of the GNU Lesser General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.

  Please also read the file readme.txt wich should have come with this file.

  @author Nils Werner {@link www.phoque.de}
  @link http://www.phoque.de/blog/defensio_klasse
  @version 0.1
  @license LGPL
 */



// Error constants
define("DEFENSIO_SERVER_NOT_FOUND", 0);
define("DEFENSIO_RESPONSE_FAILED",  1);
define("DEFENSIO_INVALID_KEY",      2);



// Base class to assist in error handling between Defensio classes
class DefensioObject {
    var $errors = array();


    /**
     * Add a new error to the errors array in the object
     *
     * @param   String  $name   A name (array key) for the error
     * @param   String  $string The error message
     * @return void
     */
    // Set an error in the object
    function setError($name, $message) {
        $this->errors[$name] = $message;
    }


    /**
     * Return a specific error message from the errors array
     *
     * @param   String  $name   The name of the error you want
     * @return mixed    Returns a String if the error exists, a false boolean if it does not exist
     */
    function getError($name) {
        if($this->isError($name)) {
            return $this->errors[$name];
        } else {
            return false;
        }
    }


    /**
     * Return all errors in the object
     *
     * @return String[]
     */
    function getErrors() {
        return (array)$this->errors;
    }


    /**
     * Check if a certain error exists
     *
     * @param   String  $name   The name of the error you want
     * @return boolean
     */
    function isError($name) {
        return isset($this->errors[$name]);
    }


    /**
     * Check if any errors exist
     *
     * @return boolean
     */
    function errorsExist() {
        return (count($this->errors) > 0);
    }


}


// Used by the Defensio class to communicate with the Defensio service
class DefensioHttpClient extends DefensioObject {
    var $defensioVersion = '1.1';
    var $con;
    var $host;
    var $port;
    var $apiKey;
    var $blogUrl;
    var $errors = array();


    // Constructor
    function DefensioHttpClient($host, $blogUrl, $apiKey, $appType = 'app', $port = 80) {
        $this->host = $host;
        $this->port = $port;
        $this->blogUrl = $blogUrl;
        $this->apiKey = $apiKey;
        $this->appType = $appType;
    }


    // Use the connection active in $con to get a response from the server and return that response
    function getResponse($request, $path, $type = "post", $responseLength = 1160) {
        $this->_connect();

        if($this->con && !$this->isError(DEFENSIO_SERVER_NOT_FOUND)) {
            $request  =
                    strToUpper($type)." /" . $this->appType . "/".$this->defensioVersion."/".$path."/".$this->apiKey.".yaml HTTP/1.1\r\n" .
                    "Host: ".$this->host."\r\n" .
                    "Content-Type: application/x-www-form-urlencoded; charset=utf-8\r\n" .
                    "Content-Length: ".strlen($request)."\r\n" .
                    "User-Agent: Defensio PHP4 Class\r\n" .
                    "\r\n".
                    $request
                ;

            @fwrite($this->con, $request);

            $response = "";
            $response = fread($this->con, $responseLength);
            if(strpos($response, "200 OK") !== false) {
                $response = explode("--- \n", $response);
                return $response[1];
            }
            else {
                if(strpos($response, "404 Not Found") !== false) {
                    $this->setError(DEFENSIO_RESPONSE_FAILED, "The response could not be retrieved: File not found.");
                }
                else if(strpos($response, "401 Unauthorized") !== false) {
                    $this->setError(DEFENSIO_INVALID_KEY, "Your Defensio API key is not valid.");
                }
            }
        } else {
            $this->setError(DEFENSIO_RESPONSE_FAILED, "The response could not be retrieved.");
        }

        $this->_disconnect();
    }


    // Connect to the Defensio server and store that connection in the instance variable $con
    function _connect() {
        if(!($this->con = @fsockopen($this->host, $this->port))) {
            $this->setError(DEFENSIO_SERVER_NOT_FOUND, "Could not connect to defensio server.");
        }
    }


    // Close the connection to the Defensio server
    function _disconnect() {
        @fclose($this->con);
    }


}





// The controlling class. This is the ONLY class the user should instantiate in
// order to use the Defensio service!
class Defensio extends DefensioObject {
    var $apiPort = 80;
    var $defensioServer = 'api.defensio.com';
    var $defensioVersion = '1.1';
    var $http;

    var $ignore = array(
            'HTTP_COOKIE',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED_HOST',
            'HTTP_MAX_FORWARDS',
            'HTTP_X_FORWARDED_SERVER',
            'REDIRECT_STATUS',
            'SERVER_PORT',
            'PATH',
            'DOCUMENT_ROOT',
            'SERVER_ADMIN',
            'QUERY_STRING',
            'PHP_SELF',
            'argv'
        );

    var $blogUrl = "";
    var $apiKey  = "";


    /**
     * Constructor
     *
     * Set instance variables, connect to Defensio, and check API key
     *
     * @param   String  $blogUrl    The URL to your own blog
     * @param     String    $apiKey     Your Defensio API key
     * @param     String[]  $comment    A formatted comment array to be examined by the Defensio service
     */
    function Defensio($blogUrl, $apiKey) {
        $this->blogUrl = $blogUrl;
        $this->apiKey  = $apiKey;

        // Connect to the Defensio server and populate errors if they exist
        $this->http = new DefensioHttpClient($this->defensioServer, $this->blogUrl, $this->apiKey);
        if($this->http->errorsExist()) {
            $this->errors = array_merge($this->errors, $this->http->getErrors());
        }

        // Check if the API key is valid
        if(!$this->validateKey($apiKey)) {
            $this->setError(DEFENSIO_INVALID_KEY, "Your Defensio API key is not valid.");
        }
    }


    /**
     * Query the Defensio and determine if the comment is spam or not
     *
     * @return  array(boolean, string)
     */
    function auditComment($comment) {
        // Populate the comment array with information needed by Defensio
        if(!isset($comment['user-ip'])) {
            $comment['user-ip'] = ($_SERVER['REMOTE_ADDR'] != getenv('SERVER_ADDR')) ? $_SERVER['REMOTE_ADDR'] : getenv('HTTP_X_FORWARDED_FOR');
        }

        $response = $this->http->getResponse($this->_getQueryString($comment), 'audit-comment');

        return array(($this->_parseResponse($response,'spam') == "true"), $this->_parseResponse($response,'spaminess'), $this->_parseResponse($response,'signature'));
    }


    /**
     * Submit this artice as a new Article on your Blog
     *
     * @param   Array $article  The Array containing the article
     * @return  boolean
     */
    function announceArticle($article) {
        /* @@@Artikel aufbereiten */

        $response = $this->http->getResponse($this->_getQueryString($article), 'announce-article');

        return ($this->_parseResponse($response,'status') == "success");
    }


    /**
     * Get Stats from Defensio
     *
     * @return  boolean
     */
    function getStats() {
        $response = $this->http->getResponse($this->_getQueryString(), 'get-stats');

        return ($this->_parseResponse($response));
    }


    /**
     * Submit this comment as an unchecked spam to the Defensio server
     *
     * @param   String $signatures  The Defensio signatures of the messages to be reported
     * @return  boolean
     */
    function submitFalsePositives($signatures) {
        $response = $this->http->getResponse($this->_getQueryString(array('signatures' => $signatures)), 'report-false-positives');

        return ($this->_parseResponse($response,'status') == "success");
    }


    /**
     * Submit a false-positive comment as "ham" to the Defensio server
     *
     * @param   String $signatures  The Defensio signatures of the messages to be reported
     * @return  boolean
     */
    function submitFalseNegatives($signatures) {
        $response = $this->http->getResponse($this->_getQueryString(array('signatures' => $signatures)), 'report-false-negatives');

        return ($this->_parseResponse($response,'status') == "success");
    }


    /**
     * Check with the Defensio server to determine if the API key is valid
     *
     * @param   String  $key    The Defensio API key passed from the constructor argument
     * @return  boolean
     */
    function validateKey($key) {
        $response = $this->http->getResponse($this->_getQueryString(), 'validate-key');

        return ($this->_parseResponse($response,'status') == "success");
    }


    /**
     * Build a query string for use with HTTP requests
     *
     * @access  Protected
     * @param   Array $values
     * @return  String
     */
    function _getQueryString($values = NULL) {
        $values['owner-url'] = $this->blogUrl;

        $query_string = "";
        foreach($values as $key => $data) {
            $query_string .= $key . '=' . urlencode(stripslashes($data)) . '&';
        }

        return $query_string;
    }


    /**
     * Parse the YAML response recieved from the Server
     *
     * @access  Protected
     * @param   String $response, [String $field]
     * @return  Array [or String]
     */
    function _parseResponse($response, $field = '') {
        $lines = explode("\n", $response);
        $array = array();
        foreach($lines as $line) {
            $line = trim($line);
            if($line != "" && $line != "defensio-result:") {
                $line = preg_replace("/(.*): \"?([^\"]*)\"?/", "$1|$2", $line);
                list($key,$data) = explode('|', $line);
                $array[$key] = $data;
            }
        }

        if($field != '' and isset($array[$field])) {
            return $array[$field];
        }
        else {
            return $array;
        }
    }
}
?>
