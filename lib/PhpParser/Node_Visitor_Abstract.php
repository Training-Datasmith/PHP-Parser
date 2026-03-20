<?php

declare (strict_types=1);
namespace Php_Parser;

/**
 * Abstract base class for node visitors that provides no-op default implementations.
 *
 * Extend this class instead of implementing {@see NodeVisitor} directly when you only
 * need to override a subset of the four visitor callbacks. All methods return null by
 * default, which instructs the traverser to leave the current node unchanged.
 *
 * @codeCoverageIgnore
 */
abstract class Node_Visitor_Abstract implements Node_Visitor
{
    /**
     * Called once before traversal begins.
     *
     * Override to perform setup or to replace the top-level node array before
     * traversal starts. Return null to keep $nodes unchanged.
     *
     * @param Node[] $nodes Top-level nodes about to be traversed
     *
     * @return null|Node[] Replacement node array, or null to keep the original
     */
    public function before_traverse(array $nodes)
    {
        return null;
    }
    /**
     * Called when the traverser enters a node.
     *
     * Override to inspect or transform nodes on the way down the tree.
     * Return null to leave the node unchanged, or one of the sentinel
     * constants from {@see NodeVisitor} to control traversal.
     *
     * @param Node $node The node being entered
     *
     * @return null|int|Node|Node[] Replacement node, traversal control constant, or null
     */
    public function enter_node(Node $node)
    {
        return null;
    }
    /**
     * Called when the traverser leaves a node.
     *
     * Override to inspect or transform nodes on the way back up the tree,
     * after all children have been visited.
     * Return null to leave the node unchanged, or one of the sentinel
     * constants from {@see NodeVisitor} to control traversal.
     *
     * @param Node $node The node being left
     *
     * @return null|int|Node|Node[] Replacement node, traversal control constant, or null
     */
    public function leave_node(Node $node)
    {
        return null;
    }
    /**
     * Called once after traversal completes.
     *
     * Override to perform post-processing or to replace the top-level node array
     * after all nodes have been visited. Return null to keep $nodes unchanged.
     *
     * @param Node[] $nodes Top-level nodes after traversal
     *
     * @return null|Node[] Replacement node array, or null to keep the original
     */
    public function after_traverse(array $nodes)
    {
        return null;
    }
}