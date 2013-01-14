<?php

namespace UriTemplate;

class Processor
{
    const RESERVED = '/[\-\._:\/?#\[\]@!\$%\'\(\)*+,;=]/';
    const EXPRESSION = '/\{(?P<operator>[+#\.\/;?&]?)(?P<var_list>(?:[a-zA-Z0-9_]|%[0-9a-fA-F]{2})(?:\.?(?:[a-zA-Z0-9_]|%[0-9a-fA-F]{2}))*\*?(?::\d+)?(?:,(?:[a-zA-Z0-9_]|%[0-9a-fA-F]{2})(?:\.?(?:[a-zA-Z0-9_]|%[0-9a-fA-F]{2}))*\*?(?::\d+)?)*)\}/';

    protected $encodeReserved = true;

    protected $prefix = '';

    protected $separator = ',';

    protected $formStyle = false;

    protected $uri = '';

    protected $context = '';

    public function __construct($uri = '', array $context = array())
    {
        $this->setUri($uri);
        $this->setContext($context);
    }

    /**
     * Gets the URI that is set to be processed.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Sets the URI to process later
     *
     * @param  array  The Uri
     * @return UriTemplate\Processor
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * Gets the context that is set to be used for processing.
     *
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Sets the context to use during processing.
     *
     * @param  array  The context
     * @return UriTemplate\Processor
     */
    public function setContext(array $context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Processed the Uri and returns the complete URI.
     *
     * @return string
     */
    public function process()
    {
        $this->reset();
        $uri = $this->uri;

        while (preg_match(self::EXPRESSION, $uri, $expression)) {
            $value = '';
            $rawExpression = $expression[0];
            $operator = $expression['operator'];
            $variableList = explode(',', $expression['var_list']);

            $this->setupOperator($operator);

            foreach ($variableList as &$var) {
                $var = $this->parseVariable($var);
                if (is_array($var)) {
                    $var = implode($this->separator, $var);
                }
            }
            $value = implode($this->separator, $variableList);

            $value = $this->prefix.$value;

            // Replace the Expression with the Value.  We only
            $uri = str_replace($rawExpression, $value, $uri);
        }

        return $uri;
    }

    /**
     * Reset all of the options to their defaults.
     */
    protected function reset()
    {
        $this->encodeReserved = true;
        $this->prefix = '';
        $this->separator = ',';
        $this->formStyle = false;
    }

    /**
     * Sets up the object to the correct settings depending on the operator.
     *
     * @param  string  The operator to setup
     */
    protected function setupOperator($operator)
    {
        switch ($operator) {
            case '+':
                $this->encodeReserved = false;
                break;
            case '#':
                $this->encodeReserved = false;
                $this->prefix = '#';
                break;
            case '?':
                $this->prefix = '?';
                $this->separator = '&';
                $this->formStyle = true;
                break;
            case '&':
                $this->prefix = '&';
                $this->separator = '&';
                $this->formStyle = true;
                break;
            case '.':
                $this->prefix = '.';
                $this->separator = '.';
                break;
            case '/':
                $this->prefix = '/';
                $this->separator = '/';
                break;
            case ';':
                $this->prefix = ';';
                $this->separator = ';';
                $this->formStyle = true;
                break;
        }
    }

    /**
     * Parses the variable from an expression into a value. This handles
     * all of the Level 4 Expansions while parsing.
     *
     * @param  string  The variable to parse
     * @return string|array  The value (encoded and parsed)
     */
    protected function parseVariable($variable) {
        $explode = false;
        if (strpos($variable, '*') !== false) {
            $explode = true;
            $variable = strstr($variable, '*', true);
        }

        $len = strstr($variable, ':');
        if ($len !== false) {
            $len = (int) ltrim($len, ':');
            $variable = strstr($variable, ':', true);
        }

        if ( ! isset($this->context[$variable])) {
            return null;
        }

        $value = $this->context[$variable];

        if ( ! is_array($value) && $len !== false) {
            $value = $this->encodeValue(substr($value, 0, $len));
            if ($this->formStyle) {
                $value = $variable.'='.$value;
            }
        } elseif (is_array($value)) {
            $newValue = array();
            foreach ($value as $k => $v) {
                if (is_int($k)) {
                    array_push($newValue, $this->encodeValue($v));
                } else {
                    if ($explode) {
                        array_push($newValue, $this->encodeValue($k).'='.$this->encodeValue($v));
                    } else {
                        array_push($newValue, $this->encodeValue($k));
                        array_push($newValue, $this->encodeValue($v));
                    }
                }
            }
            $value = $newValue;
        } else {
            $value = $this->encodeValue($value);
            if ($this->formStyle) {
                $value = $variable.'='.$value;
            }
        }

        return $value;
    }

    /**
     * Encodes a value to conform with RFC3986. rawurencode does the heavy
     * lifting.  However, we have to pre-process it so that when using the
     * "+" operator it doesn't encode the reserved characters.
     *
     * @param  string  The value to encode.
     * @return string
     */
    protected function encodeValue($value)
    {
        $chars = preg_split('//', $value);
        $value = '';

        foreach ($chars as $char) {
            if ($this->encodeReserved || ! preg_match(self::RESERVED, $char)) {
                $char = rawurlencode($char);
            }
            $value .= $char;
        }

        return $value;
    }
}
