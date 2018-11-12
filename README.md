Orbitale Benchmarker
====================

A simple PHP tool to benchmark PHP scripts.

## Install

You can install Benchmarker in different ways:

* Using the `phar` file. Go to the [Releases](https://github.com/Orbitale/Benchmarker/releases) page and download the
latest `benchmarker.phar` archive.
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
* By building the `.phar` file manually with [Box](https://github.com/box-project/box2):
  ```
  # Install the project in any directory via "composer create" or "git clone"
  $ box build
  # Find the file in build/benchmarker.phar
  ```

## Usage

### Create tests

You can use this command to create test files:
 
```
$ benchmarker create test_file.php

 No test names provided

 Provide a name for one test (or leave empty if you have finished)::
 > first test

 Provide a name for one test (or leave empty if you have finished)::
 > second test

 Provide a name for one test (or leave empty if you have finished)::
 >
 

 [OK] Created benchmark at test_file.php


```

See [File Format](#file-format) to know what the file will look like and how to create tests in them.

### Running the tests

There are two ways to use the benchmarker: **sequential** or **parallel** execution.

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

This project is highly inspired by http://www.php-benchmark-script.com/ from which the source code is based.
