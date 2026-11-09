<?php

declare (strict_types=1);
namespace Php_Parser;

use Php_Parser\Parser\Php7;
use Php_Parser\Parser\Php8;
class Parser_Factory
{
    /**
     * Create a parser targeting the given version on a best-effort basis. The parser will generally
     * accept code for the newest supported version, but will try to accommodate code that becomes
     * invalid in newer versions or changes in interpretation.
     */
    public function create_for_version(Php_Version $version): Parser
    {
        if ($version->is_host_version()) {
            $lexer = new Lexer();
        } else {
            $lexer = new Lexer\Emulative($version);
        }
        if ($version->id >= 80000) {
            return new Php8($lexer, $version);
        }
        return new Php7($lexer, $version);
    }
    /**
     * Create a parser targeting the newest version supported by this library. Code for older
     * versions will be accepted if there have been no relevant backwards-compatibility breaks in
     * PHP.
     */
    public function create_for_newest_supported_version(): Parser
    {
        return $this->create_for_version(Php_Version::get_newest_supported());
    }
    /**
     * Create a parser targeting the host PHP version, that is the PHP version we're currently
     * running on. This parser will not use any token emulation.
     */
    public function create_for_host_version(): Parser
    {
        return $this->create_for_version(Php_Version::get_host_version());
    }
}