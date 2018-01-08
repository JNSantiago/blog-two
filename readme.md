## Blog com laravel 5.5

O sistema é um Blog em Laravel 5.5 e tem os seguintes requisitos:

* Autenticação com verificação por email
* Níveis de autorização (ACL Laravel) - Editor, Leitor, Autor
* Leitor pode adicionar posts aos favoritos
* Leitor pode curtir posts

### Configurações iniciais

#### Gerando as chaves e autenticação

```php
php artisan key:generate
php artisan make:auth
```

#### Criando o banco de dados da aplicação

```php
echo "create database blogtwo" | mysql -u root -p
```

Obs.: Executar projeto:

```php
php artisan serve
// Com VAGRANT
php artisan serve --host=0.0.0.0:8000
```

### Criando funcionalidade de confirmação de email

O processo funciona assim:

* Quando o usuário se registra, ele é deslogado automaticamente
* Registrar um Provider EloquentEventServiceProvider, ele irá escutar por Eloquent Events, quando o usuário for criado um token será gerado e o evento será disparado pelo UserRegistered.
* Se o usuário requisitar um reeenvio de email de verificação , um outro evento será disparado: UserRequestedVerificationEmail
* Criar um event listener SendVerificationEmail que irá escutar por um dos eventos: UserRegistered ou UserRequestedVerificationEmail, e enviar um email usando a classe de email.
* Quando o usuário visitar o link enviado por email, é verificado se o token do banco de dados bate com o token enviado, e em caso de sucesso, a flag é alterada para true.
* Quando o usuário tentar logar, é checado se a flag é true. Se não, ele ainda não verificou o email e será redirecionado para o login.

#### Configurando as migrações

Adicionar um campo booleano na tabela de usuario, o campo é falso por padrão:

```php
$table->boolean('verified')->default(false);
```

Criar a tabela verification_tokens que irá guardar o token geardo para o usuário.

```php
php artisan make:model VerificationToken
php artisan make:migration verification_tokens
```

```php
$table->integer('user_id')->unsigned()->index(); 
$table->string('token');
$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
```

Rodar as migrações:

```php
php artisan make:migrate
```

#### Configurando os relacionamentos

Os relacionamentos entre User e VerificationToken ocorrem da seguinte maneira: um VerificationToken pertence(belongs to) a um User, ao passo que um User tem um (has one) VerificationToken.

```php
//User model
public function verificationToken()
{
    return $this->hasOne(VerificationToken::class);
}
```

```php
//VerificationToken model
public function user()
{
	return $this->belongsTo(User::class);
}
```

Mudar o nome da rota para o modelo VerificationToken.

```php
public function getRouteKeyName()
{
	return 'token';
}
```

#### Configurando o VerificationController e as rotas

Nós criaremos o VerificationController para verificar o token e reenviar o email de verificação. Neste controller, haverão dois métodos:

* verify(): Método que irá receber uma instância do modelo VerificationToken
* resend(): Método que será usado para reenviar o email de confirmação para o usuário.

Abaixo segue o exemplo do VerificationController (Movê-lo para App\Http\Controllers\Auth) e alterar namespace para 'namespace App\Http\Controllers\Auth;'.

```php
php artisan make:controller VerificationController
```

```php
class VerificationController extends Controller
{
    public function verify(VerificationToken $token)
    {
    	//
    }

    public function resend(Request $request)
    {
    	//
    }
}
```

Definir as duas rotas correspondentes ao VerificationController.

```php
Route::get('/verify/token/{token}', 'Auth\VerificationController@verify')->name('auth.verify'); 
Route::get('/verify/resend', 'Auth\VerificationController@resend')->name('auth.verify.resend');
```