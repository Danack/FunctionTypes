<?php

declare(strict_types = 1);

// Define a callable type
typedef consumes_int = callable(int $x);


$exactClosure = function(int $value) {
    if (is_int($value) !== true) {
        echo "Error: function parameter is not int or string but " . gettype($value);
    }
};


// Use that type
function uses_consumes_int($value, consumes_int $fn) {
    $fn($value);
}

uses_consumes_int(5, $widerClosure);
echo "Ok";


uses_consumes_int('foo', $widerClosure);
// Expect error, "Cannot pass string, int expected"