<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Service_Amazon
 * @subpackage SimpleDb
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Service\Amazon\SimpleDb;

use Zend\Crypt,
    Zend\Http,
    Zend\Uri;

/**
 * @category   Zend
 * @package    Zend_Service_Amazon
 * @subpackage SimpleDb
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class SimpleDb extends \Zend\Service\Amazon\AbstractAmazon
{
    /* Notes */
    // TODO SSL is required

    /**
     * The HTTP query server
     */
    protected $_sdbEndpoint = 'sdb.amazonaws.com/';

    /**
     * Period after which HTTP request will timeout in seconds
     */
    protected $_httpTimeout = 10;

    /**
     * The API version to use
     */
    protected $_sdbApiVersion = '2009-04-15';

    /**
     * Signature Version
     */
    protected $_signatureVersion = '2';

    /**
     * Signature Encoding Method
     */
    protected $_signatureMethod = 'HmacSHA256';

    /**
     * Create Amazon SimpleDB client.
     *
     * @param  string $access_key       Override the default Access Key
     * @param  string $secret_key       Override the default Secret Key
     * @param  string $region           Sets the AWS Region
     * @return void
     */
    public function __construct($accessKey, $secretKey)
    {
        parent::__construct($accessKey, $secretKey);
        $this->setEndpoint("https://" . $this->_sdbEndpoint);
    }

	/**
     * Set SimpleDB endpoint to use
     *
     * @param string|Uri\Uri $endpoint
     * @return Zend\Service\Amazon\SimpleDb\SimpleDb
     */
    public function setEndpoint($endpoint)
    {
    	if(!($endpoint instanceof Uri\Uri)) {
    		$endpoint = Uri\UriFactory::factory($endpoint);
    	}
    	if(!$endpoint->isValid()) {
    		throw new Exception\InvalidArgumentException("Invalid endpoint supplied");
    	}
    	$this->_endpoint = $endpoint;
    	return $this;
    }

    /**
     * Get SimpleDB endpoint
     *
     * @return Uri\Uri
     */
    public function getEndpoint() 
    {
    	return $this->_endpoint;
    }

    /**
     * Get attributes API method
     *
     * @param string $domainName Domain name within database
     * @param string 
     */
    public function getAttributes(
        $domainName, $itemName, $attributeName = null
    ) {
        $params               = array();
	    $params['Action']     = 'GetAttributes';
	    $params['DomainName'] = $domainName;
	    $params['ItemName']   = $itemName;

	    if (isset($attributeName)) {
	        $params['AttributeName'] = $attributeName;
	    }

	    $response = $this->_sendRequest($params);
        $document = $response->getSimpleXMLDocument();

        $attributeNodes = $document->GetAttributesResult->Attribute;

        // Return an array of arrays
        $attributes = array();
        foreach($attributeNodes as $attributeNode) {
            $name       = (string)$attributeNode->Name;
            $valueNodes = $attributeNode->Value;
            $data       = null;
            if (is_array($valueNodes) && !empty($valueNodes)) {
                $data = array();
                foreach($valueNodes as $valueNode) {
                    $data[] = (string)$valueNode;
                }
            } elseif (isset($valueNodes)) {
                $data = (string)$valueNodes;
            }
            if (isset($attributes[$name])) {
                $attributes[$name]->addValue($data);    
            } else {
                $attributes[$name] = new Attribute($itemName, $name, $data);
            }
        }
        return $attributes;
    }

    /**
     * Push attributes
     *
     * @param  string $domainName
     * @param  string $itemName
     * @param  array|Traverable $attributes
     * @param  array $replace
     * @return void
     */
    public function putAttributes(
        $domainName, $itemName, $attributes, $replace = array()
    ) {
        $params               = array();
	    $params['Action']     = 'PutAttributes';
	    $params['DomainName'] = $domainName;
	    $params['ItemName']   = $itemName;

	    $index = 0;
	    foreach ($attributes as $attribute) {
	        $attributeName = $attribute->getName();
            foreach ($attribute->getValues() as $value) {
	            $params['Attribute.' . $index . '.Name']  = $attributeName;
                $params['Attribute.' . $index . '.Value'] = $value;

	            // Check if it should be replaced
                if(array_key_exists($attributeName, $replace) && $replace[$attributeName]) {
                    $params['Attribute.' . $index . '.Replace'] = 'true';
                }
                $index++;
            }
	    }

	    // Exception should get thrown if there's an error
        $response = $this->_sendRequest($params);
    }

    /**
     * Add many attributes at once
     * 
     * @param  array $items 
     * @param  string $domainName 
     * @param  array $replace 
     * @return void
     */
    public function batchPutAttributes($items, $domainName, array $replace = array()) 
    {

        $params               = array();
        $params['Action']     = 'BatchPutAttributes';
        $params['DomainName'] = $domainName;

        $itemIndex = 0;
        foreach ($items as $name => $attributes) {
            $params['Item.' . $itemIndex . '.ItemName'] = $name;
            $attributeIndex = 0;
            foreach ($attributes as $attribute) {
                // attribute value cannot be array, so when several items are passed
                // they are treated as separate values with the same attribute name
                foreach($attribute->getValues() as $value) {
                    $params['Item.' . $itemIndex . '.Attribute.' . $attributeIndex . '.Name'] = $attribute->getName();
                    $params['Item.' . $itemIndex . '.Attribute.' . $attributeIndex . '.Value'] = $value;
                    if (isset($replace[$name]) 
                        && isset($replace[$name][$attribute->getName()]) 
                        && $replace[$name][$attribute->getName()]
                    ) {
                        $params['Item.' . $itemIndex . '.Attribute.' . $attributeIndex . '.Replace'] = 'true';
                    }
                    $attributeIndex++;
                }
            }
            $itemIndex++;
        }

        $response = $this->_sendRequest($params);
    }

    /**
     * Delete attributes
     * 
     * @param  string $domainName 
     * @param  string $itemName 
     * @param  array $attributes 
     * @return void
     */
    public function deleteAttributes($domainName, $itemName, array $attributes = array()) 
    {
        $params               = array();
	    $params['Action']     = 'DeleteAttributes';
	    $params['DomainName'] = $domainName;
	    $params['ItemName']   = $itemName;

	    $attributeIndex = 0;
	    foreach ($attributes as $attribute) {
	        foreach ($attribute->getValues() as $value) {
	            $params['Attribute.' . $attributeIndex . '.Name'] = $attribute->getName();
	            $params['Attribute.' . $attributeIndex . '.Value'] = $value;
                $attributeIndex++;
	        }
	    }

        $response = $this->_sendRequest($params);

        return true;
    }

    /**
     * List domains
     *
     * @param $maxNumberOfDomains int
     * @param $nextToken          int
     * @return array              0 or more domain names
     */
    public function listDomains($maxNumberOfDomains = 100, $nextToken = null) 
    {
        $params                       = array();
	    $params['Action']             = 'ListDomains';
	    $params['MaxNumberOfDomains'] = $maxNumberOfDomains;

	    if (null !== $nextToken) {
	        $params['NextToken'] = $nextToken;
	    }
        $response = $this->_sendRequest($params);

        $domainNodes = $response->getSimpleXMLDocument()->ListDomainsResult->DomainName;

        $data = array();
        foreach ($domainNodes as $domain) {
            $data[] = (string)$domain;
        }

        $nextTokenNode = $response->getSimpleXMLDocument()->ListDomainsResult->NextToken;
        $nextToken     = (string)$nextTokenNode;
        $nextToken     = (trim($nextToken) === '') ? null : $nextToken;

        return new Page($data, $nextToken);
    }

    /**
     * Retrieve domain metadata
     *
     * @param $domainName string Name of the domain for which metadata will be requested
     * @return array Key/value array of metadatum names and values.
     */
    public function domainMetadata($domainName) 
    {
        $params               = array();
	    $params['Action']     = 'DomainMetadata';
	    $params['DomainName'] = $domainName;
        $response             = $this->_sendRequest($params);

        $document = $response->getSimpleXMLDocument();

        $metadataNodes = $document->DomainMetadataResult->children();
        $metadata      = array();
        foreach ($metadataNodes as $metadataNode) {
            $name            = $metadataNode->getName();
            $metadata[$name] = (string)$metadataNode;
        }

        return $metadata;
    }

    /**
     * Create a new domain
     *
     * @param $domainName	string	Valid domain name of the domain to create
     * @return 				boolean True if successful, false if not
     */
	public function createDomain($domainName) 
	{
        $params               = array();
	    $params['Action']     = 'CreateDomain';
	    $params['DomainName'] = $domainName;
        $response             = $this->_sendRequest($params);
        return $response->getHttpResponse()->isSuccess();
    }

    /**
     * Delete a domain
     *
     * @param 	$domainName string  Valid domain name of the domain to delete
     * @return 				boolean True if successful, false if not
     */
	public function deleteDomain($domainName) 
	{
	    $params               = array();
	    $params['Action']     = 'DeleteDomain';
	    $params['DomainName'] = $domainName;
        $response             = $this->_sendRequest($params);
        return $response->getHttpResponse()->isSuccessful();
    }

    /**
     * Select items from the database
     *
     * @param  string $selectExpression
     * @param  null|string $nextToken
     * @return Zend\Service\Amazon\SimpleDb\Page
     */
	public function select($selectExpression, $nextToken = null) 
	{
        $params                     = array();
	    $params['Action']           = 'Select';
	    $params['SelectExpression'] = $selectExpression;

	    if (null !== $nextToken) {
	        $params['NextToken'] = $nextToken;
	    }

        $response = $this->_sendRequest($params);
        $xml      = $response->getSimpleXMLDocument();

        $attributes = array();
        foreach ($xml->SelectResult->Item as $item) {
            $itemName = (string)$item->Name;

            foreach ($item->Attribute as $attribute) {
                $attributeName = (string)$attribute->Name;

                $values = array();
                foreach ($attribute->Value as $value) {
                    $values[] = (string)$value;
                }
                $attributes[$itemName][$attributeName] = new Attribute($itemName, $attributeName, $values);
            }
        }

        $nextToken = (string)$xml->NextToken;

        return new Page($attributes, $nextToken);
    }
    
	/**
	 * Quote SDB value
	 * 
	 * Wraps it in ''
	 * 
	 * @param string $value
	 * @return string
	 */
    public function quote($value)
    {
    	// wrap in single quotes and convert each ' inside to ''
    	return "'" . str_replace("'", "''", $value) . "'";
    }
    
	/**
	 * Quote SDB column or table name
	 * 
	 * Wraps it in ``
	 * @param string $name
	 * @return string
	 */
    public function quoteName($name)
    {
    	if (preg_match('/^[a-z_$][a-z0-9_$-]*$/i', $name) == false) {
    		throw new Exception\InvalidArgumentException("Invalid name: can contain only alphanumeric characters, \$ and _");
    	}
    	return "`$name`";
    }
    
   /**
     * Sends a HTTP request to the SimpleDB service using Zend\Http\Client
     *
     * @param array $params         List of parameters to send with the request
     * @return Zend\Service\Amazon\SimpleDb\Response
     * @throws Zend\Service\Amazon\SimpleDb\Exception
     */
    protected function _sendRequest(array $params = array())
    {
        // UTF-8 encode all parameters and replace '+' characters
        foreach ($params as $name => $value) {
            unset($params[$name]);
            $params[utf8_encode($name)] = $value;
        }

        $params = $this->_addRequiredParameters($params);

        try {
            $request = self::getHttpClient();
            $request->resetParameters();

            $request->setConfig(array(
                'timeout' => $this->_httpTimeout
            ));


            $request->setUri($this->getEndpoint());
            $request->setMethod('POST');
            $request->setParameterPost($params);
            /*
            foreach ($params as $key => $value) {
                $params_out[] = rawurlencode($key)."=".rawurlencode($value);
            }
            $request->setRawData(implode('&', $params_out), Http\Client::ENC_URLENCODED);
             */
            $httpResponse = $request->send();
        } catch (Http\Client\Exception $zhce) {
            $message = 'Error in request to AWS service: ' . $zhce->getMessage();
            throw new Exception\RuntimeException($message, $zhce->getCode(), $zhce);
        } 

        $response = new Response($httpResponse);
        $this->_checkForErrors($response);
        return $response;
    }

    /**
     * Adds required authentication and version parameters to an array of
     * parameters
     *
     * The required parameters are:
     * - AWSAccessKey
     * - SignatureVersion
     * - Timestamp
     * - Version and
     * - Signature
     *
     * If a required parameter is already set in the <tt>$parameters</tt> array,
     * it is overwritten.
     *
     * @param array $parameters the array to which to add the required
     *                          parameters.
     *
     * @return array
     */
    protected function _addRequiredParameters(array $parameters)
    {
        $parameters['AWSAccessKeyId']   = $this->_getAccessKey();
        $parameters['SignatureVersion'] = $this->_signatureVersion;
        $parameters['Timestamp']        = gmdate('c');
        $parameters['Version']          = $this->_sdbApiVersion;
        $parameters['SignatureMethod']  = $this->_signatureMethod;
        $parameters['Signature']        = $this->_signParameters($parameters);

        return $parameters;
    }

    /**
     * Computes the RFC 2104-compliant HMAC signature for request parameters
     *
     * This implements the Amazon Web Services signature, as per the following
     * specification:
     *
     * 1. Sort all request parameters (including <tt>SignatureVersion</tt> and
     *    excluding <tt>Signature</tt>, the value of which is being created),
     *    ignoring case.
     *
     * 2. Iterate over the sorted list and append the parameter name (in its
     *    original case) and then its value. Do not URL-encode the parameter
     *    values before constructing this string. Do not use any separator
     *    characters when appending strings.
     *
     * @param array  $parameters the parameters for which to get the signature.
     * @param string $secretKey  the secret key to use to sign the parameters.
     *
     * @return string the signed data.
     */
    protected function _signParameters(array $paramaters)
    {
        $data  = "POST\n";
        $data .= $this->getEndpoint()->getHost() . "\n";
        $data .= "/\n";

        uksort($paramaters, 'strcmp');
        unset($paramaters['Signature']);

        $arrData = array();
        foreach ($paramaters as $key => $value) {
            $value = urlencode($value);
            $value = str_replace("%7E", "~", $value);
            $value = str_replace("+", "%20", $value);
            $arrData[] = urlencode($key) . '=' . $value;
        }

        $data .= implode('&', $arrData);

        $hmac = Crypt\Hmac::compute($this->_getSecretKey(), 'SHA256', $data, Crypt\Hmac::BINARY);

        return base64_encode($hmac);
    }

    /**
     * Checks for errors responses from Amazon
     *
     * @param Zend\Service\Amazon\SimpleDb\Response $response the response object to
     *                                                   check.
     *
     * @return void
     *
     * @throws Zend\Service\Amazon\SimpleDb\Exception if one or more errors are
     *         returned from Amazon.
     */
    private function _checkForErrors(Response $response)
    {
        $xpath = new \DOMXPath($response->getDocument());
        $list  = $xpath->query('//Error');
        if ($list->length > 0) {
            $node    = $list->item(0);
            $code    = $xpath->evaluate('string(Code/text())', $node);
            $message = $xpath->evaluate('string(Message/text())', $node);
            throw new Exception\RuntimeException($message, (double)$code);
        }
    }
}
