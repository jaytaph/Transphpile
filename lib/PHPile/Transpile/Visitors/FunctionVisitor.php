<?php

namespace PHPile\Transpile\Visitors;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

/*
 * Keeps track in which function we currently reside, which typehint it returns and will add
 * checks on parameters when in strict mode.
 */

class FunctionVisitor extends NodeVisitorAbstract
{
    /**
     * Store node function.
     *
     * @param Node $node
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\FunctionLike) {
            global $functionStack;
            $functionStack[] = $node;
        }

        if ($node instanceof ClassLike) {
            global $currentClass;
            $currentClass = $node;
            print "New current class: ".$currentClass->name."\n";
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassLike) {
            global $currentClass;
            $currentClass = null;
        }

        if (!$node instanceof Node\FunctionLike) {
            return;
        }

        global $functionStack;
        $functionNode = array_pop($functionStack);

        // Remove return type if set
        if ($node->returnType) {
            $node->returnType = null;
        }

        // Remove scalar types and store for later
        $params = array();
        foreach ($node->params as $param) {
            if (in_array($param->type, array('string', 'int', 'float', 'bool'))) {
                $params[] = array(
                    'type' => $param->type,
                    'arg' => $param->name,
                    'func' => $node->name,
                );
                $param->type = null;
            }
        }

        // Don't add checks when we don't enforce strict
        global $is_strict;
        if (! $is_strict) {
            return null;
        }

        // No typehinting on abstract
        if ($functionNode instanceOf Node\Stmt\ClassMethod && $functionNode->isAbstract()) {
            return null;
        }

        // No typehinting on interfaces
        global $currentClass;
        print "Current class: ".(isset($currentClass) ? $currentClass->name : "<null>")." (".get_class($currentClass).")\n";
        if ($currentClass instanceof Interface_) {
            print "INTERFACEDECTED!";
            return null;
        }

        // Add code for checking scalar types
        foreach ($params as $param) {
            $code = sprintf(
                    '<?php if (! is_%s($%s)) { throw new \InvalidArgumentException("Argument $%s passed to %s() must be of the type %s, ".get_class($%s)." given"); }',
                    $param['type'], $param['arg'], $param['arg'], $param['func'], $param['type'], $param['arg']
            );

            $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
            $stmts = $parser->parse($code);

            if (! $node->stmts) {
                $node->stmts = $stmts;
            } else {
                $node->stmts = array_merge($stmts, $node->stmts);
            }
        }
    }
}
