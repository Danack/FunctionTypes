


# Referencing callables


## Introduction

Currently, the only way to reference functions and methods is by using strings. Although that works, it has a couple of downsides:

* it's fragile for computers.
* it's fragile for humans.


## Proposal

Allow people to directly reference functions, static class methods and instance class methods with `$(foo)`. 


```php
class Foo {
    public function bar() {
    }
 
    public static function quux() {
    }
}

$foo = new Foo();

// this
$(strlen);
$($foo, bar);
$(Foo, quux);

// Would be equivalent to
Closure::fromCallable('strlen');
Closure::fromCallable([$foo, 'bar']);
Closure::fromCallable([Foo::class, 'quux']);
// or
Closure::fromCallable('Foo::quux');
```


*TODO - fill in remaining details*