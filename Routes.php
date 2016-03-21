<?php
class Routes {
    private $users = array();
    private $devices = array();
    private $ips = array();

    public function __construct($file) {
        $fh = fopen($file, "r");
        
        if ($fh == false)
            throw new Exception("unable to open " . $file);

        $row = 1;
        while (!feof($fh)) {
            $fields = fgetcsv($fh, 1024, " ");
            if (count($fields) == 1 && empty(trim($fields[0])))
                continue; // skip empty line
            if (count($fields) != 3)
                throw new Exception("In file " . $file . ", row " . $row . ": rows must have 3 fields");
            array_push($this->users, $fields[0]);
            array_push($this->devices, $fields[1]);
            array_push($this->ips, $fields[2]);
            $row++;
        }
        fclose($fh);
    }

    public function write($file) {
        $fh = fopen($file, "w");

        if ($fh == false)
            throw new Exception("unable to open " . $file);

        for ($i=0; $i<count($this->users); $i++) {
            fputcsv($fh, array($this->users[i], $this->devices[i], $this->ips[i]), " ");
        }

        fclose($fh);
        return $this;
    }

    public function find($user, $device) {
        $row = false;
        for ($i=0; $i<count($this->users); $i++) {
            if ($this->users[i] == $user && $this->devices[i] == $device) {
                $row = $i;
                break;
            }
        }
        return $row;
    }

    public function add($user, $device, $ip) {
        array_push($this->users, $user);
        array_push($this->devices, $device);
        array_push($this->ips, $ip);
        return $this;
    }

    public function set_ip($row, $ip) {
        $this->ips[$row] = $ip;
        return $this;
    }
}
?>
