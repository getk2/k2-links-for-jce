<?php
/**
 * @version     2.6.2
 * @package     K2 Links for JCE
 * @author      JoomlaWorks https://www.joomlaworks.net
 * @copyright   Copyright (c) 2006 - 2019 JoomlaWorks Ltd. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/licenses/gpl.html
 */

defined('_WF_EXT') or die('RESTRICTED');

class WFLinkBrowser_K2 extends JObject
{
    public $_option = array();
    public $_adapters = array();

    public function __construct($options = array())
    {
        jimport('joomla.filesystem.folder');
        jimport('joomla.filesystem.file');

        $path = dirname(__FILE__).'/k2links';

        // Get all files
        $files = JFolder::files($path, '\.(php)$');

        if (!empty($files)) {
            foreach ($files as $file) {
                require_once($path.'/'.$file);
                $classname = 'K2links'.ucfirst(basename($file, '.php'));
                $this->_adapters[] = new $classname;
            }
        }
    }

    public function getInstance()
    {
        static $instance;

        if (!is_object($instance)) {
            $instance = new WFLinkBrowser_K2();
        }
        return $instance;
    }

    public function display()
    {
    }

    public function isEnabled()
    {
        $wf = WFEditorPlugin::getInstance();
        return $wf->checkAccess($wf->getName().'.links.k2links', 1);
    }

    public function getOption()
    {
        foreach ($this->_adapters as $adapter) {
            $this->_option[] = $adapter->getOption();
        }
        return $this->_option;
    }

    public function getList()
    {
        $list = '';

        foreach ($this->_adapters as $adapter) {
            $list .= $adapter->getList();
        }
        return $list;
    }

    public function getLinks($args)
    {
        foreach ($this->_adapters as $adapter) {
            if ($adapter->getOption() == $args->option) {
                if (property_exists($args, 'task')) {
                    $task = $args->task;
                } else {
                    $task = 'category';
                }
                if ($adapter->getTask() == $task) {
                    return $adapter->getLinks($args);
                }
            }
        }
    }
}
