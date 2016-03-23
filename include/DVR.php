<?php

include(realpath(dirname(__FILE__)."/DeviceTable.php"));


/// Dynamic VPN routes controler class
class DVR {
	const VERSION = "1.0"; /// current software version

 	/// authorized variable keys in the request
	public static $ALLOWED_KEYS = array("hostname", "myip", "wildcard", "mx", "backmx", "offline", "system", "url");

	private static $LOG_HANDLE; /// file handle of the log file returned by fopen
	private static $USER = "anonymous"; /// string. username (default="anonymous" if no authentication)

	private $config_path; /// string. path to config file
	private $max_devices; /// int. max number of devices per user in the table
	private $device; /// string. name of the user device to be set
	private $ip = false; /// string. ip to be set to the user device
	private $delete = false; /// bool. true to delete device from table

	/// initialize DVR
	/// open log file, check authentication and create config file if necessary
	/// @param $config_path string. path to config file
	/// @param $log_path string. path to log file
	/// @param $passwd_path string. path to passwords file
	/// @param $max_devices int. max number of devices per user in the table
	public function __construct($config_path=DVR_CONFIG_PATH, $log_path=DVR_LOG_PATH, 
								$passwd_path=DVR_PASSWD_PATH, $max_devices=DVR_MAX_DEVICES) {
		self::openLog($log_path);
		self::auth($passwd_path);

		// check config path and create file if necessary
		if (self::createFile($config_path))
			self::log("created config file: ".realpath($config_path));

		$this->config_path = $config_path;
		$this->max_devices = $max_devices;
	}

	/// display devices and ips of authenticated user
	/// @throw if any error reading config file
	public function printDevices() {
		try {
		    // read config file
		    $table = new DeviceTable($this->config_path);

		    // get devices
		    $devices = $table->getDevices(self::$USER);

		    // print devices
		    for ($i=0; $i<count($devices["devices"]); $i++)
		        echo $devices["devices"][$i]." ".$devices["ips"][$i]."<br>";

	        self::log("printed user devices");
		} catch (DTException $e) {
	    	throw new DVRException($e->getMessage(), "911", $e);
		}
	}

	/// apply request to update the table of devices ips
	/// read config file, apply change and write config file if needed
	/// log action and display "good" return code if success
	/// @param $vars array with request variables. if empty, defaults to $_GET or $_POST
	/// @throw if no change needed or any error reading config file
	public function updateTable($vars = null) {
		self::parseRequest($vars);

		try {
			// read config file
			$table = new DeviceTable($this->config_path);

			// find device
			$ind = $table->find(self::$USER, $this->device);

			if ($this->delete) {
			    // delete device
			    if ($ind === false)
			    	throw new DVRException("ignored delete device: ".$this->device, "nochg");
			    else {
			        $table->delete($ind);
			        self::log("deleted device: ".$this->device);
			        self::returnCode("good deleted ".$this->device);
			    }
			} elseif ($ind === false) {
			    // add device
			    if ($table->ndevices(self::$USER)>=$this->max_devices)
			    	throw new DVRException("ignored add device: ".$this->device.". max number of devices reached: ".$this->max_devices, "numhost");
			    $table->add(self::$USER, $this->device, $this->ip);
		        self::log("added device: ".$this->device." ".$this->ip);
			    self::returnCode("good ".$this->ip);
			} else {
			    // change ip
			    if ($table->getIp($ind) == $this->ip)
			    	throw new DVRException("ignored change device: ".$this->device." ".$this->ip, "nochg ".$this->ip);
			    $table->setIp($ind, $this->ip);
		        self::log("changed device: ".$this->device." ".$this->ip);
			    self::returnCode("good ".$ithis->p);
			}

			// write config file
			$table->write($this->config_path);
		} catch (DTException $e) {
	    	throw new DVRException($e->getMessage(), "911", $e);
		}
	}

	/// validate request and set class members $hostname, $ip and $offline
	/// @param $vars array with request variables. if empty, defaults to $_GET or $_POST
	/// @throw if invalid
	private function parseRequest($vars) {
		if (empty($var))
			$vars = self::getRequestVars();

		// check variable keys are allowed
		foreach(array_keys($vars) as $key) {
		    if (!in_array($key, self::$ALLOWED_KEYS))
		    	throw new DVRException("bad request. invalid parameter key: ".$key, "abuse");
		}

		// get ip
		if (!empty($vars["myip"]))
		    $this->ip = filter_var($vars["myip"], FILTER_VALIDATE_IP);
		// if missing myip, determine ip from server info
		if (!$this->ip && !empty($_SERVER['HTTP_CLIENT_IP']))
		    $this->ip = filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP);
		if (!$this->ip && !empty($_SERVER['HTTP_X_FORWARDED_FOR']))
		    $this->ip = filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP);
		if (!$this->ip)
		    $this->ip = $_SERVER['REMOTE_ADDR'];

		// check hostname
		if (empty($vars["hostname"]))
		    throw new DVRException("bad request. missing hostname value", "notfqdn");
		// minimum 3 characters: alphanumeric or [_-.] and starts with letter
		if (!preg_match("/^[A-Za-z]{1}[a-zA-Z0-9_\\-\\.]{2,}\$/", $vars["hostname"]))
		    throw new DVRException("bad request. invalid hostname value: ".$vars["hostname"], "notfqdn");
		$this->device = $vars["hostname"];

		// check offline
		if (!empty($vars["offline"])) {
		    if (!in_array($vars["offline"], array("YES", "NOCHG")))
		    	throw new DVRException("bad request. invalid offline value: ".$vars["offline"], "abuse");
		    if ($vars["offline"] == "YES")
		        $this->delete = true;
		}
	}

	/// return reference to either $_GET or $_POST
	/// @return reference to array
	/// @throw if request method is neither GET nor POST
	public static function getRequestVars() {
		switch ($_SERVER['REQUEST_METHOD']) {
			case "GET":
				$vars =& $_GET;
				break;
			case "POST":
				$vars =& $_POST;
				break;
			default:
				throw new DVRException("unsupported request method: ".$_SERVER['REQUEST_METHOD'].". use GET or POST", "abuse");
		}
		return $vars;
	}

	/// echo return code
	/// @param $returnCode string.
	public static function returnCode($returnCode) {
		echo $returnCode.PHP_EOL;
	}

	/// create file and folder if necessary
	/// @param $path string
	/// @param $mode int. octal notation permissions. ex: 0774
	/// @return true if created. false otherwise
	public static function createFile($path, $mode=DVR_MODE) {
		if (!file_exists($path)) {
			$dir = dirname($path);
			if (!file_exists($dir)) {
				// create dir
			    if (!mkdir($dir))
		    		throw new Exception("failed to create dir: ".realpath($dir));
			    chmod($dir, $mode);
			}
			// create file
		    if (!touch($path))
	    		throw new Exception("failed to create file: ".realpath($path));
		    chmod($path, $mode);
		    return true;
		}
		return false;
	}

	/// open log file and set $LOG_HANLDE static member
	/// create file with header if necessary
	/// @param $log_path string. path to log file
	public static function openLog($log_path = DVR_LOG_PATH) {
		// close if already open
    	if (!empty(self::$LOG_HANDLE))
    		self::closeLog();
    	// create if necessary
	    $ok = self::createFile($log_path);
    	// open log file
	    self::$LOG_HANDLE = fopen($log_path, "a");
    	// print header if new
	    if ($ok) {    	
			fprintf(self::$LOG_HANDLE, "#Software: dvr v%s".PHP_EOL, self::VERSION);
			fprintf(self::$LOG_HANDLE, "#Start-Date: %s".PHP_EOL, strftime("%d/%b/%Y:%H:%M:%S %z"));
			fprintf(self::$LOG_HANDLE, "#Fields: ip user [time] script \"message\"".PHP_EOL);
	    }
	}

	/// print log in static $LOG_HANDLE file
	/// @details log format is: ip user [time] script "message"
	/// @param $message string
	public static function log($message) {
		fprintf(self::$LOG_HANDLE, "%s %s %s %s \"%s\"".PHP_EOL, 
			$_SERVER["REMOTE_ADDR"], 
			self::$USER, 
			strftime("[%d/%b/%Y:%H:%M:%S %z]"), 
			$_SERVER['PHP_SELF'],
			$message);
	}

	/// close log file
	public static function closeLog() {
		fclose(self::$LOG_HANDLE);
	}

	/// check authentication and set $USER static member
	/// @param passwd_path string. path to passwords file. ignore if empty
	public static function auth($passwd_path=DVR_PASSWD_PATH) {
		if (!empty($passwd_path)) {
			// get passwords table
			if (self::createFile($passwd_path))
				self::log("created passwords file: ".realpath($passwd_path));
			$passwds = self::readPasswds($passwd_path);
		}

		// check username
	    if (!empty($_SERVER['PHP_AUTH_USER']))
	        $user = $_SERVER['PHP_AUTH_USER'];

	    if (empty($user))
	    	self::badauth("authentication missing");

	    if (!empty($passwds) && !in_array($user, array_keys($passwds)))
    		self::badauth("authentication failed. unknown username: ".$user);

		// set user
		self::$USER = $user;

		// check password
		if (!empty($passwds)) {
		    if (!empty($_SERVER['PHP_AUTH_PW']))
		        $pass = $_SERVER['PHP_AUTH_PW'];

	        if (!empty($pass) && $pass !== $passwds[$user])
	        	self::badauth("authentication failed: invalid password");
		}
    }

	/// read passwords in space delimited csv file
	/// @param $passwd_path string. path to passwords file
	/// @return array with "user"=>"passwd" entries
	/// @throw if failed to open
	public static function readPasswds($passwd_path=DVR_PASSWD_PATH) {
		// open file
		$fh = fopen($passwd_path, "r");
		if (!$fh)
			throw new Exception("failed to open ".realpath($passwd_path));

		// read file lines
		$passwds = array();
        $i = 1;
		while(!feof($fh)) {
			$line = fgetcsv($fh, 1024, " ");
			if (count($line)==1) {
				if (empty(trim($line[0]))) {
        			$i++;
					continue; // skip empty line
				}
				$passwds[$line[0]] = ""; // empty password
			} elseif (count($line)==2)
				$passwds[$line[0]] = $line[1];
			else
                throw new Exception("invalid passwords file ".realpath($passwd_path).": row ".$i." must have 1 or 2 fields");
        	$i++;
		}

		// close file
		fclose($fh);
		return $passwds;
	}

	/// set response header for authentication
	/// @param $message string. exception message
	/// @throw badauth exception
    public static function badauth($message) {
        header('WWW-Authenticate: Basic realm="Authentication Required"');
        header('HTTP/1.0 401 Unauthorized');
        throw new DVRException($message, "badauth");
    }
}


/// exception thrown by DVR class
/// with return code field to be displayed on the webpage
/// and message to be logged
class DVRException extends Exception {
	private $returnCode; /// string. return code

	/// @param $message string. message to be logged
	/// @param $code string. return code to be displayed
	/// @param $previous. previous excetion if rethrown
    public function __construct($message, $code, Exception $previous = null) {
    	$this->returnCode = $code;
        parent::__construct($message, 0, $previous);
    }

    /// get return code to be displayed
    public function getReturnCode() {
    	return $this->returnCode;
    }
}

?>
