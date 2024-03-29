<?php

namespace DavidePastore\Slim\Validation\Tests;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Factory\DecoratedServerRequestFactory;
use Slim\Http\ServerRequest;
use Slim\Psr7\Stream;
use Slim\Psr7\Environment;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use DavidePastore\Slim\Validation\Validation;
use Respect\Validation\Validator as v;

class ValidationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * PSR7 request object.
     *
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * PSR7 response object.
     *
     * @var ResponseInterface
     */
    protected $response;

    /**
     * Run before each test.
     */
    public function setupGet()
    {
        $uriFactory = new UriFactory();
        $uri = $uriFactory->createUri('https://example.com:443/foo/bar?username=davidepastore&age=89&optional=value');
        $env = Environment::mock();
        $serverParams = $env;
        $requestFactory = new ServerRequestFactory();
        $serverRequestFactory = new DecoratedServerRequestFactory($requestFactory);
        $this->request = $serverRequestFactory->createServerRequest('GET', $uri, $serverParams);
        $this->response = new Response();
    }

    /**
     * Setup for the POST JSON requests.
     *
     * @param array $json The JSON to use to mock the body of the request.
     */
    public function setupJson($json)
    {
        $uriFactory = new UriFactory();
        $uri = $uriFactory->createUri('https://example.com:443/foo');
        $headers = new Headers();
        $headers->setHeader('Content-Type', 'application/json;charset=utf8');
        $cookies = [];
        $serverParams = Environment::mock([
          'SCRIPT_NAME' => '/index.php',
          'REQUEST_URI' => '/foo',
          'REQUEST_METHOD' => 'POST',
        ]);
        $stream = fopen('php://temp', 'w+');
        stream_copy_to_stream(fopen('php://input', 'r'), $stream);
        rewind($stream);
        $body = new Stream($stream);
        $body->write(json_encode($json));
        $request = new Request('POST', $uri, $headers, $cookies, $serverParams, $body);
        $this->request = new ServerRequest($request);
        $this->response = new Response();
    }

    /**
     * Setup for the XML POST requests.
     *
     * @param string $xml The XML to use to mock the body of the request.
     */
    public function setupXml($xml)
    {
        $uriFactory = new UriFactory();
        $uri = $uriFactory->createUri('https://example.com:443/foo');
        $headers = new Headers();
        $headers->setHeader('Content-Type', 'application/xml;charset=utf8');
        $cookies = [];
        $serverParams = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'POST',
        ]);
        $stream = fopen('php://temp', 'w+');
        stream_copy_to_stream(fopen('php://input', 'r'), $stream);
        rewind($stream);
        $body = new Stream($stream);
        $body->write($xml);
        $request = new Request('POST', $uri, $headers, $cookies, $serverParams, $body);
        $this->request = new ServerRequest($request);
        $this->response = new Response();
    }

    /**
     * Test for validation.
     *
     * @dataProvider validationProvider
     */
    public function testValidation($expectedValidators, $expectedTranslator, $expectedHasErrors, $expectedErrors, $requestType = 'GET', $body = null, $options = [])
    {
        if ($requestType === 'GET') {
            $this->setupGet();
        } elseif ($requestType === 'JSON') {
            $this->setupJson($body);
        } elseif ($requestType === 'XML') {
            $this->setupXml($body);
        }

        if (is_null($expectedValidators)) {
            $mw = new Validation();
            $expectedValidators = array();
        } elseif (is_null($expectedTranslator)) {
            $mw = new Validation($expectedValidators, null, $options);
        } else {
            $mw = new Validation($expectedValidators, $expectedTranslator, $options);
        }

        $handler = $this->setupHandler();

        $response = $mw($this->request, $handler);

        $errors = $handler->getErrors();
        $hasErrors = $handler->getHasErrors();
        $validators = $handler->getValidators();
        $translator = $handler->getTranslator();

        $this->assertEquals($expectedHasErrors, $hasErrors);
        $this->assertEquals($expectedErrors, $errors);
        $this->assertEquals($expectedValidators, $validators);
        $this->assertEquals($expectedTranslator, $translator);
    }

    /**
     * The validation provider.
     */
    public function validationProvider()
    {
        return array(
          //Validation without errors
          array(
            array(
              'username' => v::alnum()->noWhitespace()->length(1, 15),
            ),
            null,
            false,
            array(),
          ),
          //Validation with errors
          array(
            array(
              'username' => v::alnum()->noWhitespace()->length(1, 5),
            ),
            null,
            true,
            array(
              'username' => array(
                '"davidepastore" must have a length between 1 and 5',
              ),
            ),
          ),
          //Validation not existing optional parameter
          array(
            array(
              'notExisting' => v::optional(v::alpha()),
            ),
            null,
            false,
            array(),
          ),
          //Validation not existing parameter
          array(
            array(
              'notExisting' => v::alpha(),
            ),
            null,
            true,
            array(
              'notExisting' => array(
                'null must contain only letters (a-z)',
              ),
            ),
          ),
          //Validation without validators
          array(
            null,
            null,
            false,
            array(),
          ),
          //Multiple validation without errors
          array(
            array(
              'username' => v::alnum()->noWhitespace()->length(1, 20),
              'age' => v::numeric()->positive()->between(1, 100),
            ),
            null,
            false,
            array(),
          ),
          //Multiple validation with errors
          array(
            array(
              'username' => v::alnum()->noWhitespace()->length(1, 5),
              'age' => v::numeric()->positive()->between(1, 60),
            ),
            null,
            true,
            array(
              'username' => array(
                '"davidepastore" must have a length between 1 and 5',
              ),
              'age' => array(
                '"89" must be less than or equal to 60',
              ),
            ),
          ),
          //Validation with callable translator
          array(
            array(
              'username' => v::alnum()->noWhitespace()->length(1, 5),
            ),
            function ($message) {
                $messages = [
                  'These rules must pass for {{name}}' => 'Queste regole devono passare per {{name}}',
                  '{{name}} must be a string' => '{{name}} deve essere una stringa',
                  '{{name}} must have a length between {{minValue}} and {{maxValue}}' => '{{name}} deve avere una dimensione di caratteri compresa tra {{minValue}} e {{maxValue}}',
                ];

                return $messages[$message];
            },
            true,
            array(
              'username' => array(
                '"davidepastore" deve avere una dimensione di caratteri compresa tra 1 e 5',
              ),
            ),
          ),
          //JSON validation without errors
          array(
            array(
              'username' => v::alnum()->noWhitespace()->length(1, 15),
            ),
            null,
            false,
            array(),
            'JSON',
            array(
              'username' => 'jsonusername',
            ),
          ),
          //JSON validation with errors
          array(
            array(
              'username' => v::alnum()->noWhitespace()->length(1, 5),
            ),
            null,
            true,
            array(
              'username' => array(
                '"jsonusername" must have a length between 1 and 5',
              ),
            ),
            'JSON',
            array(
              'username' => 'jsonusername',
            ),
          ),
          //Complex JSON validation without errors
          array(
            array(
              'type' => v::alnum()->noWhitespace()->length(3, 8),
              'email' => array(
                'id' => v::numeric()->positive()->between(1, 20),
                'name' => v::alnum()->noWhitespace()->length(1, 5),
              ),
            ),
            null,
            false,
            array(),
            'JSON',
            array(
              'type' => 'emails',
              'objectid' => '1',
              'email' => array(
                'id' => 1,
                'enable_mapping' => '1',
                'name' => 'rq3r',
                'created_at' => '2016-08-23 13:36:29',
                'updated_at' => '2016-08-23 14:36:47',
              ),
            ),
          ),
          //Complex JSON validation with errors
          array(
            array(
              'type' => v::alnum()->noWhitespace()->length(3, 5),
              'email' => array(
                'name' => v::alnum()->noWhitespace()->length(1, 2),
              ),
            ),
            null,
            true,
            array(
              'type' => array(
                '"emails" must have a length between 3 and 5',
              ),
              'email.name' => array(
                '"rq3r" must have a length between 1 and 2',
              ),
            ),
            'JSON',
            array(
              'type' => 'emails',
              'objectid' => '1',
              'email' => array(
                'id' => 1,
                'enable_mapping' => '1',
                'name' => 'rq3r',
                'created_at' => '2016-08-23 13:36:29',
                'updated_at' => '2016-08-23 14:36:47',
              ),
            ),
          ),
          //More complex JSON validation without errors
          array(
            array(
              'email' => array(
                'sub' => array(
                  'sub-sub' => array(
                    'finally' => v::numeric()->positive()->between(1, 200),
                  ),
                ),
              ),
            ),
            null,
            false,
            array(),
            'JSON',
            array(
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
            ),
          ),
          //More complex JSON validation with errors
          array(
            array(
              'email' => array(
                'sub' => array(
                  'sub-sub' => array(
                    'finally' => v::numeric()->positive()->between(1, 200),
                  ),
                ),
              ),
            ),
            null,
            true,
            array(
              'email.sub.sub-sub.finally' => array(
                '321 must be less than or equal to 200',
              ),
            ),
            'JSON',
            array(
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
            ),
          ),
          //Nested complex JSON validation with errors
          array(
            array(
              'message' => array(
                'notification' => array(
                  'title' => v::stringType()->length(1, null)->setName("notificationTitle"),
                  'body' => v::stringType()->length(1, null)->setName("notificationBody"),
                  'actionName' => v::optional(v::stringType()->length(1, null))->setName("notificationAction")
                )
              ),
            ),
            null,
            true,
            array(
              'message.notification.title' => array(
                'notificationTitle must be a string',
                'notificationTitle must have a length greater than 1',
              ),
              'message.notification.body' => array(
                'notificationBody must be a string',
                'notificationBody must have a length greater than 1',
              ),
            ),
            'JSON',
            array(
              'message' => array(
                'notification' => 1,
              ),
            ),
          ),

          //XML validation without errors
          array(
            array(
               'name' => v::alnum()->noWhitespace()->length(1, 15),
            ),
            null,
            false,
            array(),
            'XML',
            '<person><name>Josh</name></person>',
          ),
          //XML validation with errors
          array(
            array(
               'name' => v::alnum()->noWhitespace()->length(1, 5),
            ),
            null,
            true,
            array(
              'name' => array(
                '"xmlusername" must have a length between 1 and 5',
              ),
            ),
            'XML',
            '<person><name>xmlusername</name></person>',
          ),
          //Complex XML validation without errors
          array(
            array(
              'type' => v::alnum()->noWhitespace()->length(3, 8),
              'email' => array(
                'id' => v::numeric()->positive()->between(1, 20),
                'name' => v::alnum()->noWhitespace()->length(1, 5),
              ),
            ),
            null,
            false,
            array(),
            'XML',
            '<person>
              <type>emails</type>
              <objectid>1</objectid>
              <email>
                <id>1</id>
                <enable_mapping>1</enable_mapping>
                <name>rq3r</name>
                <created_at>2016-08-23 13:36:29</created_at>
                <updated_at>2016-08-23 14:36:47</updated_at>
              </email>
            </person>',
          ),
          //Complex XML validation with errors
          array(
            array(
              'type' => v::alnum()->noWhitespace()->length(3, 5),
              'email' => array(
                'name' => v::alnum()->noWhitespace()->length(1, 2),
              ),
            ),
            null,
            true,
            array(
              'type' => array(
                '"emails" must have a length between 3 and 5',
              ),
              'email.name' => array(
                '"rq3r" must have a length between 1 and 2',
              ),
            ),
            'XML',
            '<person>
              <type>emails</type>
              <objectid>1</objectid>
              <email>
                <id>1</id>
                <enable_mapping>1</enable_mapping>
                <name>rq3r</name>
                <created_at>2016-08-23 13:36:29</created_at>
                <updated_at>2016-08-23 14:36:47</updated_at>
              </email>
            </person>',
          ),
          //More complex XML validation without errors
          array(
            array(
              'email' => array(
                'sub' => array(
                  'sub-sub' => array(
                    'finally' => v::numeric()->positive()->between(1, 200),
                  ),
                ),
              ),
            ),
            null,
            false,
            array(),
            'XML',
            '<person>
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
            </person>',
          ),
          //More complex XML validation with errors
          array(
            array(
              'email' => array(
                'sub' => array(
                  'sub-sub' => array(
                    'finally' => v::numeric()->positive()->between(1, 200),
                  ),
                ),
              ),
            ),
            null,
            true,
            array(
              'email.sub.sub-sub.finally' => array(
                '"321" must be less than or equal to 200',
              ),
            ),
            'XML',
            '<person>
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
            </person>',
          ),
      );
    }

    public function testSetValidators()
    {
        $this->setupGet();
        $usernameValidator = v::alnum()->noWhitespace()->length(1, 20);
        $ageValidator = v::numeric()->positive()->between(1, 100);
        $expectedValidators = array(
          'username' => $usernameValidator,
          'age' => $ageValidator,
        );
        $mw = new Validation($expectedValidators);

        $newUsernameValidator = v::alnum()->noWhitespace()->length(1, 10);
        $newAgeValidator = v::numeric()->positive()->between(1, 20);
        $newValidators = array(
          'username' => $newUsernameValidator,
          'age' => $newAgeValidator,
        );

        $mw->setValidators($newValidators);

        $handler = $this->setupHandler();

        $response = $mw($this->request, $handler);

        $errors = $handler->getErrors();
        $hasErrors = $handler->getHasErrors();
        $validators = $handler->getValidators();

        $expectedErrors = array(
          'username' => array(
            '"davidepastore" must have a length between 1 and 10',
          ),
          'age' => array(
            '"89" must be less than or equal to 20',
          ),
        );

        $this->assertTrue($hasErrors);
        $this->assertEquals($expectedErrors, $errors);
        $this->assertEquals($newValidators, $validators);
    }

    public function testSetTranslator()
    {
        $this->setupGet();
        $usernameValidator = v::alnum()->noWhitespace()->length(1, 5);
        $expectedValidators = array(
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

        $mw = new Validation($expectedValidators, $translator);

        $newTranslator = function ($message) {
            $messages = [
              'These rules must pass for {{name}}' => 'Queste regole devono passare per {{name}} (nuovo)',
              '{{name}} must be a string' => '{{name}} deve essere una stringa (nuovo)',
              '{{name}} must have a length between {{minValue}} and {{maxValue}}' => '{{name}} deve avere una dimensione di caratteri compresa tra {{minValue}} e {{maxValue}} (nuovo)',
          ];

            return $messages[$message];
        };

        $mw->setTranslator($newTranslator);

        $handler = $this->setupHandler();

        $response = $mw($this->request, $handler);

        $errors = $handler->getErrors();
        $hasErrors = $handler->getHasErrors();
        $validators = $handler->getValidators();
        $translator = $handler->getTranslator();

        $this->assertTrue($hasErrors);
        $expectedErrors = array(
          'username' => array(
            '"davidepastore" deve avere una dimensione di caratteri compresa tra 1 e 5 (nuovo)',
          ),
        );
        $this->assertEquals($expectedErrors, $errors);
        $this->assertEquals($expectedValidators, $validators);
        $this->assertEquals($newTranslator, $translator);
    }

    /**
     * Test for validation.
     *
     * @dataProvider routeParamValidationProvider
     */
    public function testRouteParamValidation($expectedValidators, $expectedHasErrors, $expectedErrors, $attributes)
    {
        $this->setupGet();
        $this->request = $this->request->withAttribute('routeInfo', [
            0,
            1,
          $attributes
        ]);

        $mw = new Validation($expectedValidators);

        $handler = $this->setupHandler();

        $response = $mw($this->request, $handler);

        $errors = $handler->getErrors();
        $hasErrors = $handler->getHasErrors();
        $validators = $handler->getValidators();

        $this->assertEquals($expectedValidators, $validators);
        $this->assertEquals($expectedHasErrors, $hasErrors);
        $this->assertEquals($expectedErrors, $errors);
    }

    /**
     * The validation provider for the route parameters.
     */
    public function routeParamValidationProvider()
    {
        return array(
          //Validation without errors
          array(
            array(
              'routeParam' => v::alnum()->noWhitespace()->length(1, 5),
            ),
            false,
            [],
            ['routeParam' => 'test'],
          ),
          //Validation with errors
          array(
            array(
              'routeParam' => v::alnum()->noWhitespace()->length(1, 5),
            ),
            true,
            array(
              'routeParam' => array(
                '"davidepastore" must have a length between 1 and 5',
              ),
            ),
            ['routeParam' => 'davidepastore'],
          ),
        );
    }

    /**
     * Create a new RequestHandler that can pull necessary attributes from the $request after validation.
     *
     * @return RequestHandlerInterface
     */
    private function setupHandler() : RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            protected $errors = null;
            protected $hasErrors = null;
            protected $translator = null;
            protected $validators = [];

            public function getErrors()
            {
                return $this->errors;
            }
            public function getHasErrors()
            {
                return $this->hasErrors;
            }
            public function getTranslator()
            {
                return $this->translator;
            }
            public function getValidators()
            {
                return $this->validators;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $getRequestAttributes = function ($req) {
                    $this->errors = $req->getAttribute('errors');
                    $this->hasErrors = $req->getAttribute('has_errors');
                    $this->validators = $req->getAttribute('validators');
                    $this->translator = $req->getAttribute('translator');
                };

                $getRequestAttributes($request);

                return new Response();
            }
        };
    }
}
