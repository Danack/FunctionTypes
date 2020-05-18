


## Referencing callables.

Rough draft of possible idea.

```php
class Foo {
    public function bar() {
    }
 
    public static function quux() {
    }
}

$foo = new Foo();

// this:

$(strlen);
$($foo, bar);
$(Foo, quux);

// Would be equivalent to:

Closure::fromCallable('strlen');
Closure::fromCallable([$foo, 'bar']);
Closure::fromCallable([Foo::class, 'quux']);
// or
Closure::fromCallable('Foo::quux');