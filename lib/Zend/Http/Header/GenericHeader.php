<?php

namespace Zend\Http\Header;

class GenericHeader implements HeaderDescription
{
    /**
     * @var string
     */
    protected $fieldName = null;

    /**
     * @var string
     */
    protected $fieldValue = null;

    /**
     * Factory to generate a header object from a string
     *
     * @static
     * @param string $headerLine
     * @return GenericHeader
     */
    public static function fromString($headerLine)
    {
        list($fieldName, $fieldValue) = explode(': ', $headerLine, 2);
        $header = new static($fieldName, $fieldValue);
        return $header;
    }

    /**
     * Constructor
     * 
     * @param null|string $fieldName
     * @param null|string $fieldValue
     */
    public function __construct($fieldName = null, $fieldValue = null)
    {
        if ($fieldName) {
            $this->setFieldName($fieldName);
        }

        if ($fieldValue) {
            $this->setFieldValue($fieldValue);
        }
    }

    /**
     * Set header field name
     * 
     * @param  string $fieldName
     * @return GenericHeader
     */
    public function setFieldName($fieldName)
    {
        if (!is_string($fieldName) || empty($fieldName)) {
            throw new Exception\InvalidArgumentException('Header name must be a string');
        }

        // Pre-filter to normalize valid characters, change underscore to dash
        $fieldName = str_replace(' ', '-', ucwords(str_replace(array('_', '-'), ' ', $fieldName)));

        // Validate what we have
        if (!preg_match('/^[a-z][a-z0-9-]*$/i', $fieldName)) {
            throw new Exception\InvalidArgumentException('Header name must start with a letter, and consist of only letters, numbers, and dashes');
        }

        $this->fieldName = $fieldName;
        return $this;
    }

    /**
     * Retrieve header field name
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Set header field value
     * 
     * @param  string $fieldValue
     * @return GenericHeader
     */
    public function setFieldValue($fieldValue)
    {
        $fieldValue = (string) $fieldValue;

        if (empty($fieldValue) || preg_match('/^\s+$/', $fieldValue)) {
            $fieldValue = '';
        }

        $this->fieldValue = $fieldValue;
        return $this;
    }

    /**
     * Retrieve header field value
     * 
     * @return string
     */
    public function getFieldValue()
    {
        return $this->fieldValue;
    }

    /**
     * Cast to string as a well formed HTTP header line
     *
     * Returns in form of "NAME: VALUE\r\n"
     *
     * @return string
     */
    public function toString()
    {
        $name  = $this->getFieldName();
        $value = $this->getFieldValue();

        return $name. ': ' . $value . "\r\n";
    }

}
