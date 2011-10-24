<?php

namespace RedpillLinpro\SearchAnalyzerBundle;

class SearchAnalyzer
{

    protected $_fields;

    protected $_sniffers;

    public function __construct($fields, $sniffers)
    {
        $this->_fields = $fields;
        foreach ($this->_fields as $option => $fields) {
            $this->_fields[$option] = explode(',', $fields);
        }
        $this->_sniffers = $sniffers;
    }

    /**
     * Convert a freely formatted query string to a query array
     *
     * @param string $string
     * @return array query converted to array of real fieldname => value
     */
    public function convert($string, $array = array())
    {

        $string = trim((string) $string);
        if (empty($string) && empty($array))  {
            return null;
        }
        $result = array();
        if (!empty($string)) {
            if (strpos($string, ':') === false) $result = $this->_guess($string);
            else {
                $pattern = '/([^,:]+){1}:([^,:]+){1}/';
                $foundAnything = preg_match_all($pattern, $string, $matches);
                if (!$foundAnything) {
                    throw new \Exception('Not valid query');
                }
                $result = $this->_replaceFields(array_combine($matches[1], $matches[2]));
            }
        }

        if (!empty($array)) {
            $array = $this->_replaceFields($array);
            $result = array_merge($array, $result);
        }
        return $result;
    }

    /**
     * Convert an array back to string
     *
     * @param array $query
     * @return string
     */
    public function string(array $query)
    {
        $query_string = array();
        foreach ($query as $key => $param) {
            $query_string[] = "{$key}:{$param}";
        }
        return join(',', $query_string);
    }

    protected function _guess($string = '')
    {
        if (strpos($string, '@') && array_key_exists('email', $this->_sniffers))
            return array($this->_sniffers['email'] => $string);
        if (is_numeric($string) && array_key_exists('numeric', $this->_sniffers))
            return array($this->_sniffers['numeric'] => $string);

        return array($this->_sniffers['default'] => $string);
    }

    protected function _replaceFields(array $fields = array())
    {
        $ret = array();
        foreach ($fields as $field => $query) {
            $value = trim($query);
            if (empty($value)) continue;
            $field = trim($field);
            if (array_key_exists($field, $this->_fields)) {
                $ret[$field] = $value;
                continue;
            }
            foreach ($this->_fields as $real => $aliases) {
                if (in_array($field, $aliases)) {
                    $ret[$real] = $value;
                    break;
                }
            }
        }
        return $ret;
    }

    public function getFieldDefinitions()
    {
        return $this->_fields;
    }

}
