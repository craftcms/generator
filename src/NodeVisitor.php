<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\generator;

use PhpParser\Node;
use PhpParser\NodeVisitor as NodeVisitorInterface;

/**
 * Node Visitor for PHP Parser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class NodeVisitor implements NodeVisitorInterface
{
    /** @var callable|null */
    private $beforeTraverseFn;
    /** @var callable|null */
    private $enterNodeFn;
    /** @var callable|null */
    private $leaveNodeFn;
    /** @var callable|null */
    private $afterTraverseFn;

    public function __construct(
        $beforeTraverse = null,
        $enterNode = null,
        $leaveNode = null,
        $afterTraverse = null,
    ) {
        $this->beforeTraverseFn = $beforeTraverse;
        $this->enterNodeFn = $enterNode;
        $this->leaveNodeFn = $leaveNode;
        $this->afterTraverseFn = $afterTraverse;
    }

    public function beforeTraverse(array $nodes)
    {
        return $this->beforeTraverseFn ? call_user_func($this->beforeTraverseFn, $nodes) : null;
    }

    public function enterNode(Node $node)
    {
        return $this->enterNodeFn ? call_user_func($this->enterNodeFn, $node) : null;
    }

    public function leaveNode(Node $node)
    {
        return $this->leaveNodeFn ? call_user_func($this->leaveNodeFn, $node) : null;
    }

    public function afterTraverse(array $nodes)
    {
        return $this->afterTraverseFn ? call_user_func($this->afterTraverseFn, $nodes) : null;
    }
}
