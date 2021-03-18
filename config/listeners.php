<?php 

return 
[
    module\event\NewClientEvent::class              => module\listener\NewClientListener::class,
    module\event\HeartbeatEvent::class              => module\listener\HeartbeatListener::class,
    module\event\NewBbMessageEvent::class           => module\listener\NewBbMessageListener::class,
    module\event\SendToBbEvent::class               => module\listener\SendToBbListener::class,
    module\event\DispatcherMessageQueueEvent::class => module\listener\DispatcherMessageQueueListener::class,
];
