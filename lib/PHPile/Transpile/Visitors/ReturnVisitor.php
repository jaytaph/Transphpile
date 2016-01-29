<?php

namespace PHPile\Transpile\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

class ReturnVisitor extends NodeVisitorAbstract
{

    public function leaveNode(Node $node)
    {
        if (! $node instanceof Node\Stmt\Return_) {
            return null;
        }

        global $functionStack;
        if (count($functionStack) == 0) {
            // return in global scope
            return null;
        }
        $functionNode = $functionStack[count($functionStack)-1];

        if ($functionNode->returnType == null) {
            return null;
        }

        // Define uniq retvar for returning, most likely not needed but done to make sure we don't
        // hit any existing variables or multiple return vars
        $retVar = '$ret' . uniqid(true);

        // Generate code for "$retVar = <originalExpression>"
        $retNode = new Node\Expr\Assign(
            new Node\Expr\Variable($retVar),
            $node->expr
        );

        // Generate remainder code
        $code = sprintf(
            '<?php ' . "\n".
            '  if (! is_%s('.$retVar.')) { ' ."\n".
            '    throw new \InvalidArgumentException("Argument returned must be of the type %s, ".get_class('.$retVar.')." given"); ' ."\n".
            '  } ' ."\n".
            '  return '.$retVar.'; ',
            $functionNode->returnType, $functionNode->returnType
        );
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $stmts = $parser->parse($code);

        // Merge $retVar = <expr> with remainder code
        $stmts = array_merge(array($retNode), $stmts);

        return $stmts;
    }

}
