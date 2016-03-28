<?php

namespace dvr;

/**
 * Dynamic VPN routes application class
 */
class App {
	/** @var array authorized parameter keys in the request */
	private static $ALLOWED_KEYS = array('hostname', 'myip', 'offline', 'wildcard', 'mx', 'backmx', 'system', 'url');
	/** @var array authorized long options for command line php */
	private static $ALLOWED_OPTS = array('hostname:', 'myip:', 'offline:', 'wildcard::', 'mx::', 'backmx::', 'system::', 'url::');
	/** @var string path to config file */
	private $configPath;
	/** @var string name of the user device to be set */
	private $device;
	/** @var string ip to be set to the user device */
	private $ip = false;
	/**
	 * @var string|null
	 *   'YES': delete device from table if existing.
	 *   'NOCHG': do not delete if existing and do not add if not.
	 *   null: ignore.
	 */
	private $offline = null;

	/**
	 * initialize dvr and check authentication
	 * @param string $configPath path to config file
	 */
	public function __construct($configPath = CONF_PATH) {

		// authenticate user
		if (php_sapi_name() === 'cli') {
			$_SERVER['REMOTE_ADDR'] = '127.0.0.1'; // localhost
			$params = getopt('', array('user:'));
			if (isset($params['user'])) {
				$_SERVER['PHP_AUTH_USER'] = $params['user'];
			}
		}

		// check username
		if (empty($_SERVER['PHP_AUTH_USER'])) {
			if (php_sapi_name() !== 'cli') {
				header('WWW-Authenticate: Basic realm="Authentication Required"');
			}
			http_response_code(401); // Unauthorized
			throw new RCException('unauthorized. authentication missing', 'badauth');
		}

		$this->configPath = $configPath;
	}

	/**
	 * display devices and ips of authenticated user
	 * @throws RCException if any error reading config file
	 */
	public function printDevices($sep = "<br>\n") {
		try {
			// read config file
			$table = new DeviceTable($this->configPath);

			// get devices
			$ips = $table->getUserIps($_SERVER['PHP_AUTH_USER']);

			// print devices
			foreach ($ips as $device=>$ip) {
				echo $ip . ' ' . $device . $sep;
			}

			log('printed user devices');
		} catch (DTException $e) {
			http_response_code(500); // Internal Server Error
			throw new RCException($e->getMessage(), '911', $e);
		}
	}

	/**
	 * apply request to update the table of devices ips
	 * read config file, apply change and write config file if needed
	 * log action and display 'good' return code if success
	 * @param array $params contains request parameters. if empty, defaults to $_GET or $_POST
	 * @throws RCException if no change needed or any error reading config file
	 */
	public function updateTable($params = null) {
		$this->parseRequest($params);

		try {
			// read config file
			$table = new DeviceTable($this->configPath);

			// find device
			$ind = $table->find($_SERVER['PHP_AUTH_USER'], $this->device);

			// get forbidden ips
			$noadd = file(CONF_NOADD_PATH, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

			if ($ind === false) {
				// device not found
				if ($this->offline === 'YES') {
					log('ignored add device: ' . $this->device);
					returnCode('nochg offline');
				} elseif ($this->offline === 'NOCHG') {
					log('ignored add device: ' . $this->device . '. offline=NOCHG');
					returnCode('nochg offline');
				} elseif ($table->count($_SERVER['PHP_AUTH_USER']) >= MAX_DEVICES) {
					log('ignored add device: ' . $this->device . '. max number of devices reached: ' . MAX_DEVICES);
					returnCode('numhost');
				} elseif ($noadd !== false && in_array($this->ip, $noadd)) {
					log('ignored add device: ' . $this->device . '. ip forbidden');
					returnCode('nochg forbidden ip');
				} else {
					// add device
					$table->add($this->ip, $this->device, $_SERVER['PHP_AUTH_USER']);
					log('added device: ' . $this->device . ' ' . $this->ip);
					returnCode('good ' . $this->ip);
				}
			} else {
				// device found
				if ($this->offline === 'YES') {
					// delete device
					$table->delete($ind);
					log('deleted device: ' . $this->device);
					returnCode('good deleted ' . $this->device);
				} else {
					if ($table->getIp($ind) === $this->ip) {
						log('ignored change device: ' . $this->device . ' ' . $this->ip);
						returnCode('nochg ' . $this->ip);
					} elseif ($noadd !== false && in_array($this->ip, $noadd)) {
						log('ignored change device: ' . $this->device . '. ip forbidden');
						returnCode('nochg forbidden ip');
					} else {
						// change ip
						$table->setIp($ind, $this->ip);
						log('changed device: ' . $this->device . ' ' . $this->ip);
						returnCode('good ' . $this->ip);
					}
				}
			}

			// write config file
			$table->write($this->configPath);
		} catch (DTException $e) {
			http_response_code(500); // Internal Server Error
			throw new RCException($e->getMessage(), '911', $e);
		}
	}

	/**
	 * validate request and set class members $hostname, $ip and $offline
	 * @param array $params contains request parameters. if empty, defaults to $_GET or $_POST
	 * @throws RCException if invalid
	 */
	private function parseRequest($params) {
		if (empty($params)) {
			$params = self::getRequestParams();
		}

		// check parameters keys are allowed
		foreach (array_keys($params) as $key) {
			if (!in_array($key, self::$ALLOWED_KEYS)) {
				self::badrequest('invalid parameter key: ' . $key, 'abuse');
			}
		}

		// get ip
		if (!empty($params['myip'])) {
			$this->ip = filter_var($params['myip'], FILTER_VALIDATE_IP);
		}
		// if missing myip, determine ip from server info
		if (!$this->ip && !empty($_SERVER['HTTP_CLIENT_IP'])) {
			$this->ip = filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP);
		}
		if (!$this->ip && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$this->ip = filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP);
		}
		if (!$this->ip) {
			$this->ip = $_SERVER['REMOTE_ADDR'];
		}

		// check hostname
		if (empty($params['hostname'])) {
			self::badrequest('missing hostname value', 'notfqdn');
		}
		// minimum 3 characters: alphanumeric or [_.-] and starts with letter
		if (!preg_match('/^[a-zA-Z][\w.-]{2,}$/', $params['hostname'])) {
			self::badrequest('invalid hostname value: ' . $params['hostname'], 'notfqdn');
		}
		$this->device = htmlspecialchars($params['hostname']);

		// check offline
		if (!empty($params['offline'])) {
			if (!in_array($params['offline'], array('YES', 'NOCHG'))) {
				self::badrequest('invalid offline value: ' . $params['offline'], 'abuse');
			}
			$this->offline = htmlspecialchars($params['offline']);
		}
	}

	/**
	 * set response header for bad request and throw exception
	 * @param string $message exception message
	 * @param string $returnCode return code
	 * @throws RCException with given message and return code
	 */
	public static function badrequest($message, $returnCode = 'abuse') {
		http_response_code(400); // Bad Request
		throw new RCException('bad request. ' . $message, $returnCode);
	}

	/**
	 * return reference to either $_GET or $_POST
	 * @return array reference to array
	 * @throws RCException if request method is neither GET nor POST
	 */
	public static function getRequestParams() {
		if (php_sapi_name() == 'cli') {
			// php executed from command line
			$params = getopt('', self::$ALLOWED_OPTS);
		} else {
			// php as web server
			switch ($_SERVER['REQUEST_METHOD']) {
			case 'GET':
				$params = &$_GET;
				break;
			case 'POST':
				$params = &$_POST;
				break;
			default:
				http_response_code(405); // Method Not Allowed
				throw new RCException('request method not allowed: ' . $_SERVER['REQUEST_METHOD'] . '. use GET or POST', 'abuse');
			}
		}

		return $params;
	}

}

/**
 * exception with return code field to be displayed on the webpage
 * and message to be logged
 * thrown by App class
 */
class RCException extends \Exception {
	private $returnCode; /** @var string return code */

	/**
	 * @param string $message message to be logged
	 * @param string $code return code to be displayed
	 * @param Exception $previous previous excetion if rethrown
	 */
	public function __construct($message, $code, \Exception $previous = null) {
		$this->returnCode = $code;
		parent::__construct($message, 0, $previous);
	}

	/** @return string the return code */
	public function getReturnCode() {
		return $this->returnCode;
	}
}

?>
