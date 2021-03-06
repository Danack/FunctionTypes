<?php

declare(strict_types = 1);

// Define a callable type
typedef consumes_int = callable(int $x);

$widerClosure = function(int|string $value) {
    if (is_int($value) !== true && is_string($value) !== true) {
        echo "Error: function parameter is not int or string but " . gettype($value);
    }
};


// Use that type
function uses_consumes_int($value, consumes_int $fn) {
    $value = $fn($value);
}

uses_consumes_int(5, $widerClosure);
uses_consumes_int('foo', $widerClosure);
echo "Ok";


uses_consumes_int([], $widerClosure);
// Expect error, "Cannot pass array int|string expected"