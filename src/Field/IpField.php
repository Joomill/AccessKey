<?php
/*
 *  package: Joomill Access Key plugin
 *  copyright: Copyright (c) 2025. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 3 or later
 *  link: https://www.joomill-extensions.com
 */

namespace Joomill\Plugin\System\Accesskey\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Log\Log;
use Joomill\Plugin\System\Accesskey\Helper\IpHelper;
use Joomill\Plugin\System\Accesskey\Exception\AccessKeyException;

/**
 * IP Form Field class for the Access Key plugin
 * Displays the current visitor's IP address
 *
 * @since  2.0.0
 */
class IpField extends TextField
{
    /**
     * The form field type
     *
     * @var    string
     * @since  2.0.0
     */
    protected $type = 'Joomill\Plugin\System\Accesskey\Field\Ip';

    /**
     * Method to get the field input markup
     *
     * @return  string  The field input markup
     *
     * @since   2.0.0
     */
    protected function getInput(): string
    {
        try {
            // Get IP address using helper class
            $ipHelper = new IpHelper();
            $ipaddress = $ipHelper->getVisitorIp(true);

            // Additional output sanitization for display
            $ipaddress = htmlspecialchars($ipaddress, ENT_QUOTES, 'UTF-8');

            return '<code class="access-key-ip">' . $ipaddress . '</code>';
        } catch (AccessKeyException $e) {
            // Log the specific error
            Log::add('Access Key IP field error: ' . $e->getMessage(), Log::ERROR, 'accesskey');

            // Return a fallback message
            return '<code class="access-key-ip error">Error detecting IP</code>';
        } catch (\Exception $e) {
            // Log any other unexpected errors
            Log::add('Unexpected error in IP form field: ' . $e->getMessage(), Log::ERROR, 'accesskey');

            // Return a fallback message
            return '<code class="access-key-ip error">Error detecting IP</code>';
        }
    }

    /**
     * Method to get the field label markup
     *
     * @return  string  The field label markup
     *
     * @since   2.0.0
     */
    protected function getLabel(): string
    {
        $label = parent::getLabel();

        // Add custom styling for the IP field label
        $label = str_replace('<label', '<label class="access-key-ip-label"', $label);

        return $label;
    }

    /**
     * Method to attach a form to the field
     *
     * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag
     * @param   mixed              $value    The form field value to validate
     * @param   string             $group    The field name group control value
     *
     * @return  boolean  True on success
     *
     * @since   2.0.0
     */
    public function setup(\SimpleXMLElement $element, $value, $group = null): bool
    {
        $result = parent::setup($element, $value, $group);

        if ($result) {
            // Set field as readonly since it's just for display
            $this->readonly = true;
        }

        return $result;
    }
}