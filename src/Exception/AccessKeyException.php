<?php
/*
 *  package: Joomill Access Key plugin
 *  copyright: Copyright (c) 2025. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 3 or later
 *  link: https://www.joomill-extensions.com
 */

namespace Joomill\Plugin\System\Accesskey\Exception;

defined('_JEXEC') or die;

/**
 * Custom exception class for Access Key plugin
 *
 * @since  2.0.0
 */
class AccessKeyException extends \Exception
{
    /**
     * Exception for unauthorized access
     *
     * @param   string  $message  The error message
     * @param   int     $code     The error code
     *
     * @return  AccessKeyException
     *
     * @since   2.0.0
     */
    public static function unauthorized(string $message = 'Unauthorized access', int $code = 401): self
    {
        return new self($message, $code);
    }

    /**
     * Exception for IP detection failure
     *
     * @param   string  $message  The error message
     * @param   int     $code     The error code
     *
     * @return  AccessKeyException
     *
     * @since   2.0.0
     */
    public static function ipDetectionFailed(string $message = 'Failed to detect IP address', int $code = 500): self
    {
        return new self($message, $code);
    }

    /**
     * Exception for configuration errors
     *
     * @param   string  $message  The error message
     * @param   int     $code     The error code
     *
     * @return  AccessKeyException
     *
     * @since   2.0.0
     */
    public static function configurationError(string $message = 'Configuration error', int $code = 500): self
    {
        return new self($message, $code);
    }

    /**
     * Exception for invalid IP addresses
     *
     * @param   string  $message  The error message
     * @param   int     $code     The error code
     *
     * @return  AccessKeyException
     *
     * @since   2.0.0
     */
    public static function invalidIp(string $message = 'Invalid IP address', int $code = 400): self
    {
        return new self($message, $code);
    }

    /**
     * Exception for access denied scenarios
     *
     * @param   string  $message  The error message
     * @param   int     $code     The error code
     *
     * @return  AccessKeyException
     *
     * @since   2.0.0
     */
    public static function accessDenied(string $message = 'Access denied', int $code = 403): self
    {
        return new self($message, $code);
    }
}