<?php

namespace dvr;

/**
 * create file and folder if necessary
 * @param string $path path to the file
 * @param int $mode octal notation permissions. ex: 0774
 * @return bool true if created. false otherwise
 * @throws Exception if failed to create dir or file
 */
function createFile($path, $mode = CREATE_FILE_MODE, $mode_dir = CREATE_DIR_MODE) {
	if (!file_exists($path)) {
		$dir = dirname($path);
		if (!file_exists($dir)) {
			// create dir
			if (!mkdir($dir)) {
				throw new \Exception('failed to create dir: ' . $dir);
			}
			chmod($dir, $mode_dir);
		}
		// create file
		if (!touch($path)) {
			throw new \Exception('failed to create file: ' . $path);
		}
		chmod($path, $mode);

		return true;
	}

	return false;
}

/**
 * print log in static $LOG_HANDLE file
 * log format is: ip user [time] script 'message'
 * open log file and set $LOG_HANLDE static variable
 * create file with header if necessary
 * @param string $message
 * @param string $logPath path to log file
 */
function log($message) {
	static $LOG_HANDLE = null;
	static $USER = 'anonymous';
	static $IP = '127.0.0.1';

	if (is_null($LOG_HANDLE)) {
		$ok = createFile(LOG_PATH);
		// open log file
		$LOG_HANDLE = fopen(LOG_PATH, 'a');
		// print header if new
		if ($ok) {
			fprintf($LOG_HANDLE, '#Software: dvr v%s' . PHP_EOL, VERSION);
			fprintf($LOG_HANDLE, '#Start-Date: %s' . PHP_EOL, strftime('%d/%b/%Y:%H:%M:%S %z'));
			fprintf($LOG_HANDLE, '#Fields: ip user [time] script "message"' . PHP_EOL);
		}
	}

	if (isset($_SERVER['PHP_AUTH_USER'])) {
		$USER = $_SERVER['PHP_AUTH_USER'];
	}
	if (isset($_SERVER['REMOTE_ADDR'])) {
		$IP = $_SERVER['REMOTE_ADDR'];
	}

	fprintf($LOG_HANDLE, '%s %s %s %s "%s"' . PHP_EOL, $IP, $USER, strftime('[%d/%b/%Y:%H:%M:%S %z]'), $_SERVER['PHP_SELF'], $message);
}

/**
 * echo return code
 * @param string $returnCode
 */
function returnCode($returnCode) {
	echo $returnCode . PHP_EOL;
}

function rclog($message) {
	returnCode($message);
	log($message);
}

function execCheck($command, &$out=null, &$ret=null) {
	$last = exec($command, $out, $ret);
	if ($last === false || $ret !== 0) {
		throw new \Exception('command failed: ' . $command);
	}
	return $last;
}

function getRouteIps() {
	// read route
	$command = 'route -n | grep \'^[0-9]\' | awk \'{print $1}\'';
	$out = array();
	execCheck($command, $out);
	$ips = filter_var_array($out, FILTER_VALIDATE_IP);
	return array_values($ips);
}

function getGateway() {
	$command = 'route -n | grep \'UG[ \t]\' | grep eth0 | grep \'^0\.0\.0\.0\' | awk \'{print $2}\'';
	return trim(filter_var(execCheck($command), FILTER_VALIDATE_IP));
}

function getMyIp($url = MYIP_URL) {
	$command = 'curl -s ' . $url;
	return trim(filter_var(execCheck($command), FILTER_VALIDATE_IP));
}

?>