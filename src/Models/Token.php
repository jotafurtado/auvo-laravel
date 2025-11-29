<?php

namespace Jcf\Auvo\Models;

class Token
{
    public function __construct(
        public bool $authenticated = false,
        public ?string $accessToken = null,
        public ?string $created = null,
        public ?string $expiration = null,
        public ?string $message = null,
    ) {}

    /**
     * Cria uma instância de Token a partir de um array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static
    {
        // A API Auvo retorna os dados dentro de "result"
        $result = $data['result'] ?? $data;

        return new static(
            authenticated: $result['authenticated'] ?? false,
            accessToken: $result['accessToken'] ?? null,
            created: $result['created'] ?? null,
            expiration: $result['expiration'] ?? null,
            message: $result['message'] ?? null,
        );
    }

    /**
     * Verifica se o token está expirado.
     */
    public function isExpired(): bool
    {
        if (! $this->expiration) {
            return true;
        }

        $expiration = \Carbon\Carbon::parse($this->expiration);

        return $expiration->isPast();
    }

    /**
     * Converte para array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'authenticated' => $this->authenticated,
            'accessToken' => $this->accessToken,
            'created' => $this->created,
            'expiration' => $this->expiration,
            'message' => $this->message,
        ];
    }
}
