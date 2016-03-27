<?php

namespace DVR;
require realpath(dirname(__FILE__)) . '/config.php';
require realpath(dirname(__FILE__)) . '/utils.php';
require realpath(dirname(__FILE__)) . '/PasswdTable.php';
require realpath(dirname(__FILE__)) . '/DeviceTable.php';
require realpath(dirname(__FILE__)) . '/App.php';

define('DVR\USAGE', 'usage: dvr update --auth=<user>:<pw> --hostname=<device> [--myip=<ip>] [--offline=NOCHG|YES]' . PHP_EOL);

try {
	$dvr = new App();
	$dvr->updateTable();
} catch (RCException $e) {
	returnCode($e->getReturnCode());
	log($e->getMessage());
	if (php_sapi_name() === 'cli') {
		echo USAGE;
	}
} catch (Exception $e) {
	http_response_code(500); // Internal Server Error
	returnCode('911');
	log('generic exception: ' . $e->getMessage());
	if (php_sapi_name() === 'cli') {
		echo USAGE;
	}
}

?>