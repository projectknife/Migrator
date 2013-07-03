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
 * Rename Tables migration model
 *
 */
class PFmigratorModelRenameTables extends JModelList
{
    protected $log     = array();
    protected $success = true;

    public function process($limitstart = 0)
    {
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
            $this->log[] = JText::sprintf('COM_PFMIGRATOR_TMP_TABLE_EXISTS', $target_table . '_tmp');

            return true;
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
        return count($this->getTables());
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
            $prefix . 'pf_access_flags', $prefix . 'pf_access_levels',
            $prefix . 'pf_comments', $prefix . 'pf_events',
            $prefix . 'pf_files', $prefix . 'pf_folders',
            $prefix . 'pf_folder_tree', $prefix . 'pf_groups',
            $prefix . 'pf_group_users', $prefix . 'pf_languages',
            $prefix . 'pf_milestones', $prefix . 'pf_mods',
            $prefix . 'pf_mod_files', $prefix . 'pf_notes',
            $prefix . 'pf_panels', $prefix . 'pf_processes',
            $prefix . 'pf_projects', $prefix . 'pf_project_invitations',
            $prefix . 'pf_project_members', $prefix . 'pf_sections',
            $prefix . 'pf_section_tasks', $prefix . 'pf_settings',
            $prefix . 'pf_tasks', $prefix . 'pf_task_attachments',
            $prefix . 'pf_task_users', $prefix . 'pf_themes',
            $prefix . 'pf_time_tracking', $prefix . 'pf_topics',
            $prefix . 'pf_topic_replies', $prefix . 'pf_topic_subscriptions',
            $prefix . 'pf_user_access_level', $prefix . 'pf_user_profile'
        );

        return $tables;
    }
}
