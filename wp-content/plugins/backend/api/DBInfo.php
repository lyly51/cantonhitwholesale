<?php

namespace CTHWP\Api;


class DBInfo
{
    public static $dbConnectInfos = array(
        'wordpress-db' => [
            'driver' => 'mysql',
            'host' => DB_HOST,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASSWORD,
            'charset' => DB_CHARSET,
            'collation' => "utf8_general_ci",
            'prefix' => 'wp_',
        ]
    );
}
