<?php

namespace Observable;

trait Notifier
{

    /**
     * @var array
     */
    private $observers = [];

    /**
     * @var bool
     */
    private $locked = false;

    /**
     * @var array
     */
    private $eventQueue = [];

    /**
     * @param \Closure|array|callable $observer
     */
    public function addObserver(callable $observer)
    {
        $this->observers[] = [is_array($observer)?new InvokableArray($observer):$observer, $this->determineType($observer)];
    }

    /**
     * @param \Closure|array|callable $observer
     */
    public function removeObserver(callable $observer)
    {
        if(false !== ($idx = array_search([$observer, $this->determineType($observer)],$this->observers))){
            unset($this->observers[$idx]);
        }
    }

    /**
     * @param $event - any single object or data type
     */
    public function notifyObservers($event)
    {
        $this->queueEvent($event);
        if(!$this->locked()){
            $this->lock();
            while($event = $this->dequeueEvent()){
                $observers = $this->observers;
                foreach ($observers as $observer) {
                    $type = $observer[1];
                    if ($type) {
                        if ($event instanceof $type) {
                            call_user_func($observer[0], $event);
                        }
                    } else {
                        // no type
                        call_user_func($observer[0], $event);
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
     * Provide a lock around the notifier loop
     */
    private function lock(){
        $this->locked = true;
    }

    /**
     * Unlock the notifier loop
     */
    public function unlock(){
        $this->locked = false;
    }

    /**
     *
     * @return bool
     */
    private function locked(){
        return $this->locked;
    }

    /**
     * @param $event
     */
    private function queueEvent($event){
        $this->eventQueue[] = $event;
    }

    /**
     * @return mixed
     */
    private function dequeueEvent(){
        return array_shift($this->eventQueue);
    }

    /**
     * @param callable $callable
     * @return null|string
     * @throws \InvalidArgumentException
     */
    private function determineType(callable $callable){
        $reflector = $this->reflect($callable);
        if ($reflector->getNumberOfParameters() != 1) throw new \InvalidArgumentException("Observers must ONLY accept 1 parameter.");
        $type = $reflector->getParameters()[0]->getClass();
        return $type?$type->getName():null;
    }

}