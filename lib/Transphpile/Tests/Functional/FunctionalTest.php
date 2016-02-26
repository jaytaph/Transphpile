<?php

namespace Transphpile\Tests\Functional;

use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;
use Transphpile\Tests\FunctionalTestCase;

class FunctionalTest extends FunctionalTestCase {

    function functionalTestProvider() {
        $it = new \RecursiveDirectoryIterator(__DIR__, \RecursiveDirectoryIterator::CURRENT_AS_PATHNAME | \RecursiveDirectoryIterator::SKIP_DOTS);
        $it = new \RecursiveRegexIterator($it, '/\.yml$/');
        $it = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::LEAVES_ONLY);

        return array_map(function($a) { return array($a);}, iterator_to_array($it));
    }

    /**
     * @dataProvider functionalTestProvider
     */
    function testFunctionalTest($path) {
        $this->assertTranspile($path);
    }

}
