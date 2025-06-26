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

FormHelper::loadFieldClass('list');

class JFormFieldIP extends Joomla\CMS\Form\Field\ListField
{
    protected $type = 'ip';

    protected function getInput()
    {
        // Get IP address using Joomla's API
        $app = Factory::getApplication();
        $ipaddress = $app->input->server->get('HTTP_CLIENT_IP', '');
        if (empty($ipaddress)) {
            $ipaddress = $app->input->server->get('HTTP_X_FORWARDED_FOR', '');
        }
        if (empty($ipaddress)) {
            $ipaddress = $app->input->server->get('HTTP_X_FORWARDED', '');
        }
        if (empty($ipaddress)) {
            $ipaddress = $app->input->server->get('HTTP_FORWARDED_FOR', '');
        }
        if (empty($ipaddress)) {
            $ipaddress = $app->input->server->get('HTTP_FORWARDED', '');
        }
        if (empty($ipaddress)) {
            $ipaddress = $app->input->server->get('REMOTE_ADDR', '');
        }
        if (empty($ipaddress)) {
            $ipaddress = 'UNKNOWN';
        }

        return
            '<code>' . $ipaddress . '</code>';
    }


}
