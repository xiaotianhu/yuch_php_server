<?php 
return 
[
    "host" => "0.0.0.0",
    "port" => "9999",

    //password for blackberry client to connect
    "password" => "berry",

    //username for email service
    "email_username"   => getenv("EMAIL_USER"),
    "email_password"   => getenv("EMAIL_PASS"),
    "pop3_host"        => "pop.exmail.qq.com",
    "pop3_port"        => "995",
    "smtp_host"        => "smtp.exmail.qq.com",
    "smtp_port"        => "465",
    "email_encrypt"    => "tls",
    "email_max_unread" => "20",
    "check_interval"   => "100",//pop3 check interval in seconds
];
