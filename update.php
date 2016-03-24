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

    require('DVR/config.php');
    require('DVR/DeviceTable.php');
    require('DVR/App.php');
    use DVR\App;

    try {
        $dvr = new App();
        $dvr->updateTable();
    } catch (DVR\RCException $e) {
        App::returnCode($e->getReturnCode());
        App::log($e->getMessage());
    } catch (Exception $e) {
        http_response_code(500); // Internal Server Error
        App::returnCode("911");
        App::log("generic exception: ".$e->getMessage());
    } finally {
        App::closeLog();
    }
    ?>
</body>
</html>
