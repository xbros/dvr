<?php

namespace dvr;

const VERSION = '1.0';
const DEBUG = false;

if (DEBUG) {
	// display errors
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}

/*============== EDIT BELOW =================================*/
define('dvr\ROOT_DIR', dirname(__DIR__) . '/.dvr'); // "../.dvr"
/** @var string default absolute path to config file */
define('dvr\CONF_PATH', ROOT_DIR . '/dvr.conf');
/** @var string default absolute path to system config file */
define('dvr\CONF_NODEL_PATH', ROOT_DIR . '/dvr.nodel.conf');
/** @var string default absolute path to system config file */
define('dvr\CONF_NOADD_PATH', ROOT_DIR . '/dvr.noadd.conf');
/** @var string default absolute path to log file */
define('dvr\LOG_PATH', ROOT_DIR . '/dvr.log');
/** @var int default max number of devices per user in the table */
define('dvr\MAX_DEVICES', 20);
/** @var int default file creation mode (0 prefix needed) */
define('dvr\CREATE_FILE_MODE', 0664);
/** @var int default directory creation mode (0 prefix needed) */
define('dvr\CREATE_DIR_MODE', 0774);
/** @var string default owner for file and dir creation. allowed when executed as root only. use '' to not change */
define('dvr\CREATE_OWNER', '');
/** @var string default group for file and dir creation. use '' to not change */
define('dvr\CREATE_GROUP', '');
/** @var string default url for getting host public ip */
define('dvr\MYIP_URL', 'ifcfg.me')

?>
