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
    define("CONFIG_DIR", "dvr/");
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

    // check device
    if (!isset($_GET["hostname"])) {
        echo "notfqdn";
        return;
    }
    $device = $_GET["hostname"];
    if (!preg_match("/^[A-Za-z]{1}[a-zA-Z0-9_\\-\\.]{3,}\$/", $device)) {
        echo "notfqdn";
        return;
    }

    // check ip
    if (!isset($_GET["myip"])) {
        echo "notfqip";
        return;
    }
    $ip = $_GET["myip"];
    if (!preg_match("/^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\$/", $ip)) {
        echo "notfqip";
        return;
    }

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
        echo "good" . $ip;
    } else {
        // change ip
        if ($routes->get_ip($row) == $ip) {
            echo "nochg " . $ip;
            return;
        }          
        $routes->set_ip($row, $ip);
        echo "good" . $ip;
    }
        
    // write config file
    $routes->write(CONFIG_FILE);
    ?>
</body>

</html>
