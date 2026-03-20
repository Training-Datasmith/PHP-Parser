<?php

declare (strict_types=1);
namespace Php_Parser\Node\Name;

class Relative extends \Php_Parser\Node\Name
{
    /**
     * Checks whether the name is unqualified. (E.g. Name)
     *
     * @return bool Whether the name is unqualified
     */
    public function is_unqualified(): bool
    {
        return false;
    }
    /**
     * Checks whether the name is qualified. (E.g. Name\Name)
     *
     * @return bool Whether the name is qualified
     */
    public function is_qualified(): bool
    {
        return false;
    }
    /**
     * Checks whether the name is fully qualified. (E.g. \Name)
     *
     * @return bool Whether the name is fully qualified
     */
    public function is_fully_qualified(): bool
    {
        return false;
    }
    /**
     * Checks whether the name is explicitly relative to the current namespace. (E.g. namespace\Name)
     *
     * @return bool Whether the name is relative
     */
    public function is_relative(): bool
    {
        return true;
    }
    public function to_code_string(): string
    {
        return 'namespace\\' . $this->to_string();
    }
    public function get_type(): string
    {
        return 'Name_Relative';
    }
}