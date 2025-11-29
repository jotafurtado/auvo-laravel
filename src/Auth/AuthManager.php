<?php

namespace Jcf\Auvo\Auth;

use Illuminate\Support\Facades\Http;
use Jcf\Auvo\Exceptions\AuthenticationException;
use Jcf\Auvo\Exceptions\AuvoException;
use Jcf\Auvo\Models\Token;

class AuthManager
{
    protected ?Token $token = null;

    public function __construct(
        protected string $baseUri,
        protected string $apiKey,
        protected string $apiToken,
    ) {}

    /**
     * Realiza a autenticação do usuário.
     *
     * @throws AuthenticationException|AuvoException
     */
    public function signIn(): Token
    {
        $response = Http::baseUrl($this->baseUri)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->post('/login/', [
                'apiKey' => $this->apiKey,
                'apiToken' => $this->apiToken,
            ]);

        if ($response->failed()) {
            if ($response->status() === 401 || $response->status() === 404) {
                throw new AuthenticationException(
                    'Não foi possível autenticar. Verifique as credenciais (API Key e API Token).',
                    $response->status(),
                );
            }

            throw new AuvoException(
                "Erro na autenticação: {$response->body()}. Status: {$response->status()}.",
                $response->status(),
            );
        }

        $responseData = $response->json();

        if (! $responseData || ! is_array($responseData)) {
            throw new AuvoException(
                'Resposta inválida da API: '.$response->body(),
            );
        }

        $this->token = Token::fromArray($responseData);

        // Verifica se a autenticação foi bem-sucedida
        if (! $this->token->authenticated) {
            throw new AuthenticationException(
                'Autenticação falhou: '.$this->token->message,
                401,
            );
        }

        return $this->token;
    }

    /**
     * A API Auvo não suporta refresh token.
     * É necessário fazer login novamente quando o token expirar.
     *
     * @throws AuvoException
     */
    public function refreshToken(): never
    {
        throw new AuvoException(
            'A API Auvo não suporta refresh token. O token tem validade de 30 minutos. '.
            'Após expiração, é necessário fazer login novamente com signIn().',
        );
    }

    /**
     * Obtém o token atual.
     */
    public function getToken(): ?Token
    {
        return $this->token;
    }

    /**
     * Define o token manualmente.
     */
    public function setToken(Token $token): void
    {
        $this->token = $token;
    }

    /**
     * Obtém um token válido, renovando se necessário.
     *
     * @throws AuthenticationException|AuvoException
     */
    public function getValidToken(): Token
    {
        // Se não tem token ou está expirado, faz login
        if (! $this->token || $this->token->isExpired()) {
            return $this->signIn();
        }

        return $this->token;
    }

    /**
     * Obtém o access token válido, renovando se necessário.
     *
     * @throws AuthenticationException|AuvoException
     */
    public function getAccessToken(): string
    {
        $token = $this->getValidToken();

        if (! $token->accessToken) {
            throw new AuvoException('Access token não disponível após autenticação.');
        }

        return $token->accessToken;
    }
}
