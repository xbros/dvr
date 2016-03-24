<?php
namespace DVR;

/** @var string default path to config file (relative to the main calling php script) */
const CONFIG_PATH = "../../.dvr/dvr.conf";
/** @var string default path to passwords file. use "" to disable */
const PASSWD_PATH = "../../.dvr/dvr.passwd";
/** @var string default path to log file (relative to the main calling php script) */
const LOG_PATH = "../../.dvr/dvr.log";
/** @var int default max number of devices per user in the table */
const MAX_DEVICES = 20;
/** @var int default file and directory creation mode (0 prefix needed) */
const MODE = 0774;
?>
