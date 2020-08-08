
# Callable types

The problem of defining callable signatures has been a requested feature for a while. This proposal introduces the definition of callable types, to separate the problem of defining function signatures from how they are used.

## Proposal

### Allow definition of a callable type

```
type logger = callable(string $message): void;
```

This can be used a parameter, return or property type.

```
function foo(logger $logger) {
    $logger("This was called");
}

```

Although they are not used in the definition of a callable type, the parameter names are required in the callable type definition, to allow callable types to be compatible with calling by named parameters. See note on 'syntax choice'.

To make the rest of the RFC clearer:

* 'callable type' - the definition of the signature. This is analogous to an interface.
* 'callable' - the function, closure, or class+method that implements the callable type.


### Inline callable types

Additionally, this RFC proposes inline declarations of callable types.

```
function foo(callable(int $carry, int $item):int $reducer) {

    $values = [1, 2, 3];
    $carry = 0;

    foreach ($values as $value) {
        $carry = $reducer($carry, $value);
    }
}

```

## Type checking

The type checking is done solely through the signature of the callable type. The function name is not required to be the same and functions do not need to declare what callable type they 'implement'.

```
type logger = callable(string $message): void;

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
type foo = callable(int $value): void;

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
type consumes_string_returns_int = callable(string $x): int

$returnStringClosure = fn(string $x) => 5;

function wraps_and_returns_int($callable) {
    // Parameter type from callable
    // Return type from 'callable type'
    return function(string $x): int {
        return $callable($x);
    };
}

uses_returns_int(wraps_and_returns_int($badClosure);
```

Note, the 'wrapping' function is there as a userland equivalent explanation. It would not appear in the callstack for the internal implementation of callable types.

The purpose of the wrapping function is to make the return type check happen in the correct place, without requiring programmers to manually wrap all short closures or other functions that lack return types.


#### Example focusing on return type checking

```
// Define a callable type that must return int
type returns_int = callable(): int

$returnStringClosure = fn() => "foo";

function wraps_and_returns_int($callable) {
    // Parameter types from 'callable', or no parameters in this case
    // Return type from 'callable type'
    return function(): int {
        // This would give a type error when the code is run
        // as the callable is returning a string, not an int.
        return $callable();
    };
}

uses_returns_int(wraps_and_returns_int($returnStringClosure);
```

Note, the 'wrapping' function is there for explanation purposes. It would not appear in the callstack.


#### Example showing parameter type checking

The type check on the parameters uses the allowed parameters of the callable being used, not the parameters allowed of the callable type, to make the error message match the code.

```
// Define a callable type
type consumes_int = callable(int $x): int;

// Define a closure that accepts int or string
$widerClosure = function(int|string $value): int {
    // 
};

// Use that type
function uses_consumes_int($value, consumes_int $fn) {
    $value = $fn($value);
}

function wraps_consumes_int_and_returns_int($callable) {
    // Parameter types from 'callable'
    // Return type from 'callable type'
    return function(int|string $value): int {
        return $callable($value);
    };
}

// This would work
uses_consumes_int(5, wraps_consumes_int_and_returns_int($widerClosure));

// This would give a type error when the code is run
// an array is not an acceptable parameter.
uses_consumes_int([], wraps_consumes_int_and_returns_int($widerClosure));

```

Doing it the other way, and using the allowed parameter types of the callable type would lead to a confusing error message, as it wouldn't match the callable that is actually being used.


## Voting choices

Yes/No

## Future scope

### Variance in inheritance of methods with callable parameters

This RFC proposes no variance in the signature of parameter types when used in methods of classes.

It would theoretically be possible to allow variance by narrowing the parameters of the callable and widening of the callable return type:

```
type fn_1 = callable(int|string $a):int|string;
type fn_2 = callable(string $a):string|int|float;

class A
{
    function foo(fn_1 $b) {} 
}

class B extends A {
    function foo(fn_2 $b) {} 
}
```

The implementation of variance in the callable during method inheritance outside the scope of this RFC as it needs more careful consideration of the exact details of how it should work.

### Dropping parameter name

A suggestion was made of being able to drop the parameter name in the callable definition.

```
// named type
type logger = callable(string): void;


// Or inline type
function foo(callable(string) $logger) 

```

The position of this RFC is that dropping the parameter name is out of scope for this RFC.

It would need to be considered fully under a separate RFC that thinks through where dropping the parameter name was acceptable (e.g. interface, abstract methods?) rather than adding a special case for just callable types.

Additionally, allowing the parameter name to be dropped now, might make a 'named parameters' RFC be much harder to implement later, which this RFC aims to avoid.

## Notes

### Syntax choice

The syntax chosen is designed to be reusable for other potential features e.g. 


#### Enum types
```
type direction = enum('North', 'South', 'East', 'West');
```

#### Union types
```
type ExpectedExceptions = S3Exception|ImagickException|BadArgumentException;
```

#### Generic definitions
```
type ArrayOfStrings =  array<string>;
```

### Why both inline and defined types

Both of them allow the same thing, of defining the signature of a callable type to for a parameter, return or property type, each of those will be more useful in specific situations.

#### Named type

These are more useful when a callable type is defined in a library and then that definition is used either in another library or in the main application code. By giving the callable type a name, you can search for usage of it across a codebase easily. Additionally named types can support nesting of callables.

```
// Define a callable that returns an int
type foo = callable(): int;

// Define a callable that returns a callable that returns int
type bar = callable(): foo;

```

#### Inline type

These are more useful when you have a callable type that is used more 'locally' and isn't a concept that is passed around different layers of an application. 

An example could be in the eventloop of Amphp.

```
function repeat(int $intervalMs, callable(Watcher $watcher): ?\Generator $callback)
```


## No implements

If we required functions to declare what callable types they implement it would result in lots of 'hoisting' code.

e.g. 

```

// I have this definition:
type logger = callable(string $message): void;


// And this function:

function echologger(string $message) {
  echo $message;
}

// If I need to declare the implements it would need to look something like
function decoratedEchologger implements logger(string $message) {
  return echologger($message);
}

```

Which is too verbose.


## Fin


### Notes

Based on work by Nikita Nefedov and Márcio Almada

Previous RFC for callable types with inline definitions only:
https://wiki.php.net/rfc/callable-types

Pull-request
https://github.com/php/php-src/pull/1633
