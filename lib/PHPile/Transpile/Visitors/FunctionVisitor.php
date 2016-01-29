<?php

namespace PHPile\Transpile\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

class FunctionVisitor extends NodeVisitorAbstract
{
    public function enterNode(Node $node)
    {
        if (! $node instanceof Node\Stmt\Function_) return;

        global $functionStack;
        print "Stacking node '".$node->name."'\n";
        $functionStack[] = clone $node;
    }

    public function leaveNode(Node $node) {
        if (! $node instanceof Node\Stmt\Function_) return;

        global $functionStack;
        array_pop($functionStack);

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


        // Add code for checking scalar types
        foreach ($params as $param) {
            $code = sprintf(
                    '<?php if (! is_%s($%s)) { throw new \InvalidArgumentException("Argument $%s passed to %s() must be of the type %s, ".get_class($%s)." given"); }',
                    $param['type'], $param['arg'], $param['arg'], $param['func'], $param['type'], $param['arg']
            );

            $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
            $stmts = $parser->parse($code);

            $node->stmts = array_merge($stmts, $node->stmts);
        }

    }
}
