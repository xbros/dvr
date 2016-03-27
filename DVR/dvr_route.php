<?php

namespace DVR;
require realpath(dirname(__FILE__)) . '/config.php';
require realpath(dirname(__FILE__)) . '/utils.php';
require realpath(dirname(__FILE__)) . '/DeviceTable.php';

try {
	// get unique ips
	$table = new DeviceTable(CONFIG_PATH);
	$sysIps = file(CONFIG_SYS_PATH, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	$ips = array_unique(array_merge($table->getIps(), $sysIps));

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
			$command = 'route add -net ' . $ip . ' netmask 255.255.255.255 gw ' . $gw . ' dev eth0';
			$last = system($command, $ret);
			if ($last === false || $ret !== 0) {
				throw new \Exception('command failed: ' . $last);
			}
			rclog($command);
		}
	}

	// delete extra routes
	for ($i = 0; $i < count($routeIps); $i++) {
		$ip = $routeIps[$i];
		if (!in_array($ip, $ips)) {
			$command = 'route del -net ' . $ip . ' netmask 255.255.255.255 gw ' . $gw . ' dev eth0';
			$last = system($command, $ret);
			if ($last === false || $ret !== 0) {
				throw new \Exception('command failed: ' . $last);
			}
			rclog($command);
		}
	}
} catch (\Exception $e) {
	rclog('generic exception: ' . $e->getMessage());
	exit(1);
}

?>