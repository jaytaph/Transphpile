<?php

namespace Transphpile\Transpile\Visitors\Php70;

use Transphpile\Transpile\Exception\TranspileException;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/*
 * converts closure::call into
 *
 */

class ClosureCallVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        // Trigger on function call "unserialize"
        if (!$node instanceof Node\Expr\MethodCall || $node->name != "call") {
            return null;
        }



/*
         echo call_user_func(function($a, $arg1) use ($c) {
         Èáááááááif ($c instanceOf Closure) {
         ÈáááááááÈááááááá$tmp = $c->bindTo($a, get_class($a));
         ÈáááááááÈáááááááreturn $tmp($arg1);
         Èááááááá} else {
         ÈáááááááÈáááááááreturn $c->call($a, $arg1);
         Èááááááá}
         }, $four, 3);
*/

//        var_dump($node->args);

        print $node->args[0]->value->name;
        print $node->args[1]->value->value;

        $closureNode = new Node\Expr\Closure(array(
            'params' => array(
                new Node\Param('a'),
                new Node\Param('arg1'),
            ),
            'uses' => array(
                new Node\Param('c')
            ),
            'stmts' => array(
                new Node\Stmt\If_(
                    new Node\Expr\Instanceof_(new Node\Expr\Variable('c'), new Node\Name('Closure')),
                    array(
                        'stmts' => array(
                            new Node\Expr\Assign(
                                new Node\Expr\Variable('tmp'),
                                new Node\Expr\MethodCall(
                                    new Node\Expr\Variable('c'),
                                    'bindTo',
                                    array(
                                        new Node\Expr\Variable('a'),
                                        new Node\Expr\FuncCall(
                                            new Node\Name('get_class'), array(
                                            new Node\Expr\Variable('a'),
                                        ))
                                    )
                                )
                            ),
                            new Node\Stmt\Return_(
                                new Node\Expr\FuncCall(
                                    new Node\Expr\Variable('tmp'),
                                    array(
                                        new Node\Expr\Variable('arg1'),
                                    )
                                )
                            ),
                        ),
                        'else' => new Node\Stmt\Else_(array(
                            new Node\Stmt\Return_(
                                new Node\Expr\MethodCall(
                                    new Node\Expr\Variable('c'),
                                    'call',
                                    array(
                                        new Node\Expr\Variable('a'),
                                        new Node\Expr\Variable('arg1'),
                                    )
                                )
                            ),
                        )),
                    )
                ),
            )
        ));

        $args = $node->args;
        array_unshift($args, new Node\Arg($closureNode));

        $callUserFuncNode = new Node\Expr\FuncCall(
            new Node\Name('call_user_func'),
            $args
        );

        return $callUserFuncNode;
    }
}
