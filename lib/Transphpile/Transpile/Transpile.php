<?php

namespace Transphpile\Transpile;

use Symfony\Component\Console\Exception\InvalidOptionException;
use Transphpile\IO\IOInterface;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class Transpile
{
    use \Transphpile\IO\IO;

    public function __construct(IOInterface $io)
    {
        $this->setIO($io);
    }

    public function transpile($srcPath, $dstPath)
    {
        // transpile based on target version
        $code = file_get_contents($srcPath);

        // Parse into statements
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $stmts = $parser->parse($code);

        // Custom traversal does the actual transpiling
        $traverser = self::getTraverser();
        $stmts = $traverser->traverse($stmts);


        $prettyPrinter = new Standard();

        if ($dstPath == '-') {
            // Output directly to stdout
            $this->getIo()->output($this->getStub() . $prettyPrinter->prettyPrint($stmts));
        } else {
            $dir = dirname($dstPath);
            if (!is_dir($dir) || !is_writeable($dir)) {
                @mkdir($dir, 0777, true);
                if (!is_dir($dir) || !is_writeable($dir)) {
                    throw new InvalidOptionException(sprintf('Destination directory "%s" does not exist or is not writable or could not be created.', $dir));
                }
            }

            file_put_contents($dstPath, $this->getStub() . $prettyPrinter->prettyPrint($stmts));
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

    protected function getStub() {
        $stub = <<< STUB

<?php

/*
 * This code has been transpiled through https://github.com/jaytaph/transphpile
 */

STUB;
        return $stub;
    }
}
