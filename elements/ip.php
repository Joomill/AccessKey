<?php
/*
 *  package: Joomla Access Key plugin
 *  copyright: Copyright (c) 2025. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 2 or later
 *  link: https://www.joomill-extensions.com
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;

// Load helper class
require_once dirname(__DIR__) . '/helpers/IpHelper.php';

FormHelper::loadFieldClass('list');

class JFormFieldIP extends Joomla\CMS\Form\Field\ListField
{
    protected $type = 'ip';

    protected function getInput()
    {
        // Get IP address using helper class
        $ipaddress = AccessKeyIpHelper::getVisitorIp(true);

        return
            '<code>' . $ipaddress . '</code>';
    }


}
