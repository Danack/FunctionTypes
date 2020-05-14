<?php

declare(strict_types = 1);


// Define a callable type that must return int
typedef returns_int = callable(int $x): int

// Use that type
function uses_returns_int(returns_int $fn) {
    $value = $fn(5);
    if (is_int($value) !== true) {
        echo "Int not returned?";
        exit(-1);
    }
}


// This is allowed, but the type returned is checked
// against the int type.
$closureWithoutReturnType = fn(int $x) => 5;
uses_returns_int($closureWithoutReturnType);

echo "Ok";

// This will give a type error
$badClosure = fn(int $x) => "foo";
uses_returns_int($badClosure);
// Expect "Cannot use closure as incompatible