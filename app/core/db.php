<?php
declare(strict_types=1);

function db(): mysqli
{
    static $link;

    if ($link instanceof mysqli) {
        return $link;
    }

    $host = 'mysql-8.2';
    $user = 'root';
    $pass = '';
    $name = 'constructor_tests';

    $link = mysqli_connect($host, $user, $pass, $name);

    if (!$link) {
        die('DB CONNECT ERROR: ' . mysqli_connect_error());
    }

    mysqli_set_charset($link, 'utf8mb4');

    return $link;
}
