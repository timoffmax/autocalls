<?php
require "autocalls_class.php";
require "db_class.php";

$path_parts = pathinfo($_SERVER['SCRIPT_FILENAME']); // определяем директорию скрипта
chdir($path_parts['dirname']);

Autocalls::checkWorkTime();
if (Autocalls::checkWorkTime()) {
    Autocalls::generateCall();
    Autocalls::sendDataWithApi();
} else {
    file_put_contents('/var/log/asterisk/autocalls.log', '['.date('Y-m-d H:i:m')."] Not Work Time!\n", FILE_APPEND);
}
