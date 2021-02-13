<?php 
return 
[
    "host" => "0.0.0.0",
    "port" => "9999",

    //password for blackberry client to connect
    "password" => "berry",

    //username for email service
    "email_username" => getenv("EMAIL_USER"),
    "email_password" => getenv("EMAIL_PASS"),
    "pop3_host"      => "notice@huiwenliye.com",
    "pop3_port"      => "993",
    "smtp_host"      => "smtp.exmail.qq.com",
    "smtp_port"      => "465",
];
