<?php

return array(
    // ----------------------------  CONFIG ENV  -----------------------------//
    'env' => array(
        'language' => 'zh_cn',
        'theme' => 'default'
    ),
    // ----------------------------  CONFIG DB  ----------------------------- //
    'db' => array(
        'host' => '127.0.0.1',
        'port' => '3306',
        'dbname' => 'open42',
        'username' => 'root',
        'password' => '110119',
        'tableprefix' => 'shkj_',
        'charset' => 'utf8'
    ),
// -------------------------  CONFIG SECURITY  -------------------------- //
    'security' => array(
        'authkey' => '8ddec84uKsSrdZqY',
    ),
// --------------------------  CONFIG COOKIE  --------------------------- //
    'cookie' => array(
        'cookiepre' => '8El8_',
        'cookiedomain' => '',
        'cookiepath' => '/',
    )
);
