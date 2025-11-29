# Jcf/Auvo

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jcf/auvo.svg?style=flat-square)](https://packagist.org/packages/jcf/auvo)
[![Total Downloads](https://img.shields.io/packagist/dt/jcf/auvo.svg?style=flat-square)](https://packagist.org/packages/jcf/auvo)
[![Tests](https://img.shields.io/github/actions/workflow/status/jotacfurtado/auvo-laravel/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/jotacfurtado/auvo-laravel/actions/workflows/tests.yml)
[![License](https://img.shields.io/packagist/l/jcf/auvo.svg?style=flat-square)](https://packagist.org/packages/jcf/auvo)

Pacote Laravel para integração com a API do Auvo - Sistema de gestão de serviços de campo (field service management).

## Instalação

```bash
composer require jcf/auvo
```

## Configuração

Publicar o arquivo de configuração:

```bash
php artisan vendor:publish --provider="Jcf\Auvo\Providers\AuvoServiceProvider" --tag=config
```

Configurar as variáveis de ambiente no arquivo `.env`:

```env
API_AUVO_API_KEY=sua-api-key
API_AUVO_API_TOKEN=seu-api-token
API_AUVO_BASE_URL=https://api.auvo.com.br/v2
AUVO_TIMEOUT=30
AUVO_RETRY=3
AUVO_RETRY_DELAY=100
AUVO_LOG_REQUESTS=false
```

**Importante:** A API Key e API Token podem ser obtidos em [Menu > Integração](https://app.auvo.com.br/integracao) na sua conta Auvo.

## Autenticação

O pacote gerencia automaticamente a autenticação JWT com a API do Auvo. Os tokens têm validade de 30 minutos e são renovados automaticamente quando necessário.

**Nota importante:** A API Auvo não suporta refresh token. Quando o token expira (após 30 minutos), o pacote faz automaticamente um novo login.

### Métodos de Autenticação

```php
use Jcf\Auvo\Facades\Auvo;

// Fazer login manualmente
$token = Auvo::auth()->signIn();

// Obter token válido (renova automaticamente se necessário)
$token = Auvo::auth()->getValidToken();

// Obter apenas o access token
$accessToken = Auvo::auth()->getAccessToken();
```

## Uso

### Consultas Básicas

```php
use Jcf\Auvo\Facades\Auvo;

// Buscar todos os usuários - retorna AuvoResponse
$response = Auvo::users()->get();

// Acessar entidades da resposta
$usuarios = $response->entityList();

// Buscar um usuário específico - retorna Collection
$usuario = Auvo::users()->first(123);

// Buscar todos os clientes
$response = Auvo::customers()->get();
$clientes = $response->entityList();

// Buscar uma tarefa específica
$tarefa = Auvo::tasks()->first(456);
```

### Objeto de Resposta AuvoResponse

Requisições GET que retornam listas retornam um objeto `AuvoResponse` com métodos úteis para acessar dados e metadados da resposta:

```php
use Jcf\Auvo\Facades\Auvo;

$response = Auvo::tasks()
    ->period('2024-01-01', '2024-01-31')
    ->get();

// Acessar a lista de entidades como Collection
$tasks = $response->entityList();

// Acessar dados de paginação
$totalItems = $response->totalItems();
$currentPage = $response->currentPage();
$pageSize = $response->pageSize();
$hasMore = $response->hasMorePages();

// Acessar links HATEOAS
$links = $response->links();

// Acessar todos os dados de paginação
$paginationData = $response->pagedSearchReturnData();

// Acessar dados brutos da resposta
$raw = $response->raw();

// Usar métodos de Collection diretamente (delegação mágica)
$filtered = $response->filter(fn($task) => $task['finished'] === true);
$mapped = $response->map(fn($task) => $task['taskID']);
$firstTask = $response->first();
$count = $response->count();
```

### Paginação Automática

O pacote oferece métodos para buscar automaticamente todos os registros, iterando sobre todas as páginas da API:

```php
use Jcf\Auvo\Facades\Auvo;

// Buscar TODOS os usuários (itera todas as páginas automaticamente)
// Retorna Collection diretamente
$todosUsuarios = Auvo::users()->getAll();

// Buscar TODAS as tarefas de um período
$todasTarefas = Auvo::tasks()
    ->period('2024-01-01', '2024-12-31')
    ->type(197448)
    ->getAll();

// Buscar TODOS os clientes ativos
$todosClientes = Auvo::customers()
    ->active()
    ->getAll();

// Buscar TODAS as equipes ativas
$todasEquipes = Auvo::teams()
    ->active()
    ->getAll();

// Para maior controle, use allPages() que retorna o objeto AllPagesQuery
$allPagesQuery = Auvo::tasks()->period('2024-01-01', '2024-12-31')->allPages();
$todasTarefas = $allPagesQuery->get(); // Retorna Collection
```

> **Nota:** O método `getAll()` itera automaticamente sobre todas as páginas, fazendo múltiplas requisições à API. Use com cuidado para não exceder o limite de 400 requisições por minuto.

### Query Builders Disponíveis

O pacote oferece Query Builders completos para os seguintes recursos:

#### Users (Usuários)

```php
// Buscar usuários administradores
$response = Auvo::users()->userType(3)->get();
$admins = $response->entityList();

// Buscar usuários disponíveis para tarefas
$response = Auvo::users()->availableForTasks()->get();
$disponiveis = $response->entityList();

// Buscar por email
$response = Auvo::users()->email('usuario@exemplo.com')->get();
$usuario = $response->entityList()->first();

// Combinar filtros
$response = Auvo::users()
    ->userType(2) // 1 - user | 2 - team manager | 3 - administrator
    ->availableForTasks()
    ->get();

$usuarios = $response->entityList();
```

#### Tasks (Tarefas)

```php
// Buscar tarefas por período
$response = Auvo::tasks()
    ->period('2024-01-01', '2024-01-31')
    ->get();

$tarefas = $response->entityList();

// Buscar tarefas de um usuário
$response = Auvo::tasks()
    ->userId(123)
    ->get();

$tarefas = $response->entityList();

// Buscar tarefas agendadas
$response = Auvo::tasks()->scheduled()->get();
$agendadas = $response->entityList();

// Buscar tarefas concluídas
$response = Auvo::tasks()->completed()->get();
$concluidas = $response->entityList();

// Combinar múltiplos filtros com paginação e seleção de campos
$response = Auvo::tasks()
    ->period('2024-01-01', '2024-01-31')
    ->userId(123)
    ->customerId(456)
    ->type(197448)
    ->page(1)
    ->pageSize(100)
    ->selectFields('taskID,customerId,customerDescription,finished')
    ->get();

$tarefas = $response->entityList();
$totalItens = $response->totalItems();
$hasMorePages = $response->hasMorePages();
```

#### Customers (Clientes)

```php
// Buscar clientes ativos
$response = Auvo::customers()->active()->get();
$clientes = $response->entityList();

// Buscar por CNPJ/CPF
$response = Auvo::customers()->document('12345678901')->get();
$cliente = $response->entityList()->first();

// Buscar por email
$response = Auvo::customers()->email('cliente@exemplo.com')->get();
$cliente = $response->entityList()->first();

// Buscar por segmento
$response = Auvo::customers()
    ->segmentId(10)
    ->active()
    ->get();

$clientes = $response->entityList();
```

#### Teams (Equipes)

```php
// Buscar equipes ativas
$response = Auvo::teams()->active()->get();
$equipes = $response->entityList();

// Buscar equipes por líder/gerente
$response = Auvo::teams()->managerId(123)->get();
$equipes = $response->entityList();

// Buscar por nome
$response = Auvo::teams()->name('Equipe A')->get();
$equipe = $response->entityList()->first();
```

### Operações CRUD

Todos os Query Builders suportam operações completas de CRUD:

```php
// GET - Buscar recursos
$response = Auvo::users()->get();
$usuarios = $response->entityList();

// GET - Buscar recurso único
$usuario = Auvo::users()->first(123);

// POST - Criar novo recurso (retorna array)
$novoUsuario = Auvo::users()->create([
    'name' => 'João Silva',
    'email' => 'joao@exemplo.com',
    'smartPhoneNumber' => '11999999999',
    'culture' => 'pt-BR',
    'jobPosition' => 'Técnico',
    'userType' => 1,
    'password' => 'senha123',
    // ... outros campos
]);

// PATCH - Atualizar recurso (retorna array)
$usuarioAtualizado = Auvo::users()
    ->find(123)
    ->update([
        'name' => 'João Silva Santos',
        'email' => 'joao.novo@exemplo.com',
    ]);

// DELETE - Excluir recurso (retorna array)
$resultado = Auvo::users()->find(123)->delete();
```

### Filtros Genéricos

Todos os Query Builders suportam filtros genéricos com o método `where()`:

```php
$response = Auvo::users()
    ->where('campo', 'valor')
    ->where('outro_campo', 'outro_valor')
    ->get();

$usuarios = $response->entityList();
```

## Rate Limiting

**Importante:** A API Auvo tem limite de **400 requisições por minuto** por endereço IP.

Se o limite for excedido, você receberá uma exception `RateLimitException`. O pacote detecta automaticamente quando o rate limit é atingido.

## Características

- ✅ Autenticação JWT automática
- ✅ Renovação automática de tokens (novo login após 30 minutos)
- ✅ Tratamento automático de erros de autenticação
- ✅ Query Builders estilo Laravel para recursos core
- ✅ Suporte completo a CRUD (GET, POST, PATCH, DELETE)
- ✅ Métodos específicos para filtros comuns
- ✅ Suporte a filtros genéricos com `where()`
- ✅ **Objeto de resposta AuvoResponse para acesso simplificado aos dados**
- ✅ **Métodos mágicos de Collection em AuvoResponse**
- ✅ Paginação automática com `getAll()` e `allPages()`
- ✅ **Retorna Collections em vez de arrays para melhor manipulação**
- ✅ Métodos auxiliares para paginação manual (`page()`, `pageSize()`)
- ✅ Seleção de campos específicos (`selectFields()`)
- ✅ **Método `first()` para buscar entidades únicas**
- ✅ Detecção automática de rate limit
- ✅ Logs de requisições (opcional)
- ✅ Configuração via variáveis de ambiente
- ✅ Testes unitários e de integração completos

## Exceptions

O pacote lança exceções específicas para diferentes tipos de erro:

- `AuvoException` - Erro genérico da API
- `AuthenticationException` - Erro de autenticação (401, 403)
- `NotFoundException` - Recurso não encontrado (404)
- `ValidationException` - Erro de validação (400, 422)
- `RateLimitException` - Rate limit excedido (403 com mensagem específica)

```php
use Jcf\Auvo\Exceptions\RateLimitException;

try {
    $response = Auvo::users()->get();
    $usuarios = $response->entityList();
} catch (RateLimitException $e) {
    // Rate limit excedido - aguarde antes de tentar novamente
    sleep(60);
} catch (\Exception $e) {
    // Outro tipo de erro
    logger()->error('Erro na API Auvo: ' . $e->getMessage());
}
```

## Documentação da API

A documentação completa da API Auvo v2 está disponível em:
- `docs/api/auvoapiv2.apib` - Especificação API Blueprint

## Requisitos

- PHP ^8.1
- Laravel ^10.0|^11.0|^12.0

## Testes

```bash
cd packages/jcf/auvo
composer install
./vendor/bin/phpunit
```

## Licença

MIT

## Suporte

Para issues e dúvidas, utilize o [repositório no GitHub](https://github.com/jotacfurtado/auvo-laravel/issues).

## Créditos

Desenvolvido por [João C. Furtado](https://github.com/jotacfurtado).
