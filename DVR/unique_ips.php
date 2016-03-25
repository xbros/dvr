<?php
// get ip table file path
if ($argc >= 2) {
	// from command line
	$filepath = $argv[1];
} else {
	// from config.php
	require(realpath(dirname(__FILE__))."/config.php");
	$filepath = DVR\CONFIG_PATH;
}
require(realpath(dirname(__FILE__))."/DeviceTable.php");
try {
	// read file
	$table = new DVR\DeviceTable($argv[1]);
	// get unique ips
	$ips = $table->getUniqueIps();
	// print
	for ($i=0; $i<count($ips); $i++) {
		echo $ips[$i].PHP_EOL;
	}
} catch (\Exception $e) {
	trigger_error($e->getMessage());
	exit(1);
}
?>