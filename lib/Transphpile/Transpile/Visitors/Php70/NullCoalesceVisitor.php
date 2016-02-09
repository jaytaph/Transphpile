<?php

namespace Transphpile\Transpile\Visitors\Php70;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/*
 * Converts $a ?? $b into:
 *
 *      call_user_func(
 *          function ($v1, $v2) { return isset($v1) ? $v1 : $v2; },
 *          @$a,
 *          @$b
 *      )
 *
 * This construct is needed because isset() only works on variables, while the null coalesce supports any expression.
 * We also add a @ operator to the variable, in case it doesn't exist so it does not throw a notice.
 *
 */

class NullCoalesceVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if (!$node instanceof Node\Expr\BinaryOp\Coalesce) {
            return null;
        }

        // Create closure node
        $closureNode = new Node\Expr\Closure(array(
            'params' => array(
                new Node\Param('v1'),
                new Node\Param('v2'),
            ),
            'stmts' => array(
                new Node\Stmt\Return_(
                    new Node\Expr\Ternary(
                        new Node\Expr\Isset_(array(
                            new Node\Expr\Variable('v1'),
                        )),
                        new Node\Expr\Variable('v1'),
                        new Node\Expr\Variable('v2')
                    )
                )
            )
        ));

        // Create call_user_func() call
        $callUserFuncNode = new Node\Expr\FuncCall(
            new Node\Name('call_user_func'),
            array(
                $closureNode,
                new Node\Expr\ErrorSuppress($node->left),
                new Node\Expr\ErrorSuppress($node->right),
            )
        );

        return $callUserFuncNode;
    }
}
