<?php
namespace DVR;

/**
 * table of users devices ips
 */
class DeviceTable {
    /** @var array of strings. usernames */
    private $users = array();
    /** @var array of strings. devices */
    private $devices = array();
    /** @var array of strings. ips */
    private $ips = array();

    /**
     * initialize table
     * read config file and set array members
     * @param  string $filepath path to config file to be read
     * @throws DTException if failed to open file or invalid entry
     */
    public function __construct($filepath) {
        // open file
        $fh = fopen($filepath, "r");
        if (!$fh) {
            throw new DTException("failed to open ".realpath($filepath));
        }

        // read lines
        $i = 1;
        while (!feof($fh)) {
            $fields = fgetcsv($fh, 1024, " ");
            if (count($fields) === 1 && empty(trim($fields[0]))) {
                $i++;
                continue; // skip empty line
            }
            if (count($fields) !== 3) {
                throw new DTException("invalid config file ".realpath($filepath).": line ".$i." does not have 3 fields");
            }
            array_push($this->users, $fields[0]);
            array_push($this->devices, $fields[1]);
            array_push($this->ips, $fields[2]);
            $i++;
        }

        // close file
        fclose($fh);
    }

    /**
     * write table in config file
     * @param string $filepath path to config file to be written
     * @throws DTException if failed to open file
     */
    public function write($filepath) {
        $fh = fopen($filepath, "w");
        if (!$fh) {
            throw new DTException("failed to open ".realpath($filepath));
        }
        for ($i=0; $i<count($this->users); $i++) {
            fputcsv($fh, array($this->users[$i], $this->devices[$i], $this->ips[$i]), " ");
        }
        fclose($fh);
    }

    /**
     * find a user-device pair entry in the table
     * @return int|false index found or false if not found
     */
    public function find($user, $device) {
        $ind = false;
        for ($i=0; $i<count($this->users); $i++) {
            if ($this->users[$i] === $user && $this->devices[$i] === $device) {
                $ind = $i;
                break;
            }
        }

        return $ind;
    }

    /**
     * delete entry from index
     * @param int $ind entry index
     * @throws DTException if invalid index
     */
    public function delete($ind) {
        if ($ind<0 || $ind>=count($this->users)) {
            throw DTException("delete failed. invalid index: ".$ind);
        }
        array_splice($this->users, $ind, 1);
        array_splice($this->devices, $ind, 1);
        array_splice($this->ips, $ind, 1);
    }

    /**
     * get number of devices of user
     * @param string $user username
     * @return int
     */
    public function countDevices($user) {
        $n = 0;
        for ($i=0; $i<count($this->users); $i++) {
            if ($this->users[$i] === $user) {
                $n++;
            }
        }

        return $n;
    }

    /**
     * get devices and ips of user
     * @param string $user username
     * @return array contains "device"=>"ip" entries
     */
    public function getDevices($user) {
        $devices = array();
        $ips = array();
        for ($i=0; $i<count($this->users); $i++) {
            if ($this->users[$i] === $user) {
                array_push($devices, $this->devices[$i]);
                array_push($ips, $this->ips[$i]);
            }
        }

        return array("devices"=>$devices, "ips"=>$ips);
    }

    /**
     * add device to the table
     * @param string $user username
     * @param string $device device name
     * @param string $ip ip adress
     */
    public function add($user, $device, $ip) {
        array_push($this->users, $user);
        array_push($this->devices, $device);
        array_push($this->ips, $ip);
    }

    /**
     * get ip by index
     * @param int $ind index
     * @return string the ip address
     * @throws DTException if invalid index
     */
    public function getIp($ind) {
        if ($ind<0 || $ind>=count($this->users)) {
            throw DTException("getIp failed. invalid index: ".$ind);
        }

        return $this->ips[$ind];
    }

    /**
     * set ip to index
     * @param int $ind index
     * @param string $ip ip address
     * @throws DTException if invalid index
     */
    public function setIp($ind, $ip) {
        if ($ind<0 || $ind>=count($this->users)) {
            throw DTException("setIp failed. invalid index: ".$ind);
        }
        $this->ips[$ind] = $ip;
    }
}


/**
 * exception thrown by DeviceTable class
 */
class DTException extends Exception {
    /**
     * @inheritDoc
     */
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct("DeviceTable exception: ".$message, $code, $previous);
    }
}

?>
