<?php

declare(strict_types=1);

namespace Php_Parser;

class Node_Visitor_For_Testing implements Node_Visitor
{
    public $trace = [];
    private $returns;
    private $returnsPos;

    public function __construct(array $returns = [])
    {
        $this->returns = $returns;
        $this->returnsPos = 0;
    }

    public function before_traverse(array $nodes): ?array
    {
        return $this->traceEvent('before_traverse', $nodes);
    }

    public function enter_node(Node $node)
    {
        return $this->traceEvent('enter_node', $node);
    }

    public function leave_node(Node $node)
    {
        return $this->traceEvent('leave_node', $node);
    }

    public function after_traverse(array $nodes): ?array
    {
        return $this->traceEvent('after_traverse', $nodes);
    }

    private function traceEvent(string $method, $param)
    {
        $this->trace[] = [$method, $param];
        if ($this->returnsPos < count($this->returns)) {
            $currentReturn = $this->returns[$this->returnsPos];
            if ($currentReturn[0] === $method && $currentReturn[1] === $param) {
                $this->returnsPos++;
                return $currentReturn[2];
            }
        }
        return null;
    }

    public function __destruct()
    {
        if ($this->returnsPos !== count($this->returns)) {
            throw new \Exception('Expected event did not occur');
        }
    }
}
