<?php
/*
 *  package: Joomill Access Key plugin
 *  copyright: Copyright (c) 2025. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 3 or later
 *  link: https://www.joomill-extensions.com
 */

namespace Joomill\Plugin\System\Accesskey\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomill\Plugin\System\Accesskey\Helper\IpHelper;
use Joomill\Plugin\System\Accesskey\Exception\AccessKeyException;

/**
 * Access Key System Plugin
 *
 * @since  2.0.0
 */
class Accesskey extends CMSPlugin
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
     * @return  void
     *
     * @since   2.0.0
     */
    public function onAfterInitialise(): void
    {
        try {
            $session = Factory::getApplication()->getSession();
            if ($session->get('accesskey')) {
                return;
            }

            // Check if key is configured
            if (!$this->params->get('key')) {
                Log::add('Access Key plugin is enabled but no key is configured', Log::WARNING, 'accesskey');
                return;
            }

            if (!Factory::getApplication()->isClient('administrator')) {
                return;
            }

            try {
                // Get visitor IP using helper class
                $ipHelper = new IpHelper();
                $visitorIP = $ipHelper->getVisitorIp();
                $whitelist = array_map('trim', explode(',', $this->params->get('whitelist') ?? ''));
                
                if ($ipHelper->isIpInWhitelist($visitorIP, $whitelist)) {
                    // Check if access key is provided in URL
                    $accessKeyProvided = !is_null(Factory::getApplication()->input->get($this->params->get('key')));
                    
                    if (!$accessKeyProvided) {
                        // Show message for whitelisted IP without access key
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
            $this->correctKey = !is_null(Factory::getApplication()->input->get($this->params->get('key')));
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
            Factory::getApplication()->setHeader('Status', '500 Internal Server Error', true);
            echo 'An error occurred in the Access Key plugin. Please check the logs.';
            Factory::getApplication()->close();
        }
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
                $url = $this->params->get('redirectUrl');

                // Fallback to site root
                if (!$url) {
                    $url = Uri::root();
                }

                Log::add('Access denied: Redirecting to ' . $url, Log::INFO, 'accesskey');
                Factory::getApplication()->redirect($url);
            }
        } catch (AccessKeyException $e) {
            // Set the appropriate HTTP status code
            Factory::getApplication()->setHeader('Status', $e->getCode() . ' ' . $this->getHttpStatusText($e->getCode()), true);

            // Output the error message
            echo $e->getMessage();

            // End the application
            Factory::getApplication()->close();
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
        $app = Factory::getApplication();
        
        // Get the message from plugin parameters or use default from language file
        $defaultMessage = Factory::getLanguage()->_('PLG_SYSTEM_ACCESSKEY_WHITELIST_MESSAGE_DEFAULT');
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