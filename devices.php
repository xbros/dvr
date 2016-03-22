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

    require('settings.php');

    // Arguments =============================================

    $user = "adrien";
    if (!empty($_SERVER["REMOTE_USER"]))
        $user = $_SERVER["REMOTE_USER"];

    // Display =============================================

    // read config file
    $routes = new Routes(CONFIG_FILE);

    // get devices
    $devices = $routes->get_devices($user);

    // display devices
    for ($i=0; $i<count($devices["devices"]); $i++)
        echo $devices["devices"][$i] . " " . $devices["ips"][$i] . "\n";

    ?>
</body>

</html>
