<?php
declare(strict_types=1);

namespace PhpParser;

use JsonSerializable;

abstract class NodeAbstract implements Node, JsonSerializable
{
    protected $attributes;

    /**
     * Creates a Node.
     *
     * @param array $attributes Array of attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function getAttribute(string $key, $default = null)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return $default;
    }    /**
     * Gets line the node started in (alias of getStartLine).
     *
     * @return int Start line (or -1 if not available)
     */
    public function getLine(): int
    {
        return $this->attributes['startLine'] ?? -1;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }    /**
     * Gets line the node started in.
     *
     * Requires the 'startLine' attribute to be enabled in the lexer (enabled by default).
     *
     * @return int Start line (or -1 if not available)
     */
    public function getStartLine(): int
    {
        return $this->attributes['startLine'] ?? -1;
    }

    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }    /**
     * Gets the line the node ended in.
     *
     * Requires the 'endLine' attribute to be enabled in the lexer (enabled by default).
     *
     * @return int End line (or -1 if not available)
     */
    public function getEndLine(): int
    {
        return $this->attributes['endLine'] ?? -1;
    }

    /**
     * Sets the doc comment of the node.
     *
     * This will either replace an existing doc comment or add it to the comments array.
     *
     * @param Comment\Doc $docComment Doc comment to set
     */
    public function setDocComment(Comment\Doc $docComment)
    {
        $comments = $this->getComments();

        $numComments = count($comments);
        if ($numComments > 0 && $comments[$numComments - 1] instanceof Comment\Doc) {
            // Replace existing doc comment
            $comments[$numComments - 1] = $docComment;
        } else {
            // Append new comment
            $comments[] = $docComment;
        }

        $this->setAttribute('comments', $comments);
    }    /**
     * Gets the token offset of the first token that is part of this node.
     *
     * The offset is an index into the array returned by Lexer::getTokens().
     *
     * Requires the 'startTokenPos' attribute to be enabled in the lexer (DISABLED by default).
     *
     * @return int Token start position (or -1 if not available)
     */
    public function getStartTokenPos(): int
    {
        return $this->attributes['startTokenPos'] ?? -1;
    }

    public function setAttribute(string $key, $value)
    {
        $this->attributes[$key] = $value;
    }    /**
     * Gets the token offset of the last token that is part of this node.
     *
     * The offset is an index into the array returned by Lexer::getTokens().
     *
     * Requires the 'endTokenPos' attribute to be enabled in the lexer (DISABLED by default).
     *
     * @return int Token end position (or -1 if not available)
     */
    public function getEndTokenPos(): int
    {
        return $this->attributes['endTokenPos'] ?? -1;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return ['nodeType' => $this->getType()] + get_object_vars($this);
    }    /**
     * Gets the file offset of the first character that is part of this node.
     *
     * Requires the 'startFilePos' attribute to be enabled in the lexer (DISABLED by default).
     *
     * @return int File start position (or -1 if not available)
     */
    public function getStartFilePos(): int
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
    public function getEndFilePos(): int
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
    public function getComments(): array
    {
        return $this->attributes['comments'] ?? [];
    }

    /**
     * Gets the doc comment of the node.
     *
     * The doc comment has to be the last comment associated with the node.
     *
     * @return null|Comment\Doc Doc comment object or null
     */
    public function getDocComment()
    {
        $comments = $this->getComments();
        if (!$comments) {
            return null;
        }

        $lastComment = $comments[count($comments) - 1];
        if (!$lastComment instanceof Comment\Doc) {
            return null;
        }

        return $lastComment;
    }





    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }








}
