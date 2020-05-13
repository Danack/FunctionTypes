
# Callable types

The problem of defining callable signatures has been a requested feature for a while. This proposal introduces the definition of callable types, to separate the problem of defining function signatures from how they are used.

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
* callable - the function, closure, or class+method that implements the callable type.


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
};

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
$closure = function (int|string $value): {...};
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
$closure = function (): int {...};
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
$closureWithoutReturnType = fn(int $x) => 5;
uses_returns_int($closureWithoutReturnType);


// This will give a type error
$badClosure = fn(int $x) => "foo";
uses_returns_int($badClosure);
```

i.e. that code behaves as if it was wrapped by an intermediate function that has the same parameters as the callable (cf. variance), and the return type of the callable type.

Example where the callable has compatible parameters: 
```
function wraps_and_returns_int($callable) {
    return function(int $x): int {
        $callable($x);
    };
}

$closureWithoutReturnType = fn(int $x) => 5;
uses_returns_int(wraps_and_returns_int($closureWithoutReturnType);

```


Example where the callable has incompatible parameters:
```
// Define a callable type that must return int
typedef consumes_string_returns_int = callable(string $x): int

$badClosure = fn(array $x) => 5;

function wraps_and_returns_int($callable) {
    // Parameter type from callable
    // Return type from 'callable type'
    return function(array $x): int {
        $callable();
    };
}

// This would give a type error, as a function that takes a
// parameter with type 'array' is not compatible with one that 
// takes 'int'.
uses_returns_int(wraps_and_returns_int($badClosure);
```

Note, the 'wrapping' function is there for explanation purposes. It would not appear in the callstack.

The type check on the parameters uses the allowed parameters of the callable being used, not the parameters allowed of the callable type, to make the error message match the code.

```
// Define a callable type
typedef consumes_int = callable(int);

// Define a closure that accepts int or string
$widerClosure = function(int|string $value) {
    if (is_int($value) !== true && is_string($value) !== true) {
        echo "Error: function parameter is not int or string but " . gettype($value);
    }
};

// Use that type
function uses_consumes_int($value, consumes_int $fn) {
    $value = $fn($value);
}

uses_consumes_int([], $widerClosure);
// Expect error, "Cannot pass array int|string expected"
```

Doing it the other way, and using the allowed parameters of the callable type would lead to a confusing error message, as it wouldn't match the callable that is actually being used.

## Voting choices

Yes/No

## Future scope

### Inline type definition

Some people have suggested allowing inline declarations of callable types.

```
function foo(callable(int|string $a):int|string $b) {
    $b("This was called");
}

```

the position of this RFC is that types defined with names are the most valuable thing to provide, and as this RFC is big enough already, the inline type definitions should be done at a later date.  
 
### Variance in inheritance of methods with callable parameters

This RFC proposes no variance in the signature of parameter types when used in methods of classes.

It would theoretically be possible to allow variance by narrowing the parameters of the callable and widening of the callable return type:

```
typedef fn_1 = callable(int|string $a):int|string;
typedef fn_2 = callable(string $a):string|int|float;

class A
{
    function foo(fn_1 $b) {} 
}

class B extends A {
    function foo(fn_2 $b) {} 
}
```

The implementation of variance in the callable during method inheritance outside the scope of this RFC as it needs more careful consideration.


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
typedef logger = callable(string $message): void;


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






