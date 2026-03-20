<?php

declare (strict_types=1);
namespace Php_Parser;

use Php_Parser\Node\Expr\Array_;
use Php_Parser\Node\Expr\Include_;
use Php_Parser\Node\Expr\List_;
use Php_Parser\Node\Scalar\Int_;
use Php_Parser\Node\Scalar\Interpolated_String;
use Php_Parser\Node\Scalar\String_;
use Php_Parser\Node\Stmt\Group_Use;
use Php_Parser\Node\Stmt\Use_;
use Php_Parser\Node\Use_Item;
class Node_Dumper
{
    private bool $dump_comments;
    private bool $dump_positions;
    private bool $dump_other_attributes;
    private ?string $code = null;
    private string $res;
    private string $nl;
    private const IGNORE_ATTRIBUTES = ['comments' => true, 'startLine' => true, 'endLine' => true, 'startFilePos' => true, 'endFilePos' => true, 'startTokenPos' => true, 'endTokenPos' => true];
    /**
     * Constructs a NodeDumper.
     *
     * Supported options:
     *  * bool dumpComments: Whether comments should be dumped.
     *  * bool dumpPositions: Whether line/offset information should be dumped. To dump offset
     *                        information, the code needs to be passed to dump().
     *  * bool dumpOtherAttributes: Whether non-comment, non-position attributes should be dumped.
     *
     * @param array $options Options (see description)
     */
    public function __construct(array $options = [])
    {
        $this->dump_comments = !empty($options['dumpComments']);
        $this->dump_positions = !empty($options['dumpPositions']);
        $this->dump_other_attributes = !empty($options['dumpOtherAttributes']);
    }
    /**
     * Dumps a node or array.
     *
     * @param array|Node $node Node or array to dump
     * @param string|null $code Code corresponding to dumped AST. This only needs to be passed if
     *                          the dumpPositions option is enabled and the dumping of node offsets
     *                          is desired.
     *
     * @return string Dumped value
     */
    public function dump($node, ?string $code = null): string
    {
        $this->code = $code;
        $this->res = '';
        $this->nl = "\n";
        $this->dump_recursive($node, false);
        return $this->res;
    }
    /** @param mixed $node */
    protected function dump_recursive($node, bool $indent = true): void
    {
        if ($indent) {
            $this->nl .= '    ';
        }
        if ($node instanceof Node) {
            $this->res .= $node->get_type();
            if ($this->dump_positions && null !== $p = $this->dump_position($node)) {
                $this->res .= $p;
            }
            $this->res .= '(';
            foreach ($node->get_sub_node_names() as $key) {
                $this->res .= "{$this->nl}    " . $key . ': ';
                $value = $node->{$key};
                if (\is_int($value)) {
                    if ('flags' === $key || 'newModifier' === $key) {
                        $this->res .= $this->dump_flags($value);
                        continue;
                    }
                    if ('type' === $key && $node instanceof Include_) {
                        $this->res .= $this->dump_include_type($value);
                        continue;
                    }
                    if ('type' === $key && ($node instanceof Use_ || $node instanceof Use_Item || $node instanceof Group_Use)) {
                        $this->res .= $this->dump_use_type($value);
                        continue;
                    }
                }
                $this->dump_recursive($value);
            }
            if ($this->dump_comments && $comments = $node->get_comments()) {
                $this->res .= "{$this->nl}    comments: ";
                $this->dump_recursive($comments);
            }
            if ($this->dump_other_attributes) {
                foreach ($node->get_attributes() as $key => $value) {
                    if (isset(self::IGNORE_ATTRIBUTES[$key])) {
                        continue;
                    }
                    $this->res .= "{$this->nl}    {$key}: ";
                    if (\is_int($value)) {
                        if ('kind' === $key) {
                            if ($node instanceof Int_) {
                                $this->res .= $this->dump_int_kind($value);
                                continue;
                            }
                            if ($node instanceof String_ || $node instanceof Interpolated_String) {
                                $this->res .= $this->dump_string_kind($value);
                                continue;
                            }
                            if ($node instanceof Array_) {
                                $this->res .= $this->dump_array_kind($value);
                                continue;
                            }
                            if ($node instanceof List_) {
                                $this->res .= $this->dump_list_kind($value);
                                continue;
                            }
                        }
                    }
                    $this->dump_recursive($value);
                }
            }
            $this->res .= "{$this->nl})";
        } elseif (\is_array($node)) {
            $this->res .= 'array(';
            foreach ($node as $key => $value) {
                $this->res .= "{$this->nl}    " . $key . ': ';
                $this->dump_recursive($value);
            }
            $this->res .= "{$this->nl})";
        } elseif ($node instanceof Comment) {
            $this->res .= \str_replace("\n", $this->nl, $node->get_reformatted_text());
        } elseif (\is_string($node)) {
            $this->res .= \str_replace("\n", $this->nl, $node);
        } elseif (\is_int($node) || \is_float($node)) {
            $this->res .= $node;
        } elseif (null === $node) {
            $this->res .= 'null';
        } elseif (false === $node) {
            $this->res .= 'false';
        } elseif (true === $node) {
            $this->res .= 'true';
        } else {
            throw new \InvalidArgumentException('Can only dump nodes and arrays.');
        }
        if ($indent) {
            $this->nl = \substr($this->nl, 0, -4);
        }
    }
    protected function dump_flags(int $flags): string
    {
        $strs = [];
        if ($flags & Modifiers::PUBLIC) {
            $strs[] = 'PUBLIC';
        }
        if ($flags & Modifiers::PROTECTED) {
            $strs[] = 'PROTECTED';
        }
        if ($flags & Modifiers::PRIVATE) {
            $strs[] = 'PRIVATE';
        }
        if ($flags & Modifiers::ABSTRACT) {
            $strs[] = 'ABSTRACT';
        }
        if ($flags & Modifiers::STATIC) {
            $strs[] = 'STATIC';
        }
        if ($flags & Modifiers::FINAL) {
            $strs[] = 'FINAL';
        }
        if ($flags & Modifiers::READONLY) {
            $strs[] = 'READONLY';
        }
        if ($flags & Modifiers::PUBLIC_SET) {
            $strs[] = 'PUBLIC_SET';
        }
        if ($flags & Modifiers::PROTECTED_SET) {
            $strs[] = 'PROTECTED_SET';
        }
        if ($flags & Modifiers::PRIVATE_SET) {
            $strs[] = 'PRIVATE_SET';
        }
        if ($strs) {
            return implode(' | ', $strs) . ' (' . $flags . ')';
        }
        return (string) $flags;
    }
    /** @param array<int, string> $map */
    private function dump_enum(int $value, array $map): string
    {
        if (!isset($map[$value])) {
            return (string) $value;
        }
        return $map[$value] . ' (' . $value . ')';
    }
    private function dump_include_type(int $type): string
    {
        return $this->dump_enum($type, [Include_::TYPE_INCLUDE => 'TYPE_INCLUDE', Include_::TYPE_INCLUDE_ONCE => 'TYPE_INCLUDE_ONCE', Include_::TYPE_REQUIRE => 'TYPE_REQUIRE', Include_::TYPE_REQUIRE_ONCE => 'TYPE_REQUIRE_ONCE']);
    }
    private function dump_use_type(int $type): string
    {
        return $this->dump_enum($type, [Use_::TYPE_UNKNOWN => 'TYPE_UNKNOWN', Use_::TYPE_NORMAL => 'TYPE_NORMAL', Use_::TYPE_FUNCTION => 'TYPE_FUNCTION', Use_::TYPE_CONSTANT => 'TYPE_CONSTANT']);
    }
    private function dump_int_kind(int $kind): string
    {
        return $this->dump_enum($kind, [Int_::KIND_BIN => 'KIND_BIN', Int_::KIND_OCT => 'KIND_OCT', Int_::KIND_DEC => 'KIND_DEC', Int_::KIND_HEX => 'KIND_HEX']);
    }
    private function dump_string_kind(int $kind): string
    {
        return $this->dump_enum($kind, [String_::KIND_SINGLE_QUOTED => 'KIND_SINGLE_QUOTED', String_::KIND_DOUBLE_QUOTED => 'KIND_DOUBLE_QUOTED', String_::KIND_HEREDOC => 'KIND_HEREDOC', String_::KIND_NOWDOC => 'KIND_NOWDOC']);
    }
    private function dump_array_kind(int $kind): string
    {
        return $this->dump_enum($kind, [Array_::KIND_LONG => 'KIND_LONG', Array_::KIND_SHORT => 'KIND_SHORT']);
    }
    private function dump_list_kind(int $kind): string
    {
        return $this->dump_enum($kind, [List_::KIND_LIST => 'KIND_LIST', List_::KIND_ARRAY => 'KIND_ARRAY']);
    }
    /**
     * Dump node position, if possible.
     *
     * @param Node $node Node for which to dump position
     *
     * @return string|null Dump of position, or null if position information not available
     */
    protected function dump_position(Node $node): ?string
    {
        if (!$node->has_attribute('startLine') || !$node->has_attribute('endLine')) {
            return null;
        }
        $start = $node->get_start_line();
        $end = $node->get_end_line();
        if ($node->has_attribute('startFilePos') && $node->has_attribute('endFilePos') && null !== $this->code) {
            $start .= ':' . $this->to_column($this->code, $node->get_start_file_pos());
            $end .= ':' . $this->to_column($this->code, $node->get_end_file_pos());
        }
        return "[{$start} - {$end}]";
    }
    // Copied from Error class
    private function to_column(string $code, int $pos): int
    {
        if ($pos > strlen($code)) {
            throw new \RuntimeException('Invalid position information');
        }
        $line_start_pos = strrpos($code, "\n", $pos - strlen($code));
        if (false === $line_start_pos) {
            $line_start_pos = -1;
        }
        return $pos - $line_start_pos;
    }
}