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
	protected static $instance;

	/*
	 */
	public function __construct(&$subject, $config = array()) {
		parent::__construct($subject, $config);

		self::$instance = $this;
	}

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

		if ($this->_replace($path))
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

		$replaced = false;

		$body = explode('href=', $app->getBody());

		foreach($body as &$bodyPart)
		{
			if (empty($bodyPart))
			{
				continue;
			}

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

			$replaced |= $this->_replace($bodyPart, $end+1);
		}
		unset($part);

		if ($replaced)
		{
			$app->setBody(implode('href=', $body));
		}
	}

	/*
	 */
	protected function _replace(&$path, $end=null)
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

			if (strpos($replacement->search, '/*/') !== false)
			{
				$search = "\001" . str_replace('/*/', '/[^/]+/', $replacement->search) . "\001";

				if (!preg_match($search, $path))
				{
					continue;
				}

				$replace = 'preg_replace';
			}
			else
			{
				if (strpos($path, $replacement->search) === false)
				{
					continue;
				}

				$search = $replacement->search;

				$replace = 'str_replace';
			}

			if ($end === null)
			{
				$path = $replace($search, $replacement->replace, $path);

				return true;
			}

			$oldURL = substr($path, 0, $end);
			$newURL = $replace($search, $replacement->replace, $oldURL);
			$path   = $newURL . substr($path, $end);

			return true;
		}

		return false;
	}

	/*
	 */
	public static function replace($path)
	{
		self::$instance->_replace($path);
		return $path;
	}
}
