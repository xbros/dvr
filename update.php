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

    require('script.php');

    ?>

</body>

</html>
