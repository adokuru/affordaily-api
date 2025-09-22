<?php

namespace App\Actions;

abstract class BaseAction
{
    /**
     * Execute the action.
     *
     * @param mixed ...$arguments
     * @return mixed
     */
    abstract public function execute(...$arguments);

    /**
     * Handle the action execution with error handling.
     *
     * @param mixed ...$arguments
     * @return array
     */
    public function handle(...$arguments): array
    {
        try {
            $result = $this->execute(...$arguments);
            
            return [
                'success' => true,
                'data' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e,
            ];
        }
    }

    /**
     * Create a new instance of the action.
     *
     * @return static
     */
    public static function make(): static
    {
        return new static();
    }
}
