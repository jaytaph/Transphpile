<?php

namespace PHPile\Transpile;

use PHPile\IO\IOInterface;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class Transpile
{
    use \PHPile\IO\IO;

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
//
//        interface Logger
//        {
//            public function log(string $msg);
//        }
//
//        ');
//        print_r($stmts);
//        exit(1);

        // Parse into statements
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $stmts = $parser->parse($code);

        // Custom traversal does the actual transpiling
        $traverser = $this->getTraverser();
        $stmts = $traverser->traverse($stmts);


        // Add final anonymous classes to the statements
        global $anonClasses;

        // We must transpile anonymous classes as well, as we haven't done this yet
        $traverser = $this->getTraverser();
        $anonClassStmts = $traverser->traverse($anonClasses);

        // Find hook point for anonymous classes, must be after declare, namespaces and use-statements, and can before anything else
        $idx = $this->getAnonymousClassHookIndex($stmts);

        $preStmts = array_slice($stmts, 0, $idx);
        $postStmts = array_slice($stmts, $idx);
        $stmts = array_merge($preStmts, $anonClassStmts, $postStmts);


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

    protected function getAnonymousClassHookIndex(array $stmts)
    {
        // Find the first statement that is not a declare, namespace or use-statement
        foreach ($stmts as $idx => $stmt) {
            if (! $stmt instanceof Declare_ &&
                ! $stmt instanceof Use_ &&
                ! $stmt instanceof Namespace_) {
                return $idx;
            }
        }

        // Seems this file only consist fo declares, use and namespaces.. That should not happen
        throw new \RuntimeException("Cannot find an location to insert anonymous classes");
    }

    /**
     * @return NodeTraverser
     */
    public function getTraverser()
    {
        $traverser = new NodeTraverser();

        // Find Path
        $reflector = new \ReflectionObject($this);
        $path = $reflector->getFileName();
        $path = dirname($path);

        // Generate FQCN
        $fqcnPath = get_class($this);
        $fqcnPath = explode('\\', $fqcnPath);
        $fqcnPath[count($fqcnPath) - 1] = 'Visitors';
        $fqcnPath = implode('\\', $fqcnPath);

        // Iterate node visitors and add them to the traverser
        foreach (new \FilesystemIterator($path.'/Visitors', \FilesystemIterator::SKIP_DOTS) as $fileInfo) {
            /* @var \SplFileInfo $fileInfo */
            $class = str_replace('.php', '', $fileInfo->getFilename());
            $fqcn = $fqcnPath.'\\'.$class;
            $this->getIo()->debug('Loading visitor: '.$fqcn, 'trns');

            $traverser->addVisitor(new $fqcn());
        }

        return $traverser;
    }
}
