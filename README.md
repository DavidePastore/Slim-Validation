# Slim Framework Validation

[![Latest version][ico-version]][link-packagist]
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]


A validation library for the Slim Framework. It internally uses [Respect/Validation](respect-validation).

## Install

Via Composer

``` bash
$ composer require davidepastore/slim-validation
```

Requires Slim 3.0.0 or newer.

## Usage

In most cases you want to register `DavidePastore\Slim\Validation` for a single route, however,
as it is middleware, you can also register it for all routes.


### Register per route

```php
use Respect\Validation\Validator as v;

$app = new \Slim\App();

// Fetch DI Container
$container = $app->getContainer();

// Register provider
$container['apiValidation'] = function () {
  //Create the validators
  $usernameValidator = v::alnum()->noWhitespace()->length(1, 20);
  $ageValidator = v::numeric()->positive()->between(1, 100);
  $validators = array(
    'username' => $usernameValidator,
    'age' => $ageValidator
  );

  return new \DavidePastore\Slim\Validation\Validation($validators);
};

$app->get('/api/myEndPoint',function ($req, $res, $args) {
    //Here you expect 'username' and 'age' parameters
    if($this->validation->hasErrors()){
      //There are errors, read them
      $errors = $this->getErrors();

      /* $errors contain:
      array(
        'username' => array(
          '"davidepastore" must have a length between 1 and 10',
        ),
        'age' => array(
          '"89" must be lower than or equals 20',
        ),
      );
      */
    } else {
      //No errors
    }

})->add($container->get('apiValidation'));

$app->run();
```


### Register for all routes

```php
use Respect\Validation\Validator as v;

$app = new \Slim\App();

// Fetch DI Container
$container = $app->getContainer();

// Register provider
$container['validation'] = function () {
  //Create the validators
  $usernameValidator = v::alnum()->noWhitespace()->length(1, 20);
  $ageValidator = v::numeric()->positive()->between(1, 100);
  $validators = array(
    'username' => $usernameValidator,
    'age' => $ageValidator
  );

  return new \DavidePastore\Slim\Validation\Validation($validators);
};

// Register middleware for all routes
// If you are implementing per-route checks you must not add this
$app->add($container->get('validation'));

$app->get('/foo', function ($req, $res, $args) {
  //Here you expect 'username' and 'age' parameters
  if($this->validation->hasErrors()){
    //There are errors, read them
    $errors = $this->getErrors();

    /* $errors contain:
    array(
      'username' => array(
        '"davidepastore" must have a length between 1 and 10',
      ),
      'age' => array(
        '"89" must be lower than or equals 20',
      ),
    );
    */
  } else {
    //No errors
  }
});

$app->post('/bar', function ($req, $res, $args) {
  //Here you expect 'username' and 'age' parameters
  if($this->validation->hasErrors()){
    //There are errors, read them
    $errors = $this->getErrors();
  } else {
    //No errors
  }
});

$app->run();
```


## Translate errors

You can provide a callable function to translate the errors.

```php
use Respect\Validation\Validator as v;

$app = new \Slim\App();

// Fetch DI Container
$container = $app->getContainer();

// Register provider
$container['validation'] = function () {
  //Create the validators
  $usernameValidator = v::alnum()->noWhitespace()->length(1, 20);
  $ageValidator = v::numeric()->positive()->between(1, 100);
  $validators = array(
    'username' => $usernameValidator,
    'age' => $ageValidator
  );

  $translator = function($message){
    $messages = [
        'These rules must pass for {{name}}' => 'Queste regole devono passare per {{name}}',
        '{{name}} must be a string' => '{{name}} deve essere una stringa',
        '{{name}} must have a length between {{minValue}} and {{maxValue}}' => '{{name}} deve avere una dimensione di caratteri compresa tra {{minValue}} e {{maxValue}}',
    ];
    return $messages[$message];
  };

  return new \DavidePastore\Slim\Validation\Validation($validators, $translator);
};

// Register middleware for all routes or only for one...

$app->run();
```

## Testing

``` bash
$ phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Davide Pastore](https://github.com/davidepastore)


[respect-validation]: https://github.com/Respect/Validation
[ico-version]: https://img.shields.io/packagist/v/DavidePastore/Slim-Validation.svg?style=flat-square
[ico-travis]: https://travis-ci.org/DavidePastore/Slim-Validation.svg?branch=master
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/DavidePastore/Slim-Validation.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/davidepastore/Slim-Validation.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/davidepastore/slim-validation.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/davidepastore/slim-validation
[link-travis]: https://travis-ci.org/DavidePastore/Slim-Validation
[link-scrutinizer]: https://scrutinizer-ci.com/g/DavidePastore/Slim-Validation/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/DavidePastore/Slim-Validation
[link-downloads]: https://packagist.org/packages/davidepastore/slim-validation
