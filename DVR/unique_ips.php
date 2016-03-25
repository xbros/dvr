<?php
require(realpath(dirname(__FILE__))."/config.php");
require(realpath(dirname(__FILE__))."/DeviceTable.php");
$table = new DVR\DeviceTable(DVR\CONFIG_PATH);
@print_r($table->getUniqueIps());
?>