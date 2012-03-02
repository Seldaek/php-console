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
 * @package    Zend\Http
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Http;

use Zend\Config\Config,
    Zend\Uri\Http,
    Zend\Http\Header\Cookie,
    Zend\Http\Header\SetCookie,
    Zend\Stdlib\Parameters,
    Zend\Stdlib\ParametersDescription,
    Zend\Stdlib\Dispatchable,
    Zend\Stdlib\RequestDescription,
    Zend\Stdlib\ResponseDescription;

/**
 * Http client
 *
 * @category   Zend
 * @package    Zend\Http
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Client implements Dispatchable
{
    /**#@+
     * @const string Supported HTTP Authentication methods
     */
    const AUTH_BASIC  = 'basic';
    const AUTH_DIGEST = 'digest';  // not implemented yet
    /**#@-*/

    /**#@+
     * @const string POST data encoding methods
     */
    const ENC_URLENCODED = 'application/x-www-form-urlencoded';
    const ENC_FORMDATA   = 'multipart/form-data';
    /**#@-*/

    /**#@+
     * @const string DIGEST Authentication
     */
    const DIGEST_REALM  = 'realm';
    const DIGEST_QOP    = 'qop';
    const DIGEST_NONCE  = 'nonce';
    const DIGEST_OPAQUE = 'opaque';
    const DIGEST_NC     = 'nc';
    const DIGEST_CNONCE = 'cnonce';
    /**#@-*/

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Client/Adapter
     */
    protected $adapter;

    /**
     * @var array
     */
    protected $auth = array();

    /**
     * @var string
     */
    protected $streamName = null;

    /**
     * @var array of Header\SetCookie
     */
    protected $cookies = array();

    /**
     * @var string
     */
    protected $encType = '';

    /**
     * @var Request
     */
    protected $lastRawRequest = null;

    /**
     * @var Response
     */
    protected $lastRawResponse = null;

    /**
     * @var int
     */
    protected $redirectCounter = 0;
    
    /**
     * Configuration array, set using the constructor or using ::setConfig()
     *
     * @var array
     */
    protected $config = array(
        'maxredirects'    => 5,
        'strictredirects' => false,
        'useragent'       => 'Zend\Http\Client',
        'timeout'         => 10,
        'adapter'         => 'Zend\Http\Client\Adapter\Socket',
        'httpversion'     => Request::VERSION_11,
        'storeresponse'   => true,
        'keepalive'       => false,
        'outputstream'    => false,
        'encodecookies'   => true,
        'rfc3986strict'   => false
    );
   
    /**
     * Fileinfo magic database resource
     *
     * This variable is populated the first time _detectFileMimeType is called
     * and is then reused on every call to this method
     *
     * @var resource
     */
    static protected $_fileInfoDb = null;
    
    /**
     * Constructor
     *
     * @param string $uri
     * @param array  $config
     */
    public function __construct($uri = null, $config = null)
    {
        if ($uri !== null) {
            $this->setUri($uri);
        }
        if ($config !== null) {
            $this->setConfig($config);
        }
    }

    /**
     * Set configuration parameters for this HTTP client
     *
     * @param  Config|array $config
     * @return Client
     * @throws Client\Exception
     */
    public function setConfig($config = array())
    {
        if ($config instanceof Config) {
            $config = $config->toArray();

        } elseif (!is_array($config)) {
            throw new Exception\InvalidArgumentException('Config parameter is not valid');
        }

        /** Config Key Normalization */
        foreach ($config as $k => $v) {
            $this->config[str_replace(array('-', '_', ' ', '.'), '', strtolower($k))] = $v; // replace w/ normalized
        }

        // Pass configuration options to the adapter if it exists
        if ($this->adapter instanceof Client\Adapter) {
            $this->adapter->setConfig($config);
        }

        return $this;
    }

    /**
     * Load the connection adapter
     *
     * While this method is not called more than one for a client, it is
     * seperated from ->request() to preserve logic and readability
     *
     * @param  Client\Adapter|string $adapter
     * @return Client
     * @throws \Zend\Http\Client\Exception
     */
    public function setAdapter($adapter)
    {
        if (is_string($adapter)) {
            if (!class_exists($adapter)) {
                throw new Client\Exception\InvalidArgumentException('Unable to locate adapter class "' . $adapter . '"');
            }
            $adapter = new $adapter;
        }

        if (! $adapter instanceof Client\Adapter) {
            throw new Client\Exception\InvalidArgumentException('Passed adapter is not a HTTP connection adapter');
        }

        $this->adapter = $adapter;
        $config = $this->config;
        unset($config['adapter']);
        $this->adapter->setConfig($config);
        return $this;
    }

    /**
     * Load the connection adapter
     *
     * @return \Zend\Http\Client\Adapter $adapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Set request
     *
     * @param Request $request
     * @return Client
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Get Request
     * 
     * @return Request
     */
    public function getRequest()
    {
        if (empty($this->request)) {
            $this->request = new Request();
        }
        return $this->request;
    }

    /**
     * Set response
     *
     * @param Response $response
     * @return Client
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * Get Response
     * 
     * @return Response
     */
    public function getResponse()
    {
        if (empty($this->response)) {
            $this->response = new Response();
        }
        return $this->response;
    }


    /**
     * Get the last request (as a string)
     * 
     * @return string 
     */
    public function getLastRawRequest()
    {
        return $this->lastRawRequest;
    }

    /**
     * Get the last response (as a string)
     * 
     * @return string 
     */
    public function getLastRawResponse()
    {
        return $this->lastRawResponse;
    }

    /**
     * Get the redirections count
     * 
     * @return integer 
     */
    public function getRedirectionsCount()
    {
        return $this->redirectCounter;
    }
    
    /**
     * Set Uri (to the request)
     * 
     * @param string|\Zend\Uri\Http $uri
     * @return Client
     */
    public function setUri($uri)
    {
        if (!empty($uri)) {
            $this->getRequest()->setUri($uri);
            
            // Set auth if username and password has been specified in the uri
            if ($this->getUri()->getUser() && $this->getUri()->getPassword()) {
                $this->setAuth($this->getUri()->getUser(), $this->getUri()->getPassword());
            }
        
            // We have no ports, set the defaults
            if (! $this->getUri()->getPort()) {
                $this->getUri()->setPort(($this->getUri()->getScheme() == 'https' ? 443 : 80));
            }
        }
        return $this;
    }

    /**
     * Get uri (from the request)
     * 
     * @return Zend\Uri\Http
     */
    public function getUri()
    {
        return $this->getRequest()->uri();
    }

    /**
     * Set the HTTP method (to the request)
     * 
     * @param string $method
     * @return Client 
     */
    public function setMethod($method)
    {
        $method = $this->getRequest()->setMethod($method)->getMethod();
        
        if (($method == Request::METHOD_POST || $method == Request::METHOD_PUT ||
             $method == Request::METHOD_DELETE) && empty($this->encType)) {
            $this->setEncType(self::ENC_URLENCODED);
        } 
        
        return $this;
    }

    /**
     * Get the HTTP method
     * 
     * @return string 
     */
    public function getMethod()
    {
        return $this->getRequest()->getMethod();
    }

    /**
     * Set the encoding type and the boundary (if any)
     * 
     * @param string $encType 
     * @param string $boundary
     * @return Client
     */
    public function setEncType($encType, $boundary = null)
    {
        if (!empty($encType)) {
            if (!empty($boundary)) {
                $this->encType = $encType . "; boundary={$boundary}";
            } else {
                $this->encType = $encType;
            }
        }
        return $this;
    }

    /**
     * Get the encoding type
     * 
     * @return type 
     */
    public function getEncType()
    {
        return $this->encType;
    }

    /**
     * Set raw body (for advanced use cases)
     * 
     * @param string $body
     * @return Client 
     */
    public function setRawBody($body)
    {
        $this->getRequest()->setContent($body);
        return $this;
    }

    /**
     * Set the POST parameters
     * 
     * @param array $post
     * @return Client 
     */
    public function setParameterPost(array $post)
    {
        $this->getRequest()->post()->fromArray($post);
        return $this;
    }

    /**
     * Set the GET parameters
     * 
     * @param array $query 
     * @return Client
     */
    public function setParameterGet(array $query)
    {
        $this->getRequest()->query()->fromArray($query);
        return $this;
    }

    /**
     * Return the current cookies
     *
     * @return array
     */
    public function getCookies()
    {
        return $this->cookies;
    }
    
    /**
     * Get the cookie Id (name+domain+path)
     * 
     * @param  SetCookie|Cookie $cookie
     * @return string|boolean 
     */
    protected function getCookieId($cookie)
    {
        if (($cookie instanceof SetCookie) || ($cookie instanceof Cookie)) {
            return $cookie->getName() . $cookie->getDomain() . $cookie->getPath();
        }
        return false;
    }

    /**
     * Add a cookie
     * 
     * @param ArrayIterator|SetCookie|string $cookie
     * @param string  $value
     * @param string  $domain
     * @param string  $expire
     * @param string  $path
     * @param boolean $secure
     * @param boolean $httponly
     * @return Client 
     */
    public function addCookie($cookie, $value = null, $domain = null, $expire = null, $path = null, $secure = false, $httponly = true)
    {
        if ($cookie instanceof \ArrayIterator) {
            foreach ($cookie as $setCookie) {
                if ($setCookie instanceof SetCookie) {
                    $this->cookies[$this->getCookieId($setCookie)] = $setCookie;
                } else {
                    throw new Exception\InvalidArgumentException('The cookie parameter is not a valid Set-Cookie type');
                }
            }
        } elseif ($cookie instanceof SetCookie) {
            $this->cookies[$this->getCookieId($cookie)] = $cookie;
        } elseif (is_string($cookie) && $value !== null) {
            $setCookie = new SetCookie($cookie, $value, $domain, $expire, $path, $secure, $httponly);
            $this->cookies[$this->getCookieId($setCookie)] = $setCookie;
        } else {
            throw new Exception\InvalidArgumentException('Invalid parameter type passed as Cookie');
        }
        return $this;
    }

    /**
     * Set an array of cookies
     * 
     * @param  array $cookies
     * @return Client 
     */
    public function setCookies($cookies)
    {
        if (is_array($cookies)) {
            $this->clearCookies();
            foreach ($cookies as $name => $value) {
                $this->addCookie($name,$value);
            }
        } else {
            throw new Exception\InvalidArgumentException('Invalid cookies passed as parameter, it must be an array');
        }
        return $this;
    }

    /**
     * Clear all the cookies
     */
    public function clearCookies()
    {
        $this->cookies = array();
    }
    
    /**
     * Set the headers (for the request)
     * 
     * @param  Headers|array $headers
     * @return Client 
     */
    public function setHeaders($headers)
    {
        if (is_array($headers)) {
            $newHeaders = new Headers();
            $newHeaders->addHeaders($headers);
            $this->getRequest()->setHeaders($newHeaders);
        } elseif ($headers instanceof Headers) {
            $this->getRequest()->setHeaders($headers);
        } else {    
            throw new Exception\InvalidArgumentException('Invalid parameter headers passed');
        }
        return $this;
    }

    /**
     * Check if exists the header type specified
     * 
     * @param  string $name
     * @return boolean 
     */
    public function hasHeader($name)
    { 
        $headers = $this->getRequest()->headers();
        
        if ($headers instanceof Headers) {
            return $headers->has($name);
        }
        
        return false;
    }

    /**
     * Get the header value of the request
     * 
     * @param  string $name
     * @return string|boolean
     */
    public function getHeader($name)
    {
        $headers = $this->getRequest()->headers();

        if ($headers instanceof Headers) {
            if ($headers->get($name)) {
                return $headers->get($name)->getFieldValue();
            }    
        }
        return false;
    }

    /**
     * Set streaming for received data
     *
     * @param string|boolean $streamfile Stream file, true for temp file, false/null for no streaming
     * @return \Zend\Http\Client
     */
    public function setStream($streamfile = true)
    {
        $this->setConfig(array("outputstream" => $streamfile));
        return $this;
    }

    /**
     * Get status of streaming for received data
     * @return boolean|string
     */
    public function getStream()
    {
        return $this->config["outputstream"];
    }

    /**
     * Create temporary stream
     *
     * @return resource
     */
    protected function openTempStream()
    {
        $this->streamName = $this->config['outputstream'];

        if(!is_string($this->streamName)) {
            // If name is not given, create temp name
            $this->streamName = tempnam(
                isset($this->config['streamtmpdir']) ? $this->config['streamtmpdir'] : sys_get_temp_dir(),
                'Zend\Http\Client'
            );
        }

        if (false === ($fp = @fopen($this->streamName, "w+b"))) {
            if ($this->adapter instanceof Client\Adapter) {
                $this->adapter->close();
            }
            throw new Exception\RuntimeException("Could not open temp file {$this->streamName}");
        }

        return $fp;
    }

    /**
     * Create a HTTP authentication "Authorization:" header according to the
     * specified user, password and authentication method.
     *
     * @param string $user
     * @param string $password
     * @param string $type 
     * @return Client
     */
    public function setAuth($user, $password, $type = self::AUTH_BASIC)
    {
        if (!defined('self::AUTH_' . strtoupper($type))) {
            throw new Exception\InvalidArgumentException("Invalid or not supported authentication type: '$type'");
        }
        if (empty($user) || empty($password)) {
            throw new Exception\InvalidArgumentException("The username and the password cannot be empty");
        }    
        
        $this->auth = array (
            'user'     => $user,
            'password' => $password,
            'type'     => $type
            
        );
    
        return $this;
    }

    /**
     * Calculate the response value according to the HTTP authentication type
     * 
     * @see http://www.faqs.org/rfcs/rfc2617.html
     * @param string $user
     * @param string $password
     * @param string $type
     * @param array $digest 
     * @return string|boolean
     */
    protected function calcAuthDigest($user, $password, $type = self::AUTH_BASIC, $digest = array(), $entityBody = null)
    {
        if (!defined('self::AUTH_' . strtoupper($type))) {
            throw new Exception\InvalidArgumentException("Invalid or not supported authentication type: '$type'");
        }
        $response = false;
        switch(strtolower($type)) {
            case self::AUTH_BASIC :
                // In basic authentication, the user name cannot contain ":"
                if (strpos($user, ':') !== false) {
                    throw new Exception\InvalidArgumentException("The user name cannot contain ':' in Basic HTTP authentication");
                }
                $response = base64_encode($user . ':' . $password);
                break;
            case self::AUTH_DIGEST :
                if (empty($digest)) {
                    throw new Exception\InvalidArgumentException("The digest cannot be empty");
                }
                foreach ($digest as $key => $value) {
                    if (!defined('self::DIGEST_' . strtoupper($key))) {
                        throw new Exception\InvalidArgumentException("Invalid or not supported digest authentication parameter: '$key'");
                    }
                }
                $ha1 = md5($user . ':' . $digest['realm'] . ':' . $password);
                if (empty($digest['qop']) || strtolower($digest['qop']) == 'auth') {
                    $ha2 = md5($this->getMethod() . ':' . $this->getUri()->getPath());
                } elseif (strtolower($digest['qop']) == 'auth-int') {
                     if (empty($entityBody)) {
                        throw new Exception\InvalidArgumentException("I cannot use the auth-int digest authentication without the entity body");
                     }
                     $ha2 = md5($this->getMethod() . ':' . $this->getUri()->getPath() . ':' . md5($entityBody));
                }
                if (empty($digest['qop'])) {
                    $response = md5 ($ha1 . ':' . $digest['nonce'] . ':' . $ha2);
                } else {
                    $response = md5 ($ha1 . ':' . $digest['nonce'] . ':' . $digest['nc']
                                    . ':' . $digest['cnonce'] . ':' . $digest['qoc'] . ':' . $ha2);
                }
                break;
        }
        return $response;
    }

    /**
     * Reset all the HTTP parameters (auth,cookies,request, response, etc)
     *
     * @param  bool   $clearCookies  Also clear all valid cookies? (defaults to false)
     * @return Client
     */
    public function resetParameters($clearCookies = false)
    {
        $uri = $this->getUri();
        
        $this->auth       = null;
        $this->streamName = null;
        $this->encType    = null;
        $this->request    = null;
        $this->response   = null;
        
        $this->setUri($uri);

        if ($clearCookies) {
            $this->clearCookies();
        }

        return $this;
    }

    /**
     * Dispatch
     *
     * @param RequestDescription $request
     * @param ResponseDescription $response
     * @return ResponseDescription
     */
    public function dispatch(RequestDescription $request, ResponseDescription $response = null)
    {
        $response = $this->send($request);
        return $response;
    }

    /**
     * Send HTTP request
     *
     * @param  Request $request
     * @return Response
     */
    public function send(Request $request = null)
    {
        if ($request !== null) {
            $this->setRequest($request);
        }

        $this->redirectCounter = 0;
        $response = null;

        // Make sure the adapter is loaded
        if ($this->adapter == null) {
            $this->setAdapter($this->config['adapter']);
        }

        // Send the first request. If redirected, continue.
        do {
            // uri
            $uri = $this->getUri();

            // query
            $query = $this->getRequest()->query();

            if (!empty($query)) {
                $queryArray = $query->toArray();

                if (!empty($queryArray)) {
                    $newUri = $uri->toString();
                    $queryString = http_build_query($query);

                    if ($this->config['rfc3986strict']) {
                        $queryString = str_replace('+', '%20', $queryString);
                    }

                    if (strpos($newUri,'?') !== false) {
                        $newUri .= '&' . $queryString;
                    } else {
                        $newUri .= '?' . $queryString;
                    }

                    $uri = new \Zend\Uri\Http($newUri);
                }
            }
            // If we have no ports, set the defaults
            if (!$uri->getPort()) {
                $uri->setPort($uri->getScheme() == 'https' ? 443 : 80);
            }

            // method
            $method = $this->getRequest()->getMethod();

            // body
            $body = $this->prepareBody();

            // headers
            $headers = $this->prepareHeaders($body,$uri);

            $secure = $uri->getScheme() == 'https';

            // cookies
            $cookie = $this->prepareCookies($uri->getHost(), $uri->getPath(), $secure);
            if ($cookie->getFieldValue()) {
                $headers['Cookie'] = $cookie->getFieldValue();
            }

            // check that adapter supports streaming before using it
            if(is_resource($body) && !($this->adapter instanceof Client\Adapter\Stream)) {
                throw new Client\Exception\RuntimeException('Adapter does not support streaming');
            }

            // Open the connection, send the request and read the response
            $this->adapter->connect($uri->getHost(), $uri->getPort(), $secure);

            if($this->config['outputstream']) {
                if($this->adapter instanceof Client\Adapter\Stream) {
                    $stream = $this->openTempStream();
                    $this->adapter->setOutputStream($stream);
                } else {
                    throw new Exception\RuntimeException('Adapter does not support streaming');
                }
            }

            // HTTP connection
            $this->lastRawRequest = $this->adapter->write($method,
                $uri, $this->config['httpversion'], $headers, $body);

            $response = $this->adapter->read();
            if (! $response) {
                throw new Exception\RuntimeException('Unable to read response, or response is empty');
            }

            if ($this->config['storeresponse']) {
                $this->lastRawResponse = $response;
            } else {
                $this->lastRawResponse = null;
            }

            if($this->config['outputstream']) {
                $streamMetaData = stream_get_meta_data($stream);
                if ($streamMetaData['seekable']) {
                    rewind($stream);
                }
                // cleanup the adapter
                $this->adapter->setOutputStream(null);
                $response = Response\Stream::fromStream($response, $stream);
                $response->setStreamName($this->streamName);
                if(!is_string($this->config['outputstream'])) {
                    // we used temp name, will need to clean up
                    $response->setCleanup(true);
                }
            } else {
                $response = Response::fromString($response);
            }

            // Get the cookies from response (if any)
            $setCookie = $response->cookie();
            if (!empty($setCookie)) {
                $this->addCookie($setCookie);
            }

            // If we got redirected, look for the Location header
            if ($response->isRedirect() && ($response->headers()->has('Location'))) {

                // Avoid problems with buggy servers that add whitespace at the
                // end of some headers
                $location = trim($response->headers()->get('Location')->getFieldValue());
                
                // Check whether we send the exact same request again, or drop the parameters
                // and send a GET request
                if ($response->getStatusCode() == 303 ||
                   ((! $this->config['strictredirects']) && ($response->getStatusCode() == 302 ||
                       $response->getStatusCode() == 301))) {

                    $this->resetParameters();
                    $this->setMethod(Request::METHOD_GET);
                }

                // If we got a well formed absolute URI
                if (($scheme = substr($location, 0, 6)) &&
                        ($scheme == 'http:/' || $scheme == 'https:')) {
                    $this->setUri($location);
                } else {

                    // Split into path and query and set the query
                    if (strpos($location, '?') !== false) {
                        list($location, $query) = explode('?', $location, 2);
                    } else {
                        $query = '';
                    }
                    $this->getUri()->setQuery($query);

                    // Else, if we got just an absolute path, set it
                    if(strpos($location, '/') === 0) {
                        $this->getUri()->setPath($location);
                        // Else, assume we have a relative path
                    } else {
                        // Get the current path directory, removing any trailing slashes
                        $path = $this->getUri()->getPath();
                        $path = rtrim(substr($path, 0, strrpos($path, '/')), "/");
                        $this->getUri()->setPath($path . '/' . $location);
                    }
                }
                ++$this->redirectCounter;

            } else {
                // If we didn't get any location, stop redirecting
                break;
            }

        } while ($this->redirectCounter < $this->config['maxredirects']);

        $this->response = $response;
        return $response;
    }

    /**
     * Set a file to upload (using a POST request)
     *
     * Can be used in two ways:
     *
     * 1. $data is null (default): $filename is treated as the name if a local file which
     * will be read and sent. Will try to guess the content type using mime_content_type().
     * 2. $data is set - $filename is sent as the file name, but $data is sent as the file
     * contents and no file is read from the file system. In this case, you need to
     * manually set the Content-Type ($ctype) or it will default to
     * application/octet-stream.
     *
     * @param  string $filename Name of file to upload, or name to save as
     * @param  string $formname Name of form element to send as
     * @param  string $data Data to send (if null, $filename is read and sent)
     * @param  string $ctype Content type to use (if $data is set and $ctype is
     *                null, will be application/octet-stream)
     * @return Client
     * @throws Exception
     */
    public function setFileUpload($filename, $formname, $data = null, $ctype = null)
    {
        if ($data === null) {
            if (($data = @file_get_contents($filename)) === false) {
                throw new Exception\RuntimeException("Unable to read file '{$filename}' for upload");
            }
            if (!$ctype) {
                $ctype = $this->detectFileMimeType($filename);
            }
        }

        $this->getRequest()->file()->set($filename, array(
            'formname' => $formname,
            'filename' => basename($filename),
            'ctype' => $ctype,
            'data' => $data
        ));

        return $this;
    }

    /**
     * Remove a file to upload
     *
     * @param  string $filename
     * @return boolean
     */
    public function removeFileUpload($filename)
    {
        $file = $this->getRequest()->file()->get($filename);
        if (!empty($file)) {
            $this->getRequest()->file()->set($filename,null);
            return true;
        }
        return false;
    }
    
    /**
     * Prepare Cookies
     * 
     * @param   string $uri
     * @param   string $domain
     * @param   boolean $secure
     * @return  Cookie|boolean
     */
    protected function prepareCookies($domain, $path, $secure)
    {
        $validCookies = array();

        if (!empty($this->cookies)) {
            foreach ($this->cookies as $id => $cookie) {
                if ($cookie->isExpired()) {
                    unset($this->cookies[$id]);
                    continue;
                }

                if ($cookie->isValidForRequest($domain, $path, $secure)) {
                    $validCookies[] = $cookie;
                }
            }
        }

        $cookies = Cookie::fromSetCookieArray($validCookies);
        $cookies->setEncodeValue($this->config['encodecookies']);

        return $cookies;
    }

    /**
     * Prepare the request headers
     *
     * @return array
     */
    protected function prepareHeaders($body, $uri)
    {
        $headers = array();

        // Set the host header
        if ($this->config['httpversion'] == Request::VERSION_11) {
            $host = $uri->getHost();
            // If the port is not default, add it
            if (!(($uri->getScheme() == 'http' && $uri->getPort() == 80) ||
                ($uri->getScheme() == 'https' && $uri->getPort() == 443))) {
                $host .= ':' . $uri->getPort();
            }

            $headers['Host'] = $host;
        }

        // Set the connection header
        if (!$this->getRequest()->headers()->has('Connection')) {
            if (!$this->config['keepalive']) {
                $headers['Connection'] = 'close';
            }
        }

        // Set the Accept-encoding header if not set - depending on whether
        // zlib is available or not.
        if (! isset($this->headers['accept-encoding'])) {
            if (function_exists('gzinflate')) {
                $headers['Accept-encoding'] = 'gzip, deflate';
            } else {
                $headers['Accept-encoding'] = 'identity';
            }
        }


        // Set the user agent header
        if (!$this->getRequest()->headers()->has('User-Agent') && isset($this->config['useragent'])) {
            $headers['User-Agent'] = $this->config['useragent'];
        }

        // Set HTTP authentication if needed
        if (!empty($this->auth)) {
            switch ($this->auth['type']) {
                case self::AUTH_BASIC :
                    $auth = $this->calcAuthDigest($this->auth['user'], $this->auth['password'], $this->auth['type']);
                    if ($auth !== false) {
                        $headers['Authorization'] = 'Basic ' . $auth;
                    } 
                    break;
                case self::AUTH_DIGEST :
                    throw new Exception\RuntimeException("The digest authentication is not implemented yet"); 
            }
        }

        // Content-type
        $encType = $this->getEncType();
        if (!empty($encType)) {
            $headers['Content-Type'] = $encType;
        }
        
        if (!empty($body)) {       
            if (is_resource($body)) {
                $fstat = fstat($body);
                $headers['Content-Length'] = $fstat['size'];
            } else {
                $headers['Content-Length'] = static::strlen($body);
            }    
        }
        
        // Merge the headers of the request (if any)
        $requestHeaders = $this->getRequest()->headers()->toArray();
        foreach ($requestHeaders as $key => $value) {
            $headers[$key] = $value;
        }
        return $headers;
    }
    

    /**
     * Prepare the request body (for POST and PUT requests)
     *
     * @return string
     * @throws \Zend\Http\Client\Exception
     */
    protected function prepareBody()
    {
        // According to RFC2616, a TRACE request should not have a body.
        if ($this->getRequest()->isTrace()) {
            return '';
        }
        
        $rawBody = $this->getRequest()->getContent();
        if (!empty($rawBody)) {
            return $rawBody;
        }

        $body = '';
        $totalFiles = 0;
        
        if (!$this->getRequest()->headers()->has('Content-Type')) {
            $totalFiles = count($this->getRequest()->file()->toArray());
            // If we have files to upload, force encType to multipart/form-data
            if ($totalFiles > 0) {
                $this->setEncType(self::ENC_FORMDATA);
            }
        } else {
            $this->setEncType($this->getHeader('Content-Type'));
        }    

        // If we have POST parameters or files, encode and add them to the body
        if (count($this->getRequest()->post()->toArray()) > 0 || $totalFiles > 0) {
            if (stripos($this->getEncType(), self::ENC_FORMDATA) === 0) {
                $boundary = '---ZENDHTTPCLIENT-' . md5(microtime());
                $this->setEncType(self::ENC_FORMDATA, $boundary);
                  
                // Get POST parameters and encode them
                $params = self::flattenParametersArray($this->getRequest()->post()->toArray());
                foreach ($params as $pp) {
                    $body .= $this->encodeFormData($boundary, $pp[0], $pp[1]);
                }

                // Encode files
                foreach ($this->getRequest()->file()->toArray() as $key => $file) {
                    $fhead = array('Content-Type' => $file['ctype']);
                    $body .= $this->encodeFormData($boundary, $file['formname'], $file['data'], $file['filename'], $fhead);
                }
                $body .= "--{$boundary}--\r\n";
            } elseif (stripos($this->getEncType(), self::ENC_URLENCODED) === 0) {
                // Encode body as application/x-www-form-urlencoded
                $body = http_build_query($this->getRequest()->post()->toArray());
            } else {
                throw new Exception\RuntimeException("Cannot handle content type '{$this->encType}' automatically");
            }
        }

        return $body;
    }

    
    /**
     * Attempt to detect the MIME type of a file using available extensions
     *
     * This method will try to detect the MIME type of a file. If the fileinfo
     * extension is available, it will be used. If not, the mime_magic
     * extension which is deprected but is still available in many PHP setups
     * will be tried.
     *
     * If neither extension is available, the default application/octet-stream
     * MIME type will be returned
     *
     * @param string $file File path
     * @return string MIME type
     */
    protected function detectFileMimeType($file)
    {
        $type = null;

        // First try with fileinfo functions
        if (function_exists('finfo_open')) {
            if (self::$_fileInfoDb === null) {
                self::$_fileInfoDb = @finfo_open(FILEINFO_MIME);
            }

            if (self::$_fileInfoDb) {
                $type = finfo_file(self::$_fileInfoDb, $file);
            }

        } elseif (function_exists('mime_content_type')) {
            $type = mime_content_type($file);
        }

        // Fallback to the default application/octet-stream
        if (! $type) {
            $type = 'application/octet-stream';
        }

        return $type;
    }

    /**
     * Encode data to a multipart/form-data part suitable for a POST request.
     *
     * @param string $boundary
     * @param string $name
     * @param mixed $value
     * @param string $filename
     * @param array $headers Associative array of optional headers @example ("Content-Transfer-Encoding" => "binary")
     * @return string
     */
    public function encodeFormData($boundary, $name, $value, $filename = null, $headers = array())
    {
        $ret = "--{$boundary}\r\n" .
            'Content-Disposition: form-data; name="' . $name .'"';

        if ($filename) {
            $ret .= '; filename="' . $filename . '"';
        }
        $ret .= "\r\n";

        foreach ($headers as $hname => $hvalue) {
            $ret .= "{$hname}: {$hvalue}\r\n";
        }
        $ret .= "\r\n";

        $ret .= "{$value}\r\n";

        return $ret;
    }

    /**
     * Convert an array of parameters into a flat array of (key, value) pairs
     *
     * Will flatten a potentially multi-dimentional array of parameters (such
     * as POST parameters) into a flat array of (key, value) paris. In case
     * of multi-dimentional arrays, square brackets ([]) will be added to the
     * key to indicate an array.
     *
     * @since 1.9
     *
     * @param array $parray
     * @param string $prefix
     * @return array
     */
    protected function flattenParametersArray($parray, $prefix = null)
    {
        if (!is_array($parray)) {
            return $parray;
        }

        $parameters = array();

        foreach($parray as $name => $value) {

            // Calculate array key
            if ($prefix) {
                if (is_int($name)) {
                    $key = $prefix . '[]';
                } else {
                    $key = $prefix . "[$name]";
                }
            } else {
                $key = $name;
            }

            if (is_array($value)) {
                $parameters = array_merge($parameters, $this->flattenParametersArray($value, $key));

            } else {
                $parameters[] = array($key, $value);
            }
        }

        return $parameters;
    }
    
    /**
     * Returns length of binary string in bytes
     *
     * @param string $str
     * @return int the string length
     */
    static public function strlen($str)
    {
        if (function_exists('mb_internal_encoding') &&
            (((int)ini_get('mbstring.func_overload')) & 2)) {
            return mb_strlen($str, '8bit');
        } else {
            return strlen($str);
        }
    }
}
