<?php

declare(strict_types = 1);


// TODO - check this is correct

typedef test_callble = callable(int $a):int|string;
typedef wider_params_callble = callable(int|string $a):int|string;
typedef narrower_returns_callble = callable(int $a):int;
typedef wider_params_narrower_returns_callble = callable(int|string $a):int;

class A {
    function foo(): test_callble {
        return function (int $a): int|string {
            return 5;
        };
    }
}

class WiderParameters extends A {
    function foo(): wider_params_callble {
        return function(int|string $a):int|string {
            return 5;
        };
    }
}


class NarrowerReturns extends A
{
    function foo(): narrower_returns_callble {
        return function (int $a):int {
            return 5;
        };
    }
}

class WiderParamsNarrowerReturns extends A
{
    function foo(): wider_params_narrower_returns_callble {
        return function(int|string $a):int {
            return 5;
        };

    }
}


$test_callble_fn = fn(int $a):int|string {
    return 5;
}
$wider_params_callble_fn = fn(int|string $a):int|string {
    return 5;
};
$narrower_returns_callble_fn =
$wider_params_narrower_returns_callble_fn = fn(int|string $a):int {
    return 5;
};


$classes = [
    A::class,
    WiderParameters::class,
    NarrowerReturns::class,
    WiderParamsNarrowerReturns::class
];


$callables = [
    $test_callble_fn,
    $wider_params_callble_fn,
    $narrower_returns_callble_fn,
    $wider_params_narrower_returns_callble_fn
];


foreach ($callables as $callable) {
    foreach ($classes as $class) {
        $object = new $class;
        $object->foo($callable);
    }
}

echo "Ok";