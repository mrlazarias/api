# 🔧 Troubleshooting Guide

## Problemas Comuns e Soluções

### 1. 🐳 Problemas de Build do Container

#### Erro: `linux/sock_diag.h: No such file or directory`

**Problema**: Extensão `sockets` não compila no Alpine Linux

**Soluções**:

**Opção A**: Use o build corrigido (já aplicado)

```bash
make build-no-cache
```

**Opção B**: Use o Containerfile simplificado

```bash
make build-simple
```

**Opção C**: Use Docker ao invés de Podman (se disponível)

```bash
# Substitua 'podman-compose' por 'docker-compose' no Makefile
sed -i 's/podman-compose/docker-compose/g' Makefile
make build
```

### 2. 🔑 Problemas de Permissão

#### Erro: Permission denied nos diretórios storage/

```bash
# Execute dentro do container
make shell-root
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage
```

### 3. 🔌 Problemas de Conexão

#### Redis não conecta

```bash
# Verifique se o Redis está rodando
make logs-redis

# Teste a conexão
make shell
php -r "
\$redis = new Redis();
\$redis->connect('redis', 6379);
echo 'Redis OK: ' . \$redis->ping();
"
```

#### MySQL não conecta

```bash
# Verifique logs do MySQL
podman-compose logs mysql

# Teste conexão
make shell
php -r "
\$pdo = new PDO('mysql:host=mysql;dbname=robust_api', 'root', 'root');
echo 'MySQL OK';
"
```

### 4. 🚀 Problemas de Performance

#### API lenta

1. Verifique se o OPcache está habilitado:

```bash
make shell
php -m | grep -i opcache
```

2. Verifique logs de erro:

```bash
make logs-app
```

3. Limpe o cache:

```bash
make cache-clear
```

### 5. 🔐 Problemas de JWT

#### Token inválido

1. Verifique se `JWT_SECRET` está configurado no `.env`
2. Gere uma nova chave:

```bash
make generate-key
```

#### Token expirado

- Tokens de acesso expiram em 1 hora por padrão
- Use o refresh token para renovar

### 6. 📝 Problemas de Logs

#### Logs não aparecem

```bash
# Verifique permissões
ls -la storage/logs/

# Crie o diretório se não existir
mkdir -p storage/logs
chmod 777 storage/logs
```

### 7. 🌐 Problemas de CORS

#### CORS bloqueando requests

Edite o `.env`:

```bash
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:8080
CORS_ALLOWED_METHODS=GET,POST,PUT,PATCH,DELETE,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Requested-With
```

### 8. 📊 Rate Limiting

#### Muitos requests bloqueados

Ajuste no `.env`:

```bash
RATE_LIMIT_REQUESTS=1000
RATE_LIMIT_WINDOW=3600
```

### 9. 🔍 Debug e Desenvolvimento

#### Habilitar debug completo

No `.env`:

```bash
APP_DEBUG=true
APP_ENV=development
LOG_LEVEL=debug
```

#### Xdebug não funciona

1. Verifique se está no ambiente de desenvolvimento
2. Configure seu IDE para a porta 9003
3. Adicione no `docker/php/xdebug.ini`:

```ini
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=host.docker.internal
xdebug.client_port=9003
```

### 10. 🧪 Problemas de Teste

#### Testes não executam

```bash
# Instale dependências de desenvolvimento
make install

# Execute testes específicos
make shell
./vendor/bin/pest tests/Unit/
```

### 11. 📦 Problemas com Composer

#### Dependências não instalam

```bash
# Limpe o cache do Composer
make shell
composer clear-cache
composer install --no-cache
```

### 12. 🔄 Reset Completo

Se nada funcionar, reset completo:

```bash
# Para tudo e limpa
make clean-all

# Reconfigure
make setup
# Edite o .env

# Rebuilde tudo
make build-no-cache
make up
```

## 📞 Comandos Úteis para Debug

```bash
# Ver todos os containers
podman ps -a

# Ver logs em tempo real
make logs

# Entrar no container
make shell

# Ver uso de recursos
podman stats

# Inspecionar container
podman inspect robust-api-app

# Ver redes
podman network ls

# Limpar tudo (cuidado!)
make clean-all
```

## 🆘 Se Nada Funcionar

1. **Verifique versões**:

   ```bash
   podman --version
   podman-compose --version
   ```

2. **Use Docker como alternativa**:

   ```bash
   # Instale Docker Desktop
   # Substitua podman por docker no Makefile
   ```

3. **Rode localmente sem containers**:

   ```bash
   # Instale PHP 8.2, Redis, MySQL localmente
   composer install
   php -S localhost:8000 -t public
   ```

4. **Reporte o problema**:
   - Copie os logs completos
   - Informe sua versão do SO
   - Descreva os passos que causaram o erro

## 📚 Recursos Adicionais

- [Documentação do Podman](https://podman.io/docs)
- [PHP-FPM Configuration](https://www.php.net/manual/en/install.fpm.configuration.php)
- [Nginx Configuration](https://nginx.org/en/docs/)
- [Redis Documentation](https://redis.io/documentation)

## 📮 Testando a API com Postman

### 1. 🚀 **Setup Inicial**

1. **Inicie a API:**

   ```bash
   make up
   # Aguarde alguns segundos para os containers iniciarem
   ```

2. **Verifique se está rodando:**
   ```bash
   make api-test
   # ou
   curl http://localhost:8000/health
   ```

### 2. 📋 **Collection do Postman**

**Base URL:** `http://localhost:8000`

#### 🏥 **Health Check**

```
GET http://localhost:8000/health
```

**Response esperado:**

```json
{
  "status": "ok",
  "timestamp": "2023-12-01T10:00:00Z",
  "version": "1.0.0",
  "environment": "development"
}
```

#### 👤 **1. Registrar Usuário**

```
POST http://localhost:8000/api/v1/auth/register
Content-Type: application/json

{
  "name": "João Silva",
  "email": "joao@example.com",
  "password": "minhasenha123"
}
```

**Response esperado (201):**

```json
{
  "message": "User registered successfully",
  "user": {
    "id": "uuid-aqui",
    "name": "João Silva",
    "email": "joao@example.com",
    "is_active": true,
    "is_verified": false,
    "roles": ["user"]
  }
}
```

#### 🔐 **2. Login**

```
POST http://localhost:8000/api/v1/auth/login
Content-Type: application/json

{
  "email": "joao@example.com",
  "password": "minhasenha123"
}
```

**Response esperado (200):**

```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "user": {
    "id": "uuid-aqui",
    "name": "João Silva",
    "email": "joao@example.com"
  }
}
```

**⚠️ IMPORTANTE:** Copie o `access_token` para usar nas próximas requisições!

#### 👥 **3. Listar Usuários (Requer Token)**

```
GET http://localhost:8000/api/v1/users
Authorization: Bearer SEU_ACCESS_TOKEN_AQUI
```

**Response esperado (200):**

```json
{
  "data": [
    {
      "id": "uuid",
      "name": "João Silva",
      "email": "joao@example.com",
      "is_active": true
    }
  ],
  "meta": {
    "total": 1,
    "limit": 50,
    "offset": 0,
    "has_more": false
  }
}
```

#### 👤 **4. Ver Perfil (Rota Protegida)**

```
GET http://localhost:8000/api/v1/protected/profile
Authorization: Bearer SEU_ACCESS_TOKEN_AQUI
```

#### 🔄 **5. Renovar Token**

```
POST http://localhost:8000/api/v1/auth/refresh
Content-Type: application/json

{
  "refresh_token": "SEU_REFRESH_TOKEN_AQUI"
}
```

#### 🚪 **6. Logout**

```
POST http://localhost:8000/api/v1/auth/logout
Authorization: Bearer SEU_ACCESS_TOKEN_AQUI
```

### 3. 🎯 **Configuração no Postman**

#### **Variáveis de Ambiente:**

1. Crie um Environment no Postman
2. Adicione as variáveis:
   ```
   base_url: http://localhost:8000
   access_token: (será preenchido automaticamente)
   refresh_token: (será preenchido automaticamente)
   ```

#### **Scripts Automáticos:**

**No request de Login, aba "Tests":**

```javascript
// Salvar tokens automaticamente
if (pm.response.code === 200) {
  const response = pm.response.json();
  pm.environment.set("access_token", response.access_token);
  pm.environment.set("refresh_token", response.refresh_token);
  console.log("Tokens salvos!");
}
```

**No request de Refresh, aba "Tests":**

```javascript
// Atualizar access token
if (pm.response.code === 200) {
  const response = pm.response.json();
  pm.environment.set("access_token", response.access_token);
  pm.environment.set("refresh_token", response.refresh_token);
  console.log("Token renovado!");
}
```

### 4. 🧪 **Sequência de Testes**

**Ordem recomendada:**

1. ✅ Health Check
2. ✅ Registrar usuário
3. ✅ Login (salvar token)
4. ✅ Ver perfil
5. ✅ Listar usuários
6. ✅ Renovar token
7. ✅ Logout

### 5. 🚨 **Testando Errors**

#### **Erro de Validação (422):**

```
POST http://localhost:8000/api/v1/auth/register
Content-Type: application/json

{
  "name": "",
  "email": "email-inválido",
  "password": "123"
}
```

#### **Erro de Autenticação (401):**

```
GET http://localhost:8000/api/v1/protected/profile
Authorization: Bearer token-inválido
```

#### **Rate Limiting (429):**

Faça mais de 100 requests em 1 hora para o mesmo endpoint.

### 6. 📊 **Headers Importantes**

**Para todas as requests:**

```
Content-Type: application/json
Accept: application/json
```

**Para rotas protegidas:**

```
Authorization: Bearer SEU_TOKEN_AQUI
```

**Observe os headers de resposta:**

```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 99
X-RateLimit-Reset: 1640995200
```

### 7. 🔍 **Debug**

Se algo não funcionar:

1. **Verifique se a API está rodando:**

   ```bash
   make logs-app
   ```

2. **Teste com curl primeiro:**

   ```bash
   curl -X GET http://localhost:8000/health
   ```

3. **Verifique o token JWT:**

   - Use [jwt.io](https://jwt.io) para decodificar
   - Verifique se não expirou

4. **Headers obrigatórios:**
   - `Content-Type: application/json`
   - `Authorization: Bearer token` (para rotas protegidas)

### 8. 📁 **Collection Completa**

Crie uma collection no Postman com esta estrutura:

```
📁 Robust PHP API
├── 🏥 Health Check
├── 📁 Auth
│   ├── 👤 Register
│   ├── 🔐 Login
│   ├── 🔄 Refresh
│   └── 🚪 Logout
├── 📁 Users
│   ├── 📋 List Users
│   └── 👁️ Get User
└── 📁 Protected
    ├── 👤 Get Profile
    └── ✏️ Update Profile
```
