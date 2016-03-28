<?php

namespace dvr;

/**
 * table of users devices ips
 */
class DeviceTable {
	/** @var array of strings. ips */
	private $ips = array();
	/** @var array of strings. devices */
	private $devices = array();
	/** @var array of strings. usernames */
	private $users = array();

	/**
	 * initialize table
	 * read config file and set array members
	 * @param  string $filepath path to config file to be read
	 * @throws DTException if failed to open file or invalid entry
	 */
	public function __construct($filepath = null) {
		if (is_null($filepath)) {
			return;
		}

		// open file
		$fh = fopen($filepath, 'r');
		if (!$fh) {
			throw new DTException('failed to open ' . realpath($filepath));
		}

		// read lines
		$i = 1;
		while (!feof($fh)) {
			$line = preg_split('/\s+/', trim(fgets($fh)));
			if (count($line) === 1 && empty($line[0])) {
				$i++;
				continue; // skip empty line
			}
			if (count($line) !== 3) {
				throw new DTException('invalid config file ' . realpath($filepath) . ': line ' . $i . ' does not have 3 fields');
			}
			array_push($this->ips, $line[0]);
			array_push($this->devices, $line[1]);
			array_push($this->users, $line[2]);
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
		$fh = fopen($filepath, 'w');
		if (!$fh) {
			throw new DTException('failed to open ' . realpath($filepath));
		}
		// determine columns width
		$ipwid = 15; // ip column width
		$devwid = 15; // devices column width
		$userwid = 15; // users column width
		foreach (array_keys($this->users) as $i) {
			if (strlen($this->ips[$i]) > $ipwid) {
				$ipwid = strlen($this->ips[$i]);
			}
			if (strlen($this->devices[$i]) > $devwid) {
				$devwid = strlen($this->devices[$i]);
			}
			if (strlen($this->users[$i]) > $userwid) {
				$userwid = strlen($this->users[$i]);
			}
		}
		// print lines
		$format = '%-' . $ipwid . 's %-' . $devwid . 's %-' . $userwid . 's' . PHP_EOL;
		foreach (array_keys($this->users) as $i) {
			fprintf($fh, $format, $this->ips[$i], $this->devices[$i], $this->users[$i]);
		}
		fclose($fh);
	}

	/**
	 * find a user-device pair entry in the table
	 * @return int|false index found or false if not found
	 */
	public function find($user, $device) {
		$ind = false;
		foreach (array_keys($this->users) as $i) {
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
		if ($ind < 0 || $ind >= count($this->users)) {
			throw DTException('delete failed. invalid index: ' . $ind);
		}
		array_splice($this->ips, $ind, 1);
		array_splice($this->devices, $ind, 1);
		array_splice($this->users, $ind, 1);
	}

	/**
	 * get number of devices
	 * @param string|null $user username
	 * @return int
	 */
	public function count($user = null) {
		if (is_null($user)) {
			return count($this->ips);
		} else {
			return (count(array_keys($this->users, $user)));
		}
	}

	/**
	 * get devices and ips of user
	 * @param string $user username
	 * @return array contains 'device'=>'ip' entries
	 */
	public function getUserIps($user = null) {
		$ips = array();
		foreach (array_keys($this->users) as $i) {
			if ($this->users[$i] === $user) {
				$ips[$this->devices[$i]] = $this->ips[$i];
			}
		}

		return $ips;
	}

	/**
	 * add device to the table
	 * @param string $ip ip adress
	 * @param string $device device name
	 * @param string $user username
	 */
	public function add($ip, $device, $user) {
		array_push($this->ips, $ip);
		array_push($this->devices, $device);
		array_push($this->users, $user);
	}

	/**
	 * get ip by index
	 * @param int $ind index
	 * @return string the ip address
	 * @throws DTException if invalid index
	 */
	public function getIp($ind) {
		if ($ind < 0 || $ind >= count($this->users)) {
			throw DTException('getIp failed. invalid index: ' . $ind);
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
		if ($ind < 0 || $ind >= count($this->users)) {
			throw DTException('setIp failed. invalid index: ' . $ind);
		}
		$this->ips[$ind] = $ip;
	}

	/**
	 * get the ip addresses in the table
	 * @return array containing ip addresses
	 */
	public function getIps() {
		return $this->ips;
	}
}

/**
 * exception thrown by DeviceTable class
 */
class DTException extends \Exception {
	/**
	 * @inheritDoc
	 */
	public function __construct($message, $code = 0, \Exception $previous = null) {
		parent::__construct('DeviceTable exception: ' . $message, $code, $previous);
	}
}

?>
