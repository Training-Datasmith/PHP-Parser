<?php

declare (strict_types=1);
namespace Php_Parser\Builder;

use Php_Parser\Builder_Helpers;
use Php_Parser\Node;
abstract class Function_Like extends Declaration
{
    protected bool $return_by_ref = false;
    /** @var Node\Param[] */
    protected array $params = [];
    /** @var Node\Identifier|Node\Name|Node\ComplexType|null */
    protected ?Node $return_type = null;
    /**
     * Make the function return by reference.
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function make_return_by_ref()
    {
        $this->return_by_ref = true;
        return $this;
    }
    /**
     * Adds a parameter.
     *
     * @param Node\Param|Param $param The parameter to add
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function add_param($param)
    {
        $param = Builder_Helpers::normalize_node($param);
        if (!$param instanceof Node\Param) {
            throw new \LogicException(sprintf('Expected parameter node, got "%s"', $param->get_type()));
        }
        $this->params[] = $param;
        return $this;
    }
    /**
     * Adds multiple parameters.
     *
     * @param (Node\Param|Param)[] $params The parameters to add
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function add_params(array $params)
    {
        foreach ($params as $param) {
            $this->add_param($param);
        }
        return $this;
    }
    /**
     * Sets the return type for PHP 7.
     *
     * @param string|Node\Name|Node\Identifier|Node\ComplexType $type
     *
     * @return $this The builder instance (for fluid interface)
     */
    public function set_return_type($type)
    {
        $this->return_type = Builder_Helpers::normalize_type($type);
        return $this;
    }
}