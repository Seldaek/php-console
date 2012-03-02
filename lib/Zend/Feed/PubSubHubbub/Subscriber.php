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
 * @package    Zend_Feed_Pubsubhubbub
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Feed\PubSubHubbub;

use Zend\Date,
    Zend\Uri;

/**
 * @category   Zend
 * @package    Zend_Feed_Pubsubhubbub
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Subscriber
{
    /**
     * An array of URLs for all Hub Servers to subscribe/unsubscribe.
     *
     * @var array
     */
    protected $_hubUrls = array();

    /**
     * An array of optional parameters to be included in any
     * (un)subscribe requests.
     *
     * @var array
     */
    protected $_parameters = array();

    /**
     * The URL of the topic (Rss or Atom feed) which is the subject of
     * our current intent to subscribe to/unsubscribe from updates from
     * the currently configured Hub Servers.
     *
     * @var string
     */
    protected $_topicUrl = '';

    /**
     * The URL Hub Servers must use when communicating with this Subscriber
     *
     * @var string
     */
    protected $_callbackUrl = '';

    /**
     * The number of seconds for which the subscriber would like to have the
     * subscription active. Defaults to null, i.e. not sent, to setup a
     * permanent subscription if possible.
     *
     * @var int
     */
    protected $_leaseSeconds = null;

    /**
     * The preferred verification mode (sync or async). By default, this
     * Subscriber prefers synchronous verification, but is considered
     * desireable to support asynchronous verification if possible.
     *
     * Zend\Feed\Pubsubhubbub\Subscriber will always send both modes, whose
     * order of occurance in the parameter list determines this preference.
     *
     * @var string
     */
    protected $_preferredVerificationMode = PubSubHubbub::VERIFICATION_MODE_SYNC;

    /**
     * An array of any errors including keys for 'response', 'hubUrl'.
     * The response is the actual Zend\Http\Response object.
     *
     * @var array
     */
    protected $_errors = array();

    /**
     * An array of Hub Server URLs for Hubs operating at this time in
     * asynchronous verification mode.
     *
     * @var array
     */
    protected $_asyncHubs = array();

    /**
     * An instance of Zend\Feed\Pubsubhubbub\Model\SubscriptionPersistence used to background
     * save any verification tokens associated with a subscription or other.
     *
     * @var \Zend\Feed\PubSubHubbub\Model\SubscriptionPersistence
     */
    protected $_storage = null;

    /**
     * An array of authentication credentials for HTTP Basic Authentication
     * if required by specific Hubs. The array is indexed by Hub Endpoint URI
     * and the value is a simple array of the username and password to apply.
     *
     * @var array
     */
    protected $_authentications = array();
    
    /**
     * Tells the Subscriber to append any subscription identifier to the path
     * of the base Callback URL. E.g. an identifier "subkey1" would be added
     * to the callback URL "http://www.example.com/callback" to create a subscription
     * specific Callback URL of "http://www.example.com/callback/subkey1".
     *
     * This is required for all Hubs using the Pubsubhubbub 0.1 Specification.
     * It should be manually intercepted and passed to the Callback class using
     * Zend\Feed\Pubsubhubbub\Subscriber\Callback::setSubscriptionKey(). Will
     * require a route in the form "callback/:subkey" to allow the parameter be
     * retrieved from an action using the Zend\Controller\Action::\getParam()
     * method.
     *
     * @var string
     */
    protected $_usePathParameter = false;

    /**
     * Constructor; accepts an array or Zend\Config instance to preset
     * options for the Subscriber without calling all supported setter
     * methods in turn.
     *
     * @param  array|\Zend\Config\Config $options Options array or \Zend\Config\Config instance
     * @return void
     */
    public function __construct($config = null)
    {
        if ($config !== null) {
            $this->setConfig($config);
        }
    }

    /**
     * Process any injected configuration options
     *
     * @param  array|\Zend\Config\Config $options Options array or \Zend\Config\Config instance
     * @return \Zend\Feed\PubSubHubbub\Subscriber\Subscriber
     */
    public function setConfig($config)
    {
        if ($config instanceof \Zend\Config\Config) {
            $config = $config->toArray();
        } elseif (!is_array($config)) {
            throw new Exception('Array or Zend\Config object'
                . ' expected, got ' . gettype($config));
        }
        if (array_key_exists('hubUrls', $config)) {
            $this->addHubUrls($config['hubUrls']);
        }
        if (array_key_exists('callbackUrl', $config)) {
            $this->setCallbackUrl($config['callbackUrl']);
        }
        if (array_key_exists('topicUrl', $config)) {
            $this->setTopicUrl($config['topicUrl']);
        }
        if (array_key_exists('storage', $config)) {
            $this->setStorage($config['storage']);
        }
        if (array_key_exists('leaseSeconds', $config)) {
            $this->setLeaseSeconds($config['leaseSeconds']);
        }
        if (array_key_exists('parameters', $config)) {
            $this->setParameters($config['parameters']);
        }
        if (array_key_exists('authentications', $config)) {
            $this->addAuthentications($config['authentications']);
        }
        if (array_key_exists('usePathParameter', $config)) {
            $this->usePathParameter($config['usePathParameter']);
        }
        if (array_key_exists('preferredVerificationMode', $config)) {
            $this->setPreferredVerificationMode(
                $config['preferredVerificationMode']
            );
        }
        return $this;
    }

    /**
     * Set the topic URL (RSS or Atom feed) to which the intended (un)subscribe
     * event will relate
     *
     * @param  string $url
     * @return \Zend\Feed\PubSubHubbub\Subscriber\Subscriber
     */
    public function setTopicUrl($url)
    {
        if (empty($url) || !is_string($url) || !Uri\UriFactory::factory($url)->isValid()) {
            throw new Exception('Invalid parameter "url"'
                .' of "' . $url . '" must be a non-empty string and a valid'
                .' URL');
        }
        $this->_topicUrl = $url;
        return $this;
    }

    /**
     * Set the topic URL (RSS or Atom feed) to which the intended (un)subscribe
     * event will relate
     *
     * @return string
     */
    public function getTopicUrl()
    {
        if (empty($this->_topicUrl)) {
            throw new Exception('A valid Topic (RSS or Atom'
                . ' feed) URL MUST be set before attempting any operation');
        }
        return $this->_topicUrl;
    }

    /**
     * Set the number of seconds for which any subscription will remain valid
     *
     * @param  int $seconds
     * @return \Zend\Feed\PubSubHubbub\Subscriber\Subscriber
     */
    public function setLeaseSeconds($seconds)
    {
        $seconds = intval($seconds);
        if ($seconds <= 0) {
            throw new Exception('Expected lease seconds'
                . ' must be an integer greater than zero');
        }
        $this->_leaseSeconds = $seconds;
        return $this;
    }

    /**
     * Get the number of lease seconds on subscriptions
     *
     * @return int
     */
    public function getLeaseSeconds()
    {
        return $this->_leaseSeconds;
    }

    /**
     * Set the callback URL to be used by Hub Servers when communicating with
     * this Subscriber
     *
     * @param  string $url
     * @return \Zend\Feed\PubSubHubbub\Subscriber\Subscriber
     */
    public function setCallbackUrl($url)
    {
        if (empty($url) || !is_string($url) || !Uri\UriFactory::factory($url)->isValid()) {
            throw new Exception('Invalid parameter "url"'
                . ' of "' . $url . '" must be a non-empty string and a valid'
                . ' URL');
        }
        $this->_callbackUrl = $url;
        return $this;
    }

    /**
     * Get the callback URL to be used by Hub Servers when communicating with
     * this Subscriber
     *
     * @return string
     */
    public function getCallbackUrl()
    {
        if (empty($this->_callbackUrl)) {
            throw new Exception('A valid Callback URL MUST be'
                . ' set before attempting any operation');
        }
        return $this->_callbackUrl;
    }

    /**
     * Set preferred verification mode (sync or async). By default, this
     * Subscriber prefers synchronous verification, but does support
     * asynchronous if that's the Hub Server's utilised mode.
     *
     * Zend\Feed\Pubsubhubbub\Subscriber will always send both modes, whose
     * order of occurance in the parameter list determines this preference.
     *
     * @param  string $mode Should be 'sync' or 'async'
     * @return \Zend\Feed\PubSubHubbub\Subscriber\Subscriber
     */
    public function setPreferredVerificationMode($mode)
    {
        if ($mode !== PubSubHubbub::VERIFICATION_MODE_SYNC
            && $mode !== PubSubHubbub::VERIFICATION_MODE_ASYNC
        ) {
            throw new Exception('Invalid preferred'
                . ' mode specified: "' . $mode . '" but should be one of'
                . ' Zend\Feed\Pubsubhubbub::VERIFICATION_MODE_SYNC or'
                . ' Zend\Feed\Pubsubhubbub::VERIFICATION_MODE_ASYNC');
        }
        $this->_preferredVerificationMode = $mode;
        return $this;
    }

    /**
     * Get preferred verification mode (sync or async).
     *
     * @return string
     */
    public function getPreferredVerificationMode()
    {
        return $this->_preferredVerificationMode;
    }

    /**
     * Add a Hub Server URL supported by Publisher
     *
     * @param  string $url
     * @return \Zend\Feed\PubSubHubbub\Subscriber\Subscriber
     */
    public function addHubUrl($url)
    {
        if (empty($url) || !is_string($url) || !Uri\UriFactory::factory($url)->isValid()) {
            throw new Exception('Invalid parameter "url"'
                . ' of "' . $url . '" must be a non-empty string and a valid'
                . ' URL');
        }
        $this->_hubUrls[] = $url;
        return $this;
    }

    /**
     * Add an array of Hub Server URLs supported by Publisher
     *
     * @param  array $urls
     * @return \Zend\Feed\PubSubHubbub\Subscriber\Subscriber
     */
    public function addHubUrls(array $urls)
    {
        foreach ($urls as $url) {
            $this->addHubUrl($url);
        }
        return $this;
    }

    /**
     * Remove a Hub Server URL
     *
     * @param  string $url
     * @return \Zend\Feed\PubSubHubbub\Subscriber\Subscriber
     */
    public function removeHubUrl($url)
    {
        if (!in_array($url, $this->getHubUrls())) {
            return $this;
        }
        $key = array_search($url, $this->_hubUrls);
        unset($this->_hubUrls[$key]);
        return $this;
    }

    /**
     * Return an array of unique Hub Server URLs currently available
     *
     * @return array
     */
    public function getHubUrls()
    {
        $this->_hubUrls = array_unique($this->_hubUrls);
        return $this->_hubUrls;
    }
    
    /**
     * Add authentication credentials for a given URL
     * 
     * @param  string $url 
     * @param  array $authentication 
     * @return \Zend\Feed\PubSubHubbub\Subscriber\Subscriber
     */
    public function addAuthentication($url, array $authentication)
    {
        if (empty($url) || !is_string($url) || !Uri\UriFactory::factory($url)->isValid()) {
            throw new Exception('Invalid parameter "url"'
                . ' of "' . $url . '" must be a non-empty string and a valid'
                . ' URL');
        }
        $this->_authentications[$url] = $authentication;
        return $this;
    }
    
    /**
     * Add authentication credentials for hub URLs
     * 
     * @param  array $authentications 
     * @return \Zend\Feed\PubSubHubbub\Subscriber\Subscriber
     */
    public function addAuthentications(array $authentications)
    {
        foreach ($authentications as $url => $authentication) {
            $this->addAuthentication($url, $authentication);
        }
        return $this;
    }
    
    /**
     * Get all hub URL authentication credentials
     * 
     * @return array
     */
    public function getAuthentications()
    {
        return $this->_authentications;
    }
    
    /**
     * Set flag indicating whether or not to use a path parameter
     * 
     * @param  bool $bool 
     * @return \Zend\Feed\PubSubHubbub\Subscriber\Subscriber
     */
    public function usePathParameter($bool = true)
    {
        $this->_usePathParameter = $bool;
        return $this;
    }

    /**
     * Add an optional parameter to the (un)subscribe requests
     *
     * @param  string $name
     * @param  string|null $value
     * @return \Zend\Feed\PubSubHubbub\Subscriber\Subscriber
     */
    public function setParameter($name, $value = null)
    {
        if (is_array($name)) {
            $this->setParameters($name);
            return $this;
        }
        if (empty($name) || !is_string($name)) {
            throw new Exception('Invalid parameter "name"'
                . ' of "' . $name . '" must be a non-empty string');
        }
        if ($value === null) {
            $this->removeParameter($name);
            return $this;
        }
        if (empty($value) || (!is_string($value) && $value !== null)) {
            throw new Exception('Invalid parameter "value"'
                . ' of "' . $value . '" must be a non-empty string');
        }
        $this->_parameters[$name] = $value;
        return $this;
    }

    /**
     * Add an optional parameter to the (un)subscribe requests
     *
     * @param  string $name
     * @param  string|null $value
     * @return \Zend\Feed\PubSubHubbub\Subscriber\Subscriber
     */
    public function setParameters(array $parameters)
    {
        foreach ($parameters as $name => $value) {
            $this->setParameter($name, $value);
        }
        return $this;
    }

    /**
     * Remove an optional parameter for the (un)subscribe requests
     *
     * @param  string $name
     * @return \Zend\Feed\PubSubHubbub\Subscriber\Subscriber
     */
    public function removeParameter($name)
    {
        if (empty($name) || !is_string($name)) {
            throw new Exception('Invalid parameter "name"'
                . ' of "' . $name . '" must be a non-empty string');
        }
        if (array_key_exists($name, $this->_parameters)) {
            unset($this->_parameters[$name]);
        }
        return $this;
    }

    /**
     * Return an array of optional parameters for (un)subscribe requests
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->_parameters;
    }

    /**
     * Sets an instance of Zend\Feed\Pubsubhubbub\Model\SubscriptionPersistence used to background
     * save any verification tokens associated with a subscription or other.
     *
     * @param  \Zend\Feed\PubSubHubbub\Model\SubscriptionPersistence $storage
     * @return \Zend\Feed\PubSubHubbub\Subscriber\Subscriber
     */
    public function setStorage(Model\SubscriptionPersistence $storage)
    {
        $this->_storage = $storage;
        return $this;
    }

    /**
     * Gets an instance of Zend\Feed\Pubsubhubbub\Storage\StoragePersistence used 
     * to background save any verification tokens associated with a subscription
     * or other.
     *
     * @return \Zend\Feed\PubSubHubbub\Model\SubscriptionPersistence
     */
    public function getStorage()
    {
        if ($this->_storage === null) {
            throw new Exception('No storage vehicle '
                . 'has been set.');
        }
        return $this->_storage;
    }

    /**
     * Subscribe to one or more Hub Servers using the stored Hub URLs
     * for the given Topic URL (RSS or Atom feed)
     *
     * @return void
     */
    public function subscribeAll()
    {
        return $this->_doRequest('subscribe');
    }

    /**
     * Unsubscribe from one or more Hub Servers using the stored Hub URLs
     * for the given Topic URL (RSS or Atom feed)
     *
     * @return void
     */
    public function unsubscribeAll()
    {
        return $this->_doRequest('unsubscribe');
    }

    /**
     * Returns a boolean indicator of whether the notifications to Hub
     * Servers were ALL successful. If even one failed, FALSE is returned.
     *
     * @return bool
     */
    public function isSuccess()
    {
        if (count($this->_errors) > 0) {
            return false;
        }
        return true;
    }

    /**
     * Return an array of errors met from any failures, including keys:
     * 'response' => the Zend\Http\Response object from the failure
     * 'hubUrl' => the URL of the Hub Server whose notification failed
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Return an array of Hub Server URLs who returned a response indicating
     * operation in Asynchronous Verification Mode, i.e. they will not confirm
     * any (un)subscription immediately but at a later time (Hubs may be
     * doing this as a batch process when load balancing)
     *
     * @return array
     */
    public function getAsyncHubs()
    {
        return $this->_asyncHubs;
    }

    /**
     * Executes an (un)subscribe request
     *
     * @param  string $mode
     * @return void
     */
    protected function _doRequest($mode)
    {
        $client = $this->_getHttpClient();
        $hubs   = $this->getHubUrls();
        if (empty($hubs)) {
            throw new Exception('No Hub Server URLs'
                . ' have been set so no subscriptions can be attempted');
        }
        $this->_errors = array();
        $this->_asyncHubs = array();
        foreach ($hubs as $url) {
            if (array_key_exists($url, $this->_authentications)) {
                $auth = $this->_authentications[$url];
                $client->setAuth($auth[0], $auth[1]);
            }
            $client->setUri($url);
            $client->setRawData($this->_getRequestParameters($url, $mode));
            $response = $client->request();
            if ($response->getStatus() !== 204
                && $response->getStatus() !== 202
            ) {
                $this->_errors[] = array(
                    'response' => $response,
                    'hubUrl'   => $url,
                );
            /**
             * At first I thought it was needed, but the backend storage will
             * allow tracking async without any user interference. It's left
             * here in case the user is interested in knowing what Hubs
             * are using async verification modes so they may update Models and
             * move these to asynchronous processes.
             */
            } elseif ($response->getStatus() == 202) {
                $this->_asyncHubs[] = array(
                    'response' => $response,
                    'hubUrl'   => $url,
                );
            }
        }
    }

    /**
     * Get a basic prepared HTTP client for use
     *
     * @param  string $mode Must be "subscribe" or "unsubscribe"
     * @return \Zend\Http\Client
     */
    protected function _getHttpClient()
    {
        $client = PubSubHubbub::getHttpClient();
        $client->setMethod(\Zend\Http\Client::POST);
        $client->setConfig(array('useragent' => 'Zend_Feed_Pubsubhubbub_Subscriber/'
            . \Zend\Version::VERSION));
        return $client;
    }

    /**
     * Return a list of standard protocol/optional parameters for addition to
     * client's POST body that are specific to the current Hub Server URL
     *
     * @param  string $hubUrl
     * @param  mode $hubUrl
     * @return string
     */
    protected function _getRequestParameters($hubUrl, $mode)
    {
        if (!in_array($mode, array('subscribe', 'unsubscribe'))) {
            throw new Exception('Invalid mode specified: "'
                . $mode . '" which should have been "subscribe" or "unsubscribe"');
        }

        $params = array(
            'hub.mode'  => $mode,
            'hub.topic' => $this->getTopicUrl(),
        );

        if ($this->getPreferredVerificationMode()
                == PubSubHubbub::VERIFICATION_MODE_SYNC
        ) {
            $vmodes = array(
                PubSubHubbub::VERIFICATION_MODE_SYNC,
                PubSubHubbub::VERIFICATION_MODE_ASYNC,
            );
        } else {
            $vmodes = array(
                PubSubHubbub::VERIFICATION_MODE_ASYNC,
                PubSubHubbub::VERIFICATION_MODE_SYNC,
            );
        }
        $params['hub.verify'] = array();
        foreach($vmodes as $vmode) {
            $params['hub.verify'][] = $vmode;
        }

        /**
         * Establish a persistent verify_token and attach key to callback
         * URL's path/querystring
         */
        $key   = $this->_generateSubscriptionKey($params, $hubUrl);
        $token = $this->_generateVerifyToken();
        $params['hub.verify_token'] = $token;

        // Note: query string only usable with PuSH 0.2 Hubs
        if (!$this->_usePathParameter) {
            $params['hub.callback'] = $this->getCallbackUrl()
                . '?xhub.subscription=' . PubSubHubbub::urlencode($key);
        } else {
            $params['hub.callback'] = rtrim($this->getCallbackUrl(), '/')
                . '/' . PubSubHubbub::urlencode($key);
        }
        if ($mode == 'subscribe' && $this->getLeaseSeconds() !== null) {
            $params['hub.lease_seconds'] = $this->getLeaseSeconds();
        }

        // hub.secret not currently supported
        $optParams = $this->getParameters();
        foreach ($optParams as $name => $value) {
            $params[$name] = $value;
        }
        
        // store subscription to storage
        $now = new Date\Date;
        $expires = null;
        if (isset($params['hub.lease_seconds'])) {
            $expires = $now->add($params['hub.lease_seconds'], Date\Date::SECOND)
                ->get('yyyy-MM-dd HH:mm:ss');
        }
        $data = array(
            'id'                 => $key,
            'topic_url'          => $params['hub.topic'],
            'hub_url'            => $hubUrl,
            'created_time'       => $now->get('yyyy-MM-dd HH:mm:ss'),
            'lease_seconds'      => $expires,
            'verify_token'       => hash('sha256', $params['hub.verify_token']),
            'secret'             => null,
            'expiration_time'    => $expires,
            'subscription_state' => PubSubHubbub::SUBSCRIPTION_NOTVERIFIED,
        );
        $this->getStorage()->setSubscription($data);

        return $this->_toByteValueOrderedString(
            $this->_urlEncode($params)
        );
    }

    /**
     * Simple helper to generate a verification token used in (un)subscribe
     * requests to a Hub Server. Follows no particular method, which means
     * it might be improved/changed in future.
     *
     * @param  string $hubUrl The Hub Server URL for which this token will apply
     * @return string
     */
    protected function _generateVerifyToken()
    {
        if (!empty($this->_testStaticToken)) {
            return $this->_testStaticToken;
        }
        return uniqid(rand(), true) . time();
    }

    /**
     * Simple helper to generate a verification token used in (un)subscribe
     * requests to a Hub Server.
     *
     * @param  string $hubUrl The Hub Server URL for which this token will apply
     * @return string
     */
    protected function _generateSubscriptionKey(array $params, $hubUrl)
    {
        $keyBase = $params['hub.topic'] . $hubUrl;
        $key     = md5($keyBase);
        return $key;
    }

    /**
     * URL Encode an array of parameters
     *
     * @param  array $params
     * @return array
     */
    protected function _urlEncode(array $params)
    {
        $encoded = array();
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $ekey = PubSubHubbub::urlencode($key);
                $encoded[$ekey] = array();
                foreach ($value as $duplicateKey) {
                    $encoded[$ekey][]
                        = PubSubHubbub::urlencode($duplicateKey);
                }
            } else {
                $encoded[PubSubHubbub::urlencode($key)]
                    = PubSubHubbub::urlencode($value);
            }
        }
        return $encoded;
    }

    /**
     * Order outgoing parameters
     *
     * @param  array $params
     * @return array
     */
    protected function _toByteValueOrderedString(array $params)
    {
        $return = array();
        uksort($params, 'strnatcmp');
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $keyduplicate) {
                    $return[] = $key . '=' . $keyduplicate;
                }
            } else {
                $return[] = $key . '=' . $value;
            }
        }
        return implode('&', $return);
    }

    /**
     * This is STRICTLY for testing purposes only...
     */
    protected $_testStaticToken = null;

    final public function setTestStaticToken($token)
    {
        $this->_testStaticToken = (string) $token;
    }
}
