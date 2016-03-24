<?php
namespace DVR;

/**
 * Dynamic VPN routes controler class
 */
class DVR {
    const VERSION = "0.1"; /** @var string current software version */
    /** @var array authorized parameter keys in the request */
    public static $ALLOWED_KEYS = array("hostname", "myip", "wildcard", "mx", "backmx", "offline", "system", "url");
    /** @var file handle of the log file returned by fopen */
    private static $LOG_HANDLE;
    /** @var string username (default="anonymous" if no authentication) */
    private static $USER = "anonymous";
    /** @var string path to config file */
    private $configPath;
    /** @var int max number of devices per user in the table */
    private $maxDevices;
    /** @var string name of the user device to be set */
    private $device;
    /** @var string ip to be set to the user device */
    private $ip = false;
    /** @var bool. true to delete device from table */
    private $delete = false;

    /**
     * initialize DVR
     * open log file, check authentication and create config file if necessary
     * @param string $configPath path to config file
     * @param string $logPath path to log file
     * @param string $passwdPath path to passwords file
     * @param int $maxDevices max number of devices per user in the table
     */
    public function __construct($configPath=CONFIG_PATH, $logPath=LOG_PATH,
                                $passwdPath=PASSWD_PATH, $maxDevices=MAX_DEVICES) {
        self::openLog($logPath);
        self::auth($passwdPath);

        // check config path and create file if necessary
        if (self::createFile($configPath)) {
            self::log("created config file: ".realpath($configPath));
        }

        $this->configPath = $configPath;
        $this->maxDevices = $maxDevices;
    }

    /**
     * display devices and ips of authenticated user
     * @throws DVRException if any error reading config file
     */
    public function printDevices() {
        try {
            // read config file
            $table = new DeviceTable($this->configPath);

            // get devices
            $devices = $table->getDevices(self::$USER);

            // print devices
            for ($i=0; $i<count($devices["devices"]); $i++) {
                echo $devices["devices"][$i]." ".$devices["ips"][$i]."<br>";
            }

            self::log("printed user devices");
        } catch (DTException $e) {
            http_response_code(500); // Internal Server Error
            throw new DVRException($e->getMessage(), "911", $e);
        }
    }

    /**
     * apply request to update the table of devices ips
     * read config file, apply change and write config file if needed
     * log action and display "good" return code if success
     * @param array $params contains request parameters. if empty, defaults to $_GET or $_POST
     * @throws DVRException if no change needed or any error reading config file
     */
    public function updateTable($params = null) {
        self::parseRequest($params);

        try {
            // read config file
            $table = new DeviceTable($this->configPath);

            // find device
            $ind = $table->find(self::$USER, $this->device);

            if ($this->delete) {
                // delete device
                if ($ind === false) {
                    throw new DVRException("ignored delete device: ".$this->device, "nochg");
                } else {
                    $table->delete($ind);
                    self::log("deleted device: ".$this->device);
                    self::returnCode("good deleted ".$this->device);
                }
            } elseif ($ind === false) {
                // add device
                if ($table->countDevices(self::$USER)>=$this->maxDevices) {
                    throw new DVRException("ignored add device: ".$this->device.". max number of devices reached: ".$this->maxDevices, "numhost");
                }
                $table->add(self::$USER, $this->device, $this->ip);
                self::log("added device: ".$this->device." ".$this->ip);
                self::returnCode("good ".$this->ip);
            } else {
                // change ip
                if ($table->getIp($ind) === $this->ip) {
                    throw new DVRException("ignored change device: ".$this->device." ".$this->ip, "nochg ".$this->ip);
                }
                $table->setIp($ind, $this->ip);
                self::log("changed device: ".$this->device." ".$this->ip);
                self::returnCode("good ".$ithis->p);
            }

            // write config file
            $table->write($this->configPath);
        } catch (DTException $e) {
            http_response_code(500); // Internal Server Error
            throw new DVRException($e->getMessage(), "911", $e);
        }
    }

    /**
     * validate request and set class members $hostname, $ip and $offline
     * @param array $params contains request parameters. if empty, defaults to $_GET or $_POST
     * @throws DVRException if invalid
     */
    private function parseRequest($params) {
        if (empty($params)) {
            $params = self::getRequestParams();
        }

        // check parameters keys are allowed
        foreach(array_keys($params) as $key) {
            if (!in_array($key, self::$ALLOWED_KEYS)) {
                self::badrequest("invalid parameter key: ".$key, "abuse");
            }
        }

        // get ip
        if (!empty($params["myip"])) {
            $this->ip = filter_var($params["myip"], FILTER_VALIDATE_IP);
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
        if (empty($params["hostname"])) {
                self::badrequest("missing hostname value", "notfqdn");
        }
        // minimum 3 characters: alphanumeric or [_-.] and starts with letter
        if (!preg_match("/^[A-Za-z]{1}[a-zA-Z0-9_\\-\\.]{2,}\$/", $params["hostname"])) {
            self::badrequest("invalid hostname value: ".$params["hostname"], "notfqdn");
        }
        $this->device = $params["hostname"];

        // check offline
        if (!empty($params["offline"])) {
            if (!in_array($params["offline"], array("YES", "NOCHG"))) {
                self::badrequest("invalid offline value: ".$params["offline"], "abuse");
            }
            if ($params["offline"] === "YES") {
                $this->delete = true;
            }
        }
    }

    /**
     * set response header for bad request and throw exception
     * @param string $message exception message
     * @param string $returnCode return code
     * @throws DVRException with given message and return code
     */
    public static function badrequest($message, $returnCode="abuse") {
        http_response_code(400); // Bad Request
        throw new DVRException("bad request. ".$message, $returnCode);
    }

    /**
     * return reference to either $_GET or $_POST
     * @return array reference to array
     * @throws DVRException if request method is neither GET nor POST
     */
    public static function getRequestParams() {
        switch ($_SERVER['REQUEST_METHOD']) {
            case "GET":
                $params =& $_GET;
                break;
            case "POST":
                $params =& $_POST;
                break;
            default:
                http_response_code(405); // Method Not Allowed
                throw new DVRException("request method not allowed: ".$_SERVER['REQUEST_METHOD'].". use GET or POST", "abuse");
        }

        return $params;
    }

    /**
     * echo return code
     * @param string $returnCode
     */
    public static function returnCode($returnCode) {
        echo $returnCode.PHP_EOL;
    }

    /**
     * create file and folder if necessary
     * @param string $path path to the file
     * @param int $mode octal notation permissions. ex: 0774
     * @return bool true if created. false otherwise
     * @throws Exception if failed to create dir or file
     */
    public static function createFile($path, $mode=MODE) {
        if (!file_exists($path)) {
            $dir = dirname($path);
            if (!file_exists($dir)) {
                // create dir
                if (!mkdir($dir)) {
                    throw new \Exception("failed to create dir: ".realpath($dir));
                }
                chmod($dir, $mode);
            }
            // create file
            if (!touch($path)) {
                throw new \Exception("failed to create file: ".realpath($path));
            }
            chmod($path, $mode);

            return true;
        }

        return false;
    }

    /**
     * open log file and set $LOG_HANLDE static member
     * create file with header if necessary
     * @param string $logPath path to log file
     */
    public static function openLog($logPath = LOG_PATH) {
        // close if already open
        if (!empty(self::$LOG_HANDLE)) {
            self::closeLog();
        }
        // create if necessary
        $ok = self::createFile($logPath);
        // open log file
        self::$LOG_HANDLE = fopen($logPath, "a");
        // print header if new
        if ($ok) {
            fprintf(self::$LOG_HANDLE, "#Software: dvr v%s".PHP_EOL, self::VERSION);
            fprintf(self::$LOG_HANDLE, "#Start-Date: %s".PHP_EOL, strftime("%d/%b/%Y:%H:%M:%S %z"));
            fprintf(self::$LOG_HANDLE, "#Fields: ip user [time] script \"message\"".PHP_EOL);
        }
    }

    /**
     * print log in static $LOG_HANDLE file
     * log format is: ip user [time] script "message"
     * @param string $message
     */
    public static function log($message) {
        fprintf(self::$LOG_HANDLE, "%s %s %s %s \"%s\"".PHP_EOL,
            $_SERVER["REMOTE_ADDR"],
            self::$USER,
            strftime("[%d/%b/%Y:%H:%M:%S %z]"),
            $_SERVER['PHP_SELF'],
            $message);
    }

    /** close log file */
    public static function closeLog() {
        fclose(self::$LOG_HANDLE);
    }

    /**
     * check authentication and set $USER static member
     * @param string $passwdPath path to passwords file. ignore if empty
     */
    public static function auth($passwdPath=PASSWD_PATH) {
        if (!empty($passwdPath)) {
            // get passwords table
            if (self::createFile($passwdPath)) {
                self::log("created passwords file: ".realpath($passwdPath));
            }
            $passwds = self::readPasswds($passwdPath);
        }

        // check username
        if (!empty($_SERVER['PHP_AUTH_USER'])) {
            $user = $_SERVER['PHP_AUTH_USER'];
        }
        if (empty($user)) {
            self::badauth("authentication missing");
        }
        if (!empty($passwds) && !in_array($user, array_keys($passwds))) {
            self::badauth("unknown username: ".$user);
        }

        // set user
        self::$USER = $user;

        // check password
        if (!empty($passwds)) {
            if (!empty($_SERVER['PHP_AUTH_PW'])) {
                $pass = $_SERVER['PHP_AUTH_PW'];
            }
            if (!empty($pass) && $pass !== $passwds[$user]) {
                self::badauth("invalid password");
            }
        }
    }

    /**
     * read passwords in space delimited csv file
     * @param string $passwdPath path to passwords file
     * @return array contains "user"=>"passwd" entries
     * @throws Exception if failed to open file
     */
    public static function readPasswds($passwdPath=PASSWD_PATH) {
        // open file
        $fh = fopen($passwdPath, "r");
        if (!$fh) {
            throw new \Exception("failed to open ".realpath($passwdPath));
        }

        // read file lines
        $passwds = array();
        $i = 1;
        while(!feof($fh)) {
            $line = fgetcsv($fh, 1024, " ");
            if (count($line) === 1) {
                if (empty(trim($line[0]))) {
                    $i++;
                    continue; // skip empty line
                }
                $passwds[$line[0]] = ""; // empty password
            } elseif (count($line) === 2) {
                $passwds[$line[0]] = $line[1];
            } else {
                throw new \Exception("invalid passwords file ".realpath($passwdPath).": line ".$i." must have 1 or 2 fields");
            }
            $i++;
        }

        // close file
        fclose($fh);

        return $passwds;
    }

    /**
     * set response header for authentication and throw badauth exception
     * @param string $message exception message
     * @throws DVRException badauth exception
     */
    public static function badauth($message) {
        header('WWW-Authenticate: Basic realm="Authentication Required"');
        http_response_code(401); // Unauthorized
        throw new DVRException("unauthorized. ".$message, "badauth");
    }
}


/**
 * exception thrown by DVR class
 * with return code field to be displayed on the webpage
 * and message to be logged
 */
class DVRException extends \Exception {
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
