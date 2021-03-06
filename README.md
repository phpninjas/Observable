I got an event!
===============

Most modern php frameworks are migrating toward event and message based paradigms.
This library goes some way to solving the problem of event object contracts between
producers and subscribers.

Installation
============

Get Composer
```bash
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```
composer.json
```javascript
{
  require: {
    "phpninjas/observable": "dev-master"
  }
}
```

Example
=======

Use the notifier in your class.

```php
use Observable\Notifier;

class MyClass {
  use Notifier;
  
  public function doSomething(){
    $this->notifyObservers("did something");
  }
  
  public function doSomeEvent(){
    $this->notifyObservers(new MyEvent(1,2));
  }
}

$newClass = new MyClass();
$newClass->addObserver(function($expect){
  echo "got $expect";
});
$newClass->addObserver(function(MyObject $o){
  echo "got an object this time";
});

// runtime
$newClass->doSomething();
$newClass->doSomeEvent();

```


Caveats
=======

The notifier doesn't accept more than 1 argument to be notified of.
This forces people to encapsulate their messages better, i.e. if you want
to pass more than 1 piece of data encapsulate it in another object

```php
class MyEvent {
  public function __construct($thing1, $things2){
    $this->thing1 = $thing1;
    $this->thing2 = $thing2;
  }
}

$newClass = new MyClass();
$newClass->addObserver(function(MyEvent $e){
  // i will ONLY get MyEvent objects, everything else will be ignored for me! YAY!
});

```

Bear in mind non-typesafe observers will receive EVERYTHING, 
If you add an observer with no typed parameter definition (i.e. the argument is not of a class Type) it will get
ALL event messages.

Dragons
=======

Recursive events are bad! Don't do it.
i.e. don't have an observer send the same event it just received to the same producer

```php
// DO NOT DO THIS!
$observable = new MyClass();
$observable->addObserver(function(\MyEvent)use($observable){
  // this will recurse infinitely (or until stack overflow).
  $observable->doSomeEvent();
});

$observable->doSomeEvent();

```


Performance
===========

The performance is roughly 50% faster than Symfony Dispatcher.

In a 2million event based test, Symfony event dispatcher took 37 seconds, while Notifier took 22 seconds (OSX 10.9.5, 2.3Ghz Intel I7, 16GB mem PHP 5.4.30).