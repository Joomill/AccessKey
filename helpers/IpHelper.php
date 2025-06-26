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
use Joomla\CMS\Log\Log;

// Load custom exception class
require_once __DIR__ . '/AccessKeyException.php';

/**
 * Helper class for IP address detection
 *
 * @since  1.0.0
 */
class AccessKeyIpHelper
{
    /**
     * Check if an IP address is within a CIDR range
     *
     * @param   string  $ip     The IP address to check
     * @param   string  $cidr   The CIDR range (e.g., 192.168.1.0/24)
     *
     * @return  bool    True if the IP is in the CIDR range, false otherwise
     *
     * @since   1.0.0
     */
    public static function isIpInCidrRange(string $ip, string $cidr): bool
    {
        // Validate inputs
        if (empty($ip) || empty($cidr)) {
            return false;
        }

        // Check if the CIDR notation is valid
        if (strpos($cidr, '/') === false) {
            return false;
        }

        list($subnet, $bits) = explode('/', $cidr);

        // Validate subnet and bits
        if (!filter_var($subnet, FILTER_VALIDATE_IP) || !is_numeric($bits)) {
            return false;
        }

        $bits = (int)$bits;

        // Convert IP addresses to binary strings
        $ipBinary = @inet_pton($ip);
        $subnetBinary = @inet_pton($subnet);

        if ($ipBinary === false || $subnetBinary === false) {
            return false;
        }

        // Check if IP version matches subnet version
        if (strlen($ipBinary) !== strlen($subnetBinary)) {
            return false;
        }

        // Calculate the number of bytes and remaining bits
        $bytes = $bits / 8;
        $remainingBits = $bits % 8;

        // Compare full bytes
        if ($bytes > 0 && strncmp($ipBinary, $subnetBinary, (int)$bytes) !== 0) {
            return false;
        }

        // If there are remaining bits, compare them
        if ($remainingBits > 0 && $bytes < strlen($ipBinary)) {
            $mask = 0xff << (8 - $remainingBits);
            if ((ord($ipBinary[$bytes]) & $mask) !== (ord($subnetBinary[$bytes]) & $mask)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if an IP address is in a whitelist (supports exact IPs and CIDR notation)
     *
     * @param   string  $ip         The IP address to check
     * @param   array   $whitelist  Array of IPs and/or CIDR ranges
     *
     * @return  bool    True if the IP is in the whitelist, false otherwise
     *
     * @since   1.0.0
     */
    public static function isIpInWhitelist(string $ip, array $whitelist): bool
    {
        if (empty($ip) || empty($whitelist)) {
            return false;
        }

        foreach ($whitelist as $entry) {
            // Check for exact IP match
            if ($ip === $entry) {
                return true;
            }

            // Check for CIDR match
            if (strpos($entry, '/') !== false) {
                if (self::isIpInCidrRange($ip, $entry)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Sanitize an IP address to prevent header injection attacks
     *
     * @param   string  $ip  The IP address to sanitize
     *
     * @return  string  The sanitized IP address or empty string if invalid
     *
     * @since   1.0.0
     */
    public static function sanitizeIp(string $ip): string
    {
        // Remove any whitespace
        $ip = trim($ip);

        // Handle comma-separated IPs (common in X-Forwarded-For)
        if (strpos($ip, ',') !== false) {
            // Take only the first IP in the list
            $ip = trim(explode(',', $ip)[0]);
        }

        // Validate IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ip;
        }

        // Validate IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $ip;
        }

        // IP is invalid
        Log::add('Invalid IP address detected: ' . $ip, Log::WARNING, 'accesskey');
        return '';
    }

    /**
     * Get the visitor's IP address
     *
     * @param   bool  $fallbackToUnknown  Whether to fallback to 'UNKNOWN' if no IP is found
     *
     * @return  string  The visitor's IP address
     *
     * @since   1.0.0
     */
    public static function getVisitorIp(bool $fallbackToUnknown = false): string
    {
        try {
            $app = Factory::getApplication();
            $ipaddress = self::sanitizeIp($app->input->server->get('HTTP_CLIENT_IP', ''));

            if (empty($ipaddress)) {
                $ipaddress = self::sanitizeIp($app->input->server->get('HTTP_X_FORWARDED_FOR', ''));
            }

            if (empty($ipaddress)) {
                $ipaddress = self::sanitizeIp($app->input->server->get('HTTP_X_FORWARDED', ''));
            }

            if (empty($ipaddress)) {
                $ipaddress = self::sanitizeIp($app->input->server->get('HTTP_FORWARDED_FOR', ''));
            }

            if (empty($ipaddress)) {
                $ipaddress = self::sanitizeIp($app->input->server->get('HTTP_FORWARDED', ''));
            }

            if (empty($ipaddress)) {
                $ipaddress = self::sanitizeIp($app->input->server->get('REMOTE_ADDR', ''));
            }

            if (empty($ipaddress)) {
                if ($fallbackToUnknown) {
                    $ipaddress = 'UNKNOWN';
                    Log::add('Failed to detect visitor IP address, using fallback value', Log::WARNING, 'accesskey');
                } else {
                    throw AccessKeyException::ipDetectionFailed();
                }
            }

            return $ipaddress;
        } catch (\Exception $e) {
            // Log the error
            Log::add('Error detecting visitor IP: ' . $e->getMessage(), Log::ERROR, 'accesskey');

            // If fallback is allowed, return UNKNOWN, otherwise rethrow
            if ($fallbackToUnknown) {
                return 'UNKNOWN';
            }

            throw AccessKeyException::ipDetectionFailed($e->getMessage(), $e->getCode());
        }
    }
}
