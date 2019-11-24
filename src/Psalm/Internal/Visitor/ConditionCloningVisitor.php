<?php
declare(strict_types=1);
namespace Psalm\Internal\Visitor;

use function array_map;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ConditionCloningVisitor extends NodeVisitorAbstract
{
    private $type_provider;

    public function __construct(\Psalm\Type\Provider $old_type_provider)
    {
        $this->type_provider = $old_type_provider;
    }

    public function enterNode(Node $origNode)
    {
        /** @var \PhpParser\Node\Expr $origNode */
        $node = clone $origNode;

        $node_type = $this->type_provider->getNodeType($origNode);

        if ($node_type) {
            $this->type_provider->setNodeType($node, clone $node_type);
        }

        return $node;
    }
}
