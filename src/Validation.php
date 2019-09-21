<?php

namespace DavidePastore\Slim\Validation;

use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

/**
 * Validation for Slim.
 */
class Validation
{

    const QUERY_PARAMS = "queryParams";
    const PARSED_BODY = "parsedBody";
    const ROUTE_PARAMS = "routeParams";
    const EVERYTHING = "params";

    /**
     * Validators.
     *
     * @var array
     */
    protected $validators = [];

    /**
     * Options.
     *
     * @var array
     */
    protected $options = [
    ];

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
     * The 'errors' attribute name.
     *
     * @var string
     */
    protected $errors_name = 'errors';

    /**
     * The 'has_error' attribute name.
     *
     * @var string
     */
    protected $has_errors_name = 'has_errors';

    /**
     * The 'validators' attribute name.
     *
     * @var string
     */
    protected $validators_name = 'validators';

    /**
     * The 'translator' attribute name.
     *
     * @var string
     */
    protected $translator_name = 'translator';

    /**
     * Is the validators an instance of Validator?
     *
     * @var boolean
     */
    protected $isValidator = false;

    /**
     * Create new Validator service provider.
     *
     * @param null|array|ArrayAccess $validators
     * @param null|callable          $translator
     * @param []|array               $options
     */
    public function __construct($validators = null, $translator = null, $options = [])
    {
        // Set the validators
        if (is_null($validators)) {
            $this->validators = [];
        } else {
            $this->validators = $validators;
        }
        $this->translator = $translator;
        $this->options = array_merge($this->options, $options);
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

        foreach ($this->validators as $key => $validator) {
            if (is_array($validator) || $validator instanceof \ArrayAccess) {
                $validator = $validator;
                $isValidator = false;
            } elseif ($validator instanceof Validator) {
                $validator = $validator;
                $isValidator = true;
            } elseif (is_null($validators)) {
                $validator = [];
            }

            $params = $this->getParamsByKey($key, $request);
            if ($isValidator) {
                $this->validateParam($params, $validator, $key);
            } else {
                $this->validate($params, $validator, $key);
            }
        }

        $request = $request->withAttribute($this->errors_name, $this->getErrors());
        $request = $request->withAttribute($this->has_errors_name, $this->hasErrors());
        $request = $request->withAttribute($this->validators_name, $this->getValidators());
        $request = $request->withAttribute($this->translator_name, $this->getTranslator());

        return $next($request, $response);
    }

    /**
     * Validate the parameters by the given params, validators and actual keys.
     * This method populates the $errors attribute.
     *
     * @param array $params     The array of parameters.
     * @param array $validators The array of validators.
     * @param string $key       The key of the parameters.
     * @param array $actualKeys An array that will save all the keys of the tree to retrieve the correct value.
     */
    private function validate($params = [], $validators = [], $key, $actualKeys = [])
    {
        //Validate every parameters in the validators array
        foreach ($validators as $key => $validator) {
            $actualKeys[] = $key;
            print_r($params);
            $param = $this->getNestedParam($params, $actualKeys);
            print_r($param);
            if (is_array($validator)) {
                $this->validate($params, $validator, $key, $actualKeys);
            } else {
                $this->validateParam($param, $validator, $key, $actualKeys);
            }

            //Remove the key added in this foreach
            array_pop($actualKeys);
        }
    }

    /**
     * Validate a param.
     * @param any $param The parameter to validate.
     * @param any $validator The validator to use to validate the given parameter.
     * @param string $key The key of the parameters.
     * @param array $actualKeys An array with the position of the parameter.
     */
    private function validateParam($param, $validator, $key, $actualKeys = [])
    {
        try {
            //echo "Trying to validate ";
            //print_r($param);
            $validator->assert($param);
        } catch (NestedValidationException $exception) {
            if ($this->translator) {
                $exception->setParam('translator', $this->translator);
            }

            $messages = $exception->getMessages();
            if (empty($actualKeys)) {
                $this->errors[$key] = $messages;
            } else {
                //$this->errors[$key]
                $localErrors = [];
                if (array_key_exists($key, $this->errors)) {
                    $localErrors = $this->errors[$key];
                }
                $this->errors[$key] = array_merge($localErrors, array(
                    $key => array(
                        implode('.', $actualKeys) => $messages
                    )
                ));
                //$this->errors[$key][implode('.', $actualKeys)] = $messages;
            }
        }
    }

    /**
     * Get the nested parameter value.
     *
     * @param array $params An array that represents the values of the parameters.
     * @param array $keys   An array that represents the tree of keys to use.
     *
     * @return mixed The nested parameter value by the given params and tree of keys.
     */
    private function getNestedParam($params = [], $keys = [])
    {
        if (empty($keys)) {
            return $params;
        } else {
            $firstKey = array_shift($keys);
            if ($this->isArrayLike($params) && array_key_exists($firstKey, $params)) {
                $params = (array) $params;
                $paramValue = $params[$firstKey];

                return $this->getNestedParam($paramValue, $keys);
            } else {
                return;
            }
        }
    }

    /**
     * Check if the given $params is an array like variable.
     *
     * @param array $params The variable to check.
     *
     * @return boolean Returns true if the given $params parameter is array like.
     */
    private function isArrayLike($params)
    {
        return is_array($params) || $params instanceof \SimpleXMLElement;
    }

    /**
     * Get the parameters by the given key.
     * 
     * @param string $key                                       The key of the parameter.
     * @param \Psr\Http\Message\ServerRequestInterface $request PSR7 request.
     * 
     * @return array Returns the array with the parameters by the given key.
     */
    private function getParamsByKey($key, $request) {
        if (self::QUERY_PARAMS === $key) {
            return $request->getQueryParams();
        } elseif (self::PARSED_BODY === $key) {
            return $request->getParsedBody();
        } elseif (self::ROUTE_PARAMS === $key){
            return (array) $request->getAttribute('routeInfo')[2];
        } elseif (self::EVERYTHING === $key) {
            $params = $request->getParams();
            return array_merge((array) $request->getAttribute('routeInfo')[2], $params);
        } else {
            // TODO Create an internal flag to handle all the parameters as the root level
            $params = $request->getParams();
            return array_merge((array) $request->getAttribute('routeInfo')[2], $params);
        }
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
