<?php
/**
 * @package      com_pfmigrator
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2013 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();


abstract class PFmigratorHelper
{
    /**
     * The component name
     *
     * @var    string
     */
    public static $extension = 'com_pfmigrator';


    /**
     * Gets a list of actions that can be performed.
     *
     * @param     integer    $id      The item id
     * @param     integer    $list    The list id
     *
     * @return    jobject
     */
    public static function getActions($id = 0, $list = 0)
    {
        $user   = JFactory::getUser();
        $result = new JObject;

        $asset = self::$extension;

        $actions = array(
            'core.admin', 'core.manage',
            'core.create', 'core.edit',
            'core.edit.own', 'core.edit.state',
            'core.delete'
        );

        foreach ($actions as $action)
        {
            $result->set($action, $user->authorise($action, $asset));
        }

        return $result;
    }


    public static function getConfig($name, $scope = 'system')
    {
        static $cache = array();

        $cache_key = $scope . '.' . $name;

        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('content')
              ->from('#__pf_settings_tmp')
              ->where($db->quoteName('parameter') . ' = ' . $db->quote($name))
              ->where($db->quoteName('scope') . ' = ' . $db->quote($scope));

        $db->setQuery($query, 0, 1);
        $result = $db->loadResult();

        $cache[$cache_key] = $result;

        return $cache[$cache_key];
    }


    public static function getCustomData()
    {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('custom_data')
              ->from('#__extensions')
              ->where('type = ' . $db->quote('component'))
              ->where('element = ' . $db->quote('com_pfmigrator'));

        $db->setQuery($query);
        $result = $db->loadResult();

        if (empty($result)) $result = '{}';

        $data = new JRegistry();
        $data->loadString($result);

        return $data;
    }


    public static function setCustomData($data)
    {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->update('#__extensions')
              ->set('custom_data = ' . $db->quote($data->__toString()))
              ->where('type = ' . $db->quote('component'))
              ->where('element = ' . $db->quote('com_pfmigrator'));

        $db->setQuery($query);
        $db->execute();
    }
}
