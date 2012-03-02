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
 * @package    Zend_Mail
 * @subpackage Transport
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Zend\Mail\Transport;

use Traversable,
    Zend\Mail\AddressDescription,
    Zend\Mail\AddressList,
    Zend\Mail\Exception,
    Zend\Mail\Header,
    Zend\Mail\Headers,
    Zend\Mail\Message,
    Zend\Mail\Transport;

/**
 * Class for sending email via the PHP internal mail() function
 *
 * @category   Zend
 * @package    Zend_Mail
 * @subpackage Transport
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Sendmail implements Transport
{
    /**
     * Config options for sendmail parameters
     *
     * @var string
     */
    protected $parameters;

    /**
     * Callback to use when sending mail; typically, {@link mailHandler()}
     * 
     * @var callable
     */
    protected $callable;

    /**
     * error information
     * @var string
     */
    protected $errstr;

    /**
     * @var string
     */
    protected $operatingSystem;

    /**
     * Constructor.
     *
     * @param  null|string|array|Traversable $parameters OPTIONAL (Default: null)
     * @return void
     */
    public function __construct($parameters = null)
    {
        if ($parameters !== null) {
            $this->setParameters($parameters);
        }
        $this->callable = array($this, 'mailHandler');
    }

    /**
     * Set sendmail parameters
     *
     * Used to populate the additional_parameters argument to mail()
     * 
     * @param  null|string|array|Traversable $parameters 
     * @return Sendmail
     */
    public function setParameters($parameters)
    {
        if ($parameters === null || is_string($parameters)) {
            $this->parameters = $parameters;
            return $this;
        }

        if (!is_array($parameters) && !$parameters instanceof Traversable) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a string, array, or Traversable object of paremeters; received "%s"',
                __METHOD__,
                (is_object($parameters) ? get_class($parameters) : gettype($parameters))
            ));
        }

        $string = '';
        foreach ($parameters as $param) {
            $string .= ' ' . $param;
        }
        trim($string);

        $this->parameters = $string;
        return $this;
    }

    /**
     * Set callback to use for mail
     *
     * Primarily for testing purposes, but could be used to curry arguments.
     * 
     * @param  callable $callable 
     * @return Sendmail
     */
    public function setCallable($callable)
    {
        if (!is_callable($callable)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a callable argument; received "%s"',
                __METHOD__,
                (is_object($callable) ? get_class($callable) : gettype($callable))
            ));
        }
        $this->callable = $callable;
        return $this;
    }

    /**
     * Send a message
     * 
     * @param  Message $message 
     * @return void
     */
    public function send(Message $message)
    {
        $to      = $this->prepareRecipients($message);
        $subject = $this->prepareSubject($message);
        $body    = $this->prepareBody($message);
        $headers = $this->prepareHeaders($message);
        $params  = $this->prepareParameters($message);

        call_user_func($this->callable, $to, $subject, $body, $headers, $params);
    }

    /**
     * Prepare recipients list
     * 
     * @param  Message $message 
     * @return string
     */
    protected function prepareRecipients(Message $message)
    {
        $headers = $message->headers();

        if (!$headers->has('to')) {
            throw new Exception\RuntimeException('Invalid email; contains no "To" header');
        }

        $to   = $headers->get('to');
        $list = $to->getAddressList();
        if (0 == count($list)) {
            throw new Exception\RuntimeException('Invalid "To" header; contains no addresses');
        }

        // If not on Windows, return normal string
        if (!$this->isWindowsOs()) {
            return $to->getFieldValue();
        }

        // Otherwise, return list of emails
        $addresses = array();
        foreach ($list as $address) {
            $addresses[] = $address->getEmail();
        }
        $addresses = implode(', ', $addresses);
        return $addresses;
    }

    /**
     * Prepare the subject line string
     * 
     * @param  Message $message 
     * @return string
     */
    protected function prepareSubject(Message $message)
    {
        return $message->getSubject();
    }

    /**
     * Prepare the body string
     * 
     * @param  Message $message 
     * @return string
     */
    protected function prepareBody(Message $message)
    {
        if (!$this->isWindowsOs()) {
            // *nix platforms can simply return the body text
            return $message->getBodyText();
        }

        // On windows, lines beginning with a full stop need to be fixed
        $text = $message->getBodyText();
        $text = str_replace("\n.", "\n..", $text);
        return $text;
    }

    /**
     * Prepare the textual representation of headers
     * 
     * @param  Message $message
     * @return string
     */
    protected function prepareHeaders(Message $message)
    {
        $headers = $message->headers();

        // On Windows, simply return verbatim
        if ($this->isWindowsOs()) {
            return $headers->toString();
        }

        // On *nix platforms, strip the "to" header
        $headersToSend = new Headers();
        foreach ($headers as $header) {
            if ('To' == $header->getFieldName()) {
                continue;
            }
            $headersToSend->addHeader($header);
        }
        return $headersToSend->toString();
    }

    /**
     * Prepare additional_parameters argument
     *
     * Basically, overrides the MAIL FROM envelope with either the Sender or 
     * From address.
     * 
     * @param  Message $message 
     * @return string
     */
    protected function prepareParameters(Message $message)
    {
        if ($this->isWindowsOs()) {
            return null;
        }

        $parameters = (string) $this->parameters;

        $sender = $message->getSender();
        if ($sender instanceof AddressDescription) {
            $parameters .= ' -r ' . $sender->getEmail();
            return $parameters;
        }

        $from = $message->from();
        if (count($from)) {
            $from->rewind();
            $sender      = $from->current();
            $parameters .= ' -r ' . $sender->getEmail();
            return $parameters;
        }

        return $parameters;
    }

    /**
     * Send mail using PHP native mail()
     *
     * @param  string $to
     * @param  string $subject
     * @param  string $message
     * @param  string $headers
     * @return void
     * @throws Exception\RuntimeException on mail failure
     */
    public function mailHandler($to, $subject, $message, $headers, $parameters)
    {
        set_error_handler(array($this, 'handleMailErrors'));
        if ($parameters === null) {
            $result = mail($to, $subject, $message, $headers);
        } else {
            $result = mail($to, $subject, $message, $headers, $parameters);
        }
        restore_error_handler();

        if ($this->errstr !== null || !$result) {
            $errstr = $this->errstr;
            if (empty($errstr)) {
                $errstr = 'Unknown error';
            }
            throw new Exception\RuntimeException('Unable to send mail: ' . $errstr);
        }
    }

    /**
     * Temporary error handler for PHP native mail().
     *
     * @param int    $errno
     * @param string $errstr
     * @param string $errfile
     * @param string $errline
     * @param array  $errcontext
     * @return true
     */
    public function handleMailErrors($errno, $errstr, $errfile = null, $errline = null, array $errcontext = null)
    {
        $this->errstr = $errstr;
        return true;
    }

    /**
     * Is this a windows OS?
     * 
     * @return bool
     */
    protected function isWindowsOs()
    {
        if (!$this->operatingSystem) {
            $this->operatingSystem = strtoupper(substr(PHP_OS, 0, 3));
        }
        return ($this->operatingSystem == 'WIN');
    }
}
