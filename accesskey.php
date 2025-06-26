<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.Accesskey
 *
 * @copyright   Copyright (c) 2025. Jeroen Moolenschot | Joomill
 * @license     GNU General Public License version 2 or later
 * @link        https://www.joomill-extensions.com
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Log\Log;

// Load helper classes
require_once __DIR__ . '/helpers/IpHelper.php';
require_once __DIR__ . '/helpers/AccessKeyException.php';

/**
 * Access Key System Plugin
 *
 * @since  1.0.0
 */
class plgSystemAccesskey extends CMSPlugin
{
    /**
     * Load the language file on instantiation
     *
     * @var    boolean
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Application object
     *
     * @var    \Joomla\CMS\Application\CMSApplication
     * @since  1.0.0
     */
    protected $app;

    /**
     * Flag to indicate if the correct key was provided
     *
     * @var    boolean
     * @since  1.0.0
     */
    private $correctKey = false;

    /**
     * Runs after Joomla has been initialized
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onAfterInitialise(): void
    {
        try {
            $session = $this->app->getSession();
            if ($session->get('accesskey')) {
                return;
            }

            // Check if key is configured
            if (!$this->params->get('key')) {
                Log::add('Access Key plugin is enabled but no key is configured', Log::WARNING, 'accesskey');
                return;
            }

            if (!$this->app->isClient('administrator')) {
                return;
            }

            try {
                // Get visitor IP using helper class
                $visitorIP = AccessKeyIpHelper::getVisitorIp();
                $whitelist = array_map('trim', explode(',', $this->params->get('whitelist') ?? ''));
                if (AccessKeyIpHelper::isIpInWhitelist($visitorIP, $whitelist)) {
                    $session->set('accesskey', true);
                    return;
                }
            } catch (AccessKeyException $e) {
                // Log IP detection error but continue with key check
                Log::add('IP detection failed: ' . $e->getMessage(), Log::WARNING, 'accesskey');
                // IP check failed, but we'll still check for the key
            }

            // Check if security key has been entered
            $this->correctKey = !is_null($this->app->input->get($this->params->get('key')));
            if ($this->correctKey) {
                $session->set('accesskey', true);
                return;
            } else {
                // Access denied - handle according to configuration
                $this->handleAccessDenied();
            }
        } catch (\Exception $e) {
            // Log any unexpected errors
            Log::add('Unexpected error in Access Key plugin: ' . $e->getMessage(), Log::ERROR, 'accesskey');

            // In case of critical error, default to showing an error message
            header('HTTP/1.0 500 Internal Server Error');
            echo 'An error occurred in the Access Key plugin. Please check the logs.';
            $this->app->close();
        }
    }

    /**
     * Handle access denied according to plugin configuration
     *
     * @return void
     *
     * @throws AccessKeyException If access is denied
     * @since  1.0.0
     */
    private function handleAccessDenied(): void
    {
        try {
            if ($this->params->get('failAction') == "message") {
                $message = $this->params->get('message') ?: 'Unauthorized access';
                Log::add('Access denied: ' . $message, Log::INFO, 'accesskey');

                throw AccessKeyException::unauthorized($message);
            }

            if ($this->params->get('failAction') == "redirect") {
                $url = $this->params->get('redirectUrl');

                // Fallback to site
                if (!$url) {
                    $url = URI::root();
                }

                Log::add('Access denied: Redirecting to ' . $url, Log::INFO, 'accesskey');
                $this->app->redirect($url);
                $this->app->close();
            }
        } catch (AccessKeyException $e) {
            // Set the appropriate HTTP status code
            header('HTTP/1.0 ' . $e->getCode() . ' ' . $this->getHttpStatusText($e->getCode()));

            // Output the error message
            echo $e->getMessage();

            // End the application
            $this->app->close();
        }
    }

    /**
     * Get HTTP status text for a given code
     *
     * @param   int  $code  HTTP status code
     *
     * @return  string  HTTP status text
     *
     * @since   1.0.0
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
