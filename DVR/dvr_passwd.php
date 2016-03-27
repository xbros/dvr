<?php

namespace DVR;
require realpath(dirname(__FILE__)) . '/config.php';
require realpath(dirname(__FILE__)) . '/utils.php';
require realpath(dirname(__FILE__)) . '/PasswdTable.php';

const USAGE = 'usage: dvr passwd <command> --auth=<user>:<pw>' . PHP_EOL
	. 'commands: -a   add' . PHP_EOL
	. '          -c   change' . PHP_EOL
	. '          -v   verify' . PHP_EOL
	. '          -d   delete' . PHP_EOL;

try {
	$opts = getopt('acvd', array('auth:'));

	if ($opts === false || !isset($opts['auth']) || is_array($opts['auth']) || count($opts) !== 2) {
		echo USAGE;
		exit(1);
	}

	$passwds = new PasswdTable(PASSWD_PATH);

	list($user, $pw) = PasswdTable::parse($opts['auth']);

	if (isset($opts['a'])) {
		// add password
		$passwds->add($user, $pw);
		$passwds->write(PASSWD_PATH);
		rclog('added ' . $user);
	} elseif (isset($opts['c'])) {
		// add password
		$passwds->change($user, $pw);
		$passwds->write(PASSWD_PATH);
		rclog('changed ' . $user);
	} elseif (isset($opts['v'])) {
		// verify password
		$ok = $passwds->verify($user, $pw);
		rclog('verified ' . $user . ': ' . ($ok ? 'TRUE' : 'FALSE'));
	} elseif (isset($opts['d'])) {
		// delete password
		$ok = $passwds->delete($user);
		$passwds->write(PASSWD_PATH);
		rclog('deleted ' . $user);
	}
} catch (\Exception $e) {
	rclog('generic exception: ' . $e->getMessage());
	exit(1);
}

?>