
<?php

define("MAX_DEVICES", 20);
define("CONFIG_DIR", "./");
define("CONFIG_FILE", CONFIG_DIR . "dvr.conf");
define("LOG_DIR", "/var/log/dvr/");
define("LOG_FILE", LOG_DIR . "dvr.log");

// create config file
if (!file_exists(CONFIG_DIR)) {
    if (!mkdir(CONFIG_DIR))
        trigger_error("Can not create " . CONFIG_DIR, E_USER_ERROR);
    chmod(CONFIG_DIR, 0774);
}
if (!file_exists(CONFIG_FILE)) {
    if (!touch(CONFIG_FILE))
        trigger_error("Can not create " . CONFIG_FILE, E_USER_ERROR);
    chmod(CONFIG_FILE, 0774);
}

?>