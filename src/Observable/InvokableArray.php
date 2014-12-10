<?php

namespace Observable;

class InvokableArray {
    /**
     * @var array
     */
    private $callable;

    public function __construct(array $callable){

        $this->callable = $callable;
    }

    /**
     * @param $event
     */
    public function __invoke($event){
        return call_user_func($this->callable, $event);
    }


}