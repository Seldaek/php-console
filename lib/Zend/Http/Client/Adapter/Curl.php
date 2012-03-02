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
 * @package    Zend_Http
 * @subpackage Client_Adapter
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Http\Client\Adapter;
use Zend\Http\Client\Adapter as HttpAdapter,
    Zend\Http\Client\Adapter\Exception as AdapterException,
    Zend\Http\Client,
    Zend\Http\Request;

/**
 * An adapter class for Zend\Http\Client based on the curl extension.
 * Curl requires libcurl. See for full requirements the PHP manual: http://php.net/curl
 *
 * @category   Zend
 * @package    Zend_Http
 * @subpackage Client_Adapter
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Curl implements HttpAdapter, Stream
{
    /**
     * Parameters array
     *
     * @var array
     */
    protected $config = array();

    /**
     * What host/port are we connected to?
     *
     * @var array
     */
    protected $connectedTo = array(null, null);

    /**
     * The curl session handle
     *
     * @var resource|null
     */
    protected $curl = null;

    /**
     * List of cURL options that should never be overwritten
     *
     * @var array
     */
    protected $invalidOverwritableCurlOptions;

    /**
     * Response gotten from server
     *
     * @var string
     */
    protected $response = null;

    /**
     * Stream for storing output
     *
     * @var resource
     */
    protected $outputStream;

    /**
     * Adapter constructor
     *
     * Config is set using setConfig()
     *
     * @return void
     * @throws \Zend\Http\Client\Adapter\Exception
     */
    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new AdapterException\InitializationException('cURL extension has to be loaded to use this Zend\Http\Client adapter');
        }
        $this->invalidOverwritableCurlOptions = array(
            CURLOPT_HTTPGET,
            CURLOPT_POST,
            CURLOPT_UPLOAD,
            CURLOPT_CUSTOMREQUEST,
            CURLOPT_HEADER,
            CURLOPT_RETURNTRANSFER,
            CURLOPT_HTTPHEADER,
            CURLOPT_POSTFIELDS,
            CURLOPT_INFILE,
            CURLOPT_INFILESIZE,
            CURLOPT_PORT,
            CURLOPT_MAXREDIRS,
            CURLOPT_CONNECTTIMEOUT,
            CURL_HTTP_VERSION_1_1,
            CURL_HTTP_VERSION_1_0,
        );
    }

    /**
     * Set the configuration array for the adapter
     *
     * @throws \Zend\Http\Client\Adapter\Exception
     * @param  \Zend\Config\Config | array $config
     * @return \Zend\Http\Client\Adapter\Curl
     */
    public function setConfig($config = array())
    {
        if ($config instanceof \Zend\Config\Config) {
            $config = $config->toArray();
        } elseif (!is_array($config)) {
            throw new AdapterException\InvalidArgumentException(
                'Array or Zend\Config\Config object expected, got ' . gettype($config)
            );
        }

        /** Config Key Normalization */
        foreach ($config as $k => $v) {
            unset($config[$k]); // unset original value
            $config[str_replace(array('-', '_', ' ', '.'), '', strtolower($k))] = $v; // replace w/ normalized
        }

        if(isset($config['proxyuser']) && isset($config['proxypass'])) {
            $this->setCurlOption(CURLOPT_PROXYUSERPWD, $config['proxyuser'].":".$config['proxypass']);
            unset($config['proxyuser'], $config['proxypass']);
        }

        foreach ($config as $k => $v) {
            $option = strtolower($k);
            switch($option) {
                case 'proxyhost':
                    $this->setCurlOption(CURLOPT_PROXY, $v);
                    break;
                case 'proxyport':
                    $this->setCurlOption(CURLOPT_PROXYPORT, $v);
                    break;
                default:
                    $this->config[$option] = $v;
                    break;
            }
        }

        return $this;
    }

    /**
      * Retrieve the array of all configuration options
      *
      * @return array
      */
     public function getConfig()
     {
         return $this->config;
     }

    /**
     * Direct setter for cURL adapter related options.
     *
     * @param  string|int $option
     * @param  mixed $value
     * @return Zend\Http\Adapter\Curl
     */
    public function setCurlOption($option, $value)
    {
        if (!isset($this->config['curloptions'])) {
            $this->config['curloptions'] = array();
        }
        $this->config['curloptions'][$option] = $value;
        return $this;
    }

    /**
     * Initialize curl
     *
     * @param  string  $host
     * @param  int     $port
     * @param  boolean $secure
     * @return void
     * @throws \Zend\Http\Client\Adapter\Exception if unable to connect
     */
    public function connect($host, $port = 80, $secure = false)
    {
        // If we're already connected, disconnect first
        if ($this->curl) {
            $this->close();
        }

        // If we are connected to a different server or port, disconnect first
        if ($this->curl
            && is_array($this->connectedTo)
            && ($this->connectedTo[0] != $host
            || $this->connectedTo[1] != $port)
        ) {
            $this->close();
        }

        // Do the actual connection
        $this->curl = curl_init();
        if ($port != 80) {
            curl_setopt($this->curl, CURLOPT_PORT, intval($port));
        }

        // Set timeout
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $this->config['timeout']);

        // Set Max redirects
        curl_setopt($this->curl, CURLOPT_MAXREDIRS, $this->config['maxredirects']);

        if (!$this->curl) {
            $this->close();

            throw new AdapterException\RuntimeException('Unable to Connect to ' .  $host . ':' . $port);
        }

        if ($secure !== false) {
            // Behave the same like Zend\Http\Adapter\Socket on SSL options.
            if (isset($this->config['sslcert'])) {
                curl_setopt($this->curl, CURLOPT_SSLCERT, $this->config['sslcert']);
            }
            if (isset($this->config['sslpassphrase'])) {
                curl_setopt($this->curl, CURLOPT_SSLCERTPASSWD, $this->config['sslpassphrase']);
            }
        }

        // Update connected_to
        $this->connectedTo = array($host, $port);
    }

    /**
     * Send request to the remote server
     *
     * @param  string        $method
     * @param  \Zend\Uri\Uri $uri
     * @param  float         $httpVersion
     * @param  array         $headers
     * @param  string        $body
     * @return string        $request
     * @throws \Zend\Http\Client\Adapter\Exception If connection fails, connected to wrong host, no PUT file defined, unsupported method, or unsupported cURL option
     */
    public function write($method, $uri, $httpVersion = 1.1, $headers = array(), $body = '')
    {
        // Make sure we're properly connected
        if (!$this->curl) {
            throw new AdapterException\RuntimeException("Trying to write but we are not connected");
        }

        if ($this->connectedTo[0] != $uri->getHost() || $this->connectedTo[1] != $uri->getPort()) {
            throw new AdapterException\RuntimeException("Trying to write but we are connected to the wrong host");
        }

        // set URL
        curl_setopt($this->curl, CURLOPT_URL, $uri->__toString());

        // ensure correct curl call
        $curlValue = true;
        switch ($method) {
            case 'GET' :
                $curlMethod = CURLOPT_HTTPGET;
                break;

            case 'POST' :
                $curlMethod = CURLOPT_POST;
                break;

            case 'PUT' :
                // There are two different types of PUT request, either a Raw Data string has been set
                // or CURLOPT_INFILE and CURLOPT_INFILESIZE are used.
                if(is_resource($body)) {
                    $this->config['curloptions'][CURLOPT_INFILE] = $body;
                }
                if (isset($this->config['curloptions'][CURLOPT_INFILE])) {
                    // Now we will probably already have Content-Length set, so that we have to delete it
                    // from $headers at this point:
                    foreach ($headers AS $k => $header) {
                        if (preg_match('/Content-Length:\s*(\d+)/i', $header, $m)) {
                            if(is_resource($body)) {
                                $this->config['curloptions'][CURLOPT_INFILESIZE] = (int)$m[1];
                            }
                            unset($headers[$k]);
                        }
                    }

                    if (!isset($this->config['curloptions'][CURLOPT_INFILESIZE])) {
                        throw new AdapterException\RuntimeException("Cannot set a file-handle for cURL option CURLOPT_INFILE without also setting its size in CURLOPT_INFILESIZE.");
                    }

                    if(is_resource($body)) {
                        $body = '';
                    }

                    $curlMethod = CURLOPT_UPLOAD;
                } else {
                    $curlMethod = CURLOPT_CUSTOMREQUEST;
                    $curlValue = "PUT";
                }
                break;

            case 'DELETE' :
                $curlMethod = CURLOPT_CUSTOMREQUEST;
                $curlValue = "DELETE";
                break;

            case 'OPTIONS' :
                $curlMethod = CURLOPT_CUSTOMREQUEST;
                $curlValue = "OPTIONS";
                break;

            case 'TRACE' :
                $curlMethod = CURLOPT_CUSTOMREQUEST;
                $curlValue = "TRACE";
                break;
            
            case 'HEAD' :
                $curlMethod = CURLOPT_CUSTOMREQUEST;
                $curlValue = "HEAD";
                break;

            default:
                // For now, through an exception for unsupported request methods
                throw new AdapterException\InvalidArgumentException("Method currently not supported");
        }

        if(is_resource($body) && $curlMethod != CURLOPT_UPLOAD) {
            throw new AdapterException\RuntimeException("Streaming requests are allowed only with PUT");
        }

        // get http version to use
        $curlHttp = ($httpVersion == 1.1) ? CURL_HTTP_VERSION_1_1 : CURL_HTTP_VERSION_1_0;

        // mark as HTTP request and set HTTP method
        curl_setopt($this->curl, $curlHttp, true);
        curl_setopt($this->curl, $curlMethod, $curlValue);

        if($this->outputStream) {
            // headers will be read into the response
            curl_setopt($this->curl, CURLOPT_HEADER, false);
            curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, array($this, "readHeader"));
            // and data will be written into the file
            curl_setopt($this->curl, CURLOPT_FILE, $this->outputStream);
        } else {
            // ensure headers are also returned
            curl_setopt($this->curl, CURLOPT_HEADER, true);

            // ensure actual response is returned
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        }

        // set additional headers
        $headers['Accept'] = '';
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);

        /**
         * Make sure POSTFIELDS is set after $curlMethod is set:
         * @link http://de2.php.net/manual/en/function.curl-setopt.php#81161
         */
        if ($method == 'POST') {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $body);
        } elseif ($curlMethod == CURLOPT_UPLOAD) {
            // this covers a PUT by file-handle:
            // Make the setting of this options explicit (rather than setting it through the loop following a bit lower)
            // to group common functionality together.
            curl_setopt($this->curl, CURLOPT_INFILE, $this->config['curloptions'][CURLOPT_INFILE]);
            curl_setopt($this->curl, CURLOPT_INFILESIZE, $this->config['curloptions'][CURLOPT_INFILESIZE]);
            unset($this->config['curloptions'][CURLOPT_INFILE]);
            unset($this->config['curloptions'][CURLOPT_INFILESIZE]);
        } elseif ($method == 'PUT') {
            // This is a PUT by a setRawData string, not by file-handle
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $body);
        }

        // set additional curl options
        if (isset($this->config['curloptions'])) {
            foreach ((array)$this->config['curloptions'] as $k => $v) {
                if (!in_array($k, $this->invalidOverwritableCurlOptions)) {
                    if (curl_setopt($this->curl, $k, $v) == false) {
                        throw new AdapterException\RuntimeException(sprintf("Unknown or erroreous cURL option '%s' set", $k));
                    }
                }
            }
        }

        // send the request
        $response = curl_exec($this->curl);

        // if we used streaming, headers are already there
        if(!is_resource($this->outputStream)) {
            $this->response = $response;
        }

        $request  = curl_getinfo($this->curl, CURLINFO_HEADER_OUT);
        $request .= $body;

        if (empty($this->response)) {
            throw new AdapterException\RuntimeException("Error in cURL request: " . curl_error($this->curl));
        }

        // cURL automatically decodes chunked-messages, this means we have to disallow the Zend\Http\Response to do it again
        if (stripos($this->response, "Transfer-Encoding: chunked\r\n")) {
            $this->response = str_ireplace("Transfer-Encoding: chunked\r\n", '', $this->response);
        }

        // Eliminate multiple HTTP responses.
        do {
            $parts  = preg_split('|(?:\r?\n){2}|m', $this->response, 2);
            $again  = false;

            if (isset($parts[1]) && preg_match("|^HTTP/1\.[01](.*?)\r\n|mi", $parts[1])) {
                $this->response    = $parts[1];
                $again              = true;
            }
        } while ($again);

        // cURL automatically handles Proxy rewrites, remove the "HTTP/1.0 200 Connection established" string:
        if (stripos($this->response, "HTTP/1.0 200 Connection established\r\n\r\n") !== false) {
            $this->response = str_ireplace("HTTP/1.0 200 Connection established\r\n\r\n", '', $this->response);
        }

        return $request;
    }

    /**
     * Return read response from server
     *
     * @return string
     */
    public function read()
    {
        return $this->response;
    }

    /**
     * Close the connection to the server
     *
     */
    public function close()
    {
        if(is_resource($this->curl)) {
            curl_close($this->curl);
        }
        $this->curl         = null;
        $this->connectedTo = array(null, null);
    }

    /**
     * Get cUrl Handle
     *
     * @return resource
     */
    public function getHandle()
    {
        return $this->curl;
    }

    /**
     * Set output stream for the response
     *
     * @param resource $stream
     * @return \Zend\Http\Client\Adapter\Socket
     */
    public function setOutputStream($stream)
    {
        $this->outputStream = $stream;
        return $this;
    }

    /**
     * Header reader function for CURL
     *
     * @param resource $curl
     * @param string $header
     * @return int
     */
    public function readHeader($curl, $header)
    {
        $this->response .= $header;
        return strlen($header);
    }
}
