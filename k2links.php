<?php
/**
 * @version     2.0.1
 * @package     K2 Links for JCE
 * @author      JoomlaWorks http://www.joomlaworks.net
 * @copyright   Copyright (c) 2006 - 2014 JoomlaWorks Ltd. All rights reserved.
 * @license     GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

defined('_WF_EXT') or die('ERROR_403');

class WFLinkBrowser_K2links extends JObject
{

	var $_option = array();
	var $_adapters = array();

	/**
	 * Constructor activating the default information of the class
	 *
	 * @access	protected
	 */
	function __construct($options = array())
	{
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');

		$path = dirname(__FILE__).DS.'k2links';

		// Get all files
		$files = JFolder::files($path, '\.(php)$');

		if (!empty($files))
		{
			foreach ($files as $file)
			{
				require_once ($path.DS.$file);
				$classname = 'K2links'.ucfirst(basename($file, '.php'));
				$this->_adapters[] = new $classname;
			}
		}
	}

	/**
	 * Returns a reference to a editor object
	 *
	 * This method must be invoked as:
	 * 		<pre>  $browser =JContentEditor::getInstance();</pre>
	 *
	 * @access	public
	 * @return	JCE  The editor object.
	 * @since	1.5
	 */
	function getInstance()
	{
		static $instance;

		if (!is_object($instance))
		{
			$instance = new WFLinkBrowser_K2links();
		}
		return $instance;
	}

	function display()
	{

	}

	function isEnabled()
	{
		$wf = WFEditorPlugin::getInstance();
		return $wf->checkAccess($wf->getName().'.links.k2links', 1);
	}

	function getOption()
	{
		foreach ($this->_adapters as $adapter)
		{
			$this->_option[] = $adapter->getOption();
		}
		return $this->_option;
	}

	function getList()
	{
		$list = '';

		foreach ($this->_adapters as $adapter)
		{
			$list .= $adapter->getList();
		}
		return $list;
	}

	function getLinks($args)
	{
		foreach ($this->_adapters as $adapter)
		{
			if ($adapter->getOption() == $args->option)
			{
				if (property_exists($args, 'task'))
				{
					$task = $args->task;
				}
				else
				{
					$task = 'category';
				}
				if ($adapter->getTask() == $task)
				{
					return $adapter->getLinks($args);
				}
			}
		}
	}

}
