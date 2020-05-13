# PHP RFC: Function Autoloading 

  * Version: 0.5
  * Date: 2020-05-09
  * Author: Danack
  * Status: Draft
  * First Published at: 

## Introduction 

This RFC proposes a new unified autoloading mechanism to allow autoloading of class types (which includes classes, interfaces and traits), callable types, and functions. Currently only class type autoloading is possible in PHP

## Example

This example shows a separate autoloader being registered for classes, 

```php

<?php

use function PHP\autoload_register;
use const PHP\AUTOLOAD_TYPE;
use const PHP\AUTOLOAD_FUNCTION;


function classTypeAutoloader($name, $type)
{
    if ($type !== AUTOLOAD_TYPE) {
        return;
    }
    if ($name !== 'foo') {
        return;
    }

    // Being able to define classes inside a function
    // already exists in PHP and is convenient for an example 
    class foo
    {
        public function __construct()
        {
            echo "class was created";
        } 
    }
}

function functionAutoloader($name, $type)
{
    if ($type !== AUTOLOAD_FUNCTION) {
        return;
    }
    if ($name !== 'foo') {
        return;
    }
    function foo() {
        echo "foo was called";
    }
}

function callableTypeAutoloader($name, $type)
{
    if ($type !== AUTOLOAD_TYPE) {
        return;
    }
    if ($name !== 'logger') {
        return;
    }

    typedef logger = callable(string $message): void;
}

autoload_register('classTypeAutoloader', AUTOLOAD_TYPE);
autoload_register('callableTypeAutoloader', AUTOLOAD_TYPE);
autoload_register('functionAutoloader', AUTOLOAD_FUNCTION);

// As is currently possible, trigger class autoload 
new Foo();
//output: class was created

// The triggers the capability to autoload a function 
foo();
// output: function was autoloaded and called


// Define a function that uses a callable type for a paramter
// This does not trigger an autoload event. The autoloading happens
// When the function is called.
function usesCallableType(logger $logger)
{
    $logger("usesCallableType was called");
}

$echoLogger = function (string $message)
{
    echo $message . "\n";
};

// When this function is dispatched, it is during the type  
// checking that the 'logger' callable type is autoloaded
usesCallableType($echoLogger);

```

## Implementation details

### Constants 

This proposal registers the following constants:

  * PHP\AUTOLOAD_TYPE => 1
  * PHP\AUTOLOAD_FUNCTION => 2


The `AUTOLOAD_TYPE` is used for registering autoloaders for all the different types, which will be classes and callable types.

The `AUTOLOAD_FUNCTION` is used for registering autoloading for functions.


### Userland Functions 

This proposal adds the following functions:

#### bool php\autoload_register(callable $callback, int $type, bool $prepend) 


#### bool php\autoload_unregister(callable $callback, int $type) 

This function behaves similar to the current //spl_autoload_unregister// function, and unregisters a callback that was previously registered. Note that if you registered the same callback for multiple types, this will unregister all of them unless the //$type// argument is specified.

#### array php\autoload_list(int $type) 

This function will return a list of all registered autoloaders for a specific type.

#### function_exists() 

A new optional boolean argument is added to `function_exists()` to match the behavior of `class_exists()` when it comes to autoloading functions.

TODO - an optional argument seems sub-optimal. Should we simply add a new function that does function_load() ?

### Behavior 

Registering autoloaders with the new API will allow callbacks to be fired on type and function missing errors.

#### Single Type Behavior 

By passing a single constant to the register function, the callback will only be called for types that match (the `$type` parameter is still set, but will never vary).

<file php single_type.php>
<?php
php\autoload_register(function($name, $type) {
    var_dump($name, $type);
    eval("function $name(){}");
    // We don't need a switch, since we only register for functions.
}, php\AUTOLOAD_FUNCTION);
foo(); // string(3) "foo" int(2)
new foo(); // FATAL_ERROR as no autoloader is registered
?>
</file>

#### Multiple Type Behavior 

By passing a bitwise-or'd constant to the register function, the callback will only be called for types that match).

<file php multiple_type.php>
<?php
php\autoload_register(function($name, $type) {
    var_dump($name, $type);
    switch ($type) {
       case php\AUTOLOAD_FUNCTION:
           eval("function $name(){}");
           break;
       case php\AUTOLOAD_CONSTANT:
           define($name, $name);
           break;
    }
}, php\AUTOLOAD_FUNCTION | php\AUTOLOAD_CONSTANT);
foo(); // string(3) "foo" int(2)
FOO; // string(3) "FOO" int(4)
new foo(); // FATAL_ERROR as no autoloader is registered
?>
</file>

#### Registering The Same Callback Multiple Times For Different Types 

<file php multiple_registration.php>
<?php
$callback = function($name, $type) {
    var_dump($name);
    if ($name === 'foo') {
        eval("function $name(){}");
    } else {
        define($name, $name);
    }
};
php\autoload_register($callback, php\AUTOLOAD_FUNCTION);
php\autoload_register($callback, php\AUTOLOAD_CONSTANT);
foo(); // string(3) "foo" int(2)
FOO; // string(3) "FOO", "FOO"
?>
</file>

### Userland Backwards Compatibility 

#### SPL 

This RFC proposes to strip the current //spl_autoload_register// functionality, and make //spl_autoload_*// simple proxies for registering core autoloaders. They will function exactly as they do now, but under the hood they will be using the new interface.

This means that calls to //spl_autoload_functions()// will include any autoloader (which indicates support for //php\AUTOLOAD_CLASS//) registered through //php\autoload_register()//. However, all autoloaders registered via //spl_autoload_register// will set the //pass_type// flag to //0//, meaning that only a single argument will be passed to the callback. This is for compatiblity.

#### __autoload() 

The legacy //__autoload()// function still works (only for classes) if no autoloader has been registered. If any autoloader is registered (class, function or constant), the legacy system will disable itself (this is how it works currently).

### C API Backwards Compatibility 

#### SPL 

The autoload related SPL globals have been removed, due to the implementation being centralized.

## Backward Incompatible Changes 

### Userland 

There should be no user-land BC changes.

### PECL 

#### EG(autoload_func) 

PECL extensions which rely on the //EG(autoload_func)// global variable will break (due to refactor).

A quick scan of LXR shows that only the [optimizer](http://lxr.php.net/xref/PECL/optimizer/optimize.c#4660) extension would change.

#### autoload_func_info 

PECL extensions which reply on the SPL type //autoload_func_info// will break (due to refactor).

A quick scan of LXR shows that no extensions use this.

#### SPL_G(autoload_functions) 

PECL extensions which rely on the SPL globals will break (due to refactor).

A quick scan of LXR shows that no extensions use this.

## Proposed PHP Version(s) 

PHP 7.1.x

## SAPIs Impacted 

None.

## Impact to Existing Extensions 

See Backward Incompatible Changes

## php.ini Defaults 

None.

## Open Issues 

None yet.


## Future Scope

A previous version of this RFC included support for autoloading constants and streams. These have been excluded from this RFC for the following reasons.

### Constant autoloading

Although it would be possible to add constant autoloading, the position of this RFC is that being unable to directly reference [functions by name](https://github.com/Danack/RfcCodex/blob/master/referencing_functions.md) is a more important problem to address.

If we added constant autoloading now, that would have a very high chance of limiting the choices surrounding being able to reference functions. Because of that, this RFC does not include constant autoloading.


### Stream autoloading

Stream autoloading is excluded from this RFC to reduce the size of the RFC. It would be possible to add it in a later version.


### Other types

Imagine when PHP supports enums, and we want to use those as parameter type:

```

enum Compass {
  North, South, East, West 
}

function foo(Compass $direction) { ... }

foo(Compass::East);

```

This should be possible to add to the type autoloading.



## Patches and Tests 

A patch will be created shortly

## References 

  * Importing namespaces: http://php.net/manual/en/language.namespaces.importing.php
  * SPL Autoloading: http://php.net/manual/en/language.oop5.autoload.php

## Rejected Features 

- None.

## Vote 


