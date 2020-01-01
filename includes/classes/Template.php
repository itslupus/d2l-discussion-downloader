<?php
    class Template {
        /**
         * @var string      The name of the folder that contains the templates
         */
        private $folderName;

        /**
         * @var array       An array containing the variables to send to the template
         */
        private $variables = array();

        /**
         * Template object constructor
         * 
         * @param   string  $folderName The name of the folder relative to GLOBAL_TEMPLATE_PATH without leading/trailing slashes
         */
        public function __construct(string $folderName) {
            $this->folderName = $folderName;
        }

        /**
         * General value setter for the template
         * 
         * @param   string  $key        The key value to reference the value by
         * @param   mixed   $value      The value
         */
        public function __set(string $key, $value) {
            $this->variables[$key] = $value;
        }

        /**
         * General value getter for the template
         * 
         * @param   string  $key        The key of the value to get
         * 
         * @return  mixed               The value at that key
         */
        public function __get(string $key) {
            return $this->variables[$key];
        }

        /**
         * Renders the document.
         * The file name is a filepath relative to the template folder without leading/trailing slashes
         * 
         * @param   string  $fileName   Name of the file to draw
         */
        public function render(string $fileName) {
            $root = __DIR__ . '/../../';

            ob_start();

            if (file_exists($root . GLOBAL_TEMPLATE_PATH . '/' . $this->folderName)) {
                if (file_exists($root . GLOBAL_TEMPLATE_PATH .  '/' . $this->folderName . '/' . $fileName)) {
                    extract($this->variables);

                    require_once($root. GLOBAL_TEMPLATE_PATH .  '/' . $this->folderName . '/' . $fileName);
                }
            }

            echo(ob_get_clean());
        }

        /**
         * Temporary function to retrieve the path for the stylesheet
         * 
         * @return  string              File path to the stylesheet relative to GLOBAL_TEMPLATE_PATH
         */
        public function getStylePath() {
            return GLOBAL_TEMPLATE_PATH . '/' . $this->folderName . '/styles.css';
        }
    }
?>