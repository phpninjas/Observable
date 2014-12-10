<?php

namespace Observable\Test;

use Observable\Notifier;

class ObservableTest extends \PHPUnit_Framework_TestCase {

    use Notifier;

    private $events;

    public function setUp(){
        $this->events= [];
    }

    public function testExpectEvent(){

        $stack = [];
        $this->addObserver(function($event)use(&$stack){
            $stack[] = $event;
        });

        $this->notifyObservers(1);

        $this->assertThat(count($stack), $this->equalTo(1));
        $this->assertThat($stack[0], $this->equalTo(1));


    }

    public function testRejectsInvalidObserver(){

        $this->setExpectedException("InvalidArgumentException");

        $stack = [];
        $this->addObserver(function($event, $someOtherThing)use(&$stack){
            $stack[] = $event;
        });

    }

    public function testAllowsClasses(){

        $this->addObserver([$this,'acceptEvent']);
        $this->notifyObservers(1);

        $this->assertThat($this->events[0], $this->equalTo(1));

    }

    public function testAllowsInvokableClasses(){

        $this->addObserver($this);
        $this->notifyObservers(1);

        $this->assertThat($this->events[0], $this->equalTo(1));

    }

    public function testTypeContract(){

        $events = &$this->events;
        $this->addObserver(function(\FixtureEvent $event)use(&$events){
            $events[] = $event;
        });
        $this->notifyObservers(1);
        $this->notifyObservers(new \FixtureEvent());

        $this->assertThat(count($this->events), $this->equalTo(1));
        $this->assertThat($this->events[0], $this->isInstanceOf('FixtureEvent'));

    }

    /**
     * If this fails, you will get a stack overflow.
     * (or a hanging test if no xdebug)
     */
    public function testEventQueueing(){

        $self =& $this;
        $this->addObserver(function(\FixtureEvent $e)use($self){
            // chained events.
            $self->notifyObservers(1);
            $self->acceptEvent($e);
        });
        // accept any event!
        $this->addObserver(function($num)use($self){
            $this->acceptEvent($num);
        });

        $this->notifyObservers(new \FixtureEvent());

        // should see both events
        $this->assertThat(count($this->events), $this->equalTo(3));
        // should see event $e, before event 1 in both handlers, and only 1 in 1 handler.
        $this->assertThat($this->events[0], $this->isInstanceOf('FixtureEvent'));
        $this->assertThat($this->events[1], $this->isInstanceOf('FixtureEvent'));
        $this->assertThat($this->events[2], $this->equalTo(1));

    }

    public function testObserverRemoval(){

        $events =& $this->events;
        $func = function($e)use(&$events){
            $events[] = $e;
        };
        $this->addObserver($func);
        $this->removeObserver($func);

        $this->notifyObservers(1);
        $this->assertThat(count($this->events), $this->equalTo(0));

    }


    public function verifyEvent($event){
        $this->assertThat(count($this->events), $this->equalTo(1));
        $this->assertThat($this->events[0], $this->equalTo($event));
    }

    public function acceptEvent($event){
        $this->events[] = $event;
    }

    public function __invoke($event){
        $this->acceptEvent($event);
    }


}