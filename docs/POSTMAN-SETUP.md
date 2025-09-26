# 📮 Setup do Postman - Robust PHP API

## 🚀 Início Rápido (5 minutos)

### 1. **Inicie a API**
```bash
cd api-php
make up
# Aguarde os containers iniciarem (30-60 segundos)
```

### 2. **Importe no Postman**

#### **Opção A: Importar Arquivos (Recomendado)**
1. Abra o Postman
2. Clique em **Import** (canto superior esquerdo)
3. Arraste os arquivos:
   - `docs/postman-collection.json`
   - `docs/postman-environment.json`
4. Clique em **Import**

#### **Opção B: Importar por URL**
```
Collection: https://raw.githubusercontent.com/seu-usuario/api-php/main/docs/postman-collection.json
Environment: https://raw.githubusercontent.com/seu-usuario/api-php/main/docs/postman-environment.json
```

### 3. **Configurar Environment**
1. No canto superior direito, selecione **"Robust API - Development"**
2. Verifique se `base_url` está como `http://localhost:8000`

### 4. **Teste a API** ✅

**Sequência recomendada:**

1. **🏥 Health Check** - Verificar se API está rodando
2. **👤 Register User** - Criar conta
3. **🔐 Login** - Fazer login (tokens salvos automaticamente)
4. **👤 Get Profile** - Testar rota protegida
5. **📋 List Users** - Listar usuários
6. **🔄 Refresh Token** - Renovar token
7. **🚪 Logout** - Fazer logout

## 📋 Collection Completa

### 📁 **Estrutura da Collection**
```
📁 Robust PHP API
├── 🏥 Health Check
├── 📁 Auth
│   ├── 👤 Register User
│   ├── 🔐 Login (salva tokens automaticamente)
│   ├── 🔄 Refresh Token
│   └── 🚪 Logout
├── 👥 Users  
│   ├── 📋 List Users
│   └── 👁️ Get User by ID
├── 🔒 Protected Routes
│   ├── 👤 Get Profile
│   └── ✏️ Update Profile
└── 🚨 Error Testing
    ├── ❌ Invalid Registration (422)
    ├── 🔐 Invalid Login (401)
    ├── 🚫 Unauthorized Access (401)
    └── 🚫 Not Found (404)
```

## 🎯 **Features Automáticas**

### **🔑 Gerenciamento de Tokens**
- Login salva automaticamente `access_token` e `refresh_token`
- Refresh atualiza automaticamente os tokens
- Todas as rotas protegidas usam `{{access_token}}`

### **🧪 Scripts de Teste**
- Validação automática de responses
- Logs informativos no console
- Salvamento automático de variáveis

### **📊 Variáveis de Environment**
- `{{base_url}}` - URL base da API
- `{{access_token}}` - Token JWT ativo
- `{{refresh_token}}` - Token de renovação
- `{{user_id}}` - ID do usuário logado

## 🔍 **Exemplos de Requests**

### **1. Health Check**
```http
GET {{base_url}}/health
```
**Response:** Status da API

### **2. Registrar Usuário**
```http
POST {{base_url}}/api/v1/auth/register
Content-Type: application/json

{
  "name": "João Silva",
  "email": "joao@example.com", 
  "password": "minhasenha123"
}
```

### **3. Login**
```http
POST {{base_url}}/api/v1/auth/login
Content-Type: application/json

{
  "email": "joao@example.com",
  "password": "minhasenha123"
}
```
**⚡ Tokens salvos automaticamente!**

### **4. Rota Protegida**
```http
GET {{base_url}}/api/v1/protected/profile
Authorization: Bearer {{access_token}}
```

## 🚨 **Testando Erros**

### **Validação (422)**
```json
{
  "name": "",
  "email": "email-inválido",
  "password": "123"
}
```

### **Autenticação (401)**
```http
Authorization: Bearer token-inválido
```

### **Rate Limiting (429)**
Faça mais de 100 requests em 1 hora

## 🔧 **Troubleshooting**

### **❌ API não responde**
```bash
# Verificar se containers estão rodando
make logs

# Testar com curl
curl http://localhost:8000/health
```

### **❌ Token inválido**
1. Refaça o login
2. Verifique se token não expirou em [jwt.io](https://jwt.io)
3. Verifique variável `{{access_token}}`

### **❌ CORS Error**
- Adicione headers: `Accept: application/json`
- Verifique se `Content-Type: application/json` está presente

### **❌ 404 Not Found**
- Verifique se URL está correta
- Confirme se API está rodando na porta 8000

## 🎉 **Pronto para Usar!**

Agora você tem uma collection completa para testar todos os endpoints da API com:

- ✅ Autenticação JWT automática
- ✅ Testes de erro
- ✅ Variáveis de ambiente
- ✅ Scripts automáticos
- ✅ Documentação integrada

**Happy Testing!** 🚀
