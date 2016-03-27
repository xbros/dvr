<?php

namespace DVR;

const VERSION = '0.1';
const DEBUG = true;

if (DEBUG) {
	// display errors
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}

/*============== EDIT BELOW =================================*/
// "../.dvr"
define('DVR\ROOT_DIR', dirname(__DIR__) . '/.dvr');
/** @var string default absolute path to config file */
define('DVR\CONFIG_PATH', ROOT_DIR . '/dvr.conf');
/** @var string default absolute path to config file */
define('DVR\CONFIG_SYS_PATH', ROOT_DIR . '/dvr.sys.conf');
/** @var string default absolute path to passwords file. use '' to disable */
define('DVR\PASSWD_PATH', ROOT_DIR . '/dvr.passwd');
/** @var string default absolute path to log file */
define('DVR\LOG_PATH', ROOT_DIR . '/dvr.log');
/** @var int default max number of devices per user in the table */
define('DVR\MAX_DEVICES', 20);
/** @var int default file and directory creation mode (0 prefix needed) */
define('DVR\CREATE_MODE', 0774);

?>
