<?php
/**
 * @name:中间件主函数
 *
 * 功能介绍
 * @author   hexu
 */

namespace Guest\EasyCom;


class Middleware
{
    /**
     * The object being passed through the pipeline.
     *
     * @var mixed
     */
    protected $passable;

    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected $pipes = [];

    /**
     * The method to call on each pipe.
     *
     * @var string
     */
    protected $method = 'handle';

    /**
     * Get the array of configured pipes.
     *
     * @return array
     */
    protected function pipes()
    {
        return $this->pipes;
    }

    /**
     * Set the object being sent through the pipeline.
     *
     * @param  mixed  $passable
     * @return $this
     */
    public function send($passable)
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * Set the array of pipes.
     *
     * @param  array|mixed  $pipes
     * @return $this
     */
    public function through($pipes)
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();

        return $this;
    }


    /**
     * Run the pipeline with a final destination callback.
     *
     * @param  \Closure  $destination
     * @return mixed
     */
    public function then(\Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes()), $this->carry(), $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    /**
     * Get a Closure that represents a slice of the application onion.
     *
     * @return \Closure
     */
    protected function carry()
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                try {
                    $parameters = [$passable, $stack];
                    $carry = method_exists($pipe, $this->method)
                        ? $pipe->{$this->method}(...$parameters)
                        : $pipe(...$parameters);

                    return $carry;
                } catch (\Throwable $e) {
                    throw $e;
                }
            };
        };
    }

    /**
     * Get the final piece of the Closure onion.
     *
     * @param  \Closure  $destination
     * @return \Closure
     */
    protected function prepareDestination(\Closure $destination)
    {
        return function ($passable) use ($destination) {
            try {
                return $destination($passable);
            } catch (\Throwable $e) {
                throw $e;
            }
        };
    }
}
