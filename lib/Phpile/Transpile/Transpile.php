<?php

namespace Phpile\Transpile;

use Phpile\IO\IOInterface;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class Transpile
{
    use \Phpile\IO\IO;

    public function __construct(IOInterface $io)
    {
        $this->setIO($io);
    }

    public function transpile($srcPath, $dstPath)
    {
        $inplace = false;
        if ($srcPath == $dstPath) {
            // Inline replacement
            $inplace = true;
            $dstPath = $dstPath.uniqid();
        }

        // transpile based on target version
        $code = file_get_contents($srcPath);

//        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
//        $stmts = $parser->parse('<?php
//              use foo\bar as baz;
//        ');
//        print_r($stmts);
//        exit(1);

        // Parse into statements
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $stmts = $parser->parse($code);

        // Custom traversal does the actual transpiling
        $traverser = self::getTraverser();
        $stmts = $traverser->traverse($stmts);


        $prettyPrinter = new Standard();

        if ($dstPath == '-') {
            // Output directly to stdout
            $this->getIo()->output("<?php \n". $prettyPrinter->prettyPrint($stmts));
        } else {
            file_put_contents($dstPath, "<?php \n".$prettyPrinter->prettyPrint($stmts));
        }

        // If inplace, we have to (atomically) rename (temp) dest to source
        if ($inplace) {
            rename($dstPath, $srcPath);
        }

    }

    /**
     * @return NodeTraverser
     */
    static public function getTraverser()
    {
        $traverser = new StackVarNodeTraverser();

        // Find Path
        $reflector = new \Reflectionclass(__CLASS__);
        $path = $reflector->getFileName();
        $path = dirname($path);

        // Generate base FQCN and path based on the current file
        $baseFqcn = __CLASS__;
        $baseFqcn = explode('\\', $baseFqcn);
        $baseFqcn[count($baseFqcn) - 1] = 'Visitors';
        $baseFqcn = implode('\\', $baseFqcn);

        $baseFqcnPath = dirname(__FILE__) . "/Visitors/";


        // Iterate node visitors and add them to the traverser
        $it = new \RecursiveDirectoryIterator($path.'/Visitors', \RecursiveDirectoryIterator::SKIP_DOTS);
        $it = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::LEAVES_ONLY);
        foreach ($it as $fileInfo) {
            /* @var \SplFileInfo $fileInfo */

            // Convert path into FQCN
            $class = $fileInfo->getPathname();
            $class = str_replace($baseFqcnPath, "", $class);
            $class = str_replace('.php', '', $class);
            $class = str_replace('/', '\\', $class);

            $fqcn = $baseFqcn.'\\'.$class;

            $traverser->addVisitor(new $fqcn());
        }

        return $traverser;
    }
}
