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

    // include files
    require('include/settings.php');
    require('include/DVR.php');

    // get user
    if (!empty($_SERVER["REMOTE_USER"]))
        define("DVR_USER", $_SERVER["REMOTE_USER"]);
    else
        define("DVR_USER", "anonymous");

    // open log file and create if necessary
    $ok = createFile(DVR_LOG_PATH);
    define("DVR_LOG_HANDLE", fopen(DVR_LOG_PATH, "a"));
    if ($ok)
        logHeader();

    try {
        $dvr = new DVR();
        $dvr->updateTable();
    } catch (DVRException $e) {
        DVR::returnCode($e->getCode());
        DVR::log($e->getMessage());
    }

    fclose(DVR_LOG_HANDLE);
    ?>
</body>
</html>
