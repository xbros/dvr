<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
</head>

<body>
    <?php
    // // display errors
    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);

    require('include/config.php');
    require('include/DVR.php');

    try {
        $dvr = new DVR();
        $dvr->printDevices();
    } catch (DVRException $e) {
        DVR::returnCode($e->getReturnCode());
        DVR::log($e->getMessage());
    } catch (Exception $e) {
        http_response_code(500); // Internal Server Error
        DVR::returnCode("911");
        DVR::log("generic exception: ".$e->getMessage());
    } finally {
        DVR::closeLog();
    }
    ?>
</body>
</html>
