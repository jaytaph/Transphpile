Transphpile: A PHP 7 to PHP 5.6 transpiler
==========================================

This transpiler lets you write PHP7 code and converts it back to equal PHP5.6 so you can run in on older PHP versions. 
The transpiler itself does not need PHP7: it can convert PHP7 code even on a PHP5.4 system.

**This is heavy beta stuff that isn't even worthy of a 0.x semver release.. But the most used PHP7 functionality (typehinting, mostly), is functional. Do not use in production or else you well have a very bad day (and don't go into space)**


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
- Closure calls (almost)
- Declare statements
- Group use statements
- Unserialize with allowed_classes

Note that the transpiler does not strip away functionality: it will convert functionality back into PHP (5.6) compatible code: for instance, when you use scalar type-hints as function arguments, the transpiler will strip away those typehints generate code, but adds additional checks inside your function to check for the actual argument types and throws exceptions if they do not match. [See this gist for such a transpiled example](https://gist.github.com/jaytaph/c491f5b5c4027c5dae24).

Theoretically, you should be able to transpile all your PHP7 code and run it against your unit-tests with a PHP5 without failed tests (well, provided you've written unit-tests, and they didn't fail in the first place). However, because of some backward compatibility issues, it's not always possible to transpile everything. In those cases, we will issue warnings/exceptions.


Todo
====

A lot: 
* Get things up and running more decent. 
* Fix up code, make sure that transpiled code works properly (even for instance when we use transpiled code in for instance `isset()` of comparisions and other features).
* Fix up the unit-tests, make them more fool-proof.

The transpiler however is not able to transpile the following due to the fact that it's very hard to emulate the functionality even in a PHP5.6 environment:

- return statements in generators
- yield from statements

Instead, the transpiler will display a warning/error when transpiling these statements.


Dropping below PHP 5.6
======================
Must functionality used in the transpilated code is compatible with PHP 5.3. However, we do not transpile PHP5.6 functionality back, but the option to even transpile that. But for now, the focus is on PHP7 -> PHP5.6.


Polyfills
=========
The transpiler does not convert some of the new functions added in PHP7, but relies on the Symfony polyfills for this purpose. You should add them manually if you are using PHP7 functions and classes. See [https://github.com/symfony/polyfill-php70](https://github.com/symfony/polyfill-php70) for more information.


Unserialize functionality
=========================
There is a neat functionality added in PHP7 that allows you to whitelist which classes are allowed to be unserialized. Unfortunately, we cannot redefine functions in PHP, so we use a different method: instead, we have a custom unserialize function that will parse your unserialize strings, removes unwanted classes and replaces them with an `__PHP_Incomplete_Class`. This makes sure that non-whitelisted classes are NEVER unserialized, and mimics the functionality pretty close. The functionality is in basis present, but it's not 100% implemented in the transpiler.
