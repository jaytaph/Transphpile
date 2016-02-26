<?php

namespace Transphpile\Transpile\Visitors\Php70;

use Transphpile\Transpile\Exception\TranspileException;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/*
 * converts closure::call into another closure that dynamically checks
 * if bindTo needs to be called, or the regular call() is needed.
 *
 *
 *      echo $closure->call($three, 4);
 *
 * into:
 *
 *      echo
 *          call_user_func(function($arg1, $arg2) use ($closure) {
 *              $tmp_var = $closure;
 *             call_user_func(function($arg1, $arg2) use ($tmp_var) {
 *             if ($closure instanceOf Closure) {
 *                  $tmp = $tmp_var->bindTo($arg1, get_class($arg1));
 *                  return $tmp($arg2);
 *              } else {
 *                  return $tmp_var->call($arg1, $arg2);
 *              }
 *          }, $three, 4);
 */

class ClosureCallVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        // Trigger on function call "call"
        if (!$node instanceof Node\Expr\MethodCall || $node->name != "call") {
            return null;
        }

        $tmpClosureVar = "closureCall_".uniqid();

        // Set the correct number of params, naming them arg1..argN
        $params = array();
        $funcCallParams = array();
        for ($i=0; $i<count($node->args); $i++) {
            $params[] = new Node\Param('arg'.($i+1));
            $funcCallParams[] = new Node\Expr\Variable('arg'.($i+1));
        }


        $closureNode = new Node\Expr\Closure(array(
            'params' => $params,
            'uses' => array(
                new Node\Param($tmpClosureVar)
            ),
            'stmts' => array(
                new Node\Stmt\If_(
                    new Node\Expr\Instanceof_(new Node\Expr\Variable($tmpClosureVar), new Node\Name('\Closure')),
                    array(
                        'stmts' => array(
                            new Node\Expr\Assign(
                                new Node\Expr\Variable('tmp'),
                                new Node\Expr\MethodCall(
                                    new Node\Expr\Variable($tmpClosureVar),
                                    'bindTo',
                                    array(
                                        new Node\Expr\Variable('arg1'),
                                        new Node\Expr\FuncCall(
                                            new Node\Name('get_class'),
                                            array(
                                                new Node\Expr\Variable('arg1'),
                                            )
                                        )
                                    )
                                )
                            ),
                            new Node\Stmt\Return_(
                                new Node\Expr\FuncCall(
                                    new Node\Expr\Variable('tmp'),
                                    $funcCallParams
                                )
                            ),
                        ),
                        'else' => new Node\Stmt\Else_(array(
                            new Node\Stmt\Return_(
                                new Node\Expr\MethodCall(
                                    new Node\Expr\Variable($tmpClosureVar),
                                    'call',
                                    $funcCallParams
                                )
                            ),
                        )),
                    )
                ),
            )
        ));


        // Assigns the closure to a temporary var so we can add it to the closure 'use' list.
        $assignNode = new Node\Expr\Assign(
            new Node\Expr\Variable($tmpClosureVar),
            $node->var
        );


        // Actual call_user_func that calls our new defined closure
        $args = $node->args;
        array_unshift($args, new Node\Arg($closureNode));
        $callUserFuncNode = new Node\Expr\FuncCall(
            new Node\Name('call_user_func'),
            $args
        );

        return array($assignNode, $callUserFuncNode);
    }
}
