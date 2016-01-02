<?php

namespace DavidePastore\Slim\Validation;

use Respect\Validation\Exceptions\NestedValidationException;

/**
 * Validation for Slim.
 */
class Validation
{
    /**
     * Validators.
     *
     * @var array
     */
    protected $validators = [];

    /**
     * The translator to use fro the exception message.
     *
     * @var callable
     */
    protected $translator = null;

    /**
     * Errors from the validation.
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Create new Validator service provider.
     *
     * @param null|array|ArrayAccess $validators
     * @param null|callable          $translator
     */
    public function __construct($validators = null, $translator = null)
    {
        // Set the validators
        if (is_array($validators) || $validators instanceof ArrayAccess) {
            $this->validators = $validators;
        } elseif (is_null($validators)) {
            $this->validators = [];
        }
        $this->translator = $translator;
    }

    /**
     * Validation middleware invokable class.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($request, $response, $next)
    {
        $this->errors = [];

        //Validate every parameters in the validators array
        foreach ($this->validators as $key => $validator) {
            $param = $request->getParam($key);
            try {
                $validator->assert($param);
            } catch (NestedValidationException $exception) {
                if ($this->translator) {
                    $exception->setParam('translator', $this->translator);
                }
                $this->errors[$key] = $exception->getMessages();
            }
        }

        return $next($request, $response);
    }

    /**
     * Check if there are any errors.
     *
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Get errors.
     *
     * @return array The errors array.
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get validators.
     *
     * @return array The validators array.
     */
    public function getValidators()
    {
        return $this->validators;
    }

    /**
     * Set validators.
     *
     * @param array $validators The validators array.
     */
    public function setValidators($validators)
    {
        $this->validators = $validators;
    }

    /**
     * Get translator.
     *
     * @return callable The translator.
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Set translator.
     *
     * @param callable $translator The translator.
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }
}
