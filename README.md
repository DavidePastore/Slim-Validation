# Slim Framework Validation

[![Latest version][ico-version]][link-packagist]
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

[![Build Status][ico-phpeye]][link-phpeye]
[![PSR2 Conformance][ico-styleci]][link-styleci]

A validation library for the Slim Framework. It internally uses [Respect/Validation][respect-validation].

## Table of contents

- [Install](#install)
- [Usage](#usage)
  - [Register per route](#register-per-route)
  - [Register for all routes](#register-for-all-routes)
  - [Route parameters](#route-parameters)
  - [JSON requests](#json-requests)
  - [XML requests](#xml-requests)
  - [Translate errors](#translate-errors)
  - [Use templates](#use-templates)
- [Testing](#testing)
- [Contributing](#contributing)
- [Credits](#credits)

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

//Create the validators
$usernameValidator = v::alnum()->noWhitespace()->length(1, 10);
$ageValidator = v::numeric()->positive()->between(1, 20);
$validators = array(
  'username' => $usernameValidator,
  'age' => $ageValidator
);

$app->get('/api/myEndPoint',function ($req, $res, $args) {
    //Here you expect 'username' and 'age' parameters
    if($req->getAttribute('has_errors')){
      //There are errors, read them
      $errors = $req->getAttribute('errors');

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

})->add(new \DavidePastore\Slim\Validation\Validation($validators));

$app->run();
```


### Register for all routes

```php
use Respect\Validation\Validator as v;

$app = new \Slim\App();

//Create the validators
$usernameValidator = v::alnum()->noWhitespace()->length(1, 10);
$ageValidator = v::numeric()->positive()->between(1, 20);
$validators = array(
  'username' => $usernameValidator,
  'age' => $ageValidator
);

// Register middleware for all routes
// If you are implementing per-route checks you must not add this
$app->add(new \DavidePastore\Slim\Validation\Validation($validators));

$app->get('/foo', function ($req, $res, $args) {
  //Here you expect 'username' and 'age' parameters
  if($req->getAttribute('has_errors')){
    //There are errors, read them
    $errors = $req->getAttribute('errors');

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
  if($req->getAttribute('has_errors')){
    //There are errors, read them
    $errors = $req->getAttribute('errors');
  } else {
    //No errors
  }
});

$app->run();
```


### Route parameters

```php
use Respect\Validation\Validator as v;

$app = new \Slim\App();

//Create the validators
$routeParamValidator = v::numeric()->positive();
$validators = array(
  'param' => $routeParamValidator,
);

$app->get('/foo/{param}', function ($req, $res, $args) {
  //Here you expect 'param' route parameter
  if($req->getAttribute('has_errors')){
    //There are errors, read them
    $errors = $req->getAttribute('errors');

    /* $errors contain:
    array(
        'param' => array(
          '"wrong" must be numeric',
        ),
    );
    */
  } else {
    //No errors
  }
})->add(new \DavidePastore\Slim\Validation\Validation($validators));

$app->run();
```

Note that requests parameters take priority over route parameters, so if you use the same name for a route and request parameter, the last will win and it will be considered for validation.


### JSON requests

You can also validate a JSON request. Let's say your body request is:

```json
{
	"type": "emails",
	"objectid": "1",
	"email": {
		"id": 1,
		"enable_mapping": "1",
		"name": "rq3r",
		"created_at": "2016-08-23 13:36:29",
		"updated_at": "2016-08-23 14:36:47"
	}
}
```

and you want to validate the `email.name` key. You can do it in this way:

```php
use Respect\Validation\Validator as v;

$app = new \Slim\App();

//Create the validators
$typeValidator = v::alnum()->noWhitespace()->length(3, 5);
$emailNameValidator = v::alnum()->noWhitespace()->length(1, 2);
$validators = array(
  'type' => $typeValidator,
  'email' => array(
    'name' => $emailNameValidator,
  ),
);
```

If you'll have an error, the result would be:

```php
//In your route
$errors = $req->getAttribute('errors');

print_r($errors);
/*
Array
(
    [email.name] => Array
        (
            [0] => "rq3r" must have a length between 1 and 2
        )

)
*/
```


### XML requests

You can also validate a XML request. Let's say your body request is:

Let's say you have a POST request with a XML in its body:

```xml
<person>
   <type>emails</type>
   <objectid>1</objectid>
   <email>
     <id>1</id>
     <enable_mapping>1</enable_mapping>
     <name>rq3r</name>
     <created_at>2016-08-23 13:36:29</created_at>
     <updated_at>2016-08-23 14:36:47</updated_at>
    </email>
</person>
```

and you want to validate the `email.name` key. You can do it in this way:

```php
use Respect\Validation\Validator as v;

$app = new \Slim\App();

//Create the validators
$typeValidator = v::alnum()->noWhitespace()->length(3, 5);
$emailNameValidator = v::alnum()->noWhitespace()->length(1, 2);
$validators = array(
  'type' => $typeValidator,
  'email' => array(
    'name' => $emailNameValidator,
  ),
);
```


If you'll have an error, the result would be:

```php
//In your route
$errors = $req->getAttribute('errors');

print_r($errors);
/*
Array
(
    [email.name] => Array
        (
            [0] => "rq3r" must have a length between 1 and 2
        )

)
*/
```


### Translate errors

You can provide a callable function to translate the errors.

```php
use Respect\Validation\Validator as v;

$app = new \Slim\App();

//Create the validators
$usernameValidator = v::alnum()->noWhitespace()->length(1, 10);
$ageValidator = v::numeric()->positive()->between(1, 20);
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

$middleware = new \DavidePastore\Slim\Validation\Validation($validators, $translator);

// Register middleware for all routes or only for one...

$app->run();
```

### Use templates

With the third parameter you can specify to use [templates][custom-messages].

```php
use Respect\Validation\Validator as v;

$app = new \Slim\App();

//Create the validators
$usernameValidator = v::alnum()->noWhitespace()->length(1, 10);
$ageValidator = v::numeric()->positive()->between(1, 20);
$validators = array(
  'hostname' => v::regex('/^[a-zA-Z]([-.a-zA-Z0-9]{0,61}[a-zA-Z0-9]){0,1}$/')->setTemplate('Hostname {{name}} is not valid')
);

$middleware = new \DavidePastore\Slim\Validation\Validation($validators, null, array('useTemplate' => true));

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
[custom-messages]: https://github.com/Respect/Validation/blob/master/docs/README.md#custom-messages
[ico-version]: https://img.shields.io/packagist/v/DavidePastore/Slim-Validation.svg?style=flat-square
[ico-travis]: https://travis-ci.org/DavidePastore/Slim-Validation.svg?branch=master
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/DavidePastore/Slim-Validation.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/davidepastore/Slim-Validation.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/davidepastore/slim-validation.svg?style=flat-square
[ico-phpeye]: http://php-eye.com/badge/DavidePastore/Slim-Validation/tested.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/48914054/shield

[link-packagist]: https://packagist.org/packages/davidepastore/slim-validation
[link-travis]: https://travis-ci.org/DavidePastore/Slim-Validation
[link-scrutinizer]: https://scrutinizer-ci.com/g/DavidePastore/Slim-Validation/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/DavidePastore/Slim-Validation
[link-downloads]: https://packagist.org/packages/davidepastore/slim-validation
[link-phpeye]: http://php-eye.com/package/DavidePastore/Slim-Validation
[link-styleci]: https://styleci.io/repos/48914054/
