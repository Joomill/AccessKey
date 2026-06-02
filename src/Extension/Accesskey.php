<?php
/*
 *  package: Joomill Access Key plugin
 *  copyright: Copyright (c) 2025. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 3 or later
 *  link: https://www.joomill-extensions.com
 */

namespace Joomill\Plugin\System\Accesskey\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\EventInterface;
use Joomla\Event\SubscriberInterface;
use Joomill\Plugin\System\Accesskey\Helper\IpHelper;
use Joomill\Plugin\System\Accesskey\Exception\AccessKeyException;

/**
 * Access Key System Plugin
 *
 * @since  2.0.0
 */
class Accesskey extends CMSPlugin implements SubscriberInterface
{
    /**
     * Load the language file on instantiation
     *
     * @var    boolean
     * @since  2.0.0
     */
    protected $autoloadLanguage = true;


    /**
     * Flag to indicate if the correct key was provided
     *
     * @var    boolean
     * @since  2.0.0
     */
    private $correctKey = false;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   2.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterInitialise' => 'onAfterInitialise',
        ];
    }

    /**
     * Runs after Joomla has been initialized
     *
     * @param   EventInterface  $event  The event being handled
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function onAfterInitialise(EventInterface $event): void
    {
        try {
            $session = $this->getApplication()->getSession();
            if ($session->get('accesskey')) {
                return;
            }

            // Check if key is configured
            if (!$this->params->get('key')) {
                Log::add('Access Key plugin is enabled but no key is configured', Log::WARNING, 'accesskey');
                return;
            }

            if (!$this->getApplication()->isClient('administrator')) {
                return;
            }

            try {
                // Get visitor IP using helper class
                $ipHelper = new IpHelper($this->getApplication());
                $visitorIP = $ipHelper->getVisitorIp();
                $whitelist = array_map('trim', explode(',', $this->params->get('whitelist') ?? ''));
                
                if ($ipHelper->isIpInWhitelist($visitorIP, $whitelist)) {
                    // Show a message for whitelisted IPs that did not also supply the key
                    if (!$this->isAccessKeyProvided()) {
                        $this->showWhitelistMessage();
                    }

                    $session->set('accesskey', true);
                    return;
                }
            } catch (AccessKeyException $e) {
                // Log IP detection error but continue with key check
                Log::add('IP detection failed: ' . $e->getMessage(), Log::WARNING, 'accesskey');
            }

            // Check if security key has been entered
            $this->correctKey = $this->isAccessKeyProvided();
            if ($this->correctKey) {
                $session->set('accesskey', true);
                return;
            }

            // Access denied - handle according to configuration
            $this->handleAccessDenied();

        } catch (\Exception $e) {
            // Log any unexpected errors
            Log::add('Unexpected error in Access Key plugin: ' . $e->getMessage(), Log::ERROR, 'accesskey');

            // In case of critical error, default to showing an error message
            $this->getApplication()->setHeader('Status', '500 Internal Server Error', true);
            echo 'An error occurred in the Access Key plugin. Please check the logs.';
            $this->getApplication()->close();
        }
    }

    /**
     * Determine whether the configured access key is present in the request.
     *
     * The configured key name acts as the trigger parameter (e.g. ?secretkey).
     * Presence is detected with a unique sentinel default so an absent key can
     * never be confused with an empty or falsy query value.
     *
     * @return  boolean  True when the key parameter is present in the request
     *
     * @since   2.1.0
     */
    private function isAccessKeyProvided(): bool
    {
        $keyName = trim((string) $this->params->get('key'));

        if ($keyName === '') {
            return false;
        }

        $sentinel = "\0__accesskey_absent__\0";

        return $this->getApplication()->getInput()->get($keyName, $sentinel, 'raw') !== $sentinel;
    }

    /**
     * Handle access denied according to plugin configuration
     *
     * @return void
     *
     * @throws AccessKeyException If access is denied
     * @since  2.0.0
     */
    private function handleAccessDenied(): void
    {
        try {
            if ($this->params->get('failAction') === 'message') {
                $message = $this->params->get('message') ?: 'Unauthorized access';
                Log::add('Access denied: ' . $message, Log::INFO, 'accesskey');

                throw AccessKeyException::unauthorized($message);
            }

            if ($this->params->get('failAction') === 'redirect') {
                $url = trim((string) $this->params->get('redirectUrl'));

                // Fall back to the site root for empty or malformed URLs.
                // Accept absolute URLs and root-relative internal paths only.
                if ($url === '' || (!filter_var($url, FILTER_VALIDATE_URL) && strpos($url, '/') !== 0)) {
                    $url = Uri::root();
                }

                Log::add('Access denied: Redirecting to ' . $url, Log::INFO, 'accesskey');
                $this->getApplication()->redirect($url);
            }
        } catch (AccessKeyException $e) {
            $app = $this->getApplication();

            // Set the appropriate HTTP status code
            $app->setHeader('Status', $e->getCode() . ' ' . $this->getHttpStatusText($e->getCode()), true);

            // Force an explicit charset so the response cannot be re-interpreted
            // through charset sniffing.
            $app->setHeader('Content-Type', 'text/html; charset=utf-8', true);
            $app->sendHeaders();

            // Output the (admin-configured) error message
            echo $e->getMessage();

            // End the application
            $app->close();
        }
    }

    /**
     * Show informational message for whitelisted IP without access key
     *
     * @return void
     *
     * @since  2.0.0
     */
    private function showWhitelistMessage(): void
    {
        $app = $this->getApplication();
        
        // Get the message from plugin parameters or use default from language file
        $defaultMessage = Text::_('PLG_SYSTEM_ACCESSKEY_WHITELIST_MESSAGE_DEFAULT');
        $message = $this->params->get('whitelist_message', $defaultMessage);
        
        // Add the message to the application message queue
        $app->enqueueMessage($message, 'info');
        
        // Log the message
        Log::add('Whitelisted IP accessed without access key: ' . $message, Log::INFO, 'accesskey');
    }

    /**
     * Get HTTP status text for a given code
     *
     * @param   int  $code  HTTP status code
     *
     * @return  string  HTTP status text
     *
     * @since   2.0.0
     */
    private function getHttpStatusText(int $code): string
    {
        $statusTexts = [
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error'
        ];

        return $statusTexts[$code] ?? 'Unknown Error';
    }
}