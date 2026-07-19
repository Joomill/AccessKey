<?php

/**
 * Access Key
 *
 * @copyright   Copyright (c) 2026 Jeroen Moolenschot | Joomill
 * @license     GNU General Public License version 3 or later; see LICENSE
 * @link        https://www.joomill-extensions.com
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomill\Plugin\System\Accesskey\Extension\Accesskey;

/**
 * Service provider for the Access Key plugin
 *
 * @since  2.0.0
 */
return new class implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function register(Container $container): void
    {
        $factory = function (Container $container) {
            $subject = $container->get(DispatcherInterface::class);
            $plugin  = new Accesskey(
                $subject,
                (array) PluginHelper::getPlugin('system', 'accesskey')
            );
            $plugin->setApplication(Factory::getApplication());

            return $plugin;
        };

        // Container::lazy() exists since Joomla 6.1 (joomla/di 3.1.0) and creates the
        // plugin on demand when the event is dispatched (PHP >= 8.4 lazy proxy; on
        // older PHP it returns the plain factory). Joomla 5.x/6.0 lack the method.
        $container->set(
            PluginInterface::class,
            method_exists($container, 'lazy') ? $container->lazy(Accesskey::class, $factory) : $factory
        );
    }
};
