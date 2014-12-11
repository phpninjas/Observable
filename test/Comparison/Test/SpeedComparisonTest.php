<?php

namespace Comparison\Test;

use Symfony\Component\EventDispatcher\EventDispatcher;

class SpeedComparisonTest extends \PHPUnit_Framework_TestCase {

    public function testSymfonyThroughPut(){

        $start = microtime(true);

        $stack = [];
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener("my.event", function($e)use(&$stack){
            $stack[] = $e;
        });

        $i=0;
        while($i++ < 2000000){
            $eventDispatcher->dispatch("my.event");
        }

        $end = microtime(true);

        echo "Symfony Dispatcher: ".round($end-$start,8)." seconds\n";

    }

    public function testObservableThroughPut(){

        $start = microtime(true);

        $stack = [];
        $dispatcher = new \NotifierDispatcher();
        $dispatcher->addObserver(function($e)use(&$stack){
            $stack[] = $e;
        });


        $i=0;
        while($i++ < 2000000){
            $dispatcher->dispatch("my.event");
        }

        $end = microtime(true);

        echo "Notifier: ".round($end-$start,8)." second\n";

    }

}