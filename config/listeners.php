<?php 
return 
[
    module\event\NewClientEvent::class    => module\listener\NewClientListener::class,
    module\event\HeartbeatEvent::class    => module\listener\HeartbeatListener::class,
    module\event\NewBbMessageEvent::class => module\listener\NewBbMessageListener::class,
];
