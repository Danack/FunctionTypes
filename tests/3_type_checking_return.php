<?php

declare(strict_types = 1);

// Define a callable type
typedef returns_int = callable(): int;

// Use that type
function uses_foo(returns_int $fn) {...}


// This is fine.
function bar(): int: {
    return 5;
}
uses_foo('bar');


// This is also fine.
$closure = function (): int {
    return 5;
};
uses_foo($closure);
echo "Ok";

// this is not fine
function baz(): array {
    return [];
};
uses_foo('baz');

// Expect "Cannot use 'baz' as incompatible
