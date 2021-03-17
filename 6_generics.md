# Generics

1. Make the appropriate changes to allow this code to compile.

i.e. support for using template parameters in class definitions.


```php
<?php

class Entry<KeyType, ValueType>
{
    protected KeyType $key;
    protected ValueType $value;

    public function __construct(
        protected KeyType $key,
        protected ValueType $value
    ) {
    }
 
    public function getKey(): KeyType
    {
        return $this->key;
    }
 
    public function getValue(): ValueType
    {
        return $this->value;
    }
}
```

For each of the 'KeyType' and 'ValueType' (aka the template parameters) inside the class emit a new type of token (T_TEMPLATE or whatever), that has a value of the typename as appropriate.

Do whatever work is needed to allow the classname to be followed by `Entry<KeyType, ValueType>` aka the template information.

However this code wouldn't be runnable. If the VM ever tries to execute a T_TEMPLATE it just dies with fatal error.


2. Add support for autoloading generics

The users would have to register a function:

```
/**
* @param string $type_name The name of the type being created
* @param array<string, string> $template_parameters An array of the template parameters
*   where the keys are the names in the template and the values are the names requested.
* @param AST $ast A copy of the abstract syntax tree from the pre-compiled generic class.
  */
  function generic_autoloader(string $type_name, array $template_parameters, AST $ast) {
  // Programmer modifies AST in here replacing the template tokens/nodes
  // with the appropriate ones.
  }

autoload_register_generic('generic_autoloader');
```

We could/should cache the values so that each generic template is only called once max for each combination of template parameter/ 

3. Do the work to make the following compile

```
<?php

require_once __DIR__ . "/vendor/autoload.php";

$entry = new Entry<int,string>(1, 'test');
```

When this code is run, first it triggers an autoload for the class type "Entry<int,string>" which loads the class file, and so then the generic class is defined. 

Secondly, it triggers an autoload for the specific generic, and so 'generic_autoloader' is called with parameters of:

$type_name = 'Entry<int,string>'
$template_parameters = ['KeyType' => 'int', 'ValueType' => 'string']

Inside the generic_autoloader function, the user has some code that manipuates the AST as appropriate to replace the T_TOKEN AST nodes with the appropriate tokens needed, so that the code behaves correctly. 

After the AST is modified and the function exits, the engine then does a quick check of it's validity. If nothing else, check all the template nodes have been replaced.

After that compile the generated code as normal.

Most programmers aren't going to be up to writing their own generic autoloader code....so most people will just use a composer or other library provided one. 

Or is that all not the difficult part that needs solving?