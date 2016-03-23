<?php

include(realpath(dirname(__FILE__)."/DeviceTable.php"));


class DVR {
	const VERSION = "1.0";

	public static $ALLOWED_KEYS = array("hostname", "myip", "wildcard", "mx", "backmx", "offline", "system", "url");
	public static $LOG_HANDLE;
	public static $USER;

	private $config_path;
	private $max_devices;
	private $mode;
	private $device;
	private $ip = false;
	private $delete = false;

	public function __construct($config_path=DVR_CONFIG_PATH, $max_devices=DVR_MAX_DEVICES) {
		self::auth();
		self::openLog();
		self::setUser();

		// check config path and create file if necessary
		if (self::createFile($config_path))
			self::log("create config file: ".realpath($config_path));

		$this->config_path = $config_path;
		$this->max_devices = $max_devices;
	}

	public function printDevices() {
		try {
		    // read config file
		    $table = new DeviceTable($this->config_path);

		    // get devices
		    $devices = $table->getDevices(self::$USER);

		    // print devices
		    for ($i=0; $i<count($devices["devices"]); $i++)
		        echo $devices["devices"][$i]." ".$devices["ips"][$i]."<br>";

	        self::log("print user devices");
		} catch (DTException $e) {
	    	throw new DVRException($e->getMessage(), "911", $e);
		}
	}

	public function updateTable($vars = null) {
		self::parseRequest($vars);

		try {
			// read config file
			$table = new DeviceTable($this->config_path);

			// find device
			$row = $table->find(self::$USER, $this->device);

			if ($this->delete) {
			    // delete device
			    if ($row === false)
			    	throw new DVRException("ignore delete device: ".$this->device, "nochg");
			    else {
			        $table->delete($row);
			        self::log("delete device: ".$this->device);
			        self::returnCode("good delete ".$this->device);
			    }
			} elseif ($row === false) {
			    // add device
			    if ($table->ndevices(self::$USER)>=$this->max_devices)
			    	throw new DVRException("ignore add device: ".$this->device.". max number of devices reached: ".$this->max_devices, "numhost");
			    $table->add(self::$USER, $this->device, $this->ip);
		        self::log("add device: ".$this->device." ".$this->ip);
			    self::returnCode("good ".$this->ip);
			} else {
			    // change ip
			    if ($table->getIp($row) == $this->ip)
			    	throw new DVRException("ignore change device: ".$this->device." ".$this->ip, "nochg ".$this->ip);
			    $table->setIp($row, $this->ip);
		        self::log("change device: ".$this->device." ".$this->ip);
			    self::returnCode("good ".$ithis->p);
			}

			// write config file
			$table->write($this->config_path);
		} catch (DTException $e) {
	    	throw new DVRException($e->getMessage(), "911", $e);
		}
	}

	private function parseRequest($vars) {
		if (empty($var))
			$vars = self::requestVars();

		// check parameter keys are allowed
		foreach(array_keys($vars) as $key) {
		    if (!in_array($key, self::$ALLOWED_KEYS))
		    	throw new DVRException("ignore request. invalid parameter key: ".$key, "abuse");
		}

		// get ip
		if (!empty($vars["myip"]))
		    $this->ip = filter_var($vars["myip"], FILTER_VALIDATE_IP);
		if (!$this->ip && !empty($_SERVER['HTTP_CLIENT_IP']))
		    $this->ip = filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP);
		if (!$this->ip && !empty($_SERVER['HTTP_X_FORWARDED_FOR']))
		    $this->ip = filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP);
		if (!$this->ip)
		    $this->ip = $_SERVER['REMOTE_ADDR'];

		// check device
		if (empty($vars["hostname"]))
		    throw new DVRException("ignore request. missig hostname value", "notfqdn");
		if (!preg_match("/^[A-Za-z]{1}[a-zA-Z0-9_\\-\\.]{2,}\$/", $vars["hostname"]))
		    throw new DVRException("ignore request. invalid hostname value: ".$vars["hostname"], "notfqdn");
		$this->device = $vars["hostname"];

		// check delete
		if (!empty($vars["offline"])) {
		    if (!in_array($vars["offline"], array("YES", "NOCHG")))
		    	throw new DVRException("ignore request. invalid offline value: ".$vars["offline"], "abuse");
		    if ($vars["offline"] == "YES")
		        $this->delete = true;
		}
	}

	public static function requestVars() {
		switch ($_SERVER['REQUEST_METHOD']) {
			case "GET":
				$vars =& $_GET;
				break;
			case "POST":
				$vars =& $_POST;
				break;
			default:
				throw new DVRException("Unsupported request method: ".$_SERVER['REQUEST_METHOD'].". use GET or POST", "abuse");
		}
		return $vars;
	}

	public static function returnCode($returnCode) {
		echo $returnCode.PHP_EOL;
	}

	public static function createFile($path, $mode=DVR_MODE) {
		if (!file_exists($path)) {
			$dir = dirname($path);
			if (!file_exists($dir)) {
			    if (!mkdir($dir))
		    		throw new Exception("unable to create dir: ".realpath($dir));
			    chmod($dir, $mode);
			}
		    if (!touch($path))
	    		throw new Exception("unable to create file: ".realpath($path));
		    chmod($path, $mode);
		    return true;
		}
		return false;
	}

	public static function setUser() {
	    self::$USER = "anonymous";
	    if (!empty($_SERVER["PHP_AUTH_USER"]))
	       self::$USER = $_SERVER["PHP_AUTH_USER"];
	}

	public static function openLog($log_path = DVR_LOG_PATH) {
    	// open log file and create if necessary
	    $ok = self::createFile($log_path);
	    self::$LOG_HANDLE = fopen($log_path, "a");
	    if ($ok) {    	
			fprintf(self::$LOG_HANDLE, "#Software: dvr v%s".PHP_EOL, self::VERSION);
			fprintf(self::$LOG_HANDLE, "#Start-Date: %s".PHP_EOL, strftime("%d/%b/%Y:%H:%M:%S %z"));
			fprintf(self::$LOG_HANDLE, "#Fields: ip user [time] script \"message\"".PHP_EOL);
	    }
	}

	public static function log($message) {
		fprintf(self::$LOG_HANDLE, "%s %s %s %s \"%s\"".PHP_EOL, 
			$_SERVER["REMOTE_ADDR"], 
			self::$USER, 
			strftime("[%d/%b/%Y:%H:%M:%S %z]"), 
			$_SERVER['PHP_SELF'],
			$message);
	}

	public static function closeLog() {
		fclose(self::$LOG_HANDLE);
	}

	public static function auth($passwd_path=DVR_PASSWD_PATH) {
		// read passwords
		$fh = fopen($passwd_path, "r");
		if (!$fh)
			throw new Exception("Can not read file: ".realpath($passwd_path));
		while(!feof($fh)) {
			$line = fgetcsv($fh, 1024, ":");
			if (count($line)==1 && trim($line[0])=="")
				continue;
			$passwds[$line[0]] = $line[1];
		}
		// get username and password
	    if (!empty($_SERVER['PHP_AUTH_USER'])) {
	        $user = $_SERVER['PHP_AUTH_USER'];
	        $pass = $_SERVER['PHP_AUTH_PW'];
	    }
	    // send error if not valid
	    if (empty($user) || !in_array($user, array_keys($passwds)) || ($pass !== $passwds[$user])) {
	        header('WWW-Authenticate: Basic realm="Authentication Required"');
	        header('HTTP/1.0 401 Unauthorized');
	        echo "badauth";
	        die();
	    }
    }
}

}


class DVRException extends Exception {
	private $returnCode;

    public function __construct($message, $code, Exception $previous = null) {
    	$this->returnCode = $code;
        parent::__construct($message, 0, $previous);
    }

    public function getReturnCode() {
    	return $this->returnCode;
    }
}

?>
