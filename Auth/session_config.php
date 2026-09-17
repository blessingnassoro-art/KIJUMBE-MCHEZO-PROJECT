<?php
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0,        
        'path'     => '/',
        'domain'   => '',       
        'secure'   => $isHttps, 
        'httponly' => true,     
        'samesite' => 'Lax',    
    ]);

    ini_set('session.use_strict_mode', 1);

    session_start();
}