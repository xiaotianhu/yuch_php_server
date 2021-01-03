<?php 
declare(strict_types=1);
namespace module\listener;

abstract class AbstractListener {
    abstract public function handle($event):void;
}
