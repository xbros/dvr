<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
</head>

<body>
    <?php
    // display errors
    //ini_set('display_errors', 1);
    //ini_set('display_startup_errors', 1);
    //error_reporting(E_ALL);

    $passwords = array("adrien"=>"pass", "simon"=>"pass");
    $headers = getallheaders();
    var_dump($headers);
    var_dump($_SERVER);

    if (isset($_SERVER['PHP_AUTH_USER'])) {
        // mod_php
        $user = $_SERVER['PHP_AUTH_USER'];
        $pass = $_SERVER['PHP_AUTH_PW'];
    } if (isset($headers['Authorization'])) {
        // most other servers
        if (strpos(strtolower($headers['Authorization']), 'basic')===0)
            list($user, $pass) = explode(':',base64_decode(substr($headers['Authorization'], 6)));
    } else {
        // if (is_null($user) || !in_array($user, array_keys($passwords)) || ($pass !== $passwords[$user])) {
            header('WWW-Authenticate: Basic realm="Authentication Required"');
            header('HTTP/1.0 401 Unauthorized');
            echo "badauth";
            die();
        // }
    }

    var_dump($user);
    var_dump($pass);


    require('include/config.php');
    require('include/DVR.php');

    try {
        $dvr = new DVR();
        $dvr->updateTable();
    } catch (DVRException $e) {
        DVR::returnCode($e->getReturnCode());
        DVR::log($e->getMessage());
    } catch (Exception $e) {
        DVR::returnCode("911");
        DVR::log("generic exception: ".$e->getMessage());
    } finally {
        DVR::closeLog();
    }
    ?>
</body>
</html>
