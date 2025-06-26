<?php
/*
 *  package: Joomla Access Key plugin
 *  copyright: Copyright (c) 2025. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 2 or later
 *  link: https://www.joomill-extensions.com
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

/**
 * Helper class for IP address detection
 *
 * @since  1.0.0
 */
class AccessKeyIpHelper
{
    /**
     * Get the visitor's IP address
     *
     * @param   bool  $fallbackToUnknown  Whether to fallback to 'UNKNOWN' if no IP is found
     *
     * @return  string  The visitor's IP address
     *
     * @since   1.0.0
     */
    public static function getVisitorIp($fallbackToUnknown = false)
    {
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
        
        if (empty($ipaddress) && $fallbackToUnknown) {
            $ipaddress = 'UNKNOWN';
        }
        
        return $ipaddress;
    }
}