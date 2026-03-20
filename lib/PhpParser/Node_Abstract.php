<?php

declare (strict_types=1);
namespace Php_Parser;

abstract class Node_Abstract implements Node, \JsonSerializable
{
    /** @var array<string, mixed> Attributes */
    protected array $attributes;
    /**
     * Creates a Node.
     *
     * @param array<string, mixed> $attributes Array of attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }
    /**
     * Gets line the node started in (alias of getStartLine).
     *
     * @return int Start line (or -1 if not available)
     * @phpstan-return -1|positive-int
     */
    public function get_line(): int
    {
        return $this->attributes['startLine'] ?? -1;
    }
    /**
     * Gets line the node started in.
     *
     * Requires the 'startLine' attribute to be enabled in the lexer (enabled by default).
     *
     * @return int Start line (or -1 if not available)
     * @phpstan-return -1|positive-int
     */
    public function get_start_line(): int
    {
        return $this->attributes['startLine'] ?? -1;
    }
    /**
     * Gets the line the node ended in.
     *
     * Requires the 'endLine' attribute to be enabled in the lexer (enabled by default).
     *
     * @return int End line (or -1 if not available)
     * @phpstan-return -1|positive-int
     */
    public function get_end_line(): int
    {
        return $this->attributes['endLine'] ?? -1;
    }
    /**
     * Gets the token offset of the first token that is part of this node.
     *
     * The offset is an index into the array returned by Lexer::getTokens().
     *
     * Requires the 'startTokenPos' attribute to be enabled in the lexer (DISABLED by default).
     *
     * @return int Token start position (or -1 if not available)
     */
    public function get_start_token_pos(): int
    {
        return $this->attributes['startTokenPos'] ?? -1;
    }
    /**
     * Gets the token offset of the last token that is part of this node.
     *
     * The offset is an index into the array returned by Lexer::getTokens().
     *
     * Requires the 'endTokenPos' attribute to be enabled in the lexer (DISABLED by default).
     *
     * @return int Token end position (or -1 if not available)
     */
    public function get_end_token_pos(): int
    {
        return $this->attributes['endTokenPos'] ?? -1;
    }
    /**
     * Gets the file offset of the first character that is part of this node.
     *
     * Requires the 'startFilePos' attribute to be enabled in the lexer (DISABLED by default).
     *
     * @return int File start position (or -1 if not available)
     */
    public function get_start_file_pos(): int
    {
        return $this->attributes['startFilePos'] ?? -1;
    }
    /**
     * Gets the file offset of the last character that is part of this node.
     *
     * Requires the 'endFilePos' attribute to be enabled in the lexer (DISABLED by default).
     *
     * @return int File end position (or -1 if not available)
     */
    public function get_end_file_pos(): int
    {
        return $this->attributes['endFilePos'] ?? -1;
    }
    /**
     * Gets all comments directly preceding this node.
     *
     * The comments are also available through the "comments" attribute.
     *
     * @return Comment[]
     */
    public function get_comments(): array
    {
        return $this->attributes['comments'] ?? [];
    }
    /**
     * Gets the doc comment of the node.
     *
     * @return null|Comment\Doc Doc comment object or null
     */
    public function get_doc_comment(): ?Comment\Doc
    {
        $comments = $this->get_comments();
        for ($i = count($comments) - 1; $i >= 0; $i--) {
            $comment = $comments[$i];
            if ($comment instanceof Comment\Doc) {
                return $comment;
            }
        }
        return null;
    }
    /**
     * Sets the doc comment of the node.
     *
     * This will either replace an existing doc comment or add it to the comments array.
     *
     * @param Comment\Doc $docComment Doc comment to set
     */
    public function set_doc_comment(Comment\Doc $doc_comment): void
    {
        $comments = $this->get_comments();
        for ($i = count($comments) - 1; $i >= 0; $i--) {
            if ($comments[$i] instanceof Comment\Doc) {
                // Replace existing doc comment.
                $comments[$i] = $doc_comment;
                $this->set_attribute('comments', $comments);
                return;
            }
        }
        // Append new doc comment.
        $comments[] = $doc_comment;
        $this->set_attribute('comments', $comments);
    }
    /**
     * Sets an attribute on this node.
     *
     * Common attribute keys: 'startLine', 'endLine', 'startFilePos', 'endFilePos',
     * 'startTokenPos', 'endTokenPos', 'comments'.
     *
     * @param string $key   Attribute name
     * @param mixed  $value Attribute value (any type)
     */
    public function set_attribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }
    /**
     * Returns whether an attribute with the given key exists on this node.
     *
     * @param string $key Attribute name to check
     *
     * @return bool True if the attribute is set, false otherwise
     */
    public function has_attribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }
    /**
     * Returns the value of an attribute, or a default if the attribute is not set.
     *
     * @param string $key     Attribute name to retrieve
     * @param mixed  $default Value returned when the attribute is absent (default: null)
     *
     * @return mixed Attribute value, or $default if the attribute does not exist
     */
    public function get_attribute(string $key, $default = null)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }
        return $default;
    }
    /**
     * Returns all attributes set on this node.
     *
     * @return array<string, mixed> Map of attribute name → value
     */
    public function get_attributes(): array
    {
        return $this->attributes;
    }
    /**
     * Replaces all attributes on this node with the given map.
     *
     * This overwrites any previously set attributes, including source position
     * and comment data. Use with care.
     *
     * @param array<string, mixed> $attributes New attribute map
     */
    public function set_attributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }
    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return ['nodeType' => $this->get_type()] + get_object_vars($this);
    }
}