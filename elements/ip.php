<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.Accesskey
 *
 * @copyright   Copyright (c) 2025. Jeroen Moolenschot | Joomill
 * @license     GNU General Public License version 2 or later
 * @link        https://www.joomill-extensions.com
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

// Load helper class
require_once dirname(__DIR__) . '/helpers/IpHelper.php';

FormHelper::loadFieldClass('text');

/**
 * IP Form Field class for the Access Key plugin
 * Displays the current visitor's IP address
 *
 * @since  1.0.0
 */
class JFormFieldIP extends Joomla\CMS\Form\Field\TextField
{
    /**
     * The form field type
     *
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'ip';

    /**
     * Method to get the field input markup
     *
     * @return  string  The field input markup
     *
     * @since   1.0.0
     */
    protected function getInput(): string
    {
        try {
            // Get IP address using helper class
            $ipaddress = AccessKeyIpHelper::getVisitorIp(true);

            // Additional output sanitization for display
            $ipaddress = htmlspecialchars($ipaddress, ENT_QUOTES, 'UTF-8');

            return '<code>' . $ipaddress . '</code>';
        } catch (\Exception $e) {
            // Log the error
            Log::add('Error in IP form field: ' . $e->getMessage(), Log::ERROR, 'accesskey');

            // Return a fallback message
            return '<code>Error detecting IP</code>';
        }
    }


}
