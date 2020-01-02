<?php
    class CURL {
        /**
         * @var resource    The private CURL resource instance variable
         */
        private $curl;

        /**
         * @var string      The string that was last used in a cURL session
         */
        private $url;

        /**
         * @var resource    The temporary cookie file used to store the session cookie
         */
        private $tmpFile;

        /**
         * CURL object constructor
         */
        public function __construct() {
            $this->tmpFile = tmpfile();
            $tmpFilePath = stream_get_meta_data($this->tmpFile)['uri'];

            $this->curl = curl_init();
            curl_setopt($this->curl, CURLOPT_VERBOSE, true);
            curl_setopt($this->curl, CURLOPT_COOKIEJAR, $tmpFilePath);
            curl_setopt($this->curl, CURLOPT_COOKIEFILE, $tmpFilePath);
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($this->curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux i586; rv:31.0) Gecko/20100101 Firefox/70.0');
        }

        /**
         * Sets the POST option for cURL execution
         * Set to false to set to GET, true for POST
         * 
         * @param   string  $post   True to set method to POST
         */
        public function setPOST(bool $post) {
            curl_setopt($this->curl, CURLOPT_POST, $post);
        }

        /**
         * Set the POST fields to send on a request
         * Input can be an associative array or encoded string
         * 
         * @param   mixed     $fields The data to send along with the request
         */
        public function setFields($fields) {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $fields);
        }

        /**
         * Sets the URL for the next request
         * 
         * @param   string  $url    The URL of the web address
         */
        public function setURL(string $url) {
            $this->url = $url;
            curl_setopt($this->curl, CURLOPT_URL, $url);
        }

        /**
         * Executes a cURL request
         * 
         * @return  mixed           Returns the standard curl_exec() result
         */
        public function execute() {
            return curl_exec($this->curl);
        }
    }
?>