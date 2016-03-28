<?php

namespace dvr;
require realpath(dirname(__FILE__)) . '/config.php';
require realpath(dirname(__FILE__)) . '/utils.php';

try {
	// create config file if necessary
	if (createFile(CONF_PATH)) {
		rclog('created config file: ' . realpath(CONF_PATH));
	}

	// create sys config file if necessary
	if (createFile(CONF_NODEL_PATH)) {
		rclog('created config file: ' . realpath(CONF_NODEL_PATH));
	}

	// initialize sys config file with route ips
	file_put_contents(CONF_NODEL_PATH, implode(PHP_EOL, getRouteIps()));
	rclog('initialized config file: ' . realpath(CONF_NODEL_PATH));

	// create sys config file if necessary
	if (createFile(CONF_NOADD_PATH)) {
		rclog('created config file: ' . realpath(CONF_NOADD_PATH));
	}

	// initialize sys config file with route ips
	file_put_contents(CONF_NOADD_PATH, getMyIp(MYIP_URL));
	rclog('initialized config file: ' . realpath(CONF_NOADD_PATH));
} catch (\Exception $e) {
	rclog('generic exception: ' . $e->getMessage());
	exit(1);
}

?>