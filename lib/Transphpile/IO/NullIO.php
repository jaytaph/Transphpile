<?php

namespace Transphpile\IO;

class NullIO implements IOInterface
{
    public function getOption($option)
    {
        return false;
    }

    public function getOptions()
    {
        return array();
    }

    public function getArgument($argument)
    {
        return false;
    }

    public function getArguments()
    {
        return array();
    }

    public function output($message)
    {
    }

    public function verbose($message, $tag)
    {
    }

    public function veryVerbose($message, $tag)
    {
    }

    public function debug($message, $tag)
    {
    }
}
