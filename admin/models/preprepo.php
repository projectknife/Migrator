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
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_pfrepo/tables');


/**
 * Repo preparation migration model
 *
 */
class PFmigratorModelPrepRepo extends JModelList
{
    protected $log     = array();
    protected $success = true;
    protected $access  = 1;

    public function process($limitstart = 0)
    {
        $config = JFactory::getConfig();

        $this->access = $config->get('access', 1);

        $query = $this->_db->getQuery(true);
        $query->select('*')
              ->from('#__pf_projects')
              ->order('id ASC');

        $limit = $this->getLimit();

        $this->_db->setQuery($query, $limitstart, $limit);
        $rows = $this->_db->loadObjectList();

        if (empty($rows) || !is_array($rows)) {
            return true;
        }

        // Update the config
        if ($limitstart == 0) {
            $path = $this->getUploadPath();

            if (!$path) {
                $this->success = false;
                $this->log[] = JText::_('COM_PFMIGRATOR_PREPREPO_UPLOAD_DIR_ERROR');

                return false;
            }

            $query->clear();
            $query->select($this->_db->quoteName('params'))
                  ->from('#__extensions')
                  ->where($this->_db->quoteName('type') . ' = ' . $this->_db->quote('component'))
                  ->where($this->_db->quoteName('element') . ' = ' . $this->_db->quote('com_pfrepo'));

            $this->_db->setQuery($query, 0, 1);
            $params = $this->_db->loadResult();

            $base_path = str_replace(JPATH_SITE . '/', $path, '');

            $registry = new JRegistry();
            $registry->loadString($params);
            $registry->set('repo_basepath', $base_path);

            $query->clear();
            $query->update('#__extensions')
                  ->set($this->_db->quoteName('params') . ' = ' . $db->quote(strval($registry)))
                  ->where($this->_db->quoteName('type') . ' = ' . $this->_db->quote('component'))
                  ->where($this->_db->quoteName('element') . ' = ' . $this->_db->quote('com_pfrepo'));

            $this->_db->setQuery($query);

            if (!$this->_db->execute()) {
                $this->success = false;
                $this->log[] = $this->_db->getError();
                return false;
            }
        }

        // Create repo base dirs
        $titles = array();

        foreach ($rows AS $row)
        {
            if (!$this->migrate($row)) return false;

            $titles[] = $row->title;
        }

        $projects = implode(', ', $titles);
        $this->log[] = JText::sprintf('COM_PFMIGRATOR_MIGRATE_PREP_REPO_SUCCESS', $projects);

        return true;
    }


    public function getTotal()
    {
        $query = $this->_db->getQuery(true);

        $query->select('COUNT(*)')
              ->from('#__pf_projects');

        $this->_db->setQuery($query);
        $total = (int) $this->_db->loadResult();

        return $total;
    }


    public function getLimit()
    {
        return 10;
    }


    public function getLog()
    {
        return $this->log;
    }


    public function getSuccess()
    {
        return $this->success;
    }


    protected function migrate($row)
    {
        $base_path = $this->getUploadPath();
        $old_path  = $base_path . '/project_' . $row->id;
        $new_path  = JPath::clean($base_path . '/' . $row->alias);

        // Rename existing path?
        if (JFolder::exists($old_path)) {
            if (!JFolder::exists($new_path)) {
                if (!JFolder::move($old_path, $new_path)) {
                    $this->success = false;
                    $this->log[]   = JText::sprintf('COM_PFMIGRATOR_PREPREPO_RENAME_ERROR', $old_path, $new_path);

                    return false;
                }
            }
        }
        else {
            // Create repo
            if (!JFolder::create($new_path)) {
                $this->success = false;
                $this->log[]   = JText::sprintf('COM_PFMIGRATOR_PREPREPO_CREATE_ERROR', $new_path);

                return false;
            }
        }

        $data = array();
        $data['project_id'] = $row->id;
        $data['title']      = $row->title;
        $data['alias']      = $row->alias;
        $data['created']    = $row->created;
        $data['created_by'] = $row->created_by;
        $data['access']     = $row->access;
        $data['protected']  = 1;
        $data['parent_id']  = 1;

        $tbl = JTable::getInstance('Directory', 'PFTable');

        if (!$tbl) {
            $this->success = false;
            $this->log[]   = JText::_('COM_PFMIGRATOR_PREPREPO_TBL_INSTANCE_ERROR');

            return false;
        }

        $tbl->setLocation($data['parent_id'], 'last-child');

        if (!$tbl->bind($data)) {
            $this->success = false;
            $this->log[]   = $tbl->getError();

            return false;
        }

        if (!$tbl->check()) {
            $this->success = false;
            $this->log[]   = $tbl->getError();

            return false;
        }

        if (!$tbl->store()) {
            $this->success = false;
            $this->log[]   = $tbl->getError();

            return false;
        }

        if (!$tbl->rebuildPath($tbl->id)) {
            $this->success = false;
            $this->log[]   = $tbl->getError();

            return false;
        }

        if (!$tbl->rebuild($tbl->id, $tbl->lft, $tbl->level, $tbl->path)) {
            $this->success = false;
            $this->log[]   = $tbl->getError();

            return false;
        }

        // Set the repo dir in the project settings
        $registry = new JRegistry();
        $registry->loadString($row->attribs);
        $registry->set('repo_dir', $tbl->id);

        $query = $this->_db->getQuery(true);

        $query->update('#__pf_projects')
              ->set('attribs = ' . strval($registry))
              ->where('id = ' . (int) $row->id);

        $this->_db->setQuery($query);

        if (!$this->_db->execute()) {
            $this->success = false;
            $this->log[] = $this->_db->getError();
            return false;
        }

        // Check if the directory id is already occupied
        $query->clear();
        $query->select('id')
              ->from('#__pf_folders_tmp')
              ->where('id = ' . $tbl->id);

        $this->_db->setQuery($query);
        $exists = (int) $this->_db->loadResult($tbl->id);

        if ($exists) {
            $new_id = $this->getNewId();

            if (!$new_id) return false;

            $query->clear();
            $query->update('#__pf_folders_tmp')
                  ->set('id = ' . $new_id)
                  ->where('id = ' . $tbl->id);

            $this->_db->setQuery($query);

            if (!$this->_db->execute()) {
                $this->success = false;
                $this->log[] = $this->_db->getError();
                return false;
            }

            $query->clear();
            $query->update('#__pf_folder_tree_tmp')
                  ->set('folder_id = ' . $new_id)
                  ->where('folder_id = ' . $tbl->id);

            $this->_db->setQuery($query);

            if (!$this->_db->execute()) {
                $this->success = false;
                $this->log[] = $this->_db->getError();
                return false;
            }

            $query->clear();
            $query->update('#__pf_folder_tree_tmp')
                  ->set('parent_id = ' . $new_id)
                  ->where('parent_id = ' . $tbl->id);

            $this->_db->setQuery($query);

            if (!$this->_db->execute()) {
                $this->success = false;
                $this->log[] = $this->_db->getError();
                return false;
            }

            $query->clear();
            $query->update('#__pf_files_tmp')
                  ->set('dir = ' . $new_id)
                  ->where('dir = ' . $tbl->id);

            $this->_db->setQuery($query);

            if (!$this->_db->execute()) {
                $this->success = false;
                $this->log[] = $this->_db->getError();
                return false;
            }

            $query->clear();
            $query->update('#__pf_notes_tmp')
                  ->set('dir = ' . $new_id)
                  ->where('dir = ' . $tbl->id);

            $this->_db->setQuery($query);

            if (!$this->_db->execute()) {
                $this->success = false;
                $this->log[] = $this->_db->getError();
                return false;
            }

            $query->clear();
            $query->update('#__pf_task_attachments_tmp')
                  ->set('attach_id = ' . $new_id)
                  ->where('attach_id = ' . $tbl->id)
                  ->where('attach_type = ' . $this->_db->quote('folder'));

            $this->_db->setQuery($query);

            if (!$this->_db->execute()) {
                $this->success = false;
                $this->log[] = $this->_db->getError();
                return false;
            }
        }

        return true;
    }


    protected function getUploadPath()
    {
        static $path = null;

        if (!is_null($path)) return $path;

        $cfg_path = PFmigratorHelper::getConfig('upload_path', 'filemanager');

        if (empty($cfg_path)) {
            $path = false;
            return $path;
        }

        if (substr($cfg_path, 0, 1) != '/' && substr($cfg_path, 0, 1) != '\\') {
            $cfg_path = '/' . $cfg_path;
        }

        if (substr($cfg_path, -1) == '/' || substr($cfg_path, -1) == '\\') {
            $cfg_path = substr($cfg_path, 0, -1);
        }

        $path = JPath::clean(JPATH_SITE . $cfg_path);

        if (!JFolder::exists($path)) {
            if (!JFolder::create($path)) {
                $path = false;
            }
        }

        return $path;
    }


    protected function getNewId($id)
    {
        $obj = new stdClass();
        $obj->id = null;
        $obj->title = 'migration_tmp_' . $id;

        if (!$this->_db->insertObject('#__pf_folders_tmp', $obj, 'id')) {
            $this->success = false;
            $this->log[] = $this->_db->getError();
            return false;
        }

        return $obj->id;
    }
}
