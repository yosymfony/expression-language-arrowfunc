<?php

namespace uuf6429\ExpressionLanguage;

use RuntimeException;

/**
 * A wrapper for an anonymous function.
 * We do not return anonymous functions directly for security reason, to avoid
 * calling arbitrary functions by returning arrays containing class/method or
 * string function names. From the userland, one can still get access to the
 * anonymous function using the various public methods.
 *
 * @author Christian Sciberras <christian@sciberras.me>
 */
class SafeCallable
{
    protected $callback;

    /**
     * Constructor.
     *
     * @param callable $callback The target callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function getCallback(): callable
    {
        return $this->callback;
    }

    /**
     * Call the callback with the provided arguments and returns result.
     *
     * @return mixed
     */
    public function call()
    {
        return $this->callArray(func_get_args());
    }

    /**
     * Call the callback with the provided arguments and returns result.
     *
     * @return mixed
     */
    public function callArray(array $arguments)
    {
        $callback = $this->getCallback();

        return count($arguments)
            ? call_user_func_array($callback, $arguments)
            : $callback();
    }

    public function __invoke()
    {
        throw new RuntimeException('Callback wrapper cannot be invoked, use $wrapper->getCallback() instead.');
    }
}
