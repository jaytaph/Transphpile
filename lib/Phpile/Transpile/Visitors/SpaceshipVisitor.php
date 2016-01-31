<?php

namespace Phpile\Transpile\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;


/*
 * Converts $a <=> $b into;
 *
 *      call_user_func(
 *          function($v1, $v2)) { if ($v1 == $v2) return 0; return $v1 > $v2 ? 1 : -1 },
 *          $a,
 *          $b
 *      )
 */

class SpaceshipVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if (!$node instanceof Node\Expr\BinaryOp\Spaceship) {
            return null;
        }

        // Create closure node
        $closureNode = new Node\Expr\Closure(array(
            'params' => array(
                new Node\Param('v1'),
                new Node\Param('v2'),
            ),
            'stmts' => array(
                new Node\Stmt\If_(
                    new Node\Expr\BinaryOp\Equal(
                        new Node\Expr\Variable('v1'),
                        new Node\Expr\Variable('v2')
                    ),
                    array('stmts' => array(
                        new Node\Stmt\Return_(
                            new Node\Scalar\LNumber(0)
                        )
                    ))
                ),
                
                new Node\Stmt\Return_(
                    new Node\Expr\Ternary(
                        new Node\Expr\BinaryOp\Greater(
                            new Node\Expr\Variable('v1'),
                            new Node\Expr\Variable('v2')
                        ),
                        new Node\Scalar\Lnumber(1),
                        new Node\Scalar\Lnumber(-1)
                    )
                ),
            )
        ));

        // Create call_user_func() call
        $callUserFuncNode = new Node\Expr\FuncCall(
            new Node\Name('call_user_func'),
            array(
                $closureNode,
                $node->left,
                $node->right,
            )
        );

        return $callUserFuncNode;
    }
}
