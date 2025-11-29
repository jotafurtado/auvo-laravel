# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [Unreleased]

## [1.0.0] - 2024-11-27

### Adicionado
- Integração inicial com API Auvo v2
- Autenticação JWT com API Key e API Token
- Renovação automática de tokens (novo login após expiração de 30 minutos)
- Query Builder base abstrato com suporte completo a CRUD
- Query Builder para Users com métodos específicos:
  - `porTipo()` - Filtrar por tipo de usuário
  - `disponiveis()` - Filtrar usuários disponíveis
  - `comEmail()` - Filtrar por email
  - `comLogin()` - Filtrar por login
- Query Builder para Tasks com métodos específicos:
  - `porPeriodo()` - Filtrar por período
  - `porUsuario()` - Filtrar por usuário
  - `porCliente()` - Filtrar por cliente
  - `porStatus()` - Filtrar por status
  - `porEquipe()` - Filtrar por equipe
  - `agendadas()` - Filtrar tarefas agendadas
  - `concluidas()` - Filtrar tarefas concluídas
  - `porTipo()` - Filtrar por tipo de tarefa
- Query Builder para Customers com métodos específicos:
  - `porSegmento()` - Filtrar por segmento
  - `porGrupo()` - Filtrar por grupo
  - `ativos()` - Filtrar clientes ativos
  - `comEmail()` - Filtrar por email
  - `porCnpjCpf()` - Filtrar por documento
  - `porNome()` - Filtrar por nome
- Query Builder para Teams com métodos específicos:
  - `ativas()` - Filtrar equipes ativas
  - `porLider()` - Filtrar por líder
  - `porNome()` - Filtrar por nome
- Tratamento específico de rate limit (400 req/min)
- Exceções específicas:
  - `AuvoException` - Erro genérico
  - `AuthenticationException` - Erro de autenticação
  - `NotFoundException` - Recurso não encontrado
  - `ValidationException` - Erro de validação
  - `RateLimitException` - Rate limit excedido
- Facade Auvo para acesso simplificado
- Service Provider com auto-discovery
- Configuração via variáveis de ambiente
- Logs opcionais de requisições
- Testes unitários completos (Token, AuthManager)
- Testes de feature completos (Client, QueryBuilders)
- Documentação completa em português

### Características Técnicas
- PHP 8.1+ com strict types
- Constructor property promotion
- Match expressions
- Suporte a Laravel 10, 11 e 12
- PSR-4 autoloading
- Orchestra Testbench para testes
- PHPUnit 10

[Unreleased]: https://github.com/jotacfurtado/auvo-laravel/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/jotacfurtado/auvo-laravel/releases/tag/v1.0.0

