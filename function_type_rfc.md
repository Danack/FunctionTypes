<?php

# Function types

The problem of defining callable signatures has been a requested feature for a while. This proposal introduces the definition of function types, to separate the problem of definining function signatures from how they are used.


## Allow definition of a function type

```
typedef logger = callable(string $message): void;
```

This can be used a parameter, return or property type.


```
function foo(logger $logger) {
    $logger("This was called");
}

```

Although they are not used in the definition of a function type, the parameter names are required to allow function type to be compatible with calling by named parameters.

## Function type + autoloading

When a function type is used as a parameter, return or property type and that type is not already loaded, the autoloader is called. If the function type is failed to be loaded, an error message would be shown. 

Note because it's not possible to tell the different between a class type and function type, the error message for failure to load a symbol should be changed from: 

```
Fatal error: Uncaught Error: Class 'foo' not found in
```

to
```
Fatal error: Uncaught Error: Type 'foo' not found in
```

The full details of the changes needed for the autoloader are in a separate document.


## Type checking

The type checking is done solely through the signature of the method. The function name is not required to be the same and functions do not need to declare what function type they 'implement'.

```
typedef logger = callable(string $message): void;

function foo(logger $logger) {
    $logger("This was called");
}


function echologger(string $message) {
  echo $message;
}

$closurelogger = function (string $message) {
  echo $message;
}

class Staticlogger {
  static function log(string $message) {
    echo $message;
  } 
}

class Instancelogger {
  function log(string $message) {
    echo $message;
  } 
}

// These are all fine.
foo('echologger');
foo($closurelogger);
foo('Staticlogger::log');
foo([new Instancelogger, 'log']);

```

The rationalisation for this is that trying attach 'implements' information to a function or closure would be quite verbose, and make a very difficult developer experience. See 'no implements' note below.

## Using function types directly

It is possible to use a function type directly.

```
// App.php

namespace Foo;

use function logger;

require "Bootstrap.php";

function bar($value) {
  logger('value was: ' . $logger)
}

bar('Hello world');

```


```
// Bootstrap.php

function echologger(string $message) {
  echo $message;
}

function simpleFunctionLoader($name, $type) {

    if ($type === AUTOLOAD_TYPE) {
        if ($name === 'logger') {
             typedef logger = callable(string $message): void;
        }
    }

    if ($type === AUTOLOAD_FUNCTION) {
        if ($name === 'logger') {
             bindCallableToFunction('logger', 'echologger');
        }
    }
}

autoload_register(simpleFunctionLoader, AUTOLOAD_FUNCTION);

```

When calling 'bar' and the PHP engine reached the line, the autoloader would be first be called with the AUTOLOAD_TYPE and the name 'logger'. This would define the function type 'logger'.


As a second step, the autoloader would be called with the type AUTOLOAD_FUNCTION and the name 'logger'. This would define the implmentation for the 'logger'.

The bindCallableToFunction does a signature check on the implementing function to make sure it is LSP compatible with the function type.  


## Voting choices

Yes/No



## Notes

### 'property vs method'

Fixing the problem of properties and methods being confusable, i.e. requiring ()'s for this:

```
class Foo {
    function bar() {
      ($this->logger)("Bar was called.")
    }
}
```

is ugly. But fixing that is outside the scope of this RFC, unless someone can say a clearly genius solution.


## No implements


If we required functions to declare what function types they implement it would result in lots of 'hoisting' code.

e.g. 

```

// I have this definition:
typedef logger = callable(string ): void;


// And this function:

function echologger(string $message) {
  echo $message;
}

// If I need to declar the implements it would need to look something like
function decoratedEchologger implements logger(string $message) {
  return echologger($message);
}

```

Which is too verbose.


## Fin








