<?php
/**
 * @package      com_pfmigrator
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2013 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();


jimport('joomla.application.component.modellist');


/**
 * Main migration model
 *
 */
class PFmigratorModelMigrate extends JModelList
{
    public function getItems()
    {
        $app   = JFactory::getApplication();
        $proc  = (int) $app->getUserState('com_pfmigrator.process');
        $items = array();

        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_CREATE_TABLES');
        $item['model']  = 'CreateTables';
        $item['active'] = false;

        $items[] = $item;

        foreach ($items AS $i => $item)
        {
            if ($i == $proc) {
                $items[$i]['active'] = true;
            }
        }

        return $items;
    }
}
