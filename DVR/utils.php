<?php

namespace DVR;

/**
 * create file and folder if necessary
 * @param string $path path to the file
 * @param int $mode octal notation permissions. ex: 0774
 * @return bool true if created. false otherwise
 * @throws Exception if failed to create dir or file
 */
function createFile($path, $mode = CREATE_MODE) {
	if (!file_exists($path)) {
		$dir = dirname($path);
		if (!file_exists($dir)) {
			// create dir
			if (!mkdir($dir)) {
				throw new \Exception('failed to create dir: ' . $dir);
			}
			chmod($dir, $mode);
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

/**
 * check authentication
 * @param string $passwdPath path to passwords file. ignore if empty
 */
function authenticate($passwdPath = PASSWD_PATH) {
	// php executed from command line
	if (php_sapi_name() === 'cli') {
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1'; // localhost
		$params = getopt('', array('auth:'));
		if (isset($params['auth'])) {
			list($user, $pw) = PasswdTable::parse($params['auth']);
			$_SERVER['PHP_AUTH_USER'] = $user;
			$_SERVER['PHP_AUTH_PW'] = $pw;
		}
	}

	if (!empty($passwdPath)) {
		$passwds = new PasswdTable($passwdPath);
	}

	// check username
	if (empty($_SERVER['PHP_AUTH_USER'])) {
		throw new BadauthException('authentication missing');
	}
	$user = $_SERVER['PHP_AUTH_USER'];
	if (!empty($passwdPath) && !$passwds->has($user)) {
		throw new BadauthException('unknown username: ' . $user);
	}

	// check password
	if (!empty($passwdPath)) {
		$pw = '';
		if (isset($_SERVER['PHP_AUTH_PW'])) {
			$pw = $_SERVER['PHP_AUTH_PW'];
		}
		if (!$passwds->verify($user, $pw)) {
			throw new BadauthException('invalid password');
		}
	}
}

/**
 * exception thrown by authentication
 */
class BadauthException extends \Exception {
}

function getRouteIps() {
	// read route
	$ips = array();
	exec('route -n | grep \'^[0-9]\' | awk \'{print $1}\'', $ips);
	$ips = filter_var_array($ips, FILTER_VALIDATE_IP);
	return array_values($ips);
}

function getGateway() {
	return trim(system('route -n | grep \'UG[ \t]\' | grep eth0 | grep \'^0\.0\.0\.0\' | awk \'{print $2}\''));
}

?>