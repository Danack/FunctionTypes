<?php

declare(strict_types = 1);


// Define a callable type
typedef returns_int = callable(): int|string;


$typedReturnClosure = function(): int {
    return 5;
};

$untypedReturnClosure = fn() => 5;


// Use that type
function uses_returns_int(returns_int $fn) {
    $value = $fn();
    if (is_int($value) !== true) {
        echo "Error: int function did not return int but " . gettype($value);
    }
}


uses_returns_int($typedReturnClosure);
uses_returns_int($untypedReturnClosure);

echo "Ok";

// This would give a type error, as a function that takes a
// parameter with type 'array' is not compatible with one that
// takes 'int'.

$badReturnClosure = fn() => 'foo';

// Expect "Cannot use closure as incompatible signature
uses_returns_int($badReturnClosure);