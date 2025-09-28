<?php
/*
 *  package: Joomill Access Key plugin
 *  copyright: Copyright (c) 2025. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 3 or later
 *  link: https://www.joomill-extensions.com
 */

defined('_JEXEC') or die;

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
		$container->set(
			PluginInterface::class,
			function (Container $container) {
				$subject = $container->get(DispatcherInterface::class);
				$plugin  = new Accesskey(
					$subject,
					(array) PluginHelper::getPlugin('system', 'accesskey')
				);
				$plugin->setApplication(Factory::getApplication());

				return $plugin;
			}
		);
    }
};