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

// Load helper class
require_once __DIR__ . '/helpers/IpHelper.php';

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

        $session = $this->app->getSession();
        if ($session->get('accesskey')) {
            return;
        }

        if (!$this->params->get('key')) {
            return;
        }

        if (!$this->app->isClient('administrator')) {
            return;
        }

        // Get visitor IP using helper class
        $visitorIP = AccessKeyIpHelper::getVisitorIp();
        $whitelist = array_map('trim', explode(',', $this->params->get('whitelist') ?? ''));
        if (in_array($visitorIP, $whitelist)) {
            $session->set('accesskey', true);
            return;
        }

        // Check if security key has been entered
        $this->correctKey = !is_null($this->app->input->get($this->params->get('key')));
        if ($this->correctKey) {
            $session->set('accesskey', true);
            return;
        } else {
            if ($this->params->get('failAction') == "message") {
                header('HTTP/1.0 401 Unauthorized');
                die($this->params->get('message'));
            }

            if ($this->params->get('failAction') == "redirect") {
                $url = $this->params->get('redirectUrl');

                // Fallback to site
                if (!$url) {
                    $url = URI::root();
                }

                $this->app->redirect($url);
                die;
            }
        }

    }
}
