<?php
/*
 *  package: Access Key
 *  copyright: Copyright (c) 2023. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 2 or later
 *  link: https://www.joomill-extensions.com
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

class plgSystemAccesskey extends CMSPlugin {

	protected $autoloadLanguage = true;
	protected $app;

	private $correctKey = false;

	public function onAfterInitialise(): void
	{
		
		$session = Factory::getSession();
		if ($session->get('accesskey'))
		{
			return;
		}

		if (!$this->params->get('key')) 
		{
			return;
		}

		if (!$this->app->isClient('administrator'))
		{
			return;
		}

		$visitorIP = '';
	    if (getenv('HTTP_CLIENT_IP'))
	        $visitorIP = getenv('HTTP_CLIENT_IP');
	    else if(getenv('HTTP_X_FORWARDED_FOR'))
	        $visitorIP = getenv('HTTP_X_FORWARDED_FOR');
	    else if(getenv('HTTP_X_FORWARDED'))
	        $visitorIP = getenv('HTTP_X_FORWARDED');
	    else if(getenv('HTTP_FORWARDED_FOR'))
	        $visitorIP = getenv('HTTP_FORWARDED_FOR');
	    else if(getenv('HTTP_FORWARDED'))
	       $visitorIP = getenv('HTTP_FORWARDED');
	    else if(getenv('REMOTE_ADDR'))
	        $visitorIP = getenv('REMOTE_ADDR');
		$whitelist = array_map('trim', explode(',', $this->params->get('whitelist')));
		if (in_array($visitorIP, $whitelist)) {
			$session->set('accesskey', true);
			return;
		}


		// Check if security key has been entered
		$this->correctKey = !is_null($this->app->input->get($this->params->get('key')));
		if($this->correctKey) {
			$session->set('accesskey', true);
			return;
		}

		else {
			if($this->params->get('failAction') == "message") {
				header('HTTP/1.0 401 Unauthorized');
				die($this->params->get('message'));
				return; 
			} 

			if($this->params->get('failAction') == "redirect") {
			$url = $this->params->get('redirectUrl');

			// Fallback to site
			if (!$url)
			{
				$url = URI::root();
			}

			$this->app->redirect($url);
			die;
			}
		}
	
	}
}
