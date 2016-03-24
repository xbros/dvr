<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
</head>

<body>
    <?php
    // display errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require('DVR/config.php');
    require('DVR/DeviceTable.php');
    require('DVR/DVR.php');

    try {
        $dvr = new \DRV\DVR();
        $dvr->updateTable();
    } catch (\DRV\RCException $e) {
        \DRV\DVR::returnCode($e->getReturnCode());
        \DRV\DVR::log($e->getMessage());
    } catch (Exception $e) {
        http_response_code(500); // Internal Server Error
        \DRV\DVR::returnCode("911");
        \DRV\DVR::log("generic exception: ".$e->getMessage());
    } finally {
        \DRV\DVR::closeLog();
    }
    ?>
</body>
</html>
