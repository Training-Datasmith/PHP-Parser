<?php

declare (strict_types=1);
namespace Php_Parser\Node_Visitor;

use Php_Parser\Comment;
use Php_Parser\Node;
use Php_Parser\Node_Visitor_Abstract;
use Php_Parser\Token;
class Comment_Annotating_Visitor extends Node_Visitor_Abstract
{
    /** @var int Last seen token start position */
    private int $pos = 0;
    /** @var Token[] Token array */
    private array $tokens;
    /** @var list<int> Token positions of comments */
    private array $comment_positions = [];
    /**
     * Create a comment annotation visitor.
     *
     * @param Token[] $tokens Token array
     */
    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
        // Collect positions of comments. We use this to avoid traversing parts of the AST where
        // there are no comments.
        foreach ($tokens as $i => $token) {
            if ($token->id === \T_COMMENT || $token->id === \T_DOC_COMMENT) {
                $this->comment_positions[] = $i;
            }
        }
    }
    public function enter_node(Node $node)
    {
        $next_comment_pos = current($this->comment_positions);
        if ($next_comment_pos === false) {
            // No more comments.
            return self::STOP_TRAVERSAL;
        }
        $old_pos = $this->pos;
        $this->pos = $pos = $node->get_start_token_pos();
        if ($next_comment_pos > $old_pos && $next_comment_pos < $pos) {
            $comments = [];
            while (--$pos >= $old_pos) {
                $token = $this->tokens[$pos];
                if ($token->id === \T_DOC_COMMENT) {
                    $comments[] = new Comment\Doc($token->text, $token->line, $token->pos, $pos, $token->get_end_line(), $token->get_end_pos() - 1, $pos);
                    continue;
                }
                if ($token->id === \T_COMMENT) {
                    $comments[] = new Comment($token->text, $token->line, $token->pos, $pos, $token->get_end_line(), $token->get_end_pos() - 1, $pos);
                    continue;
                }
                if ($token->id !== \T_WHITESPACE) {
                    break;
                }
            }
            if (!empty($comments)) {
                $node->set_attribute('comments', array_reverse($comments));
            }
            do {
                $next_comment_pos = next($this->comment_positions);
            } while ($next_comment_pos !== false && $next_comment_pos < $this->pos);
        }
        $end_pos = $node->get_end_token_pos();
        if ($next_comment_pos > $end_pos) {
            // Skip children if there are no comments located inside this node.
            $this->pos = $end_pos;
            return self::DONT_TRAVERSE_CHILDREN;
        }
        return null;
    }
}