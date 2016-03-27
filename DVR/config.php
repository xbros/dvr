<?php

namespace DVR;

const VERSION = '0.1';

const DEBUG = false;
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
const CONFIG_PATH = ROOT_DIR . '/dvr.conf';
/** @var string default absolute path to config file */
const CONFIG_SYS_PATH = ROOT_DIR . '/dvr.sys.conf';
/** @var string default absolute path to passwords file. use '' to disable */
const PASSWD_PATH = ROOT_DIR . '/dvr.passwd';
/** @var string default absolute path to log file */
const LOG_PATH = ROOT_DIR . '/dvr.log';
/** @var int default max number of devices per user in the table */
const MAX_DEVICES = 20;
/** @var int default file and directory creation mode (0 prefix needed) */
const CREATE_MODE = 0774;

?>
