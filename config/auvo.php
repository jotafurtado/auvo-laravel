<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Auvo API Configuration
    |--------------------------------------------------------------------------
    |
    | Este arquivo contém as configurações necessárias para integração com
    | a API do Auvo. Todas as configurações podem ser definidas através
    | de variáveis de ambiente no arquivo .env da aplicação.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | Chave de API do Auvo. Pode ser obtida em Menu > Integração na sua
    | conta Auvo em https://app.auvo.com.br/integracao
    |
    */

    'api_key' => env('AUVO_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | API Token
    |--------------------------------------------------------------------------
    |
    | Token de API do Auvo. Pode ser obtido em Menu > Integração na sua
    | conta Auvo em https://app.auvo.com.br/integracao
    |
    */

    'api_token' => env('AUVO_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Base URI
    |--------------------------------------------------------------------------
    |
    | URL base da API do Auvo. Por padrão, aponta para a URL de produção
    | da API v2. Você pode sobrescrever através da variável de ambiente.
    |
    */

    'base_uri' => env('AUVO_API_BASE_URL', 'https://api.auvo.com.br/v2'),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Tempo máximo em segundos para aguardar uma resposta da API.
    | Valor padrão: 30 segundos.
    |
    */

    'timeout' => env('AUVO_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry
    |--------------------------------------------------------------------------
    |
    | Número de tentativas em caso de falha na requisição.
    | Valor padrão: 3 tentativas.
    |
    */

    'retry' => env('AUVO_RETRY', 3),

    /*
    |--------------------------------------------------------------------------
    | Retry Delay
    |--------------------------------------------------------------------------
    |
    | Tempo em milissegundos entre tentativas de retry.
    | Valor padrão: 100ms.
    |
    */

    'retry_delay' => env('AUVO_RETRY_DELAY', 100),

    /*
    |--------------------------------------------------------------------------
    | Log Requests
    |--------------------------------------------------------------------------
    |
    | Se habilitado, registra todas as requisições HTTP no log da aplicação.
    | Útil para debug, mas pode gerar muitos logs em produção.
    | Valor padrão: false.
    |
    | ATENÇÃO: A API Auvo tem limite de 400 requisições por minuto.
    |
    */

    'log_requests' => env('AUVO_LOG_REQUESTS', false),

];
