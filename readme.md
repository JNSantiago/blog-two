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

#### Alterando o Auth Controllers

Quando registramos uma conta, o usuário é logado por padrão. O RegisterController usa o RegistersUsers, que é definido em Illuminate\Foundation\Auth. Nós podemos sobrescrever o método registered() no RegisterController para deslogar o usuário imediatamente após o cadastro.

```php
protected function registered(Request $request, $user)
{
    $this->guard()->logout();

    return redirect('/login')->withInfo('Please verify your email');
}
```

O método guard é definido em RegistersUsers e irá pegar o usuário logado e deslogá-lo. Redirecionamos então o usuário para a página de login com a devida mensagem.

Nós precisamos garantir que o usuário não possa se logar sem que tenha verificado o email. Tem muitas maneiras de fazer, mas a mais simples delas é sobrescrever o método authenticated() de AuthenticatesUsers, no nosso LoginController:

```php
protected function authenticated(Request $request, $user)
{
    if(!$user->hasVerifiedEmail()) {
        $this->guard()->logout();

        return redirect('/login')
            ->withError('Por favor ative a sua conta. <a href="' . route('auth.verify.resend') . '?email=' . $user->email .'">Reenviar?</a>');
    }
}
```

Quando o usuário resetar o email com sucesso, por padrão o laravel o loga. O ResetPasswordController usa o ResetsPasswords de Illuminate\Foundation\Auth, e implementa o método sendResetResponse(), que verifica se o reset foi realizado com sucesso. Precisamos sobrescrever esse método no ResetPasswordController.

```php
protected function sendResetResponse($response)
{
    if(!$this->guard()->user()->hasVerifiedEmail()) {
        $this->guard()->logout();
        return redirect('/login')->withInfo('Password changed successfully. Please verify your email');
    }
    return redirect($this->redirectPath())
                        ->with('status', trans($response));
}
```

#### Registrando um Service Provider para Eloquent Events

Agora nós vamos criar um service provider e registra-lo em config\app.php. Este service provider irá observar um especifico Eloquent Event: created no nosso modelo user. Com este service, quando um usuário for criado é gerado um token para este usuário. É usado a função random_bytes() para gerar 64 caracteres, e um evento UserRegistered é chamado.

```php
public function boot()
{
    User::created(function($user) {

        $token = $user->verificationToken()->create([
            'token' => bin2hex(random_bytes(32))
        ]);

        event(new UserRegistered($user));
    });
}
```

#### Criando um UserRegistered event

A idéia por trás de usar events e listeners aqui é além de disparam um email de verificação, é que posteriormente também poderemos enviar um e-mail de boas-vindas, ou fazer algumas coisas de pagamento quando um usuário se registrar. Podemos facilmente ligar tudo sem muita refatoração.

Abaixo segue o código de UserRegistered event (Criar pasta Events se nao houver):

```php
public $user;

public function __construct(User $user)
{
    $this->user = $user;
}
```

#### Criando um UserRequestedVerificationEmail

Um novo evento será criado afim de que o usuário possa solicitar um reenvio de email. Criar um UserRequestedVerificationEmail.

```php
public $user;

public function __construct(User $user)
{
    $this->user = $user;
}
```

#### Criando um Listener

Agora que os eventos estão prontos, criar um listener SendVerificationEmail, que é apenas um Job, que quando o UserRegistered ou o UserRequestedVerificationEmail são disparados, um novo email e enviado.

* Criar uma pasta Listeners
* Adicionar o arquivo SendVerificationEmail.php

```php
<?php
namespace App\Listeners;
use Mail;
use App\User;
use App\Mail\SendVerificationToken;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
class SendVerificationEmail
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
    /**
     * Handle the event.
     *
     * @param  Event  $event
     * @return void
     */
    public function handle($event)
    {
        //$user = User::find($event->user->id);
        //dd($user->verificationToken);
        Mail::to($event->user)->send(new SendVerificationToken($event->user->verificationToken));
    }
}
```
