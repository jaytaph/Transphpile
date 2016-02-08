<?php

namespace {

    use Phpile\Unserializer;

    class c2 {
        protected $testprop;

        function __construct($a) {
            $this->testprop = $a;
        }
    }

    class c3 extends c2 {}

    $c2 = new c2(42);
    $c3 = new c3(96);

    class foobar {
        protected $foo = 1;
        private $bar = "asf";
        public $baz2;
        public $baz3;
    }
    $f = new foobar();
    $f->baz2 = $c2;
    $f->baz3 = $c3;

    $data = array(
        5,
        "foobar",
        true,
        false,
        null,
        -1,
        0,
        1.63,
        (10/3),
        0x123,
        array("foo", 5, 1.2, "bar", "baz"),
        $f,
        log(0),
        NAN,
    );

    foreach ($data as $entity) {
        print "-- serialize --\n";
        $tmp = serialize($entity);
        print "\033[32;1m";
        var_dump($tmp);
        print "\033[0m";

        $a1 = unserialize($tmp, array('allowed_classes' => array('foobar', 'c2')));
        $a2 = Unserializer::unserialize($tmp, array('allowed_classes' => array('foobar', 'c2')));

        print "-- unserialize() --\n";
        print "\033[32;1m";
        var_dump($a1);
        print "\033[0m";
        print "-- php7 Unserializer() --\n";
        print "\033[32;1m";
        var_dump($a2);
        print "\033[0m";

        print "\n\n\n";
    }
    exit(1);
}


namespace Phpile {

    class Unserializer {
        protected $str = "";                        // Current unserialize string
        protected $idx = 0;                         // Current index in unserialize string
        protected $options = array();               // Options passed to unserialize()
        protected $whitelist = array();             // Whitelist of classes passed to unserialize()
        protected $useDefaultSerializer = false;    // True when default unserialize() function can be used

        /**
         * Create unserialize class
         *
         * @param $str
         * @param null $options
         * @return bool|int|mixed|null|string
         */
        static function unserialize($str, $options = null)
        {
            $tmp = new self($str, is_array($options) ? $options : array());
            return $tmp->run();
        }


        /**
         * @param $str
         * @param array $options
         * @throws \Exception
         */
        protected function __construct($str, array $options) {
            $this->idx = 0;
            $this->str = $str;
            $this->options = $options;

            // By default we use the default unserialize() function of PHP
            $this->useDefaultSerializer = true;

            // No allowed_classes means default behaviour
            if (!isset($options['allowed_classes'])) {
                return;
            }

            // Sanity check on allowed_class options
            if (!is_bool($options['allowed_classes']) && !is_array($options['allowed_classes'])) {
                throw new \Exception('allowed_classes can only be true, false or an array');
            }

            // False means use default unserialize() behaviour
            if ($options['allowed_classes'] === false) {
                return;
            }

            // Otherwise use our custom serializer
            $this->useDefaultSerializer = false;
            $this->whitelist = $options['allowed_classes'] === true ? array() : $options['allowed_classes'];
        }

        /**
         * Run our serializer
         * @return mixed
         */
        protected function run() {
            if ($this->useDefaultSerializer) {
                // Use default functionality
                return unserialize($this->str);
            }

            // Otherwise, use our custom unserializer
            return $this->php7_unserialize();
        }

        /**
         * @param int $depth
         * @return bool|int|null|string
         */
        function php7_unserialize($depth = 1)
        {
            // Find type, terminated by a :, or in case of N, with a ;
            $type = $this->findToken(':;');
            switch ($type) {
                case 'i' :
                    // Integer
                    $val = (int)$this->findToken(';');
                    break;
                case 's' :
                    $len = $this->findToken(':');
                    // Read string length plus quotes, but don't add them to the output
                    $val = (string)substr($this->readChars($len+2), 1, -1);
                    $this->findToken(';');
                    break;
                case 'b' :
                    // Boolean
                    $val = (bool)$this->findToken(';');
                    break;
                case 'N' :
                    // Null
                    $this->findToken(';');
                    $val = null;
                    break;
                case 'd' :
                    // Double
                    $val = (string)$this->findToken(';');
                    if ($val == 'NAN') {
                        $val = NAN;
                    } else if ($val == 'INF') {
                        // Creates INF
                        $val = (0 - (log(0)));
                    } else if ($val == '-INF') {
                        // Creates -INF
                        $val = log(0);
                    } else {
                        $val = (double)$val;
                    }
                    break;
                case 'a' :
                    // Array
                    $len = $this->findToken(':');
                    $this->findToken('{');
                    $val = array();
                    for ($i=0; $i!=$len; $i++) {
                        // Read key and var by recursively unserialize
                        $key = $this->php7_unserialize($depth + 1);
                        $var = $this->php7_unserialize($depth + 1);
                        $val[$key] = $var;
                    }
                    $this->findToken('}');

                    break;
                case 'O' :
                    // Object

                    // Save current position (actually, start of the O:)
                    $savedIdx = $this->getIdx()-2;

                    // Find classname
                    $len = $this->findToken(':');
                    $class = (string)substr($this->readChars($len+2), 1, -1);
                    $this->findToken(':');

                    // Find number of properties
                    $propLen = (int)$this->findToken(':');

                    $this->findToken('{');

                    // Save property start index for later use
                    $propIdxStart = $this->getIdx();

                    // Read properties, but don't do anything with them
                    for ($i=0; $i!=$propLen; $i++) {
                        // Read (and ignore) key
                        $this->php7_unserialize($depth + 1);
                        // Read (and ignore) value
                        $this->php7_unserialize($depth + 1);
                    }

                    // Save end of properties
                    $propIdxEnd = $this->getIdx();

                    $this->findToken('}');


                    // Check if the class is whitelisted, or if there is no whitelist
                    if (in_array($class, $this->whitelist) || count($this->whitelist) == 0) {

                        // Fetch the complete class, and unserialize it through the PHP unserializer. In case this is a
                        // subclass, the result will not be stored.

                        $serializedClass = substr($this->str, $savedIdx, $this->getIdx() - $savedIdx + 1);

                        // Only unserialize on the lowest depth
                        $val = ($depth == 1) ? unserialize($serializedClass) : false;

                    } else {
                        // Class is blacklisted.

                        // Fetch properties
                        $props = substr($this->str, $propIdxStart, $propIdxEnd - $propIdxStart + 2);

                        // Create a new class that certainly will not exist. This will generate a "PHP_Incomplete_class"
                        // However, the name of the class will be wrong. We add a __PHP_Incomplete_class_Name that will
                        // overrule that name. Furthermore, any other existing properties (stored serialized in $props)
                        // will be added as well.

                        $dummy = uniqid('php7unserialize_');
                        $data = sprintf('O:%d:"%s":%d:{s:27:"__PHP_Incomplete_Class_Name";s:%d:"%s";%s}',
                            strlen($dummy), $dummy,
                            strlen($props) == 0 ? $propLen : $propLen + 1,
                            strlen($class), $class,
                            $props
                        );

                        // Save the new incomplete class back into the serialized data
                        $this->updateString($data, $savedIdx);

                        // Unserialize the (incomplete) class, only on the lowest depth
                        $val = ($depth == 1) ? unserialize($data) : false;
                    }
                    break;
                default :
                    // Unknown
                    throw new \UnexpectedValueException(sprintf('Unknown type "%s" encountered', $type));
                    break;
            }

            return $val;
        }

        function updateString($data, $offset)
        {
            $this->str = substr_replace($this->str, $data, $offset, ($this->idx - $offset));
            $this->idx += strlen($data) - ($this->idx - $offset);
        }

        function getIdx()
        {
            return $this->idx;
        }

        function findToken($charList)
        {
            $len = strcspn($this->str, $charList, $this->idx);

            $str = substr($this->str, $this->idx, $len);
            $this->idx += ($len + 1);

            return $str;
        }

        function readChars($len) {
            $str = substr($this->str, $this->idx, $len);
            $this->idx += $len;

            return $str;
        }

    }
}

