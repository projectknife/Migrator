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
 * Unregister PF migration model
 *
 */
class PFmigratorModelUnregisterPF extends JModelList
{
    protected $log     = array();
    protected $success = true;

    public function process($limitstart = 0)
    {
        $query = $this->_db->getQuery(true);

        // Get the extension record
        $query->select('*')
              ->from('#__extensions')
              ->where('type = ' . $this->_db->quote('component'))
              ->where('element = ' . $this->_db->quote('com_projectfork'));

        $this->_db->setQuery($query);
        $row = $this->_db->loadObject();

        // Delete from extensions table
        $query->clear();
        $query->delete('#__extensions')
              ->where('type = ' . $this->_db->quote('component'))
              ->where('element = ' . $this->_db->quote('com_projectfork'));

        $this->_db->setQuery($query);

        try {
            $this->_db->execute();
        }
        catch (Exception $e) {
            $this->success = false;
            $this->log[] = $e->getMessage();

            return false;
        }

        // Delete from assets table
        $asset = JTable::getInstance('Asset');

        if ($asset->loadByName('com_projectfork')) {
            if (!$asset->delete()) {
                $this->success = false;
                $this->log[] = $asset->getError();

                return false;
            }
        }

        // Remove admin menu entries
        if (!empty($row)) {
            $this->removeAdminMenus($row->extension_id);
        }

        $this->log[] = JText::_('COM_PFMIGRATOR_UNREG_PF_SUCCESS');

        return true;
    }


    public function getTotal()
    {
        return 1;
    }


    public function getLimit()
    {
        return 1;
    }


    public function getLog()
    {
        return $this->log;
    }


    public function getSuccess()
    {
        return $this->success;
    }


    protected function removeAdminMenus($id)
    {
        $query = $this->_db->getQuery(true);
        $table = JTable::getInstance('menu');

        // Get the ids of the menu items
        $query->select('id')
              ->from('#__menu')
              ->where($query->qn('client_id') . ' = 1')
              ->where($query->qn('component_id') . ' = ' . (int) $id);

        $this->_db->setQuery($query);
        $ids = $this->_db->loadColumn();

        if (empty($ids)) return true;

        // Iterate the items to delete each one.
        foreach ($ids as $menuid)
        {
            $table->delete((int) $menuid);
        }

        // Rebuild the whole tree
        $table->rebuild();

        return true;
    }
}
