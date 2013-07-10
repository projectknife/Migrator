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


/**
 * Repo file revisions migration model
 *
 */
class PFmigratorModelRepoFileRevs extends JModelList
{
    protected $log     = array();
    protected $success = true;
    protected $access  = 1;

    public function process($limitstart = 0)
    {
        if (!PFMigratorHelper::fmProInstalled()) {
            $this->log[] = JText::_('COM_PFMIGRATOR_FMPRO_NOT_INSTALLED');
            return true;
        }

        $config = JFactory::getConfig();

        $this->access = $config->get('access', 1);

        $query = $this->_db->getQuery(true);
        $query->select('a.*, f.project_id')
              ->from('#__pf_file_versions_tmp AS a')
              ->join('LEFT', '#__pf_repo_files AS f ON f.id = a.file_id')
              ->order('a.id ASC');

        $limit = $this->getLimit();

        $this->_db->setQuery($query, $limitstart, $limit);
        $rows = $this->_db->loadObjectList();

        if (empty($rows) || !is_array($rows)) {
            return true;
        }

        $titles = array();

        foreach ($rows AS $row)
        {
            if (!$this->migrate($row)) return false;

            $titles[] = $row->name;
        }

        $titles = implode(', ', $titles);
        $this->log[] = JText::sprintf('COM_PFMIGRATOR_MIGRATE_REPO_FILE_REVS_SUCCESS', $titles);

        return true;
    }


    public function getTotal()
    {
        if (!PFMigratorHelper::fmProInstalled()) {
            return 1;
        }

        $query = $this->_db->getQuery(true);

        $query->select('COUNT(*)')
              ->from('#__pf_file_versions_tmp');

        $this->_db->setQuery($query);
        $total = (int) $this->_db->loadResult();

        return $total;
    }


    public function getLimit()
    {
        return 15;
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
        if (!$row->project_id) {
            return true;
        }

        $base_path      = $this->getUploadPath();
        $project_dir_id = $this->getProjectRepo($row->project_id);
        $project_folder = $this->getDirectoryPath($project_dir_id);

        if (empty($project_folder)) {
            $this->log[] = JText::sprintf('COM_PFMIGRATOR_MIGRATE_REPO_FILES_PROJECT_DIR_ERROR', $row->name);
            return true;
        }

        $source_path = JPath::clean($base_path . '/' . $project_folder);
        $source_file =  $row->prefix . $row->name;

        if (!JFile::exists($source_path . '/' . $source_file)) {
            // Check if this is the current revision
            $query = $this->_db->getQuery(true);

            $query->select('name, prefix, cdate')
                  ->from('#__pf_files_tmp')
                  ->where('id = ' . (int) $row->file_id);

            $this->_db->setQuery($query);
            $pfile = $this->_db->loadObject();

            if (empty($pfile)) {
                $this->log[] = JText::sprintf('COM_PFMIGRATOR_MIGRATE_REPO_FILES_NOTFOUND_ERROR', $source_file, $source_path);
                return true;
            }

            if ($pfile->name == $row->name && $pfile->prefix == $row->prefix && $pfile->cdate == $row->cdate) {
                return true;
            }

            $this->log[] = JText::sprintf('COM_PFMIGRATOR_MIGRATE_REPO_FILES_NOTFOUND_ERROR', $source_file, $source_path);
            return true;
        }

        $dest_path = $base_path . '/' . $project_folder . '/_revs/file_' . $row->file_id;

        if (!JFolder::exists($dest_path)) {
            if (!JFolder::create($dest_path)) {
                $this->log[] = JText::sprintf('COM_PFMIGRATOR_MIGRATE_REPO_FILES_CREATE_DIR_ERROR', $dest_path);
                return true;
            }
        }

        $name = $this->generateNewFileName($dest_path, $row->name);

        if (!JFile::move($source_path . '/' . $source_file, $dest_path . '/' . $name)) {
            $this->log[] = JText::sprintf('COM_PFMIGRATOR_MIGRATE_REPO_FILES_MOVE_ERROR', $row->name);
            return true;
        }

        $nd  = $this->_db->getNullDate();
        $obj = new stdClass();

        $title = $name;
        $alias = JApplication::stringURLSafe($name);
        list($title, $alias) = $this->generateNewTitle($title, $alias, $row->file_id);

        $obj->id          = $row->id;
        $obj->title       = $title;
        $obj->file_name   = $name;
        $obj->file_size   = $row->filesize;
        $obj->file_extension = JFile::getExt($name);
        $obj->alias       = $alias;
        $obj->description = $row->description;
        $obj->created_by  = $row->author;
        $obj->project_id  = $row->project_id;
        $obj->parent_id   = $row->file_id;
        $obj->ordering    = $this->getOrdering($row->file_id);

        // Set creation date
        if ($row->cdate) {
            $date = new JDate($row->cdate);
            $obj->created = $date->toSql();
        }

        // Set attribs
        $obj->attribs = '{}';

        // Store base item
        if (!$this->_db->insertObject('#__pf_repo_file_revs', $obj)) {
            $this->success = false;
            $this->log[] = $this->_db->getError();
            return false;
        }

        return true;
    }


    /**
     * Method to change the title & alias.
     * Overloaded from JModelAdmin class
     *
     * @param     string     The title
     * @param     string     The alias
     * @param     integer    The item id
     *
     * @return    array      Contains the modified title and alias
     */
    protected function generateNewTitle($title, $alias = '', $parent = 0, $id = 0)
    {
        $query = $this->_db->getQuery(true);

        if (empty($alias)) {
            $alias = JApplication::stringURLSafe($title);

            if (trim(str_replace('-', '', $alias)) == '') {
                $alias = JApplication::stringURLSafe(JFactory::getDate()->format('Y-m-d-H-i-s'));
            }
        }

        if (trim(str_replace('-', '', $alias)) == '') {
            $alias = JApplication::stringURLSafe(JFactory::getDate()->format('Y-m-d-H-i-s'));
        }

        $query->select('COUNT(id)')
              ->from('#__pf_repo_file_revs')
              ->where('parent_id = ' . (int) $parent)
              ->where('alias = ' . $this->_db->quote($alias));

        if ($id) {
            $query->where('id != ' . intval($id));
        }

        $this->_db->setQuery($query);
        $count = (int) $this->_db->loadResult();

        if ($id > 0 && $count == 0) {
            return array($title, $alias);
        }
        elseif ($id == 0 && $count == 0) {
            return array($title, $alias);
        }
        else {
            while ($this->aliasExists($dir, $alias))
            {
                $m = null;

                if (preg_match('#-(\d+)$#', $alias, $m)) {
                    $alias = preg_replace('#-(\d+)$#', '-'.($m[1] + 1).'', $alias);
                }
                else {
                    $alias .= '-2';
                }

                if (preg_match('#\((\d+)\)$#', $title, $m)) {
                    $title = preg_replace('#\(\d+\)$#', '('.($m[1] + 1).')', $title);
                }
                else {
                    $title .= ' (2)';
                }
            }
        }

        return array($title, $alias);
    }


    /**
     * Method to change the file name.
     *
     * @param     string    $dest    The target destination folder
     * @param     string    $name    The file name
     *
     * @return    string             Contains the new name
     */
    protected function generateNewFileName($dest, $name)
    {
        $name = JFile::makeSafe($name);
        $ext  = JFile::getExt($name);
        $name = substr($name, 0 , (strlen($name) - (strlen($ext) + 1)));

        if ($name == '') {
            $name = JFile::makeSafe(JFactory::getDate()->format('Y-m-d-H-i-s'));
        }

        $exists = true;
        $files  = JFolder::files($dest);

        if (!is_array($files)) {
            return $name . '.' . $ext;
        }

        if (!count($files)) {
            return $name . '.' . $ext;
        }

        if (!in_array($name . '.' . $ext, $files)) {
            return $name . '.' . $ext;
        }

        while ($exists == true)
        {
            $m = null;

            if (preg_match('#-(\d+)$#', $name, $m)) {
                $name   = preg_replace('#-(\d+)$#', '-'.($m[1] + 1).'', $name);
                $exists = JFile::exists($dest . '/' . $name . '.' . $ext);
            }
            else {
                $name  .= '-2';
                $exists = JFile::exists($dest . '/' . $name . '.' . $ext);
            }
        }

        return $name . '.' . $ext;
    }


    protected function aliasExists($parent = 0, $alias = '')
    {
        $query = $this->_db->getQuery(true);

        $query->select('id')
              ->from('#__pf_repo_file_revs')
              ->where('parent_id = ' . (int) $parent)
              ->where('alias = ' . $this->_db->quote($alias));

        $this->_db->setQuery($query, 0, 1);
        $result = (int) $this->_db->loadResult();

        return ($result > 0 ? true : false);
    }


    protected function getProjectRepo($project)
    {
        static $cache = array();

        if (isset($cache[$project])) return $cache[$project];

        $query = $this->_db->getQuery(true);

        $query->select('attribs')
              ->from('#__pf_projects')
              ->where('id = ' . (int) $project);

        $this->_db->setQuery($query);
        $params = $this->_db->loadResult();

        if (empty($params)) {
            $cache[$project] = false;
            return false;
        }

        $registry = new JRegistry();
        $registry->loadString($params);

        $cache[$project] = (int) $registry->get('repo_dir');

        return $cache[$project];
    }


    protected function getUploadPath()
    {
        static $path = null;

        if (!is_null($path)) return $path;

        if (PFmigratorHelper::fmProInstalled()) {
            $cfg_path = PFmigratorHelper::getConfig('upload_path', 'filemanager_pro');

            if (empty($cfg_path)) {
                $cfg_path = PFmigratorHelper::getConfig('upload_path', 'filemanager');
            }
        }
        else {
            $cfg_path = PFmigratorHelper::getConfig('upload_path', 'filemanager');
        }

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


    protected function getDirectoryPath($dir)
    {
        static $cache = array();

        if (isset($cache[$dir])) return $cache[$dir];

        $query = $this->_db->getQuery(true);

        $query->select('path')
              ->from('#__pf_repo_dirs')
              ->where('id = ' . $dir);

        $this->_db->setQuery($query);
        $cache[$dir] = $this->_db->loadResult();

        return $cache[$dir];
    }


    protected function getOrdering($parent = 0)
    {
        $query = $this->_db->getQuery(true);

        $query->select('COUNT(*)')
              ->from('#__pf_repo_file_revs')
              ->where('parent_id = ' . (int) $parent);

        $this->_db->setQuery($query);
        $count = (int) $this->_db->loadResult();

        $count++;

        return $count;
    }
}
