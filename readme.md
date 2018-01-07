## Blog com laravel 5.5

O sistema é um Blog em Laravel 5.5 e tem os seguintes requisitos:

* Autenticação com verificação por email
* Níveis de autorização (ACL Laravel) - Editor, Leitor, Autor
* Leitor pode adicionar posts aos favoritos
* Leitor pode curtir posts

### Gerando as chaves e autenticação

```php
php artisan key:generate
php artisan make:auth
```

### Criando o banco de dados da aplicação

```php
echo "create database blogtwo" | mysql -u root -p
```

