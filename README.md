# Clínica Vet - Backend

API backend em PHP / Symfony para gerenciamento de clientes, pets, raças e endereços.

Resumo
- Symfony 7+ (PHP 8.3 compatível)
- Autenticação JWT (Lexik) com refresh tokens (gesdinet)
- Endpoints principais sob `/api` (pets, breeds, addresses, profile)
- Rotas de autenticação em `/auth` (register, login, refresh)

Pré-requisitos
- PHP 8.1+ (recomendado 8.3)
- Composer
- MySQL ou outro banco suportado (configure `DATABASE_URL`)
- (Opcional) Symfony CLI para rodar o servidor local

Estrutura relevante
- `src/Controller/` - controllers customizados (AuthController, PetController, AddressController, BreedController, ProfileController)
- `src/Entity/` - entidades Doctrine (User, Pet, Breed, Address, RefreshToken)
- `config/packages/` - configurações (security, lexik_jwt_authentication, nelmio_cors, gesdinet)
- `migrations/` - migrations do Doctrine

Configuração (rápida)
1. Clone o repositório

```bash
git clone <repo-url>
cd clinica-vet-backend
```

2. Instale dependências

```bash
composer install
```

3. Arquivo de ambiente
- Copie `.env` para `.env.local` e ajuste valores locais (não comitar `.env.local`).

Exemplo mínimo de `.env.local`:

```dotenv
APP_ENV=dev
DATABASE_URL="mysql://root:root@127.0.0.1:3306/clinica_vet?serverVersion=8.0.32&charset=utf8mb4"
CORS_ALLOW_ORIGIN='^https?://localhost:5173$'
JWT_PASSPHRASE=Osimbativeis@39
# JWT_SECRET_KEY e JWT_PUBLIC_KEY apontam para config/jwt/private.pem e config/jwt/public.pem por padrão
```

- Observação: o projeto já inclui chaves JWT em `config/jwt/private.pem` e `config/jwt/public.pem` (arquivo presente no repositório). Se quiser gerar novas chaves:

```bash
mkdir -p config/jwt
openssl genrsa -out config/jwt/private.pem 4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
# se quiser criptografar a private key, use -aes256 e ajuste JWT_PASSPHRASE
```

4. Banco de dados e migrations

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. Rodar o servidor local
- Usando Symfony CLI:

```bash
symfony server:start --watch
```

- Ou com PHP embutido (apenas para dev rápido):

```bash
php -S 127.0.0.1:8000 -t public
```

Testando a API (exemplos)

- Registrar usuário
```bash
curl -X POST http://127.0.0.1:8000/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"senha","firstName":"Joao","lastName":"Silva","phone":"11999999999"}'
```

- Login (retorna token JWT via Lexik)
```bash
curl -X POST http://127.0.0.1:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"senha"}'
```
Resposta esperada (exemplo):
```json
{ "token": "<JWT>" }
```

- Usar token para acessar rota protegida (ex: perfil)
```bash
curl -H "Authorization: Bearer <TOKEN>" http://127.0.0.1:8000/api/profile
```

- Listar pets (requer autenticação)
```bash
curl -H "Authorization: Bearer <TOKEN>" http://127.0.0.1:8000/api/pets
```

- Criar pet
```bash
curl -X POST http://127.0.0.1:8000/api/pets \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{"name":"Rex","gender":"M","breedId":1,"birthDate":"2020-05-01"}'
```

Refresh token
- A rota de refresh está disponível em `/auth/token/refresh` (gesdinet). O formato do corpo é `{"refresh_token":"..."}` dependendo do cliente de refresh configurado.

CORS
- A variável `CORS_ALLOW_ORIGIN` controla quais origens são permitidas. Em dev, definir `^https?://localhost:5173$` permite o frontend Vite no 5173.

Boas práticas de frontend
- Armazene o access token em memória (ou em cookie HttpOnly) e o refresh token em HttpOnly cookie quando possível para reduzir riscos de XSS.
- Use interceptor no Axios para injetar `Authorization: Bearer <token>` e para tentar renovar token quando receber 401.

Dicas de troubleshooting
- Se receber `JWT Token not found` verifique headers e se o token está sendo enviado e não expirou.
- Limpe cache se alterar configurações: `php bin/console cache:clear`
- Verifique permissões de arquivos das chaves JWT se ocorrer erro ao carregar a chave.

Próximos passos sugeridos
- Adicionar OpenAPI / Swagger para facilitar integração com frontend.
- Implementar paginação / filtros nos endpoints de listagem.
- Implementar revogação de refresh tokens / logout se necessário.

---

Se quiser, eu posso também gerar uma collection do Postman/Insomnia com os exemplos de chamadas (register -> login -> profile -> pets) ou criar um pequeno template Vue + Axios para autenticação e listagem de pets.
