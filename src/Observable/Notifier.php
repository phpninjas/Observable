<?php

namespace Observable;

trait Notifier
{

    /**
     * @var \SplObjectStorage
     */
    private $observers;

    /**
     * @var bool
     */
    private $locked = false;

    /**
     * @var \SplQueue
     */
    private $eventQueue;

    /**
     * @param \Closure|array|callable $observer
     */
    public function addObserver(callable $observer)
    {
        $functor = $this->reflect($observer);
        if ($functor->getNumberOfParameters() != 1) throw new \InvalidArgumentException("Observers must ONLY accept 1 parameter.");
        $this->getObservers()->attach(is_array($observer)?new InvokableArray($observer):$observer, $functor);
    }

    /**
     * @param \Closure|array|callable $callback
     */
    public function removeObserver(callable $callback)
    {
        $this->getObservers()->detach($callback);
    }

    /**
     * @param $event - any single object or data type
     */
    public function notifyObservers($event)
    {
        $this->queueEvent($event);
        if(!$this->locked()){
            $this->lock();
            while(!$this->getEventQueue()->isEmpty()){
                $event = $this->getEventQueue()->dequeue();
                foreach ($this->getObservers() as $observer) {
                    $reflector = $this->getObservers()[$observer];
                    foreach ($reflector->getParameters() as $p) {
                        $parameterType = $p->getClass();
                        if ($parameterType) {
                            $type = $p->getClass()->getName();
                            if ($event instanceof $type) {
                                call_user_func($observer, $event);
                            }
                        } else {
                            // no type
                            call_user_func($observer, $event);
                        }
                    }
                }
            }
            $this->unlock();
        }
    }

    /**
     * @param $callable
     * @return \ReflectionFunction|\ReflectionMethod
     * @throws \ReflectionException
     */
    private function reflect($callable)
    {
        if (is_array($callable)) {
            $reflector = new \ReflectionMethod($callable[0], $callable[1]);
        } elseif (is_string($callable)) {
            $reflector = new \ReflectionFunction($callable);
        } elseif ($callable instanceof \Closure) {
            $objReflector = new \ReflectionObject($callable);
            $reflector = $objReflector->getMethod('__invoke');
        } elseif (is_callable($callable) && is_object($callable)) {
            $objReflector = new \ReflectionClass($callable);
            $reflector = $objReflector->getMethod('__invoke');
        } else {
            throw new \ReflectionException("Unable to reflect on observer.");
        }
        return $reflector;
    }

    /**
     * @return \SplObjectStorage
     */
    private function getObservers()
    {
        if (!$this->observers)
            $this->observers = new \SplObjectStorage();
        return $this->observers;
    }

    /**
     * @param mixed $event
     */
    private function queueEvent($event){
        $this->getEventQueue()->enqueue($event);
    }

    /**
     * @return \SplQueue
     */
    private function getEventQueue(){
        if(!$this->eventQueue)
            $this->eventQueue = new \SplQueue();
        return $this->eventQueue;
    }

    private function lock(){
        $this->locked = true;
    }

    public function unlock(){
        $this->locked = false;
    }

    private function locked(){
        return $this->locked;
    }

}