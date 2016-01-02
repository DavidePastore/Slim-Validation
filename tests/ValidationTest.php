<?php

namespace DavidePastore\Slim\Validation\Tests;

use Slim\Http\Body;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;
use DavidePastore\Slim\Validation\Validation;
use Respect\Validation\Validator as v;

class ValidationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * PSR7 request object.
     *
     * @var Psr\Http\Message\RequestInterface
     */
    protected $request;

    /**
     * PSR7 response object.
     *
     * @var Psr\Http\Message\ResponseInterface
     */
    protected $response;

    /**
     * Run before each test.
     */
    public function setUp()
    {
        $uri = Uri::createFromString('https://example.com:443/foo/bar?username=davidepastore&age=89&optional=value');
        $headers = new Headers();
        $cookies = [];
        $env = Environment::mock();
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $this->request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $this->response = new Response();
    }

    public function testValidationWithoutErrors()
    {
        $usernameValidator = v::alnum()->noWhitespace()->length(1, 15);
        $validators = array(
          'username' => $usernameValidator,
        );
        $mw = new Validation($validators);

        $next = function ($req, $res) {
            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $this->assertFalse($mw->hasErrors());
        $this->assertEquals($validators, $mw->getValidators());
    }

    public function testValidationWithErrors()
    {
        $usernameValidator = v::alnum()->noWhitespace()->length(1, 5);
        $validators = array(
          'username' => $usernameValidator,
        );
        $mw = new Validation($validators);

        $next = function ($req, $res) {
            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $this->assertTrue($mw->hasErrors());
        $errors = array(
          'username' => array(
            '"davidepastore" must have a length between 1 and 5',
          ),
        );
        $this->assertEquals($errors, $mw->getErrors());
        $this->assertEquals($validators, $mw->getValidators());
    }

    public function testValidationNotExistingOptionalParameter()
    {
        $notExistingValidator = v::optional(v::alpha());
        $validators = array(
          'notExisting' => $notExistingValidator,
        );
        $mw = new Validation($validators);

        $next = function ($req, $res) {
            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $errors = array();
        $this->assertFalse($mw->hasErrors());
        $this->assertEquals($errors, $mw->getErrors());
    }

    public function testValidationNotExistingParameter()
    {
        $notExistingValidator = v::alpha();
        $validators = array(
          'notExisting' => $notExistingValidator,
        );
        $mw = new Validation($validators);

        $next = function ($req, $res) {
            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $errors = array(
          'notExisting' => array(
            'null must contain only letters (a-z)',
          ),
        );
        $this->assertTrue($mw->hasErrors());
        $this->assertEquals($errors, $mw->getErrors());
    }

    public function testValidationWithoutValidators()
    {
        $mw = new Validation();

        $next = function ($req, $res) {
            return $res;
        };

        $response = $mw($this->request, $this->response, $next);
        $errors = array();
        $validators = [];
        $this->assertFalse($mw->hasErrors());
        $this->assertEquals($errors, $mw->getErrors());
        $this->assertEquals($validators, $mw->getValidators());
    }

    public function testMultipleValidationWithoutErrors()
    {
        $usernameValidator = v::alnum()->noWhitespace()->length(1, 20);
        $ageValidator = v::numeric()->positive()->between(1, 100);
        $validators = array(
          'username' => $usernameValidator,
          'age' => $ageValidator,
        );
        $mw = new Validation($validators);

        $next = function ($req, $res) {
            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $this->assertFalse($mw->hasErrors());
        $this->assertEquals(array(), $mw->getErrors());
        $this->assertEquals($validators, $mw->getValidators());
    }

    public function testMultipleValidationWithErrors()
    {
        $usernameValidator = v::alnum()->noWhitespace()->length(1, 5);
        $ageValidator = v::numeric()->positive()->between(1, 60);
        $validators = array(
          'username' => $usernameValidator,
          'age' => $ageValidator,
        );
        $mw = new Validation($validators);

        $next = function ($req, $res) {
            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $this->assertTrue($mw->hasErrors());
        $errors = array(
          'username' => array(
            '"davidepastore" must have a length between 1 and 5',
          ),
          'age' => array(
            '"89" must be lower than or equals 60',
          ),
        );
        $this->assertEquals($errors, $mw->getErrors());
        $this->assertEquals($validators, $mw->getValidators());
    }

    public function testSetValidators()
    {
        $usernameValidator = v::alnum()->noWhitespace()->length(1, 20);
        $ageValidator = v::numeric()->positive()->between(1, 100);
        $validators = array(
          'username' => $usernameValidator,
          'age' => $ageValidator,
        );
        $mw = new Validation($validators);

        $next = function ($req, $res) {
            return $res;
        };

        $newUsernameValidator = v::alnum()->noWhitespace()->length(1, 10);
        $newAgeValidator = v::numeric()->positive()->between(1, 20);
        $newValidators = array(
          'username' => $newUsernameValidator,
          'age' => $newAgeValidator,
        );

        $mw->setValidators($newValidators);

        $response = $mw($this->request, $this->response, $next);

        $errors = array(
          'username' => array(
            '"davidepastore" must have a length between 1 and 10',
          ),
          'age' => array(
            '"89" must be lower than or equals 20',
          ),
        );

        $this->assertTrue($mw->hasErrors());
        $this->assertEquals($errors, $mw->getErrors());
        $this->assertEquals($newValidators, $mw->getValidators());
    }

    public function testValidationWithCallableTranslator()
    {
        $usernameValidator = v::alnum()->noWhitespace()->length(1, 5);
        $validators = array(
          'username' => $usernameValidator,
        );

        $translator = function ($message) {
          $messages = [
              'These rules must pass for {{name}}' => 'Queste regole devono passare per {{name}}',
              '{{name}} must be a string' => '{{name}} deve essere una stringa',
              '{{name}} must have a length between {{minValue}} and {{maxValue}}' => '{{name}} deve avere una dimensione di caratteri compresa tra {{minValue}} e {{maxValue}}',
          ];

          return $messages[$message];
        };

        $mw = new Validation($validators, $translator);

        $next = function ($req, $res) {
            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $this->assertTrue($mw->hasErrors());
        $errors = array(
          'username' => array(
            '"davidepastore" deve avere una dimensione di caratteri compresa tra 1 e 5',
          ),
        );
        $this->assertEquals($errors, $mw->getErrors());
        $this->assertEquals($validators, $mw->getValidators());
        $this->assertEquals($translator, $mw->getTranslator());
    }

    public function testSetTranslator()
    {
        $usernameValidator = v::alnum()->noWhitespace()->length(1, 5);
        $validators = array(
          'username' => $usernameValidator,
        );

        $translator = function ($message) {
          $messages = [
              'These rules must pass for {{name}}' => 'Queste regole devono passare per {{name}}',
              '{{name}} must be a string' => '{{name}} deve essere una stringa',
              '{{name}} must have a length between {{minValue}} and {{maxValue}}' => '{{name}} deve avere una dimensione di caratteri compresa tra {{minValue}} e {{maxValue}}',
          ];

          return $messages[$message];
        };

        $mw = new Validation($validators, $translator);

        $next = function ($req, $res) {
            return $res;
        };

        $newTranslator = function ($message) {
          $messages = [
              'These rules must pass for {{name}}' => 'Queste regole devono passare per {{name}} (nuovo)',
              '{{name}} must be a string' => '{{name}} deve essere una stringa (nuovo)',
              '{{name}} must have a length between {{minValue}} and {{maxValue}}' => '{{name}} deve avere una dimensione di caratteri compresa tra {{minValue}} e {{maxValue}} (nuovo)',
          ];

          return $messages[$message];
        };

        $mw->setTranslator($newTranslator);

        $response = $mw($this->request, $this->response, $next);

        $this->assertTrue($mw->hasErrors());
        $errors = array(
          'username' => array(
            '"davidepastore" deve avere una dimensione di caratteri compresa tra 1 e 5 (nuovo)',
          ),
        );
        $this->assertEquals($errors, $mw->getErrors());
        $this->assertEquals($validators, $mw->getValidators());
        $this->assertEquals($newTranslator, $mw->getTranslator());
    }
}
