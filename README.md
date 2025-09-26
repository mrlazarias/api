# 🚀 Robust PHP API

Uma API PHP moderna e robusta construída com **arquitetura hexagonal**, autenticação JWT, cache Redis, rate limiting avançado e containerização com Podman.

## ✨ Recursos Diferenciados

### 🏗️ **Arquitetura Hexagonal (Clean Architecture)**

- Separação clara entre domínio, aplicação e infraestrutura
- Testabilidade e manutenibilidade maximizadas
- Inversão de dependências com interfaces

### 🔐 **Sistema de Autenticação Avançado**

- JWT com refresh tokens
- Criptografia Argon2ID para senhas
- Middleware de autenticação robusto
- Gerenciamento de sessões com Redis

### 🚀 **Performance e Cache**

- Cache inteligente com Redis
- OPcache otimizado para produção
- Cache de consultas de usuários
- Compressão Gzip no Nginx

### 🛡️ **Segurança Empresarial**

- Rate limiting por usuário e IP
- Headers de segurança configurados
- Validação rigorosa de entrada
- Logging estruturado de segurança

### 🐳 **Containerização com Podman**

- Multi-stage builds para otimização
- Configurações separadas para dev/prod
- Supervisord para gerenciamento de processos
- Volume persistence para dados

### 📊 **Observabilidade**

- Logging estruturado com Monolog
- Métricas de performance
- Health checks automáticos
- Rastreamento de requests

## 🛠️ Tecnologias Utilizadas

- **PHP 8.2+** - Linguagem moderna com tipagem forte
- **Slim Framework 4** - Micro-framework performático
- **Doctrine ORM** - Mapeamento objeto-relacional
- **Firebase JWT** - Autenticação segura
- **Redis** - Cache e sessões
- **MySQL 8.0** - Banco de dados relacional
- **Podman** - Containerização
- **Nginx** - Servidor web
- **Monolog** - Sistema de logs
- **PHPUnit/Pest** - Testes automatizados

## 🚀 Início Rápido

### Pré-requisitos

- Podman e Podman Compose
- Make (opcional, mas recomendado)

### 1. Clone e Configure

```bash
git clone <seu-repositorio>
cd api-php
make setup
```

### 2. Configure o Ambiente

```bash
# Edite o arquivo .env com suas configurações
cp env.example .env

# Gere uma chave JWT segura
make generate-key
```

### 3. Execute a Aplicação

```bash
# Construa e inicie os containers
make build
make up

# Verifique se está funcionando
make api-test
```

### 4. Acesse a API

- **API**: http://localhost:8000
- **Documentação**: http://localhost:8000/docs
- **Health Check**: http://localhost:8000/health
- **phpMyAdmin**: http://localhost:8080
- **MailHog**: http://localhost:8025

## 📖 Documentação da API

A API está totalmente documentada com OpenAPI 3.0. Acesse:

- **Swagger UI**: http://localhost:8000/docs
- **Spec YAML**: `/docs/api-spec.yml`

### Endpoints Principais

#### 🔐 Autenticação

```http
POST /api/v1/auth/register     # Registrar usuário
POST /api/v1/auth/login        # Login
POST /api/v1/auth/refresh      # Renovar token
POST /api/v1/auth/logout       # Logout
```

#### 👥 Usuários

```http
GET    /api/v1/users           # Listar usuários
GET    /api/v1/users/{id}      # Obter usuário
PUT    /api/v1/users/{id}      # Atualizar usuário
DELETE /api/v1/users/{id}      # Deletar usuário
```

#### 👤 Perfil (Protegido)

```http
GET /api/v1/protected/profile  # Obter perfil
PUT /api/v1/protected/profile  # Atualizar perfil
```

### Exemplo de Uso

```bash
# Registrar usuário
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao@example.com",
    "password": "minhasenha123"
  }'

# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "joao@example.com",
    "password": "minhasenha123"
  }'

# Usar token para acessar rota protegida
curl -X GET http://localhost:8000/api/v1/protected/profile \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"
```

## 📮 Testando com Postman

### **Importação Rápida**

1. Importe a collection: `docs/postman-collection.json`
2. Importe o environment: `docs/postman-environment.json`
3. Selecione o environment "Robust API - Development"
4. Execute os requests na ordem sugerida

### **Sequência de Teste**

1. **🏥 Health Check** - Verificar API
2. **👤 Register User** - Criar conta
3. **🔐 Login** - Tokens salvos automaticamente
4. **👤 Get Profile** - Testar autenticação
5. **📋 List Users** - Listar dados
6. **🔄 Refresh Token** - Renovar acesso

**📖 Guia completo:** [POSTMAN-SETUP.md](docs/POSTMAN-SETUP.md)

## 🏗️ Arquitetura

```
src/
├── Application/          # Casos de uso e serviços
│   ├── Commands/        # Commands (CQRS)
│   ├── Queries/         # Queries (CQRS)
│   └── Services/        # Serviços de aplicação
├── Domain/              # Regras de negócio
│   ├── Entities/        # Entidades de domínio
│   ├── ValueObjects/    # Objetos de valor
│   ├── Repositories/    # Interfaces de repositório
│   └── Exceptions/      # Exceções de domínio
├── Infrastructure/      # Implementações técnicas
│   ├── Persistence/     # Repositórios concretos
│   ├── Http/           # Web framework
│   ├── Cache/          # Sistema de cache
│   ├── Logging/        # Sistema de logs
│   └── Security/       # Segurança e JWT
└── Presentation/        # Camada de apresentação
    ├── Controllers/     # Controladores HTTP
    ├── Middleware/      # Middlewares
    └── Requests/        # Validação de requests
```

## 🧪 Testes

```bash
# Executar todos os testes
make test

# Testes com coverage
make test-coverage

# Análise estática
make phpstan

# Verificar padrões de código
make cs-check
make cs-fix
```

## 📊 Monitoramento e Logs

### Health Check

```json
GET /health
{
  "status": "ok",
  "timestamp": "2023-12-01T10:00:00Z",
  "version": "1.0.0",
  "environment": "development"
}
```

### Rate Limiting

- **100 requests/hora** por padrão
- Headers de resposta: `X-RateLimit-*`
- Configurável via variáveis de ambiente

### Logs Estruturados

```json
{
  "level": "INFO",
  "message": "User authenticated",
  "context": {
    "user_id": "uuid",
    "ip": "127.0.0.1",
    "user_agent": "...",
    "timestamp": "2023-12-01T10:00:00Z"
  }
}
```

## 🚀 Deploy em Produção

### Build da Imagem de Produção

```bash
make deploy
```

### Variáveis de Ambiente Críticas

```bash
# Segurança
JWT_SECRET=sua-chave-super-secreta-256-bits
APP_ENV=production
APP_DEBUG=false

# Banco de dados
DB_HOST=seu-db-host
DB_PASSWORD=senha-forte

# Cache
REDIS_HOST=seu-redis-host
REDIS_PASSWORD=senha-redis
```

## 🛠️ Comandos Make Disponíveis

```bash
make help              # Mostrar ajuda
make setup             # Configuração inicial
make build             # Construir containers
make up                # Iniciar aplicação
make down              # Parar aplicação
make restart           # Reiniciar aplicação
make logs              # Ver logs
make shell             # Acessar container
make test              # Executar testes
make install           # Instalar dependências
make cache-clear       # Limpar cache
make deploy            # Deploy produção
make clean             # Limpeza completa
```

## 🔧 Desenvolvimento

### Estrutura de Branches

- `main` - Produção
- `develop` - Desenvolvimento
- `feature/*` - Novas funcionalidades
- `hotfix/*` - Correções urgentes

### Conventional Commits

```bash
feat: adicionar endpoint de usuários
fix: corrigir validação de email
docs: atualizar documentação da API
test: adicionar testes de integração
refactor: melhorar estrutura de cache
```

### Configuração do IDE

Recomendamos usar as configurações incluídas para:

- **VS Code**: `.vscode/settings.json`
- **PHPStorm**: `.idea/` (incluído no .gitignore)

## 📈 Performance

### Benchmarks

- **Tempo de resposta**: < 100ms (média)
- **Throughput**: > 1000 req/s
- **Memória**: < 64MB por request
- **Cache hit ratio**: > 90%

### Otimizações Implementadas

- OPcache habilitado
- Autoloader otimizado
- Conexões persistentes
- Compressão Gzip
- Cache de queries

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Push para a branch
5. Abra um Pull Request

## 📄 Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## 👨‍💻 Autor

**Murilo Azarias**

- GitHub: [@muriloazarias](https://github.com/muriloazarias)
- LinkedIn: [Murilo Azarias](https://linkedin.com/in/muriloazarias)
- Email: murilo@example.com

---

⭐ **Se este projeto te ajudou, considere dar uma estrela!**

## 🎯 Próximas Funcionalidades

- [ ] Sistema de notificações em tempo real
- [ ] API de upload de arquivos
- [ ] Integração com serviços de email
- [ ] Dashboard administrativo
- [ ] API de relatórios
- [ ] Sistema de permissões granulares
- [ ] Integração com OAuth2 (Google, Facebook)
- [ ] API de webhooks
- [ ] Sistema de auditoria completo
- [ ] Métricas avançadas com Prometheus
