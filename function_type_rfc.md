
# Callable types

The problem of defining callable signatures has been a requested feature for a while. This proposal introduces the definition of callable types, to separate the problem of definining function signatures from how they are used.


## Allow definition of a callable type

```
typedef logger = callable(string $message): void;
```

This can be used a parameter, return or property type.

```
function foo(logger $logger) {
    $logger("This was called");
}

```

Although they are not used in the definition of a callable type, the parameter names are required in the callable type definition, to allow callable types to be compatible with calling by named parameters. See note on 'syntax choice'.


To make the rest of the RFC clearer:

* callable type - the definition of the signature. This is analogous to an interface.
* callable - the function, closure, or class that implements the callable type.

## callable type + autoloading

When a callable type is used as a parameter, return or property type and that type is not already loaded, the autoloader is called. If the callable type is failed to be loaded, an error message would be shown. 

Note because it's not possible to tell the different between a class type and callable type, the error message for failure to load a symbol should be changed from: 

```
Fatal error: Uncaught Error: Class 'foo' not found in
```

to
```
Fatal error: Uncaught Error: Type 'foo' not found in
```

The full details of the changes needed for the autoloader are in a separate document.


## Using callable types directly

It is possible to use a callable type directly.

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

When calling 'bar' and the PHP engine reached the line, the autoloader would be first be called with the AUTOLOAD_TYPE and the name 'logger'. This would define the callable type 'logger'.


As a second step, the autoloader would be called with the type AUTOLOAD_FUNCTION and the name 'logger'. This would load the implmentation for the 'logger'.

The bindCallableToFunction does a signature check on the implementing function to make sure it is LSP compatible with the callable type.  



## Type checking

The type checking is done solely through the signature of the callable type. The function name is not required to be the same and functions do not need to declare what callable type they 'implement'.

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


The signature checking is done according to the following rules.

### Parameter types

For all callables, the parameters are checked with contravariance (aka type widening) for parameter types to obey the LSP principle. An callable may use 'wider' aka less specific type in place of the type for a parameter in the function defintion.


```
// Define a callable type
typdef foo = callable(int $value): void;

// Use that type 
function uses_foo(foo $fn) {...}

// This is fine.
function bar(int|string $value): {...}
uses_foo('bar');

// This is also fine.
$closure = function (int|string $value): {...}
uses_foo($closure);

// this is not fine
function baz(array $value): {...}
uses_foo('baz');

```


### Return type check for callables with defined return types

For callables that have a return type defined, PHP allows covariance (aka type narrowing) for return types to obey the LSP principle. A callable may use a 'narrower' aka more specific type in place of the type for a function return.


```
// Define a callable type
typdef foo = callable(): int|string;

// Use that type 
function uses_foo(foo $fn) {...}


// This is fine.
function bar(): int: {...}
uses_foo('bar');


// This is also fine.
$closure = function (): int {...}
uses_foo($closure);

// this is not fine
function baz(): array {...}
uses_foo('baz');

```


### Return type check for callables without defined return types.

For callables that do not have a return type defined, the function is dispatched as if it was wrapped in a function that takes the same parameters as the callable, and the return type of callable type where it is being used. 

```
// Define a callable type that must return int
typedef returns_int = callable(int $x): int

// Use that type 
function uses_returns_int(returns_int $fn) {...}


// This is allowed, but the type returned is checked
// against the int type.
$closureWithoutReturnType = (int $x) => 5;
uses_returns_int($closureWithoutReturnType);


// This will give a type error
$badClosure = (int $x) => "foo";
uses_returns_int($badClosure);
```

i.e. that code behaves as if it was wrapped by an intermediate function that has the same parameters as the callable (cf. variance), and the return type of the callable type.

Example where the callable has compatible parameters: 
```
function wraps_and_returns_int($callable) {
    return (int $x): int {
        $callable();
    };
}

$closureWithoutReturnType = (int $x) => 5;
uses_returns_int(wraps_and_returns_int($closureWithoutReturnType);

```


Example where the callable incompatible parameters:
```
// Define a callable type that must return int
typedef consumes_string_returns_int = callable(string $x): int

$badClosure = (array $x) => 5;

function wraps_and_returns_int($callable) {
    // Parameter type from callable
    // Return type from 'callable type'
    return (array $x): int {
        $callable();
    };
}

// This would give a type error, as a function that takes a
// parameter with type 'array' is not compatible with one that 
// takes 'int'.
uses_returns_int(wraps_and_returns_int($badClosure);
```



Note, the 'wrapping' function is there for explanation purposes. It would not appear in the callstack.


## Voting choices

Yes/No



## Notes

### Syntax choice

The syntax chosen is designed to be reusable for other potential features e.g. 


Enum types
```
typedef direction = enum('North', 'South', 'East', 'West');
```

Union types
```
typedef ExpectedExceptions = S3Exception|ImagickException|BadArgumentException;
```

Generic like definitions
```
typedef ArrayOfStrings =  array<string>;
```

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


If we required functions to declare what callable types they implement it would result in lots of 'hoisting' code.

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








