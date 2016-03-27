<?php

namespace DVR;
require realpath(dirname(__FILE__)) . '/config.php';
require realpath(dirname(__FILE__)) . '/utils.php';
require realpath(dirname(__FILE__)) . '/DeviceTable.php';

try {
	// create config file if necessary
	if (createFile(CONFIG_PATH)) {
		rclog('created config file: ' . realpath(CONFIG_PATH));
	}

	// create sys config file if necessary
	if (createFile(CONFIG_SYS_PATH)) {
		rclog('created config file: ' . realpath(CONFIG_SYS_PATH));
	}

	// initialize sys config file with route ips
	file_put_contents(CONFIG_SYS_PATH, implode(PHP_EOL, getRouteIps()));
	rclog('initialized config file: ' . realpath(CONFIG_SYS_PATH));

	// create passwords file if necessary
	if (!empty(PASSWD_PATH) && createFile(PASSWD_PATH)) {
		rclog('created password file: ' . realpath(PASSWD_PATH));
	}
} catch (\Exception $e) {
	rclog('generic exception: ' . $e->getMessage());
	exit(1);
}

?>