<?php
/*
 *  package: Access Key
 *  copyright: Copyright (c) 2023. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 2 or later
 *  link: https://www.joomill-extensions.com
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormHelper;

FormHelper::loadFieldClass('list');

class JFormFieldIP extends Joomla\CMS\Form\Field\ListField
{
	protected $type = 'ip';

	protected function getInput()
	{
		$ipaddress = '';
	    if (getenv('HTTP_CLIENT_IP'))
	        $ipaddress = getenv('HTTP_CLIENT_IP');
	    else if(getenv('HTTP_X_FORWARDED_FOR'))
	        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
	    else if(getenv('HTTP_X_FORWARDED'))
	        $ipaddress = getenv('HTTP_X_FORWARDED');
	    else if(getenv('HTTP_FORWARDED_FOR'))
	        $ipaddress = getenv('HTTP_FORWARDED_FOR');
	    else if(getenv('HTTP_FORWARDED'))
	       $ipaddress = getenv('HTTP_FORWARDED');
	    else if(getenv('REMOTE_ADDR'))
	        $ipaddress = getenv('REMOTE_ADDR');
	    else
	        $ipaddress = 'UNKNOWN';

		return
			'<code>' . $ipaddress . '</code>';
	}


}
