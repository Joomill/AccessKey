<?php
/*
 *  package: Joomla Access Key plugin
 *  copyright: Copyright (c) 2025. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 2 or later
 *  link: https://www.joomill-extensions.com
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Custom exception class for Access Key plugin
 *
 * @since  1.0.0
 */
class AccessKeyException extends Exception
{
    /**
     * Exception for unauthorized access
     *
     * @param   string  $message  The error message
     * @param   int     $code     The error code
     *
     * @return  AccessKeyException
     *
     * @since   1.0.0
     */
    public static function unauthorized(string $message = 'Unauthorized access', int $code = 401): AccessKeyException
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
     * @since   1.0.0
     */
    public static function ipDetectionFailed(string $message = 'Failed to detect IP address', int $code = 500): AccessKeyException
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
     * @since   1.0.0
     */
    public static function configurationError(string $message = 'Configuration error', int $code = 500): AccessKeyException
    {
        return new self($message, $code);
    }
}