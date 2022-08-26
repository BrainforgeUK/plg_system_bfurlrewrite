<?php
/**
 * @package   System plugin for for URL rewriting
 * @version   0.0.1
 * @author    https://www.brainforge.co.uk
 * @copyright Copyright (C) 2022 Jonathan Brain. All rights reserved.
 * @license   GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;

class plgSystemBfurlrewrite extends CMSPlugin {
	protected $app;

	/**
	 */
	public function onAfterInitialise()
	{
		if (!$this->app->isClient('site'))
		{
			return;
		}

		$uri = Uri::getInstance();
		$path = $uri->getPath();

		if ($this->replace($path))
		{
			$this->app->redirect($path);
		}
	}

	/*
	 */
	public function onAfterRender()
	{
		$app = Factory::getApplication();

		if (!$app->isClient('site'))
		{
			return;
		}

		$body = explode('href=', $app->getBody());

		foreach($body as &$bodyPart)
		{
			switch($bodyPart[0])
			{
				case '"':
				case "'":
					break;
				default:
					continue 2;
			}

			$end = strpos($bodyPart, $bodyPart[0], 1);
			if ($end === false)
			{
				continue;
			}

			$this->replace($bodyPart, $end+1);
		}
		unset($part);

		$app->setBody(implode('href=', $body));
	}

	/*
	 */
	protected function replace(&$path, $end=null)
	{
		$replacements = $this->params->get('replacements');
		if (empty($replacements))
		{
			return false;
		}

		foreach((array) $replacements as $replacement)
		{
			if (empty($replacement->state))
			{
				continue;
			}

			if (strpos($path, $replacement->search) !== false)
			{
				if (strpos($replacement->replace, $replacement->search) !== false)
				{
					// Guard against recursive loops
					continue;
				}

				if ($end === null)
				{
					$path = str_replace($replacement->search, $replacement->replace, $path);
					return true;
				}

				$oldURL = substr($path, 0, $end);
				$newURL = str_replace($replacement->search, $replacement->replace, $oldURL);
				$path = $newURL . substr($path, $end);
				return true;
			}
		}

		return false;
	}
}
