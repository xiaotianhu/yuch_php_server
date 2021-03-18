<?php 
declare(strict_types=1);
use module\process\ProcessMessager;
/*
 * first will be default channel.
 */

return[
    ProcessMessager::EMAIL_CHANNEL_SEND_MSG => [

    ],

    ProcessMessager::FLOMO_CHANNEL_SEND_MSG => [
        'flomo@flomo.com',
    ],
];

