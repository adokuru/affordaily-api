<?php

namespace App\Actions;

abstract class BaseAction
{

    /**
     * Handle the action execution with error handling.
     * 
     * @param mixed ...$arguments
     *
     * @throws \Exception
     * 
     * 
     * @return array
     */
    public function handle(...$arguments): array
    {
        try {

            // Defer to child class implementation; pass through any arguments
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