<?php

declare(strict_types = 1);

// Define a callable type
typedef logger = callable(string $message): void;

// Define a function that uses that type
function foo(logger $logger) {
    $logger("This was called");
}

// Function implementation
function echologger(string $message) {
    echo $message;
}

// Closure implementation
$closurelogger = function (string $message) {
    echo $message;
};

// Static class implementation
class Staticlogger {
    static function log(string $message) {
        echo $message;
    }
}

// Instance class implementation
class Instancelogger {
    function log(string $message) {
        echo $message;
    }
}

// Anonymous instance class implementation
$anonymousInstance = new class() {
    function log(string $message) {
        echo $message;
    }
};

// These are all fine.
foo('echologger');
foo($closurelogger);
foo('Staticlogger::log');
foo([new Instancelogger, 'log']);
foo([$anonymousInstance, 'log']);

echo "Ok";