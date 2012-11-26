<?php
    # Register a simple autoload function
    require_once("lib/Dropbox/AutoLoader.php");

    class DropboxModel extends Model { 
        function __construct() {
            # Your token and secret
            $config = Config::current();
            $token  = $config->module_dropbox["oauth_token"];
            $secret = $config->module_dropbox["oauth_secret"];

            // Instantiate the Encrypter and storage objects
            $encrypter = new \Dropbox\OAuth\Storage\Encrypter($secret);
            $storage = new \Dropbox\OAuth\Storage\Session($encrypter);

            $OAuth = new \Dropbox\OAuth\Consumer\Curl($token, $secret, $storage);
            $dropbox = new \Dropbox\API($OAuth);
            
            return $dropbox;
        }

        // Retrieve the account information
        private function account_info() {
            return $this->accountInfo();
        }

        private function get_file($path, $outFile = false) {
            return $this->getFile($path, $outFile);
        }

        private function put_file() {
            // Create a temporary file and write some data to it
            $tmp = tempnam('/tmp', 'dropbox');
            $data = 'This file was uploaded using the Dropbox API!';
            file_put_contents($tmp, $data);
            
            // Upload the file with an alternative filename
            $put = $dropbox->putFile($tmp, 'api_upload_test.txt');
            
            // Unlink the temporary file
            unlink($tmp);
        }

        private function put_stream() {
            // Open a stream for reading and writing
            $stream = fopen('php://temp', 'rw');
            
            // Write some data to the stream
            $data = 'This file was uploaded using the Dropbox API!';
            fwrite($stream, $data);
            
            // Upload the stream data to the specified filename
            $put = $dropbox->putStream($stream, 'api_upload_test.txt');
            
            // Close the stream
            fclose($stream);
        }

        private function meta_data() {
            // Set the file path
            // You will need to modify $path or run putFile.php first
            $path = 'api_upload_test.txt';
            
            // Get the metadata for the file/folder specified in $path
            $metaData = $this->metaData($path);
        }

        private function chunked_upload() {
            // Extend your sript execution time where required
            set_time_limit(0);
            
            // Upload the large file
            $largeFilePath = 'path/to/large/file';
            $chunked = $dropbox->chunkedUpload($largeFilePath);
        }
    }
