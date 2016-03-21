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

    // include class Routes
    require('Routes.php');

    // Settings =============================================

    define("MAX_DEVICES", 20);
    define("CONFIG_DIR", "./");
    define("CONFIG_FILE", CONFIG_DIR . "dvr.conf");
    define("LOG_DIR", "/var/log/dvr/");
    define("LOG_FILE", LOG_DIR . "dvr.log");

    // create config file
    if (!file_exists(CONFIG_DIR)) {
        if (!mkdir(CONFIG_DIR))
            trigger_error("Can not create " . CONFIG_DIR, E_USER_ERROR);
        chmod(CONFIG_DIR, 0774);
    }
    if (!file_exists(CONFIG_FILE)) {
        if (!touch(CONFIG_FILE))
            trigger_error("Can not create " . CONFIG_FILE, E_USER_ERROR);
        chmod(CONFIG_FILE, 0774);
    }

    // Arguments =============================================

    //$user = $_SERVER["REMOTE_USER"];
    $user = "adrien";

    // check argument keys are allowed
    $allowed = array("hostname", "myip", "wildcard", "mx", "backmx", "offline", "system", "url");
    foreach(array_keys($_GET) as $key) {
        if (!in_array($key, $allowed)) {
            echo "abuse";
            return;
        }
    }

    // check device
    if (empty($_GET["hostname"]) ||
        !preg_match("/^[A-Za-z]{1}[a-zA-Z0-9_\\-\\.]{3,}\$/", $_GET["hostname"])) {
        echo "notfqdn";
        return;
    }

    // get ip
    if (!empty($_GET["myip"]))
        $ip = filter_var($_GET["myip"], FILTER_VALIDATE_IP);
    if ($ip == false && !empty($_SERVER['HTTP_CLIENT_IP']))
        $ip = filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP);
    if ($ip == false && !empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ip = filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP);
    if ($ip == false)
        $ip = $_SERVER['REMOTE_ADDR'];

    // Update =============================================

    // read config file
    $routes = new Routes(CONFIG_FILE);

    // find device
    $row = $routes->find($user, $device);

    // update routes
    if ($row === false) {
        // add device
        if ($routes->ndevices($user)>=MAX_DEVICES) {
            echo "numhost";
            return;
        }
        $routes->add($user, $device, $ip);
        echo "good " . $ip;
    } else {
        // change ip
        if ($routes->get_ip($row) == $ip) {
            echo "nochg " . $ip;
            return;
        }          
        $routes->set_ip($row, $ip);
        echo "good " . $ip;
    }
        
    // write config file
    $routes->write(CONFIG_FILE);
    ?>
</body>

</html>
