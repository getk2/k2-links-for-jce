<?php
/**
 * @version     2.6.2
 * @package     K2 Links for JCE
 * @author      JoomlaWorks https://www.joomlaworks.net
 * @copyright   Copyright (c) 2006 - 2019 JoomlaWorks Ltd. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/licenses/gpl.html
 */

defined('_WF_EXT') or die('ERROR_403');

/**
 * This class fetches K2 Users
 */
class K2linksUsers extends JObject
{
    public $_option = 'com_k2';
    public $_task = 'user';

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
        if ($advlink->checkAccess('k2links.users', '1')) {
            $list = '<li id="index.php?option=com_k2&task=user"><div class="uk-tree-row"><a href="#"><span class="uk-tree-icon folder content nolink"></span><span class="uk-tree-text">'.JText::_('PLG_JCE_LINKS_K2_USERS').'</span></a></div></li>';
        }
        return $list;
    }

    public static function _getK2Users()
    {
        if (defined('K2_JVERSION')) {
            $db = JFactory::getDBO();
            $query = "SELECT juser.id, juser.name FROM #__users as juser
        RIGHT JOIN #__k2_users as k2user ON juser.id = k2user.userID";
            $db->setQuery($query);
            $rows = $db->loadObjectList();
        } else {
            $model = K2Model::getInstance('Users');
            $rows = $model->getRows();
        }
        return $rows;
    }

    public static function _getK2Items($userID = '')
    {
        if (defined('K2_JVERSION')) {
            $user = JFactory::getUser();
            $db = JFactory::getDBO();
            $query = "SELECT item.id, item.title, item.alias, item.catid, category.alias AS categoryAlias
                FROM #__k2_items AS item
                LEFT JOIN #__k2_categories AS category ON item.catid = category.id
                WHERE item.created_by = ".(int)$userID."
                    AND item.created_by_alias = ''
                    AND item.published = 1
                    AND category.published = 1";
            if (version_compare(JVERSION, '1.6.0', 'ge')) {
                $query .= ' AND item.access IN ('.implode(',', $user->getAuthorisedViewLevels()).')';
                $query .= ' AND category.access IN ('.implode(',', $user->getAuthorisedViewLevels()).')';
            } else {
                $query .= " AND item.access <=".(int)$user->get('aid');
                $query .= " AND category.access <=".(int)$user->get('aid');
            }
            $query .= " ORDER BY title, created ASC";
            $db->setQuery($query);
            $rows = $db->loadObjectList();
        } else {
            $model = K2Model::getInstance('Items');
            $model->setState('site', true);
            $model->setState('author', (int)$userID);
            $model->setState('sorting', 'title');
            $rows = $model->getRows();
        }
        return $rows;
    }

    public function getLinks($args)
    {
        $app = JFactory::getApplication();

        $advlink = WFEditorPlugin::getInstance();

        if (defined('K2_JVERSION')) {
            require_once JPATH_SITE.'/components/com_k2/helpers/utilities.php';
            require_once JPATH_SITE.'/components/com_k2/helpers/route.php';
        }

        $items = array();
        $view = isset($args->view) ? $args->view : '';

        switch ($view) {

            default:
                $users = self::_getK2Users();
                foreach ($users as $user) {
                    if ($user->id) {
                        if (defined('K2_JVERSION')) {
                            $user->href = K2HelperRoute::getUserRoute($user->id);
                        } else {
                            if (JFactory::getConfig()->get('unicodeslugs') == 1) {
                                $alias = JFilterOutput::stringURLUnicodeSlug($user->name);
                            } else {
                                $alias = JFilterOutput::stringURLSafe($user->name);
                            }
                            $user->href = K2HelperRoute::getUserRoute($user->id.':'.$alias);
                        }
                        $items[] = array('id' => $user->href, 'name' => $user->name, 'class' => 'folder content');
                    }
                }
                break;

            case 'itemlist':
                $itemlist = self::_getK2Items($args->id);
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
