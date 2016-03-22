<?php

include(realpath(dirname(__FILE__)."/DeviceTable.php"));


function createFile($path, $mode=0774) {
	if (!file_exists($path)) {
		$dir = dirname($path);
		if (!file_exists($dir)) {
		    if (!mkdir($dir))
	    		throw new DVRException("unable to create dir: ".$dir, "911");
		    chmod($dir, $mode);
		}
	    if (!touch($path))
    		throw new DVRException("unable to create file: ".$path, "911");
	    chmod($path, $mode);
	    return true;
	}
	return false;
}


class DVR {
	const VERSION = "1.0";

	public static $ALLOWED_KEYS = array("hostname", "myip", "wildcard", "mx", "backmx", "offline", "system", "url");

	private $config_path;
	private $max_devices;
	private $table;
	private $user;
	private $device;
	private $ip = false;
	private $delete = false;

	public function __construct($user=DVR_USER, $config_path=DVR_CONFIG_PATH, $max_devices=DVR_MAX_DEVICES) {

		// check config path and create file if necessary
		if (createFile($config_path))
			self::log("create config file: ".$config_path);

		$this->config_path = $config_path;
		$this->max_devices = $max_devices;
	}

	public function __destruct() {
		fclose($this->log_handle);
	}

	public function printDevices() {
		try {
		    // read config file
		    $table = new DeviceTable($this->config_path);
	        self::log("read config file: ".$this->config_path);

		    // get devices
		    $devices = $table->getDevices($this->user);

		    // print devices
		    for ($i=0; $i<count($devices["devices"]); $i++)
		        echo $devices["devices"][$i]." ".$devices["ips"][$i].PHP_EOL;

	        self::log("print user devices");
		} catch (DVRException $e) {
	    	throw $e;
		} catch (DTException $e) {
	    	throw new DVRException($e->getMessage(), "911", $e);
		} catch (Exception $e) {
	    	throw new DVRException("Generic exception: ".$e->getMessage(), "911", $e);
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
		if (empty($vars["hostname"]) || !preg_match("/^[A-Za-z]{1}[a-zA-Z0-9_\\-\\.]{2,}\$/", $vars["hostname"]))
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

	public function updateTable($vars = null) {
		self::parseRequest($vars);

		try {
			// read config file
			$table = new DeviceTable($this->config_path);
	        self::log("read config file: ".$this->config_path);

			// find device
			$row = $table->find($user, $device);

			if ($this->delete) {
			    // delete device
			    if ($row === false)
			    	throw new DVRException("ignore delete device: ".$device, "nochg");
			    else {
			        $table->delete($row);
			        self::log("delete device: ".$device);
			        self::returnCode("good delete ".$device);
			    }
			} elseif ($row === false) {
			    // add device
			    if ($table->ndevices($user)>=$this->max_devices)
			    	throw new DVRException("ignore add device: ".$device.". max number of devices reached: ".$this->max_devices, "numhost");
			    $table->add($user, $device, $ip);
		        self::log("add device: ".$device." ".$ip);
			    self::returnCode("good ".$ip);
			} else {
			    // change ip
			    if ($table->getIp($row) == $ip)
			    	throw new DVRException("ignore change device: ".$device." ".$ip, "nochg ".$ip);
			    $table->setIp($row, $ip);
		        self::log("change device: ".$device." ".$ip);
			    self::returnCode("good ".$ip);
			}

			// write config file
			$table->write($this->config_path);
	        self::log("write config file: ".$this->config_path);
		} catch (DVRException $e) {
	    	throw $e;
		} catch (DTException $e) {
	    	throw new DVRException($e->getMessage(), "911", $e);
		} catch (Exception $e) {
	    	throw new DVRException("Generic exception: ".$e->getMessage(), "911", $e);
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

	public static function log($message) {
    	if (empty(DVR_LOG_HANDLE))
    		return;
		fprintf(DVR_LOG_HANDLE, "%s %s %s \"%s\"".PHP_EOL, 
			$_SERVER["REMOTE_ADDR"], 
			DVR_USER, 
			strftime("[%d/%b/%Y:%H:%M:%S %z]"), 
			$message);
	}

	public static function logHeader() {
		fprintf($this->log_handle, "#Software: dvr %s".PHP_EOL, self::VERSION);
		fprintf($this->log_handle, "#Start-Date: %s".PHP_EOL, strftime("%d/%b/%Y:%H:%M:%S %z"));
		fprintf($this->log_handle, "#Fields: ip user [time] \"message\"".PHP_EOL);
	}
}


class DVRException extends Exception {

}

?>
