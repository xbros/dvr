<?php

// Arguments =============================================

$user = "adrien";
if (!empty($_SERVER["REMOTE_USER"]))
    $user = $_SERVER["REMOTE_USER"];    

// check argument keys are allowed
$allowed = array("hostname", "myip", "wildcard", "mx", "backmx", "offline", "system", "url");
foreach(array_keys($_GET) as $key) {
    if (!in_array($key, $allowed)) {
        echo "abuse";
        return;
    }
}

// check device
if (empty($_GET["hostname"]) || !preg_match("/^[A-Za-z]{1}[a-zA-Z0-9_\\-\\.]{2,}\$/", $_GET["hostname"])) {
    echo "notfqdn";
    return;
}
$device = $_GET["hostname"];

// get ip
$ip = false;
if (!empty($_GET["myip"]))
    $ip = filter_var($_GET["myip"], FILTER_VALIDATE_IP);
if ($ip == false && !empty($_SERVER['HTTP_CLIENT_IP']))
    $ip = filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP);
if ($ip == false && !empty($_SERVER['HTTP_X_FORWARDED_FOR']))
    $ip = filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP);
if ($ip == false)
    $ip = $_SERVER['REMOTE_ADDR'];

// check offline
$offline = false;
if (!empty($_GET["offline"])) {
    if (!in_array($_GET["offline"], array("YES", "NOCHG"))) {
        echo "abuse";
        return;
    }
    if ($_GET["offline"] == "YES")
        $offline = true;
}

// Update =============================================

// read config file
$routes = new Routes(CONFIG_FILE);

// find device
$row = $routes->find($user, $device);

if ($offline) {
    // delete device
    if ($row === false) {
        echo "nochg";
        return;
    } else {
        $routes->delete($row);
        echo "good " . $device . " offline";
    }
} elseif ($row === false) {
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