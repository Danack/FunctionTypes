<?php

declare(strict_types = 1);


//PHP allows contravariance (aka type widening) for parameter types to obey the LSP principle. A subclass may use a 'wider' aka less specific type in place of the inherited type for a parameter.

//PHP allows covariance (aka type narrowing) for return types to obey the LSP principle. A subclass may use a 'narrower' aka more specific type in place of the inherited type for a function return.

typedef test_callable = callable(int|string $a):int|string;
typedef narrower_params_callable = callable(int $a):int|string;
typedef wider_returns_callable = callable(int|string $a):int|string|float;
typedef narrower_params_wider_returns_callable = callable(int $a):int|string|float;

class A
{
    function foo(test_callable $fn)
    {
        $fn(5);
        $fn('foo');
        $this->return_pass_through($fn);
    }

    function return_pass_through(test_callable $fn): test_callble
    {
        return $fn;
    }
}

class NarrowerParameters extends A
{
    function foo(narrower_params_callable $fn)
    {
        $fn(5);
        // This will not work as although $fn is compatible as
        // a parameter type, it is not compatible as a return type.
        $this->return_pass_through($fn);
    }
}


class WiderReturns extends A
{
    function foo(wider_returns_callable $fn) {
        $fn(5);
    }
}

class NarrowerParamsWiderReturns extends A
{
    function foo(narrower_params_wider_returns_callable $fn) {
        $fn(5);
    }
}


$test_callable_fn = fn(int $a):int|string {
    return 5;
}
$narrower_params_callable_fn = fn(int|string $a):int|string {
    return 5;
};
$wider_returns_callable_fn = fn(int $a):int {
    return 5;
};
$narrower_params_wider_returns_callable_fn = fn(int|string $a):int {
    return 5;
};


$classes = [
    A::class,
    NarrowerParameters::class,
    WiderReturns::class,
    NarrowerParamsWiderReturns::class
];

$callables = [
    $test_callble_fn,
    $narrower_params_callable_fn,
    $wider_returns_callable_fn,
    $narrower_params_wider_returns_callable_fn
];


foreach ($callables as $callable) {
    foreach ($classes as $class) {
        $object = new $class;
        $object->foo($callable);
    }
}

echo "Ok";