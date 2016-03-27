<?php

namespace DVR;
require realpath(dirname(__FILE__)) . '/config.php';
require realpath(dirname(__FILE__)) . '/utils.php';
require realpath(dirname(__FILE__)) . '/DeviceTable.php';

define('DVR\USAGE', 'usage: dvr route [-n]' . PHP_EOL
	. 'options: -n   echo commands without running them' . PHP_EOL);

try {
	// check options
	$opts = getopt('n');
	if ($opts === false) {
		echo (USAGE);
		exit(1);
	}

	// get unique ips
	$table = new DeviceTable(CONFIG_PATH);
	$nodelIps = file(CONFIG_NODEL_PATH, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	$ips = array_values(array_unique(array_merge($table->getIps(), $nodelIps)));

	// get route ips
	$routeIps = getRouteIps();

	// get gateway
	$gw = getGateway();
	if (empty($gw)) {
		throw new \Exception('gateway is empty');
	}

	// add missing routes
	for ($i = 0; $i < count($ips); $i++) {
		$ip = $ips[$i];
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
	for ($i = 0; $i < count($routeIps); $i++) {
		$ip = $routeIps[$i];
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