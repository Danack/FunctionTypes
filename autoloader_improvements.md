

## Autoloading improvements to support function types 

Todo - copy https://wiki.php.net/rfc/function_autoloading

But change the types to be

AUTOLOAD_TYPE ⇒ 1 - Represents type autoloading. Currently that is class and function types, but may expand in the future to other types. 
AUTOLOAD_FUNCTION ⇒ 2 - Represents function implementation autoloading
AUTOLOAD_CONSTANT ⇒ 4 - Represents constant autoloading
AUTOLOAD_STREAM = 8 - Represents stream autoloading




## Future scope

### Other types

Imagine when PHP supports enums, and we have a library file that defines compass points.

```
// compass.php

enum Compass {
  North, South, East, West 
}

```

And we have a function that uses that Compass enum

```
// app.php

function foo(Compass $direction) { ... }


```                                           

And we execute the file app.php. When the engine compiles the function 'foo' it can't tell of 'Compass' is a class type, function type, or enum type.  

