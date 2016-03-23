<?php

class DeviceTable {
    private $users = array();
    private $devices = array();
    private $ips = array();

    public function __construct($file) {
        $fh = fopen($file, "r");
        
        if (!$fh)
            throw new DTException("unable to open ".realpath($file));

        $row = 1;
        while (!feof($fh)) {
            $fields = fgetcsv($fh, 1024, " ");
            if (count($fields) == 1 && empty(trim($fields[0])))
                continue; // skip empty line
            if (count($fields) != 3)
                throw new DTException("in file ".realpath($file).": row ".$row." does not have 3 fields");
            array_push($this->users, $fields[0]);
            array_push($this->devices, $fields[1]);
            array_push($this->ips, $fields[2]);
            $row++;
        }
        fclose($fh);
    }

    public function write($file) {
        $fh = fopen($file, "w");

        if (!$fh)
            throw new DTException("unable to open ".realpath($file));

        for ($i=0; $i<count($this->users); $i++) {
            fputcsv($fh, array($this->users[$i], $this->devices[$i], $this->ips[$i]), " ");
        }

        fclose($fh);
        return $this;
    }

    public function find($user, $device) {
        $row = false;
        for ($i=0; $i<count($this->users); $i++) {
            if ($this->users[$i] == $user && $this->devices[$i] == $device) {
                $row = $i;
                break;
            }
        }
        return $row;
    }

    public function delete($row) {
        array_splice($this->users, $row, 1);
        array_splice($this->devices, $row, 1);
        array_splice($this->ips, $row, 1);
    }

    public function ndevices($user) {
        $n = 0;
        for ($i=0; $i<count($this->users); $i++) {
            if ($this->users[$i] == $user)
                $n++;
        }
        return $n;
    }

    public function getDevices($user) {
        $devices = array();
        $ips = array();
        for ($i=0; $i<count($this->users); $i++) {
            if ($this->users[$i] == $user) {
                array_push($devices, $this->devices[$i]);
                array_push($ips, $this->ips[$i]);
            }
        }
        return array("devices"=>$devices, "ips"=>$ips);
    }

    public function add($user, $device, $ip) {
        array_push($this->users, $user);
        array_push($this->devices, $device);
        array_push($this->ips, $ip);
        return $this;
    }

    public function getIp($row) {
        return $this->ips[$row];
    }

    public function setIp($row, $ip) {
        $this->ips[$row] = $ip;
    }
}


class DTException extends Exception {
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct("DeviceTable exception: ".$message, $code, $previous);
    }
}

?>
