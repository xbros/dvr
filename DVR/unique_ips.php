<?php
require(realpath(dirname(__FILE__))."/DeviceTable.php");
if ($argc !== 2) {
	trigger_error("argument needed: config filepath");
}
$table = new DVR\DeviceTable($argv[1]);
@print_r($table->getUniqueIps());
?>