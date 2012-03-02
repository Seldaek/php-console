<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-webat this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Session
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @namespace
 */
namespace Zend\Session;

use Zend\Validator\Alnum as AlnumValidator,
    Zend\EventManager\EventCollection;

/**
 * Session Manager implementation utilizing ext/session
 *
 * @category   Zend
 * @package    Zend_Session
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class SessionManager extends AbstractManager
{
    /**
     * Default options when a call to {@link destroy()} is made
     * - send_expire_cookie: whether or not to send a cookie expiring the current session cookie
     * - clear_storage: whether or not to empty the storage object of any stored values
     * @var array
     */
    protected $defaultDestroyOptions = array(
        'send_expire_cookie' => true,
        'clear_storage'      => false,
    );

    /**
     * @var string value returned by session_name()
     */
    protected $name;

    /**
     * @var EventCollection Validation chain to determine if session is valid
     */
    protected $validatorChain;

    /**
     * Does a session exist and is it currently active?
     * 
     * @return bool
     */
    public function sessionExists()
    {
        $sid = defined('SID') ? constant('SID') : false;
        if ($sid !== false && $this->getId()) {
            return true;
        }
        if (headers_sent()) {
            return true;
        }
        return false;
    }

    /**
     * Start session
     *
     * if No sesion currently exists, attempt to start it. Calls
     * {@link isValid()} once session_start() is called, and raises an
     * exception if validation fails.
     *
     * @param bool $preserveStorage        If set to true, current session storage will not be overwritten by the 
     *                                     contents of $_SESSION.
     * @return void
     * @throws Exception
     */
    public function start($preserveStorage = false)
    {
        if ($this->sessionExists()) {
            return;
        }

        $saveHandler = $this->getSaveHandler();
        if ($saveHandler instanceof SaveHandler) {
            // register the session handler with ext/session
            $this->registerSaveHandler($saveHandler);
        }

        session_start();
        if (!$this->isValid()) {
            throw new Exception\RuntimeException('Session failed validation');
        }
        $storage = $this->getStorage();

        // Since session is starting, we need to potentially repopulate our 
        // session storage
        if ($storage instanceof Storage\SessionStorage
            && $_SESSION !== $storage
        ) {
            if (!$preserveStorage){
                $storage->fromArray($_SESSION);
            }
            $_SESSION = $storage;
        }
    }

    /**
     * Destroy/end a session
     * 
     * @param  array $options See {@link $defaultDestroyOptions}
     * @return void
     */
    public function destroy(array $options = null)
    {
        if (!$this->sessionExists()) {
            return;
        }

        if (null === $options) {
            $options = $this->defaultDestroyOptions;
        } else {
            $options = array_merge($this->defaultDestroyOptions, $options);
        }

        session_destroy();
        if ($options['send_expire_cookie']) {
            $this->expireSessionCookie();
        }

        if ($options['clear_storage']) {
            $this->getStorage()->clear();
        }
    }

    /**
     * Write session to save handler and close
     *
     * Once done, the Storage object will be marked as immutable.
     * 
     * @return void
     */
    public function writeClose()
    {
        // The assumption is that we're using PHP's ext/session.
        // session_write_close() will actually overwrite $_SESSION with an 
        // empty array on completion -- which leads to a mismatch between what
        // is in the storage object and $_SESSION. To get around this, we
        // temporarily reset $_SESSION to an array, and then re-link it to 
        // the storage object.
        //
        // Additionally, while you _can_ write to $_SESSION following a 
        // session_write_close() operation, no changes made to it will be 
        // flushed to the session handler. As such, we now mark the storage 
        // object immutable.
        $storage  = $this->getStorage();
        $_SESSION = (array) $storage;
        session_write_close();
        $storage->fromArray($_SESSION);
        $storage->markImmutable();
    }

    /**
     * Get session name
     *
     * Proxies to {@link session_name()}.
     * 
     * @return string
     */
    public function getName()
    {
        if (null === $this->name) {
            // If we're grabbing via session_name(), we don't need our 
            // validation routine; additionally, calling setName() after
            // session_start() can lead to issues, and often we just need the name
            // in order to do things such as setting cookies.
            $this->name = session_name();
        }
        return $this->name;
    }

    /**
     * Attempt to set the session name
     *
     * If the session has already been started, or if the name provided fails 
     * validation, an exception will be raised.
     * 
     * @param  string $name 
     * @return SessionManager
     * @throws Exception
     */
    public function setName($name)
    {
        if ($this->sessionExists()) {
            throw new Exception\InvalidArgumentException('Cannot set session name after a session has already started');
        }

        $validator = new AlnumValidator();
        if (!$validator->isValid($name)) {
            throw new Exception\InvalidArgumentException('Name provided contains invalid characters; must be alphanumeric only');
        }

        $this->name = $name;
        session_name($name);
        return $this;
    }

    /**
     * Get session ID
     *
     * Proxies to {@link session_id()}
     * 
     * @return string
     */
    public function getId()
    {
        return session_id();
    }

    /**
     * Set session ID
     *
     * Can safely be called in the middle of a session.
     * 
     * @param  string $id 
     * @return SessionManager
     */
    public function setId($id)
    {
        if (!$this->sessionExists()) {
            session_id($id);
            return $this;
        }
        $this->destroy();
        session_id($id);
        $this->start();
        return $this;
    }

    /**
     * Regenerate id
     *
     * Regenerate the session ID, using session save handler's 
     * native ID generation Can safely be called in the middle of a session.
     *
     * @param  bool $deleteOldSession
     * @return SessionManager
     */
    public function regenerateId($deleteOldSession = true)
    {
        if (!$this->sessionExists()) {
            session_regenerate_id((bool) $deleteOldSession);
            return $this;
        }

        session_regenerate_id((bool) $deleteOldSession);
        return $this;
    }

    /**
     * Set the TTL (in seconds) for the session cookie expiry
     *
     * Can safely be called in the middle of a session.
     *
     * @param  null|int $ttl 
     * @return SessionManager
     */
    public function rememberMe($ttl = null)
    {
        if (null === $ttl) {
            $ttl = $this->getConfig()->getRememberMeSeconds();
        }
        $this->setSessionCookieLifetime($ttl);
        return $this;
    }

    /**
     * Set a 0s TTL for the session cookie
     *
     * Can safely be called in the middle of a session.
     * 
     * @return SessionManager
     */
    public function forgetMe()
    {
        $this->setSessionCookieLifetime(0);
        return $this;
    }

    /**
     * Set the validator chain to use when validating a session
     *
     * In most cases, you should use an instance of {@link ValidatorChain}.
     * 
     * @param  EventCollection $chain 
     * @return SessionManager
     */
    public function setValidatorChain(EventCollection $chain)
    {
        $this->validatorChain = $chain;
        return $this;
    }

    /**
     * Get the validator chain to use when validating a session
     *
     * By default, uses an instance of {@link ValidatorChain}.
     * 
     * @return void
     */
    public function getValidatorChain()
    {
        if (null === $this->validatorChain) {
            $this->setValidatorChain(new ValidatorChain($this->getStorage()));
        }
        return $this->validatorChain;
    }

    /**
     * Is this session valid?
     *
     * Notifies the Validator Chain until either all validators have returned 
     * true or one has failed.
     * 
     * @return bool
     */
    public function isValid()
    {
        $validator = $this->getValidatorChain();
        $responses = $validator->triggerUntil('session.validate', $this, array($this), function($test) {
            return !$test;
        });
        if ($responses->stopped()) {
            // If execution was halted, validation failed
            return false;
        }
        // Otherwise, we're good to go
        return true;
    }

    /**
     * Expire the session cookie
     *
     * Sends a session cookie with no value, and with an expiry in the past.
     * 
     * @return void
     */
    public function expireSessionCookie()
    {
        $config = $this->getConfig();
        if (!$config->getUseCookies()) {
            return;
        }
        setcookie(
            $this->getName(),                 // session name
            '',                               // value
            $_SERVER['REQUEST_TIME'] - 42000, // TTL for cookie
            $config->getCookiePath(),
            $config->getCookieDomain(),
            $config->getCookieSecure(), 
            $config->getCookieHTTPOnly()
        );
    }

    /**
     * Set the session cookie lifetime
     *
     * If a session already exists, destroys it (without sending an expiration 
     * cookie), regenerates the session ID, and restarts the session.
     *
     * @param  int $ttl 
     * @return void
     */
    protected function setSessionCookieLifetime($ttl)
    {
        $config = $this->getConfig();
        if (!$config->getUseCookies()) {
            return;
        }

        // Set new cookie TTL
        $config->setCookieLifetime($ttl);

        if ($this->sessionExists()) {
            // There is a running session so we'll regenerate id to send a new cookie
            $this->regenerateId();
        }
    }

    /**
     * Register Save Handler with ext/session
     *
     * Since ext/session is coupled to this particular session manager
     * register the save handler with ext/session.
     *
     * @param SaveHandler $saveHandler
     * @return bool
     */
    protected function registerSaveHandler(SaveHandler $saveHandler)
    {
        return session_set_save_handler(
            array($saveHandler, 'open'),
            array($saveHandler, 'close'),
            array($saveHandler, 'read'),
            array($saveHandler, 'write'),
            array($saveHandler, 'destroy'),
            array($saveHandler, 'gc')
        );
    }
}
