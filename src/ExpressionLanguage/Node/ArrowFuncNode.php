<?php

namespace uuf6429\ExpressionLanguage\Node;

use Symfony\Component\ExpressionLanguage\Compiler;
use Symfony\Component\ExpressionLanguage\Node\NameNode;
use Symfony\Component\ExpressionLanguage\Node\Node;
use uuf6429\ExpressionLanguage\SafeCallable;

/**
 * @author Christian Sciberras <christian@sciberras.me>
 *
 * @internal
 */
class ArrowFuncNode extends Node
{
    /**
     * @var SafeCallable
     */
    private static $noopSafeCallable;

    /**
     * @param NameNode[] $parameters
     */
    public function __construct(array $parameters, Node $body = null)
    {
        parent::__construct(
            [
                'parameters' => $parameters,
                'body' => $body,
            ]
        );

        if (!self::$noopSafeCallable) {
            self::$noopSafeCallable = new SafeCallable(static function () {
            });
        }
    }

    public function compile(Compiler $compiler): void
    {
        $arguments = [];

        foreach ($this->nodes['parameters'] as $parameterNode) {
            $arguments[] = $compiler->subcompile($parameterNode);
        }

        $compiler->raw(
            sprintf(
                'function (%s) { return %s; }',
                implode(', ', $arguments),
                $this->nodes['body'] ? $compiler->subcompile($this->nodes['body']) : 'null'
            )
        );
    }

    public function evaluate($functions, $values)
    {
        /** @var Node|null $bodyNode */
        $bodyNode = $this->nodes['body'];

        if (!$bodyNode) {
            return self::$noopSafeCallable;
        }

        $paramNames = [];

        foreach ($this->nodes['parameters'] as $parameterNode) {
            /** @var NameNode $parameterNode */
            $nodeData = $parameterNode->toArray();
            $paramNames[] = $nodeData[0];
        }

        return new SafeCallable(
            static function () use ($functions, $paramNames, $bodyNode) {
                $args = func_get_args();

                if (count($paramNames) > count($args)) {
                    throw new \BadFunctionCallException('Invalid arrow function call: too many parameters.');
                }

                $passedValues = [];
                $argIndex = 0;
                foreach ($paramNames as $paramName) {
                    $passedValues[$paramName] = $args[$argIndex];
                    ++$argIndex;
                }

                return $bodyNode->evaluate($functions, $passedValues);
            }
        );
    }

    public function toArray()
    {
        $array = [];

        foreach ($this->nodes['parameters'] as $node) {
            $array[] = ', ';
            $array[] = $node;
        }
        $array[0] = '(';
        $array[] = ') -> {';
        if ($this->nodes['body']) {
            $array[] = $this->nodes['body'];
        }
        $array[] = '}';

        return $array;
    }
}
