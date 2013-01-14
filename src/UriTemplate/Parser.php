<?php

namespace UriTemplate;

class Parser
{
    const VARIABLE = '/((?:[a-zA-Z0-9_]|%[0-9a-fA-F]{2})(?:\.?(?:[a-zA-Z0-9_]|%[0-9a-fA-F]{2}))*)(\*)?(?::(\d+))?/';
    const RESERVED = '/[\-\._:\/?#\[\]@!\$%\'\(\)*+,;=]/';
    const EXPRESSION = '/\{(?P<operator>[+#\.\/;?&]?)(?P<var_list>(?:[a-zA-Z0-9_]|%[0-9a-fA-F]{2})(?:\.?(?:[a-zA-Z0-9_]|%[0-9a-fA-F]{2}))*\*?(?::\d+)?(?:,(?:[a-zA-Z0-9_]|%[0-9a-fA-F]{2})(?:\.?(?:[a-zA-Z0-9_]|%[0-9a-fA-F]{2}))*\*?(?::\d+)?)*)\}/';

    protected $encodeReserved = true;

    public function __construct($uri, $context)
    {
        $this->uri = $uri;
        $this->context = $context;
    }

    public function parse()
    {
        $uri = $this->uri;

        while (preg_match(self::EXPRESSION, $uri, $expression)) {
            $value = '';
            $rawExpression = $expression[0];
            $operator = $expression['operator'];
            $variableList = explode(',', $expression['var_list']);

            if ($operator === '+' || $operator === '#') {
                $this->encodeReserved = false;
            } else {
                $this->encodeReserved = true;
            }

            foreach ($variableList as &$var) {
                $var = $this->parseVariable($var);
                if (is_array($var)) {
                    $var = implode(',', $var);
                }
            }
            $value = implode(',', $variableList);

            if ($operator === '#') {
                $value = '#'.$value;
            }

            // Replace the Expression with the Value.  We only
            $uri = str_replace($rawExpression, $value, $uri);
        }

        return $uri;
    }

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
        }

        return $value;
    }

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