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
 * Prepare FM pro model
 *
 */
class PFmigratorModelPrepFMPro extends JModelList
{
    protected $log     = array();
    protected $success = true;

    public function process($limitstart = 0)
    {
        if (!PFMigratorHelper::fmProInstalled()) {
            $this->log[] = JText::_('COM_PFMIGRATOR_FMPRO_NOT_INSTALLED');
            return true;
        }

        $tables    = $this->_db->getTableList();
        $rn_tables = $this->getTables();

        $target_table = (isset($rn_tables[$limitstart]) ? $rn_tables[$limitstart] : null);

        if (!$target_table) return true;

        // Check if the table exists
        if (!in_array($target_table, $tables)) {
            // Check if the tmp table exists...
            if (in_array($target_table . '_tmp', $tables)) {
                $this->log[] = JText::sprintf('COM_PFMIGRATOR_TMP_TABLE_EXISTS', $target_table . '_tmp');
                return true;
            }

            // ...Or else return error
            $this->success = false;
            $this->log[] = JText::sprintf('COM_PFMIGRATOR_TABLE_NOT_FOUND', $target_table);

            return false;
        }

        // Check if the tmp table exists
        if (in_array($target_table . '_tmp', $tables)) {
            // Try to delete it
            $this->_db->setQuery('DROP TABLE ' . $target_table . '_tmp');

            if (!$this->_db->execute()) {
                // Skip it on error
                $this->log[] = JText::sprintf('COM_PFMIGRATOR_TMP_TABLE_EXISTS', $target_table . '_tmp');
                return true;
            }
        }

        $this->_db->setQuery('RENAME TABLE ' . $target_table . ' TO ' . $target_table . '_tmp');

        if (!$this->_db->execute()) {
            $this->log[] = JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true));

            $this->success = false;
            return false;
        }

        $this->log[] = JText::sprintf('COM_PFMIGRATOR_RENAME_TABLE_SUCCESS', $target_table, $target_table . '_tmp');

        return true;
    }


    public function getTotal()
    {
        if (PFMigratorHelper::fmProInstalled()) {
            return count($this->getTables());
        }

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


    protected function getTables()
    {
        $prefix = JFactory::getConfig()->get('dbprefix');

        $tables = array(
            $prefix . 'pf_folder_access', $prefix . 'pf_file_properties',
            $prefix . 'pf_note_properties', $prefix . 'pf_file_versions',
            $prefix . 'pf_note_versions'
        );

        return $tables;
    }
}
