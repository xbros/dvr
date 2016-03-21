<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
</head>


<body>
    <?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require('Routes.php');

    define("DATA_FILE", "/etc/dvr.conf");
    define("LOG_FILE", "dvr.log");

    if (!file_exists(DATA_FILE) && !touch(DATA_FILE))
        error_log("Can not create " . DATA_FILE);

    //$user = $_SERVER["REMOTE_USER"];
    $user = "adrien";
    $device = $_GET["hostname"];
    $ip = $_GET["myip"];

    $routes = new Routes(DATA_FILE);
    $row = $routes->find($user, $device);

    if ($row === false)
        $routes->add($user, $device, $ip);
    else
        $routes->set_ip($row, $ip);

    $routes->write(DATA_FILE);
    ?>
</body>

</html>
