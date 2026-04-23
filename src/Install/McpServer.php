<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use InvalidArgumentException;

class McpServer
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $command = null,
        public readonly array $args = [],
        public readonly ?string $url = null,
        public readonly ?string $type = null,
        public readonly array $env = [],
        public readonly ?string $description = null,
    ) {
        //
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidArgumentException when name is absent or empty
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['name']) || ! is_string($data['name']) || trim($data['name']) === '') {
            throw new InvalidArgumentException('McpServer requires a non-empty string "name" field.');
        }

        return new self(
            name: $data['name'],
            command: isset($data['command']) && is_string($data['command']) ? $data['command'] : null,
            args: isset($data['args']) && is_array($data['args']) ? $data['args'] : [],
            url: isset($data['url']) && is_string($data['url']) ? $data['url'] : null,
            type: isset($data['type']) && is_string($data['type']) ? $data['type'] : null,
            env: isset($data['env']) && is_array($data['env']) ? $data['env'] : [],
            description: isset($data['description']) && is_string($data['description']) ? $data['description'] : null,
        );
    }

    /**
     * Returns the server config as an array, omitting null and empty fields.
     *
     * @return array<string, mixed>
     */
    public function toConfigArray(): array
    {
        $data = [
            'name' => $this->name,
            'command' => $this->command,
            'args' => $this->args ?: null,
            'url' => $this->url,
            'type' => $this->type,
            'env' => $this->env ?: null,
            'description' => $this->description,
        ];

        return array_filter($data, fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
