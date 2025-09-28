<?php
/*
 *  package: Joomill Access Key plugin
 *  copyright: Copyright (c) 2025. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 3 or later
 *  link: https://www.joomill-extensions.com
 */

namespace Joomill\Plugin\System\Accesskey\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomill\Plugin\System\Accesskey\Exception\AccessKeyException;

/**
 * Helper class for IP address detection and validation
 *
 * @since  2.0.0
 */
class IpHelper
{


    /**
     * Check if an IP address is within a CIDR range
     *
     * @param   string  $ip     The IP address to check
     * @param   string  $cidr   The CIDR range (e.g., 192.168.1.0/24)
     *
     * @return  bool    True if the IP is in the CIDR range, false otherwise
     *
     * @since   2.0.0
     */
    public function isIpInCidrRange(string $ip, string $cidr): bool
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
     * @since   2.0.0
     */
    public function isIpInWhitelist(string $ip, array $whitelist): bool
    {
        if (empty($ip) || empty($whitelist)) {
            return false;
        }

        foreach ($whitelist as $entry) {
            $entry = trim($entry);
            if (empty($entry)) {
                continue;
            }

            // Check for exact IP match
            if ($ip === $entry) {
                return true;
            }

            // Check for CIDR match
            if (strpos($entry, '/') !== false) {
                if ($this->isIpInCidrRange($ip, $entry)) {
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
     * @since   2.0.0
     */
    public function sanitizeIp(string $ip): string
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
     * Get the visitor's IP address from various server variables
     *
     * @param   bool  $fallbackToUnknown  Whether to return 'Unknown' if IP cannot be detected
     *
     * @return  string  The visitor's IP address
     *
     * @throws  AccessKeyException  If IP detection fails and no fallback is requested
     * @since   2.0.0
     */
    public function getVisitorIp(bool $fallbackToUnknown = false): string
    {
        $ipSources = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipSources as $source) {
            $ip = Factory::getApplication()->input->server->getString($source, '');
            if (!empty($ip)) {
                $sanitizedIp = $this->sanitizeIp($ip);
                if (!empty($sanitizedIp)) {
                    Log::add('Visitor IP detected from ' . $source . ': ' . $sanitizedIp, Log::DEBUG, 'accesskey');
                    return $sanitizedIp;
                }
            }
        }

        // If we reach here, IP detection failed
        if ($fallbackToUnknown) {
            Log::add('Failed to detect visitor IP address, using fallback', Log::WARNING, 'accesskey');
            return 'Unknown';
        }

        throw AccessKeyException::ipDetectionFailed('Unable to detect visitor IP address from any source');
    }
}