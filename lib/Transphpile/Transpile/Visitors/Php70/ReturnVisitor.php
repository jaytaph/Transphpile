<?php

namespace Transphpile\Transpile\Visitors\Php70;

use Transphpile\Transpile\NodeStateStack;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

/*
 * Check if returned values are correctly typed if source is set to strict
 */

class ReturnVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if (!$node instanceof Node\Stmt\Return_) {
            return null;
        }

        $functionNode = NodeStateStack::getInstance()->end('currentFunction');

        // No functionNode means we are doing a return in the global scope
        if (! $functionNode) {
            return null;
        }

        $functionNode = $functionNode['node'];

        // Check return type of current function
        if ($functionNode->returnType == null) {
            return null;
        }

        // Not strict, so no need to check return type;
        if (! NodeStateStack::getInstance()->get('isStrict')) {
            return null;
        }

        // Define uniq retvar for returning, most likely not needed but done to make sure we don't
        // hit any existing variables or multiple return vars
        $retVar = 'ret'.uniqid();

        // Generate code for "$retVar = <originalExpression>"
        $retNode = new Node\Expr\Assign(
            new Node\Expr\Variable($retVar),
            $node->expr
        );

        // Generate remainder code

        $isNullable = false;
        $returnTypeNode = $functionNode->returnType;
        if ($returnTypeNode instanceof Node\NullableType) {
            $returnTypeNode = $returnTypeNode->type;
            $isNullable = true;
        }
        if ($isNullable) {
            $nullCheck = '$'.$retVar.' !== null && ';
        } else {
            $nullCheck = '';
        }
        $returnType = (string)$returnTypeNode;
        // Manually add starting namespace separator for FQCN
        if ($functionNode->returnType instanceof Node\Name\FullyQualified && $returnType[0] != '\\') {
            $returnType = '\\' . $returnType;
        }

        // @TODO: It might be easier to read when we generate ALL code directly from Nodes instead of generating it

        if (in_array(strtolower($returnType), array('string', 'bool', 'int', 'float', 'array'), true)) {
            // Scalars are treated a bit different
            $code = sprintf(
                '<?php '."\n".
                '  if ('.$nullCheck.'! is_%s($'.$retVar.')) { '."\n".
                '    throw new \InvalidArgumentException("Argument returned must be of the type %s, ".gettype($'.$retVar.')." given"); '."\n".
                '  } '."\n".
                '  return $'.$retVar.'; ',
                $returnType, $returnType
            );
        } else {
            // Otherwise use instanceof for check against classes
            $code = sprintf(
                '<?php '."\n".
                '  if ('.$nullCheck.' ! $'.$retVar.' instanceof %s) { '."\n".
                '    throw new \InvalidArgumentException("Argument returned must be of the type %s, ".(gettype($'.$retVar.') == "object" ? get_class($'.$retVar.') : gettype($'.$retVar.'))." given"); '."\n".
                '  } '."\n".
                '  return $'.$retVar.'; ',
                $returnType, $returnType
            );
        }

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $stmts = $parser->parse($code);

        // Merge $retVar = <expr> with remainder code
        $stmts = array_merge(array($retNode), $stmts);

        return $stmts;
    }
}
