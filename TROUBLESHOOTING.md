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
