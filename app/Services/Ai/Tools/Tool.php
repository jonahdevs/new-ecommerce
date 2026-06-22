<?php

namespace App\Services\Ai\Tools;

interface Tool
{
    /** The function name the model calls — must match definition()['function']['name']. */
    public function name(): string;

    /**
     * The OpenAI-style tool definition advertised to the model.
     *
     * @return array<string, mixed>
     */
    public function definition(): array;

    /**
     * Execute the tool and return a string (usually JSON) fed back to the model.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function handle(array $arguments): string;
}
