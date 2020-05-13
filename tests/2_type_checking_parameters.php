<?php

declare(strict_types = 1);




// Define a callable type
typdef consumes_int = callable(int $value): void;

// Use that type
function uses_foo(consumes_int $fn) {
    $fn(5);
}

// This is fine.
function bar(int|string $value): {}
uses_foo('bar');

// This is also fine.
$closure = function (int|string $value): {...};
uses_foo($closure);

echo "Ok";

// this is not fine
function baz(array $value): {...}
uses_foo('baz');

// Type error
