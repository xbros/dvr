<?php

namespace DVR;

class PasswdTable {
	/** @var array contains 'user'=>'passwd' entries */
	private $passwds = array();

	const USER_PATTERN = '/^[a-zA-Z][\w.-]{2,}$/';
	const PW_PATTERN = '/^[^\s:]*$/';

	/**
	 * read passwords in file with lines
	 * user:password_hash
	 * @param string $filepath path to passwords file
	 * @throws \Exception if failed to open or invalid file
	 */
	public function __construct($filepath) {
		// open file
		$fh = fopen($filepath, 'r');
		if (!$fh) {
			throw new \Exception('failed to open file: ' . $filepath);
		}

		// read file lines
		$i = 1;
		while (!feof($fh)) {
			$line = preg_split('/:/', trim(fgets($fh)));
			if (count($line) === 1) {
				if (empty($line[0])) {
					$i++;
					continue; // skip empty line
				}
				$this->passwds[$line[0]] = ''; // empty password
			} elseif (count($line) === 2) {
				$this->passwds[$line[0]] = $line[1];
			} else {
				throw new \Exception('invalid password file: ' . realpath($filepath) . '. line ' . $i . ' must have format: user:password_hash');
			}
			$i++;
		}

		// close file
		fclose($fh);
	}

	public static function check($user, $pw) {
		if (!preg_match(self::USER_PATTERN, $user)) {
			throw new \Exception('malformed user name: ' . $user);
		}if (!preg_match(self::PW_PATTERN, $pw)) {
			throw new \Exception('malformed password: space of colon not allowed.');
		}
	}

	public function add($user, $pw) {
		if (array_key_exists($user, $this->passwds)) {
			throw new \Exception('failed to add. already existing user name: ' . $user);
		}
		self::check($user, $pw);
		$this->passwds[$user] = password_hash($pw, PASSWORD_DEFAULT);
	}

	public function change($user, $pw) {
		if (!array_key_exists($user, $this->passwds)) {
			throw new \Exception('failed to change. not existing user name: ' . $user);
		}
		self::check($user, $pw);
		$this->passwds[$user] = password_hash($pw, PASSWORD_DEFAULT);
	}

	public function delete($user) {
		if (!$this->has($user)) {
			throw new \Exception('failed to delete. unknown user: ' . $user);
		}
		unset($this->passwds[$user]);
	}

	public function has($user) {
		return array_key_exists($user, $this->passwds);
	}

	public function verify($user, $pw) {
		if (!$this->has($user)) {
			throw new \Exception('failed to verify. unknown user: ' . $user);
		}
		return password_verify($pw, $this->passwds[$user]);
	}

	/**
	 * write table in passwd file
	 * @param string $filepath path to config file to be written
	 * @throws \Exception if failed to open file
	 */
	public function write($filepath) {
		$fh = fopen($filepath, 'w');
		if (!$fh) {
			throw new \Exception('failed to open file: ' . $filepath);
		}
		$users = array_keys($this->passwds);
		for ($i = 0; $i < count($users); $i++) {
			fputs($fh, $users[$i] . ':' . $this->passwds[$users[$i]] . PHP_EOL);
		}
		fclose($fh);
	}

	public static function parse($auth) {
		$user_pw = preg_split('/:/', $auth);
		if (count($user_pw) == 1) {
			$user_pw[1] = '';
		} elseif (count($user_pw) !== 2) {
			throw new \Exception('invalid user:passwd');
		}

		return $user_pw;
	}
}
?>