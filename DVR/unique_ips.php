<?php
require(realpath(dirname(__FILE__))."/config.php");
require(realpath(dirname(__FILE__))."/DeviceTable.php");
$table = new DVR\DeviceTable(DVR\CONFIG_FILE);
print_r($table->uniqueIps());
?>