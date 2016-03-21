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


    define("CONFIG_DIR", "/etc/dvr/");
    define("LOG_DIR", "/var/log/dvr/");

    define("CONFIG_FILE", CONFIG_DIR . "dvr.conf");
    define("LOG_FILE", LOG_DIR . "dvr.log");

    if (!file_exists(CONFIG_DIR)) {
        $oldmask = umask(0);  // helpful when used in linux server  
        mkdir (CONFIG_DIR, 0744);
    }
    if (!file_exists(CONFIG_FILE) && !touch(CONFIG_FILE))
        trigger_error("Can not create " . CONFIG_FILE);

    //$user = $_SERVER["REMOTE_USER"];
    $user = "adrien";
    $device = $_GET["hostname"];
    $ip = $_GET["myip"];

    $routes = new Routes(CONFIG_FILE);
    $row = $routes->find($user, $device);

    if ($row === false)
        $routes->add($user, $device, $ip);
    else
        $routes->set_ip($row, $ip);

    $routes->write(CONFIG_FILE);
    ?>
</body>

</html>
