Transphpile: A PHP 7 to PHP 5.6 transpiler
==========================================

This transpiler lets you write PHP7 code and converts it back to equal PHP5.6 so you can run in on older PHP versions. 
The transpiler itself does not need PHP7: it can convert PHP7 code even on a PHP5.4 system, so you do not need any 
PHP7 version.

**The transpiler itself can be run on even PHP 5.4!**


Usage
=====

Downloading and installing the transpiler: 

    composer require jaytaph/transphpile
    ./vendor/bin/transphpile help
        
Transpiling a single file:
    
    ./vendor/bin/transphpile transpile test.php --stdout
    
Transpiling a directory of files:

    ./vendor/bin/transphpile transpile src --dest php5


Phar file
=========

The transpiler can function as a phar file too. Current documentation still has to be setup on how to download. 
Creation can be done manually by running:
 
    ./vendor/bin/phar-composer build .
    
inside the transphpile repository.



Transpiling PHP7
================
The transpiler will transpile the following language features of PHP 7 back to PHP 5.6 compatible code:

- Parameter scalar type hinting
- Return type hinting
- Anonymous classes
- Array defines
- Null coalesce (??)
- Spaceship operator (<=>)
- Closure calls
- Declare statements
- Group use statements
- Unserialize with allowed_classes


Todo's:

The transpiler however, is not able to transpile the following:
- return statements in generators

Instead, the transpiler will display a warning when transpiling.


It's possible to even transpile back to 5.5 compatible code, but some features will not be able to be transpiled. For 
instance generators. In these cases, warnings will be triggered.
