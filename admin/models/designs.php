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
 * Designs migration model
 *
 */
class PFmigratorModelDesigns extends JModelList
{
    protected $log     = array();
    protected $success = true;
    protected $access  = 1;

    public function process($limitstart = 0)
    {
        if (!PFMigratorHelper::designsInstalled()) {
            $this->log[] = JText::_('COM_PFMIGRATOR_DESIGNS_NOT_INSTALLED');
            return true;
        }

        $config = JFactory::getConfig();

        $this->access = $config->get('access', 1);

        $query = $this->_db->getQuery(true);
        $query->select('a.*, p.access, p.state')
              ->from('#__pf_designs_tmp AS a')
              ->join('LEFT', '#__pf_projects AS p ON p.id = a.project_id')
              ->where('a.rev_id = 0')
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
        $this->log[] = JText::sprintf('COM_PFMIGRATOR_MIGRATE_DESIGNS_SUCCESS', $titles);

        return true;
    }


    public function getTotal()
    {
        if (!PFMigratorHelper::designsInstalled()) {
            return 1;
        }

        $query = $this->_db->getQuery(true);

        $query->select('COUNT(*)')
              ->from('#__pf_designs_tmp');

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
        $base_path = $this->getUploadPath();

        $source_path = JPath::clean($base_path . '/project_' . $row->project_id);
        $source_file =  $row->prefix . '_' . $row->name;

        if (!JFile::exists($source_path . '/' . $source_file)) {
            $this->log[] = JText::sprintf('COM_PFMIGRATOR_MIGRATE_REPO_FILES_NOTFOUND_ERROR', $source_file, $source_path);
            return true;
        }

        $name = $this->generateNewFileName($source_path, $row->name);

        if (!JFile::move($source_path . '/' . $source_file, $source_path . '/' . $name)) {
            $this->log[] = JText::sprintf('COM_PFMIGRATOR_MIGRATE_REPO_FILES_MOVE_ERROR', $row->name);
            return true;
        }

        $nd  = $this->_db->getNullDate();
        $obj = new stdClass();

        $title = (empty($row->title) ? $name : $row->title);
        $alias = JApplication::stringURLSafe($name);
        list($title, $alias) = $this->generateNewTitle($title, $alias, $row->project_id);

        $obj->id          = $row->id;
        $obj->title       = $title;
        $obj->file_name   = $name;
        $obj->file_size   = $row->filesize;
        $obj->file_extension = JFile::getExt($name);
        $obj->alias       = $alias;
        $obj->description = $row->description;
        $obj->created_by  = $row->author;
        $obj->access      = ($row->access ? $row->access : $this->access);
        $obj->project_id  = $row->project_id;
        $obj->state       = $row->state;

        // Set creation date
        if ($row->cdate) {
            $date = new JDate($row->cdate);
            $obj->created = $date->toSql();
        }

        // Set attribs
        $obj->attribs = '{}';

        // Store base item
        if (!$this->_db->insertObject('#__pf_designs', $obj)) {
            $this->success = false;
            $this->log[] = $this->_db->getError();
            return false;
        }

        // Create asset
        $nulls  = false;
        $parent = $this->getParentAsset();
        $asset  = JTable::getInstance('Asset', 'JTable', array('dbo' => $this->_db));

        $asset->loadByName('com_pfdesigns.design.' . $row->id);

        // Check for an error.
        if ($error = $asset->getError()) {
            $this->log[] = $error;
        }
        else {
            if (empty($asset->id)) {
                $asset->setLocation($parent, 'last-child');
            }

            // Prepare the asset to be stored.
            $asset->parent_id = $parent;
            $asset->name      = 'com_pfdesigns.design.' . $row->id;
            $asset->title     = $obj->title;
            $asset->rules     = '{}';

            if (!$asset->check() || !$asset->store($nulls)) {
                $this->log[] = $asset->getError();
            }
            else {
                $query = $this->_db->getQuery(true);

                $query->update('#__pf_designs')
                      ->set('asset_id = ' . (int) $asset->id)
                      ->where('id = ' . (int) $row->id);

                $this->_db->setQuery($query);

                if (!$this->_db->execute()) {
                    $this->log[] = JText::sprintf('JLIB_DATABASE_ERROR_STORE_FAILED_UPDATE_ASSET_ID', $this->_db->getErrorMsg());
                }
            }
        }

        // Add approvals
        if ($row->approved_users) {
            $this->addApprovals($row->id, $row->approved_users, $row->cdate, 0);
        }

        // Migrate revisions
        if (!$this->migrateRevisions($obj)) return false;

        return true;
    }


    protected function migrateRevisions($design)
    {
        $query = $this->_db->getQuery(true);

        $query->select('*')
              ->from('#__pf_designs_tmp')
              ->where('rev_id = ' . $design->id)
              ->order('rev_num ASC');

        $this->_db->setQuery($query);
        $rows = $this->_db->loadObjectList();

        if (empty($rows) || !is_array($rows)) {
            return true;
        }


        foreach ($rows AS $row)
        {
            if (!$this->migrateRevision($design, $row)) return false;
        }

        return true;
    }


    protected function migrateRevision($design, $row)
    {
        $base_path = $this->getUploadPath();

        $source_path = JPath::clean($base_path . '/project_' . $design->project_id);
        $source_file =  $row->prefix . '_' . $row->name;

        if (!JFile::exists($source_path . '/' . $source_file)) {
            $this->log[] = JText::sprintf('COM_PFMIGRATOR_MIGRATE_REPO_FILES_NOTFOUND_ERROR', $source_file, $source_path);
            return true;
        }

        $name = $this->generateNewFileName($source_path, $row->name);

        if (!JFile::move($source_path . '/' . $source_file, $source_path . '/' . $name)) {
            $this->log[] = JText::sprintf('COM_PFMIGRATOR_MIGRATE_REPO_FILES_MOVE_ERROR', $row->name);
            return true;
        }

        $nd  = $this->_db->getNullDate();
        $obj = new stdClass();

        $title = (empty($row->title) ? $name : $row->title);
        $alias = JApplication::stringURLSafe($name);
        list($title, $alias) = $this->generateNewRevTitle($title, $alias, $design->id, $design->project_id);

        $obj->id          = $row->id;
        $obj->title       = $title;
        $obj->file_name   = $name;
        $obj->file_size   = $row->filesize;
        $obj->file_extension = JFile::getExt($name);
        $obj->alias       = $alias;
        $obj->description = $row->description;
        $obj->created_by  = $row->author;
        $obj->access      = $design->access;
        $obj->project_id  = $design->project_id;
        $obj->state       = $design->state;
        $obj->ordering    = $row->rev_num;
        $obj->parent_id   = $design->id;

        // Set creation date
        if ($row->cdate) {
            $date = new JDate($row->cdate);
            $obj->created = $date->toSql();
        }

        // Set attribs
        $obj->attribs = '{}';

        // Store base item
        if (!$this->_db->insertObject('#__pf_design_revisions', $obj)) {
            $this->success = false;
            $this->log[] = $this->_db->getError();
            return false;
        }

        // Create asset
        $nulls  = false;
        $parent = $this->getParentRevAsset($design->id);
        $asset  = JTable::getInstance('Asset', 'JTable', array('dbo' => $this->_db));

        $asset->loadByName('com_pfdesigns.revision.' . $row->id);

        // Check for an error.
        if ($error = $asset->getError()) {
            $this->log[] = $error;
        }
        else {
            if (empty($asset->id)) {
                $asset->setLocation($parent, 'last-child');
            }

            // Prepare the asset to be stored.
            $asset->parent_id = $parent;
            $asset->name      = 'com_pfdesigns.revision.' . $row->id;
            $asset->title     = $obj->title;
            $asset->rules     = '{}';

            if (!$asset->check() || !$asset->store($nulls)) {
                $this->log[] = $asset->getError();
            }
            else {
                $query = $this->_db->getQuery(true);

                $query->update('#__pf_design_revisions')
                      ->set('asset_id = ' . (int) $asset->id)
                      ->where('id = ' . (int) $row->id);

                $this->_db->setQuery($query);

                if (!$this->_db->execute()) {
                    $this->log[] = JText::sprintf('JLIB_DATABASE_ERROR_STORE_FAILED_UPDATE_ASSET_ID', $this->_db->getErrorMsg());
                }
            }
        }

        // Add approvals
        if ($row->approved_users) {
            $this->addApprovals($design->id, $row->approved_users, $row->cdate, $row->id);
        }

        return true;
    }


    protected function getParentAsset()
    {
        static $cache = 0;

        if ($cache) return $cache;

        $query = $this->_db->getQuery(true);

        $query->select('id')
              ->from('#__assets')
              ->where('name = ' . $this->_db->quote('com_pfdesigns'));

        $this->_db->setQuery($query);
        $parent = (int) $this->_db->loadResult();

        if (!$parent) $parent = 1;

        $cache = $parent;

        return $cache;
    }


    protected function getParentRevAsset($design)
    {
        static $cache = array();

        if (isset($cache[$design])) return $cache[$design];

        $query = $this->_db->getQuery(true);

        $query->select('id')
              ->from('#__assets')
              ->where('name = ' . $this->_db->quote('com_pfdesigns.design.' . $design));

        $this->_db->setQuery($query);
        $parent = (int) $this->_db->loadResult();

        if (!$parent) {
            $parent = $this->getParentAsset();
        }

        $cache[$design] = $parent;

        return $cache[$design];
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
    protected function generateNewTitle($title, $alias = '', $project = 0, $id = 0)
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
              ->from('#__pf_designs')
              ->where('project_id = ' . (int) $project)
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
            while ($this->aliasExists($project, $alias))
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
     * Method to change the title & alias.
     * Overloaded from JModelAdmin class
     *
     * @param     string     The title
     * @param     string     The alias
     * @param     integer    The item id
     *
     * @return    array      Contains the modified title and alias
     */
    protected function generateNewRevTitle($title, $alias = '', $design = 0, $project = 0, $id = 0)
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
              ->from('#__pf_design_revisions')
              ->where('project_id = ' . (int) $project)
              ->where('parent_id = ' . (int) $design)
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
            while ($this->revAliasExists($design, $project, $alias))
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


    protected function aliasExists($project = 0, $alias = '')
    {
        $query = $this->_db->getQuery(true);

        $query->select('id')
              ->from('#__pf_designs')
              ->where('project_id = ' . (int) $project)
              ->where('alias = ' . $this->_db->quote($alias));

        $this->_db->setQuery($query, 0, 1);
        $result = (int) $this->_db->loadResult();

        return ($result > 0 ? true : false);
    }


    protected function revAliasExists($design = 0, $project = 0, $alias = '')
    {
        $query = $this->_db->getQuery(true);

        $query->select('id')
              ->from('#__pf_design_revisions')
              ->where('project_id = ' . (int) $project)
              ->where('parent_id = ' . (int) $design)
              ->where('alias = ' . $this->_db->quote($alias));

        $this->_db->setQuery($query, 0, 1);
        $result = (int) $this->_db->loadResult();

        return ($result > 0 ? true : false);
    }


    protected function getUploadPath()
    {
        static $path = null;

        if (!is_null($path)) return $path;

        $cfg_path = PFmigratorHelper::getConfig('upload_path', 'design_review');

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


    protected function addApprovals($design, $users, $timestamp = 0, $revision = 0)
    {
        $query  = $this->_db->getQuery(true);
        $looped = array();

        $users = explode(',', $users);

        if (!is_array($users) || !count($users)) return true;

        if ($timestamp) {
            $date = new JDate($timestamp);
            $created = $date->toSql();
        }
        else {
            $created = $this->_db->getNullDate();
        }

        foreach ($users AS $uid)
        {
            $uid = (int) $uid;
            if (in_array($uid, $looped) || !$uid) continue;

            $looped[] = $uid;

            $query->clear();
            $query->select('COUNT(*)')
                  ->from('#__pf_designs_approved')
                  ->where('id = ' . (int) $design)
                  ->where('revision_id = ' . (int) $revision)
                  ->where('created_by = ' . $uid);

            $this->_db->setQuery($query);
            $count = (int) $this->_db->loadResult();

            if ($count) continue;

            $obj = new stdClass();
            $obj->id = $design;
            $obj->revision_id = $revision;
            $obj->created_by = $uid;
            $obj->created = $created;
            $obj->state = 1;

            $this->_db->insertObject('#__pf_designs_approved', $obj);
        }

        return true;
    }
}
