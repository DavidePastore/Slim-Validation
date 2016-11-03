<?php

namespace DavidePastore\Slim\Validation\Tests;

use Slim\Http\Request;
use Slim\Http\Response;
use DavidePastore\Slim\Validation\Validation;
use Respect\Validation\Validator as v;

class ValidationPostTest extends \PHPUnit_Framework_TestCase
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

    /*
    public function testJsonValidationWithErrors()
    {
        $json = array(
          'username' => 'jsonusername',
        );
        $this->setUpPost($json);
        $usernameValidator = v::alnum()->noWhitespace()->length(1, 5);
        $expectedValidators = array(
          'username' => $usernameValidator,
        );
        $mw = new Validation($expectedValidators);

        $errors = null;
        $hasErrors = null;
        $validators = [];
        $next = function ($req, $res) use (&$errors, &$hasErrors, &$validators) {
            $errors = $req->getAttribute('errors');
            $hasErrors = $req->getAttribute('has_errors');
            $validators = $req->getAttribute('validators');

            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $expectedErrors = array(
          'username' => array(
            '"jsonusername" must have a length between 1 and 5',
          ),
        );

        $this->assertEquals($expectedErrors, $errors);
        $this->assertEquals($expectedValidators, $validators);
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
        $expectedValidators = array(
          'type' => $typeValidator,
          'email' => array(
            'id' => $emailIdValidator,
            'name' => $emailNameValidator,
          ),
        );
        $mw = new Validation($expectedValidators);

        $errors = null;
        $hasErrors = null;
        $validators = [];
        $next = function ($req, $res) use (&$errors, &$hasErrors, &$validators) {
            $errors = $req->getAttribute('errors');
            $hasErrors = $req->getAttribute('has_errors');
            $validators = $req->getAttribute('validators');

            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $this->assertFalse($hasErrors);
        $this->assertEquals($expectedValidators, $validators);
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
        $expectedValidators = array(
          'type' => $typeValidator,
          'email' => array(
            'name' => $emailNameValidator,
          ),
        );
        $mw = new Validation($expectedValidators);

        $errors = null;
        $hasErrors = null;
        $validators = [];
        $next = function ($req, $res) use (&$errors, &$hasErrors, &$validators) {
            $errors = $req->getAttribute('errors');
            $hasErrors = $req->getAttribute('has_errors');
            $validators = $req->getAttribute('validators');

            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $expectedErrors = array(
          'type' => array(
            '"emails" must have a length between 3 and 5',
          ),
          'email.name' => array(
            '"rq3r" must have a length between 1 and 2',
          ),
        );

        $this->assertEquals($expectedErrors, $errors);
        $this->assertEquals($expectedValidators, $validators);
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
        $expectedValidators = array(
          'email' => array(
            'sub' => array(
              'sub-sub' => array(
                'finally' => $finallyValidator,
              ),
            ),
          ),
        );
        $mw = new Validation($expectedValidators);

        $hasErrors = null;
        $validators = [];
        $next = function ($req, $res) use (&$hasErrors, &$validators) {
            $hasErrors = $req->getAttribute('has_errors');
            $validators = $req->getAttribute('validators');

            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $this->assertFalse($hasErrors);
        $this->assertEquals($expectedValidators, $validators);
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
        $expectedValidators = array(
          'email' => array(
            'sub' => array(
              'sub-sub' => array(
                'finally' => $finallyValidator,
              ),
            ),
          ),
        );
        $mw = new Validation($expectedValidators);

        $errors = null;
        $validators = [];
        $next = function ($req, $res) use (&$errors, &$validators) {
            $errors = $req->getAttribute('errors');
            $validators = $req->getAttribute('validators');

            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $expectedErrors = array(
          'email.sub.sub-sub.finally' => array(
            '321 must be lower than or equals 200',
          ),
        );

        $this->assertEquals($expectedErrors, $errors);
        $this->assertEquals($expectedValidators, $validators);
    }

    public function testXmlValidationWithoutErrors()
    {
        $xml = '<person><name>Josh</name></person>';
        $this->setUpXmlPost($xml);
        $nameValidator = v::alnum()->noWhitespace()->length(1, 15);
        $expectedValidators = array(
          'name' => $nameValidator,
        );
        $mw = new Validation($expectedValidators);

        $hasErrors = null;
        $validators = [];
        $next = function ($req, $res) use (&$hasErrors, &$validators) {
            $hasErrors = $req->getAttribute('has_errors');
            $validators = $req->getAttribute('validators');

            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $this->assertFalse($hasErrors);
        $this->assertEquals($expectedValidators, $validators);
    }

    public function testXmlValidationWithErrors()
    {
        $xml = '<person><name>jsonusername</name></person>';
        $this->setUpXmlPost($xml);
        $nameValidator = v::alnum()->noWhitespace()->length(1, 5);
        $expectedValidators = array(
          'name' => $nameValidator,
        );
        $mw = new Validation($expectedValidators);

        $errors = null;
        $validators = [];
        $next = function ($req, $res) use (&$errors, &$validators) {
            $errors = $req->getAttribute('errors');
            $validators = $req->getAttribute('validators');

            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $expectedErrors = array(
          'name' => array(
            '"jsonusername" must have a length between 1 and 5',
          ),
        );

        $this->assertEquals($expectedErrors, $errors);
        $this->assertEquals($expectedValidators, $validators);
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
        $expectedValidators = array(
          'type' => $typeValidator,
          'email' => array(
            'id' => $emailIdValidator,
            'name' => $emailNameValidator,
          ),
        );
        $mw = new Validation($expectedValidators);

        $hasErrors = null;
        $validators = [];
        $next = function ($req, $res) use (&$hasErrors, &$validators) {
            $hasErrors = $req->getAttribute('has_errors');
            $validators = $req->getAttribute('validators');

            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $this->assertFalse($hasErrors);
        $this->assertEquals($expectedValidators, $validators);
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
        $expectedValidators = array(
          'type' => $typeValidator,
          'email' => array(
            'name' => $emailNameValidator,
          ),
        );
        $mw = new Validation($expectedValidators);

        $errors = null;
        $validators = [];
        $next = function ($req, $res) use (&$errors, &$validators) {
            $errors = $req->getAttribute('errors');
            $validators = $req->getAttribute('validators');

            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $expectedErrors = array(
          'type' => array(
            '"emails" must have a length between 3 and 5',
          ),
          'email.name' => array(
            '"rq3r" must have a length between 1 and 2',
          ),
        );

        $this->assertEquals($expectedErrors, $errors);
        $this->assertEquals($expectedValidators, $validators);
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
        $expectedValidators = array(
          'email' => array(
            'sub' => array(
              'sub-sub' => array(
                'finally' => $finallyValidator,
              ),
            ),
          ),
        );
        $mw = new Validation($expectedValidators);

        $hasErrors = null;
        $validators = [];
        $next = function ($req, $res) use (&$hasErrors, &$validators) {
            $hasErrors = $req->getAttribute('has_errors');
            $validators = $req->getAttribute('validators');

            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $this->assertFalse($hasErrors);
        $this->assertEquals($expectedValidators, $validators);
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
        $expectedValidators = array(
          'email' => array(
            'sub' => array(
              'sub-sub' => array(
                'finally' => $finallyValidator,
              ),
            ),
          ),
        );
        $mw = new Validation($expectedValidators);

        $errors = null;
        $validators = [];
        $next = function ($req, $res) use (&$errors, &$validators) {
            $errors = $req->getAttribute('errors');
            $validators = $req->getAttribute('validators');

            return $res;
        };

        $response = $mw($this->request, $this->response, $next);

        $expectedErrors = array(
          'email.sub.sub-sub.finally' => array(
            '"321" must be lower than or equals 200',
          ),
        );

        $this->assertEquals($expectedErrors, $errors);
        $this->assertEquals($expectedValidators, $validators);
    }
    */
}
