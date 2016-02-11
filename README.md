Transphpile: A PHP 7 to PHP 5.3 transpiler
==========================================

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
