<?php

namespace Transphpile\Transpile\Visitors\Php70;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;


/*
 * Converts foo(a1)(a2, a3) into;
 *
 *      call_user_func(
 *          foo(a1),
 *          a2,
 *          a3
 *      )
 */

class DoubleCallVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if (!$node instanceof Node\Expr\FuncCall) {
            return null;
        }

        $name = $node->name;
        if (!$name instanceof Node\Expr) {
            return null;
        }
        // Can't call ClassName::foo(type)(args) or (new Visitor())(args) in php5
        if (($name instanceof Node\Expr\MethodCall) ||
            ($name instanceof Node\Expr\StaticCall) ||
            ($name instanceof Node\Expr\FuncCall) ||
            ($name instanceof Node\Expr\New_)) {

            // Create call_user_func() call
            return new Node\Expr\FuncCall(
                new Node\Name('call_user_func'),
                array_merge(
                    array($name),
                    $node->args
                )
            );
        }
        return null;
    }
}
