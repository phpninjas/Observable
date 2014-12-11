<?php

use Observable\Notifier;

class NotifierDispatcher {
    use Notifier;

    public function dispatch($event){
        $this->notifyObservers($event);
    }
}