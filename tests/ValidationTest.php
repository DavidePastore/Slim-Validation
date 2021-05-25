<?php

namespace DavidePastore\Slim\Validation\Tests;

use DavidePastore\Slim\Validation\Validation;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Respect\Validation\Validator as v;

class ValidationTest extends TestCase
{
    public function testSetValidators()
    {
        $usernameValidator = v::alnum()->noWhitespace()->length(1, 20);
        $ageValidator = v::numericVal()->positive()->between(1, 100);
        $expectedValidators = [
            'username' => $usernameValidator,
            'age'      => $ageValidator,
        ];
        $mw = new Validation($expectedValidators);

        $newUsernameValidator = v::alnum()->noWhitespace()->length(1, 10);
        $newAgeValidator = v::numericVal()->positive()->between(1, 20);
        $newValidators = [
            'username' => $newUsernameValidator,
            'age'      => $newAgeValidator,
        ];

        $mw->setValidators($newValidators);

        $expectedErrors = [
            'username' => [
                'length' => '"davidepastore" must have a length between 1 and 10',
            ],
            'age' => [
                'between' => '"89" must be between 1 and 20',
            ],
        ];

        $app = $this->getAppInstance();

        $app->get('/foo', function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($expectedErrors, $newValidators) {
            $errors = $request->getAttribute('errors');
            $hasErrors = $request->getAttribute('has_errors');
            $validators = $request->getAttribute('validators');

            \PHPUnit\Framework\Assert::assertTrue($hasErrors);
            \PHPUnit\Framework\Assert::assertEquals($expectedErrors, $errors);
            \PHPUnit\Framework\Assert::assertEquals($newValidators, $validators);

            $response->getBody()->write('Hello world!');

            return $response;
        })->add($mw);

        $params = [
            'username' => 'davidepastore',
            'age'      => 89,
        ];

        $url = sprintf('/foo?%s', http_build_query($params));

        $request = $this->createRequest('GET', $url);

        $response = $app->handle($request);

        $result = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testSetTranslator()
    {
        $usernameValidator = v::alnum()->noWhitespace()->length(1, 5);
        $expectedValidators = [
            'username' => $usernameValidator,
        ];

        $translator = [
            'alnum'        => 'Queste regole devono passare per {{name}}', //'These rules must pass for {{name}}'
            'noWhitespace' => '{{name}} deve essere una stringa', //'{{name}} must be a string'
            'length'       => '{{name}} deve avere una dimensione di caratteri compresa tra {{minValue}} e {{maxValue}}', //'{{name}} must have a length between {{minValue}} and {{maxValue}}'
        ];

        $mw = new Validation($expectedValidators, $translator);

        $newTranslator = [
            'length' => '"davidepastore" deve avere una dimensione di caratteri compresa tra 1 e 5 (nuovo)', //'{{name}} must have a length between {{minValue}} and {{maxValue}}'
        ];

        $mw->setTranslator($newTranslator);

        $expectedErrors = [
            'username' => [
                'length' => '"davidepastore" deve avere una dimensione di caratteri compresa tra 1 e 5 (nuovo)',
            ],
        ];

        $app = $this->getAppInstance();

        $app->post('/translator', function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($expectedErrors, $expectedValidators, $newTranslator) {
            $errors = $request->getAttribute('errors');
            $hasErrors = $request->getAttribute('has_errors');
            $validators = $request->getAttribute('validators');
            $translator = $request->getAttribute('translator');

            \PHPUnit\Framework\Assert::assertTrue($hasErrors);
            \PHPUnit\Framework\Assert::assertEquals($expectedErrors, $errors);
            \PHPUnit\Framework\Assert::assertEquals($expectedValidators, $validators);
            \PHPUnit\Framework\Assert::assertEquals($newTranslator, $translator);

            $response->getBody()->write('Hello world!');

            return $response;
        })->add($mw);

        $data = [
            'username' => 'davidepastore',
        ];

        $request = $this->createJsonRequest('POST', '/translator', $data);

        $response = $app->handle($request);

        $result = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
    }
}
