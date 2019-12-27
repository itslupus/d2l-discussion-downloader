<?php
    class CURL {
        private $curl;
        private $url;
        private $tempFile;

        public function __construct() {
            $this->tempFile = tmpfile();
            $tempFilePath = stream_get_meta_data($this->tmpFile)['uri'];

            curl_setopt($this->curl, CURLOPT_VERBOSE, true);
            curl_setopt($this->curl, CURLOPT_COOKIEJAR, $tempFilePath);
            curl_setopt($this->curl, CURLOPT_COOKIEFILE, $tmpFilePath);
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($this->curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux i586; rv:31.0) Gecko/20100101 Firefox/70.0');
        }

        public function setPOST(bool $post) {
            curl_setopt($this->curl, CURLOPT_VERBOSE, $post);
        }

        public function setFields($fields) {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $fields);
        }

        public function setURL(string $url) {
            curl_setopt($curl, CURLOPT_URL, $url);
        }

        public function execute() {
            return curl_exec($this->curl);
        }
    }
?>