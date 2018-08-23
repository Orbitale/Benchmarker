Orbitale Benchmarker
====================

A simple PHP tool to benchmark PHP scripts.

## Install

⚠️ For now the project is in alpha (and nothing says whether it'll be "stable" one day) and Phar files will be used to
release it.

If you still want to give it a try, there are many ways to do so:

* In an existing project:
  ```
  $ composer require orbitale/benchmarker
  ```
  And then run it via `vendor/bin/benchmarker`.
* In a separate directory with Composer:
  ```
  $ composer create-project orbitale/benchmarker benchmarker
  $ cd benchmarker/
  $ php bin/benchmarker
  ```
* In a separate directory, using Git:
  ```
  $ git clone git@github.com:Orbitale/Benchmarker.git
  $ cd benchmarker/
  $ php bin/benchmarker
  ```

## Usage

Basically there are two ways to use it: **sequential** or **parallel** execution.

### Sequential

```
$ benchmarker run my_test.php --count=1000
```

(Check the `run` command help to know other usages)

### Parallel

```
$ benchmarker parallel test_one.php test_two --count=1000
```

(Check the `parallel` command help to know other usages)
ℹ️ **Note:** Parallelization is only usable with multiple files, else it won't change anything.

## File format

A test file must return an iterable with callables, each callable being a test for the benchmark.

You can do anything you want in it: autoload, create global vars, etc.

If you use parallelization, you are guaranteed that a file execution will be **isolated** from other tests.

Here is an example of a test file:

```php
<?php

declare(strict_types=1);

return (function(){
    yield 'simple' => function(){
        $a = 'quote';
    };
    yield 'double' => function(){
        $a = "quote";
    };
})();
```

You can see more examples in the [examples](examples) directory.


## More

More coming soon! Stay tuned 😉
