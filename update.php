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
    require('DVR/App.php');

    try {
        $dvr = new DRV\App();
        $dvr->updateTable();
    } catch (DRV\RCException $e) {
        DRV\App::returnCode($e->getReturnCode());
        DRV\App::log($e->getMessage());
    } catch (Exception $e) {
        http_response_code(500); // Internal Server Error
        DRV\App::returnCode("911");
        DRV\App::log("generic exception: ".$e->getMessage());
    } finally {
        DRV\App::closeLog();
    }
    ?>
</body>
</html>
