<?php

declare (strict_types=1);
namespace Php_Parser;

class Json_Decoder
{
    /** @var \ReflectionClass<Node>[] Node type to reflection class map */
    private array $reflection_class_cache;
    /** @return mixed */
    public function decode(string $json)
    {
        $value = json_decode($json, true);
        if (json_last_error()) {
            throw new \RuntimeException('JSON decoding error: ' . json_last_error_msg());
        }
        return $this->decode_recursive($value);
    }
    /**
     * @param mixed $value
     * @return mixed
     */
    private function decode_recursive($value)
    {
        if (\is_array($value)) {
            if (isset($value['nodeType'])) {
                if ($value['nodeType'] === 'Comment' || $value['nodeType'] === 'Comment_Doc') {
                    return $this->decode_comment($value);
                }
                return $this->decode_node($value);
            }
            return $this->decode_array($value);
        }
        return $value;
    }
    private function decode_array(array $array): array
    {
        $decoded_array = [];
        foreach ($array as $key => $value) {
            $decoded_array[$key] = $this->decode_recursive($value);
        }
        return $decoded_array;
    }
    private function decode_node(array $value): Node
    {
        $node_type = $value['nodeType'];
        if (!\is_string($node_type)) {
            throw new \RuntimeException('Node type must be a string');
        }
        $reflection_class = $this->reflection_class_from_node_type($node_type);
        $node = $reflection_class->new_instance_without_constructor();
        if (isset($value['attributes'])) {
            if (!\is_array($value['attributes'])) {
                throw new \RuntimeException('Attributes must be an array');
            }
            $node->set_attributes($this->decode_array($value['attributes']));
        }
        foreach ($value as $name => $sub_node) {
            if ($name === 'nodeType') {
                continue;
            }
            if ($name === 'attributes') {
                continue;
            }
            $node->{$name} = $this->decode_recursive($sub_node);
        }
        return $node;
    }
    private function decode_comment(array $value): Comment
    {
        $class_name = $value['nodeType'] === 'Comment' ? Comment::class : Comment\Doc::class;
        if (!isset($value['text'])) {
            throw new \RuntimeException('Comment must have text');
        }
        return new $class_name($value['text'], $value['line'] ?? -1, $value['filePos'] ?? -1, $value['tokenPos'] ?? -1, $value['endLine'] ?? -1, $value['endFilePos'] ?? -1, $value['endTokenPos'] ?? -1);
    }
    /** @return \ReflectionClass<Node> */
    private function reflection_class_from_node_type(string $node_type): \ReflectionClass
    {
        if (!isset($this->reflection_class_cache[$node_type])) {
            $class_name = $this->class_name_from_node_type($node_type);
            $reflection_class = new \ReflectionClass($class_name);
            if (!$reflection_class->is_subclass_of(Node::class)) {
                throw new \RuntimeException("Class \"{$class_name}\" for node type \"{$node_type}\" is not a subclass of Node");
            }
            $this->reflection_class_cache[$node_type] = $reflection_class;
        }
        return $this->reflection_class_cache[$node_type];
    }
    /** @return class-string<Node> */
    private function class_name_from_node_type(string $node_type): string
    {
        $class_name = 'PhpParser\Node\\' . strtr($node_type, '_', '\\');
        if (class_exists($class_name)) {
            return $class_name;
        }
        $class_name .= '_';
        if (class_exists($class_name)) {
            return $class_name;
        }
        throw new \RuntimeException("Unknown node type \"{$node_type}\"");
    }
}