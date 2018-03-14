<?php
/**
 * @version     2.6.2
 * @package     K2 Links for JCE
 * @author      JoomlaWorks http://www.joomlaworks.net
 * @copyright   Copyright (c) 2006 - 2018 JoomlaWorks Ltd. All rights reserved.
 * @license     GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

defined('_WF_EXT') or die('ERROR_403');

/**
 * This class fetches K2 tags and related items
 */
class K2linksTags extends JObject
{
    public $_option = 'com_k2';
    public $_task = 'tag';

    public function __construct($options = array())
    {
    }

    public function getInstance()
    {
        static $instance;

        if (!is_object($instance)) {
            $instance = new K2linksTags();
        }
        return $instance;
    }

    public function getOption()
    {
        return $this->_option;
    }

    public function getTask()
    {
        return $this->_task;
    }

    public function getList()
    {
        $advlink = WFEditorPlugin::getInstance();
        $list = '';
        if ($advlink->checkAccess('k2links.tags', '1')) {
            $list = '<li id="index.php?option=com_k2&task=tag"><div class="uk-tree-row"><a href="#"><span class="uk-tree-icon folder content nolink"></span><span class="uk-tree-text">'.JText::_('PLG_JCE_LINKS_K2_TAGS').'</span></a></div></li>';
        }
        return $list;
    }

    public static function _getK2Tags()
    {
        if (defined('K2_JVERSION')) {
            $db = JFactory::getDBO();
            $user = JFactory::getUser();
            $query = 'SELECT id, name FROM #__k2_tags WHERE published = 1 ORDER BY name ASC';
            $db->setQuery($query);
            $rows = $db->loadObjectList();
        } else {
            $model = K2Model::getInstance('Tags');
            $model->setState('state', 1);
            $model->setState('sorting', 'name');
            $rows = $model->getRows();
        }
        return $rows;
    }

    public static function _getK2Items($tag = '')
    {
        if (defined('K2_JVERSION')) {
            $db = JFactory::getDBO();
            $user = JFactory::getUser();
            $query = "SELECT `i`.`id`, `i`.`title`, `i`.`alias`, `i`.`catid`, `c`.`alias` AS categoryAlias
		        FROM `#__k2_tags_xref` as `x`
		        INNER JOIN `#__k2_items` as `i` ON(`i`.`id` = `x`.`itemID`)
		        INNER JOIN `#__k2_tags` as `t` ON (`t`.`id` = `x`.`tagID`)
		        INNER JOIN `#__k2_categories` as `c` ON(`i`.`catid` = `c`.`id`)
		        WHERE `t`.`name` = ".$db->quote($tag);
            $query .= ' AND `i`.`published` = 1 AND `c`.`published` = 1 ';
            if (version_compare(JVERSION, '1.6.0', 'ge')) {
                $query .= ' AND `i`.`access` IN ('.implode(',', $user->getAuthorisedViewLevels()).')';
                $query .= ' AND `c`.`access` IN ('.implode(',', $user->getAuthorisedViewLevels()).')';
            } else {
                $query .= "\nAND `i`.`access` <=".(int)$user->get('aid');
                $query .= "\nAND `c`.`access` <=".(int)$user->get('aid');
            }
            $query .= "\nORDER BY `i`.`title`, `i`.`created` ASC";
            $db->setQuery($query);
            $rows = $db->loadObjectList();
        } else {
            $model = K2Model::getInstance('Items');
            $model->setState('site', true);
            $model->setState('tag', (int)$tag);
            $model->setState('sorting', 'title');
            $rows = $model->getRows();
        }
        return $rows;
    }

    public function getLinks($args)
    {
        $mainframe = JFactory::getApplication();

        $advlink = WFEditorPlugin::getInstance();

        if (defined('K2_JVERSION')) {
            require_once JPATH_SITE.'/components/com_k2/helpers/route.php';
        }
        $items = array();
        $view = isset($args->view) ? $args->view : '';

        switch ($view) {

            default:
                $tags = self::_getK2Tags();
                foreach ($tags as $tag) {
                    if (defined('K2_JVERSION')) {
                        $tag->href = K2HelperRoute::getTagRoute($tag->name);
                    } else {
                        $tag->href = K2HelperRoute::getTagRoute($tag->id.':'.$tag->alias);
                    }
                    $items[] = array('id' => $tag->href, 'name' => $tag->name, 'class' => 'folder content');
                }
                break;

            case 'itemlist':
                $itemlist = self::_getK2Items($args->tag);
                foreach ($itemlist as $item) {
                    $item->href = K2HelperRoute::getItemRoute($item->id.':'.$item->alias, $item->catid);
                    $items[] = array('id' => $item->href, 'name' => $item->title, 'class' => 'file');
                }
                break;

            case 'item':
                break;
        }
        return $items;
    }
}
