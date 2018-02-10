<?php
function mysqliorm_autoloader($class) {
    if (file_exists(IAPP_DIR.'/models/' . $class . '.php')) {
        require_once(IAPP_DIR.'/models/' . $class . '.php');
        return;
    }
}

spl_autoload_register('mysqliorm_autoloader');