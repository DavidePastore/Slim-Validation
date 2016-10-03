<?php

namespace DavidePastore\Slim\Validation\Tests;

use Slim\Http\Body;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\RequestBody;
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

    /**
     * Setup for the POST requests.
     *
     * @param array $json The JSON to use to mock the body of the request.
     */
    public function setUpPost($json)
    {
        $uri = Uri::createFromString('https://example.com:443/foo');
        $headers = new Headers();
        $headers->set('Content-Type', 'application/json;charset=utf8');
        $cookies = [];
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'POST',
        ]);
        $serverParams = $env->all();
        $body = new RequestBody();
        $body->write(json_encode($json));
        $this->request = new Request('POST', $uri, $headers, $cookies, $serverParams, $body);
        $this->response = new Response();
    }

    /**
     * Setup for the XML POST requests.
     *
     * @param string $xml The XML to use to mock the body of the request.
     */
    public function setUpXmlPost($xml)
    {
        $uri = Uri::createFromString('https://example.com:443/foo');
        $headers = new Headers();
        $headers->set('Content-Type', 'application/xml;charset=utf8');
        $cookies = [];
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'POST',
        ]);
        $serverParams = $env->all();
        $body = new RequestBody();
        $body->write($xml);
        $this->request = new Request('POST', $uri, $headers, $cookies, $serverParams, $body);
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

    public function testJsonValidationWithoutErrors()
    {
        $json = array(
          'username' => 'jsonusername',
        );
        $this->setUpPost($json);
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

    public function testJsonValidationWithErrors()
    {
        $json = array(
          'username' => 'jsonusername',
        );
        $this->setUpPost($json);
        $usernameValidator = v::alnum()->noWhitespace()->length(1, 5);
        $validators = array(
          'username' => $usernameValidator,
        );
        $mw = new Validation($validators);

        $next = function ($req, $res) {
            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $errors = array(
          'username' => array(
            '"jsonusername" must have a length between 1 and 5',
          ),
        );

        $this->assertEquals($errors, $mw->getErrors());
        $this->assertEquals($validators, $mw->getValidators());
    }

    public function testComplexJsonValidationWithoutErrors()
    {
        $json = array(
          'type' => 'emails',
          'objectid' => '1',
          'email' => array(
            'id' => 1,
            'enable_mapping' => '1',
            'name' => 'rq3r',
            'created_at' => '2016-08-23 13:36:29',
            'updated_at' => '2016-08-23 14:36:47',
          ),
        );
        $this->setUpPost($json);
        $typeValidator = v::alnum()->noWhitespace()->length(3, 8);
        $emailNameValidator = v::alnum()->noWhitespace()->length(1, 5);
        $emailIdValidator = v::numeric()->positive()->between(1, 20);
        $validators = array(
          'type' => $typeValidator,
          'email' => array(
            'id' => $emailIdValidator,
            'name' => $emailNameValidator,
          ),
        );
        $mw = new Validation($validators);

        $next = function ($req, $res) {
            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $this->assertFalse($mw->hasErrors());
        $this->assertEquals($validators, $mw->getValidators());
    }

    public function testComplexJsonValidationWithErrors()
    {
        $json = array(
          'type' => 'emails',
          'objectid' => '1',
          'email' => array(
            'id' => 1,
            'enable_mapping' => '1',
            'name' => 'rq3r',
            'created_at' => '2016-08-23 13:36:29',
            'updated_at' => '2016-08-23 14:36:47',
          ),
        );
        $this->setUpPost($json);
        $typeValidator = v::alnum()->noWhitespace()->length(3, 5);
        $emailNameValidator = v::alnum()->noWhitespace()->length(1, 2);
        $validators = array(
          'type' => $typeValidator,
          'email' => array(
            'name' => $emailNameValidator,
          ),
        );
        $mw = new Validation($validators);

        $next = function ($req, $res) {
            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $errors = array(
          'type' => array(
            '"emails" must have a length between 3 and 5',
          ),
          'email.name' => array(
            '"rq3r" must have a length between 1 and 2',
          ),
        );

        $this->assertEquals($errors, $mw->getErrors());
        $this->assertEquals($validators, $mw->getValidators());
    }

    public function testMoreComplexJsonValidationWithoutErrors()
    {
        $json = array(
          'finally' => 'notvalid',
          'email' => array(
            'finally' => 'notvalid',
            'sub' => array(
              'finally' => 'notvalid',
              'sub-sub' => array(
                'finally' => 123,
              ),
            ),
          ),
        );
        $this->setUpPost($json);
        $finallyValidator = v::numeric()->positive()->between(1, 200);
        $validators = array(
          'email' => array(
            'sub' => array(
              'sub-sub' => array(
                'finally' => $finallyValidator,
              ),
            ),
          ),
        );
        $mw = new Validation($validators);

        $next = function ($req, $res) {
            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $this->assertFalse($mw->hasErrors());
        $this->assertEquals($validators, $mw->getValidators());
    }

    public function testMoreComplexJsonValidationWithErrors()
    {
        $json = array(
        'finally' => 22,
        'email' => array(
          'finally' => 33,
          'sub' => array(
            'finally' => 97,
            'sub-sub' => array(
              'finally' => 321,
            ),
          ),
        ),
      );
        $this->setUpPost($json);
        $finallyValidator = v::numeric()->positive()->between(1, 200);
        $validators = array(
          'email' => array(
            'sub' => array(
              'sub-sub' => array(
                'finally' => $finallyValidator,
              ),
            ),
          ),
        );
        $mw = new Validation($validators);

        $next = function ($req, $res) {
            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $errors = array(
          'email.sub.sub-sub.finally' => array(
            '321 must be lower than or equals 200',
          ),
        );

        $this->assertEquals($errors, $mw->getErrors());
        $this->assertEquals($validators, $mw->getValidators());
    }

    public function testXmlValidationWithoutErrors()
    {
        $xml = '<person><name>Josh</name></person>';
        $this->setUpXmlPost($xml);
        $nameValidator = v::alnum()->noWhitespace()->length(1, 15);
        $validators = array(
          'name' => $nameValidator,
        );
        $mw = new Validation($validators);

        $next = function ($req, $res) {
            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $this->assertFalse($mw->hasErrors());
        $this->assertEquals($validators, $mw->getValidators());
    }

    public function testXmlValidationWithErrors()
    {
        $xml = '<person><name>jsonusername</name></person>';
        $this->setUpXmlPost($xml);
        $nameValidator = v::alnum()->noWhitespace()->length(1, 5);
        $validators = array(
          'name' => $nameValidator,
        );
        $mw = new Validation($validators);

        $next = function ($req, $res) {
            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $errors = array(
          'name' => array(
            '"jsonusername" must have a length between 1 and 5',
          ),
        );

        $this->assertEquals($errors, $mw->getErrors());
        $this->assertEquals($validators, $mw->getValidators());
    }

    public function testComplexXmlValidationWithoutErrors()
    {
        $xml = '<person>
          <type>emails</type>
          <objectid>1</objectid>
          <email>
            <id>1</id>
            <enable_mapping>1</enable_mapping>
            <name>rq3r</name>
            <created_at>2016-08-23 13:36:29</created_at>
            <updated_at>2016-08-23 14:36:47</updated_at>
          </email>
        </person>';
        $this->setUpXmlPost($xml);
        $typeValidator = v::alnum()->noWhitespace()->length(3, 8);
        $emailNameValidator = v::alnum()->noWhitespace()->length(1, 5);
        $emailIdValidator = v::numeric()->positive()->between(1, 20);
        $validators = array(
          'type' => $typeValidator,
          'email' => array(
            'id' => $emailIdValidator,
            'name' => $emailNameValidator,
          ),
        );
        $mw = new Validation($validators);

        $next = function ($req, $res) {
            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $this->assertFalse($mw->hasErrors());
        $this->assertEquals($validators, $mw->getValidators());
    }

    public function testComplexXmlValidationWithErrors()
    {
        $xml = '<person>
          <type>emails</type>
          <objectid>1</objectid>
          <email>
            <id>1</id>
            <enable_mapping>1</enable_mapping>
            <name>rq3r</name>
            <created_at>2016-08-23 13:36:29</created_at>
            <updated_at>2016-08-23 14:36:47</updated_at>
          </email>
        </person>';
        $this->setUpXmlPost($xml);
        $typeValidator = v::alnum()->noWhitespace()->length(3, 5);
        $emailNameValidator = v::alnum()->noWhitespace()->length(1, 2);
        $validators = array(
          'type' => $typeValidator,
          'email' => array(
            'name' => $emailNameValidator,
          ),
        );
        $mw = new Validation($validators);

        $next = function ($req, $res) {
            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $errors = array(
          'type' => array(
            '"emails" must have a length between 3 and 5',
          ),
          'email.name' => array(
            '"rq3r" must have a length between 1 and 2',
          ),
        );

        $this->assertEquals($errors, $mw->getErrors());
        $this->assertEquals($validators, $mw->getValidators());
    }

    public function testMoreComplexXmlValidationWithoutErrors()
    {
        $xml = '<person>
          <finally>notvalid</finally>
          <email>
            <finally>notvalid</finally>
            <sub>
              <finally>notvalid</finally>
              <sub-sub>
                <finally>123</finally>
              </sub-sub>
            </sub>
          </email>
        </person>';
        $this->setUpXmlPost($xml);
        $finallyValidator = v::numeric()->positive()->between(1, 200);
        $validators = array(
          'email' => array(
            'sub' => array(
              'sub-sub' => array(
                'finally' => $finallyValidator,
              ),
            ),
          ),
        );
        $mw = new Validation($validators);

        $next = function ($req, $res) {
            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $this->assertFalse($mw->hasErrors());
        $this->assertEquals($validators, $mw->getValidators());
    }

    public function testMoreComplexXmlValidationWithErrors()
    {
        $xml = '<person>
        <finally>22</finally>
        <email>
          <finally>33</finally>
          <sub>
            <finally>97</finally>
            <sub-sub>
              <finally>321</finally>
            </sub-sub>
          </sub>
        </email>
      </person>';
        $this->setUpXmlPost($xml);
        $finallyValidator = v::numeric()->positive()->between(1, 200);
        $validators = array(
          'email' => array(
            'sub' => array(
              'sub-sub' => array(
                'finally' => $finallyValidator,
              ),
            ),
          ),
        );
        $mw = new Validation($validators);

        $next = function ($req, $res) {
            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $errors = array(
          'email.sub.sub-sub.finally' => array(
            '321 must be lower than or equals 200',
          ),
        );

        $this->assertEquals($errors, $mw->getErrors());
        $this->assertEquals($validators, $mw->getValidators());
    }
}
