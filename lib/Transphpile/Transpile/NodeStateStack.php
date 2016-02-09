<?php

namespace Transphpile\Transpile;

/*
 * This singleton class allows us to deal with "global" variables that needs to be used while traversing the AST.
 * In some occasions (like during anonymous classes), we actually traverse a part of the subtree manually so we need to
 * "store" the original global variables, and use new global variables for that traversal. Once completed, we return to
 * the original global variables.
 */


class NodeStateStack {

    /** @var NodeStateStack */
    private static $instance = null;

    private function __construct() { }
    private function __clone() { }
    private function __wakeup() { }


    /**
     * We can only reach the nodeStackState class through a ::getInstance() call.
     *
     * @return NodeStateStack
     */
    static public function getInstance()
    {
        if (static::$instance == null) {
            static::$instance = new NodeStateStack();
            static::$instance->pushVars();
        }

        return static::$instance;
    }


    protected $vars = array();

    /**
     *
     */
    public function pushVars()
    {
        $vars = array(
            'anonClasses' => array(),       // Any anonymous classes that must be converted to regular classes
            'isStrict' => false,            // declare(strict_type=1) has been set
            'currentClass' => null,         // Current class, interface or trait we are currently visiting
            'currentFunction' => null,      // Current function, method or closure we are currently visiting
        );

        $this->vars[] = $vars;
    }

    /**
     *
     */
    public function popVars()
    {
        array_pop($this->vars);
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        if (count($this->vars) == 0) {
            return false;
        }

        return isset($this->vars[count($this->vars) -1][$name]);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if (count($this->vars) == 0) {
            return null;
        }

        return $this->vars[count($this->vars) -1][$name];
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (count($this->vars) == 0) {
            return null;
        }

        if (is_array($this->vars[count($this->vars) -1][$name])) {
            $this->vars[count($this->vars) -1][$name][] = $value;
        } else {
            $this->vars[count($this->vars) -1][$name] = $value;
        }
    }



}

