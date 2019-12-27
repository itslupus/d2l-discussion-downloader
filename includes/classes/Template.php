<?php
    class Template {
        private $folderName;
        private $variables = array();

        public function __construct(string $folderName) {
            $this->folderName = $folderName;
        }

        public function __set(string $key, $value) {
            $this->variables[$key] = $value;
        }

        public function __get(string $key) {
            return $this->variables[$key];
        }

        public function render(string $fileName) {
            ob_start();

            if (file_exists(__DIR__ . GLOBAL_TEMPLATE_PATH . '/' . $this->folderName)) {
                if (file_exists(__DIR__ . GLOBAL_TEMPLATE_PATH .  '/' . $this->folderName . '/' . $fileName)) {
                    extract($this->variables);

                    require_once(__DIR__ . GLOBAL_TEMPLATE_PATH .  '/' . $this->folderName . '/' . $fileName);
                }
            }

            echo(ob_get_clean());
        }

        public function getStylePath() {
            return GLOBAL_TEMPLATE_PATH . '/' . $this->folderName . '/styles.css';
        }
    }
?>