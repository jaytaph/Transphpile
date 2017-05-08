<?php

namespace Transphpile\Transpile\Visitors\Php70;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/*
 * Converts $a ?? $b into:
 *
 *      call_user_func(
 *          function($v1) { return $v1[0]; },
 *          call_user_func(
 *              function($v1) { return $v1 !== null ? array($v1) : null; },
 *              @$a
 *          ) ?: [$b]
 *      )
 *
 * (Can't use (expr)[0] in php 5.6)
 * (Can't use reset(expr) in php 7.0+ without a warning about taking a reference on non-reference)
 *
 * This construct is sometimes needed because isset() only works on variables, while the null coalesce supports any expression.
 * We also add a @ operator to the variable, in case it doesn't exist so it does not throw a notice.
 *
 * TODO: Can optimize/simplify the cases where the left hand side is always simple,
 * and avoid method calls (Assume ArrayAccess is implemented properly).
 *
 * - e.g. convert `$x->y['key'] ?? DEFAULT` into `isset($x->y['key']) ? $x->y['key'] : DEFAULT`
 */

class NullCoalesceVisitor extends NodeVisitorAbstract
{

    public function leaveNode(Node $node)
    {
        if (!$node instanceof Node\Expr\BinaryOp\Coalesce) {
            return null;
        }

        // Create closure node
        $closureLHSNode = new Node\Expr\Closure(array(
            'params' => array(
                new Node\Param('v1'),
            ),
            'stmts' => array(
                new Node\Stmt\Return_(
                    new Node\Expr\Ternary(
                        new Node\Expr\Isset_(array(
                            new Node\Expr\Variable('v1'),
                        )),
                        new Node\Expr\Array_([
                            new Node\Expr\ArrayItem(
                                new Node\Expr\Variable('v1')
                            ),
                        ]),
                        new Node\Expr\ConstFetch(new Node\Name('null'))
                    )
                )
            )
        ));

        // Create call_user_func() call
        // Implementation of coalesce LHS
        $coalesceLhs = new Node\Expr\FuncCall(
            new Node\Name\FullyQualified('call_user_func'),
            array(
                $closureLHSNode,
                new Node\Expr\ErrorSuppress($node->left),
            )
        );

        $inner = new Node\Expr\Ternary($coalesceLhs, null, new Node\Expr\Array_([
            new Node\Expr\ArrayItem(
                $node->right
            ),
        ]));

        return $this->buildGetFirstElement($inner);
    }

    /**
     * @return Node PHP code to fetch the first element
     */
    private function buildGetFirstElement(Node $node) {
        // This closure does the same thing as `new Node\Name\FullyQualified('reset')`
        // but avoids the notice about taking a reference of a non-reference in php7.0+
        $closureLHSNode = new Node\Expr\Closure(array(
            'params' => array(
                new Node\Param('v1'),
            ),
            'stmts' => array(
                new Node\Stmt\Return_(
                    new Node\Expr\ArrayDimFetch(
                        new Node\Expr\Variable('v1'),
                        new Node\Scalar\LNumber(0)
                    )
                )
            )
        ));
        return new Node\Expr\FuncCall(
            new Node\Name\FullyQualified('call_user_func'),
            array(
                $closureLHSNode,
                $node,
            )
        );
    }
}
