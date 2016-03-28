<?php

namespace dvr;
require realpath(dirname(__FILE__)) . '/config.php';
require realpath(dirname(__FILE__)) . '/utils.php';
require realpath(dirname(__FILE__)) . '/DeviceTable.php';

define('dvr\USAGE', 'usage: dvr route [-n]' . PHP_EOL
	. 'options: -n   echo commands without running them' . PHP_EOL);

try {
	// check options
	$opts = getopt('n');
	if ($opts === false) {
		echo (USAGE);
		exit(1);
	}

	// get unique ips
	$table = new DeviceTable(CONF_PATH);
	$nodelIps = file(CONF_NODEL_PATH, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if ($nodelIps === false)
		throw new \Exception('failed to open file: ' . CONF_NODEL_PATH);
	}
	$ips = array_unique(array_merge($table->getIps(), $nodelIps));

	// get route ips
	$routeIps = getRouteIps();

	// get gateway
	$gw = getGateway();
	if (empty($gw)) {
		throw new \Exception('gateway is empty');
	}

	// add missing routes
	foreach ($ips as $ip) {
		if (!in_array($ip, $routeIps)) {
			$command = 'route add -host ' . $ip . ' gw ' . $gw . ' dev eth0';
			if (isset($opts['n'])) {
				echo $command . PHP_EOL;
			} else {
				$last = system($command, $ret);
				if ($last === false || $ret !== 0) {
					throw new \Exception('command failed: ' . $last);
				}
				rclog($command);
			}
		}
	}

	// delete extra routes
	foreach ($routeIps as $ip) {
		if (!in_array($ip, $ips)) {
			$command = 'route del -host ' . $ip . ' gw ' . $gw . ' dev eth0';
			if (isset($opts['n'])) {
				echo $command . PHP_EOL;
			} else {
				$last = system($command, $ret);
				if ($last === false || $ret !== 0) {
					throw new \Exception('command failed: ' . $last);
				}
				rclog($command);
			}
		}
	}
} catch (\Exception $e) {
	rclog('generic exception: ' . $e->getMessage());
	exit(1);
}

?>