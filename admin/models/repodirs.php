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
 * Repo directory migration model
 *
 */
class PFmigratorModelRepoDirs extends JModelList
{
    protected $log     = array();
    protected $success = true;
    protected $access  = 1;

    protected $tree_map_parents  = array();
    protected $tree_map_children = array();
    protected $items;
    protected $project;

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

        $titles = array();

        foreach ($rows AS $row)
        {
            if (!$this->migrate($row)) return false;

            $titles[] = $row->title;
        }

        $projects = implode(', ', $titles);
        $this->log[] = JText::sprintf('COM_PFMIGRATOR_MIGRATE_REPO_DIRS_SUCCESS', $projects);

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
        return 3;
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
        $this->project = $row;

        $registry = new JRegistry();
        $registry->loadString($row->attribs);
        $repo_root_id = (int) $registry->get('repo_dir');

        $query = $this->_db->getQuery(true);

        $query->select('a.*, t.parent_id')
              ->from('#__pf_folders_tmp AS a')
              ->join('INNER', '#__pf_folder_tree_tmp AS t ON t.folder_id = a.id')
              ->where('a.project = ' . $row->id)
              ->order('a.id ASC');

        $this->_db->setQuery($query);
        $this->items = $this->_db->loadObjectList();

        if (empty($this->items) || !is_array($this->items)) return true;

        $this->items = $this->sortItems();

        // Start migrating from the top
        foreach ($this->items AS $item)
        {
            if ($item->parent_id == 0) $item->parent_id = $repo_root_id;

            if (!$this->store($item)) {
                return false;
            }
        }

        return true;
    }


    protected function sortItems($parent_id = 0)
    {
        $sort = array();

        foreach ($this->items as $i => $item)
        {
            if ($item->parent_id == $parent_id) {
                $sort[] = $item;

                unset($this->items[$i]);

                $children = $this->sortItems($item->id);

                if (count($children)) {
                    foreach ($children AS $child)
                    {
                        $sort[] = $child;
                    }
                }
            }
        }

        return $sort;
    }


    protected function store($row)
    {
        $parent_id = $row->parent_id;

        $title = $row->title;
        $alias = JApplication::stringURLSafe($row->title);
        list($title, $alias) = $this->generateNewTitle($parent_id, $title, $alias);

        $data = array();
        $data['project_id']  = $this->project->id;
        $data['description'] = $row->description;
        $data['title']       = $title;
        $data['alias']       = $alias;
        $data['access']      = $this->project->access;
        $data['protected']   = 0;
        $data['parent_id']   = $parent_id;
        $data['created_by']  = $row->author;

        if ($row->cdate) {
            $date = new JDate($row->cdate);
            $data['created'] = $date->toSql();
        }

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

        // Change the ID back to the original
        $query = $this->_db->getQuery(true);

        $query->update('#__pf_repo_dirs')
              ->set('id = ' . $row->id)
              ->where('id = ' . $tbl->id);

        $this->_db->setQuery($query);

        if (!$this->_db->execute()) {
            $this->success = false;
            $this->log[] = $this->_db->getError();
            return false;
        }

        // Update the asset name
        $query->clear();
        $query->update('#__assets')
              ->set('name = ' . $this->_db->quote('com_pfrepo.directory.' . $row->id))
              ->where('id = ' . $tbl->asset_id);

        $this->_db->setQuery($query);

        if (!$this->_db->execute()) {
            $this->success = false;
            $this->log[] = $this->_db->getError();
            return false;
        }

        // Create physical folder
        $base = $this->getUploadPath();

        if (!JFolder::exists($base . '/' . $tbl->path)) {
            JFolder::create($base . '/' . $tbl->path);
        }

        return true;
    }


    protected function folderExists($id)
    {
        static $cache = array();

        if (isset($cache[$id])) return $cache[$id];

        $query = $this->_db->getQuery(true);

        $query->select('id')
              ->from('#__pf_repo_dirs')
              ->where('id = ' . $id);

        $this->_db->setQuery($query);
        $exists = (int) $this->_db->loadResult();

        $cache[$id] = ($exists > 0 ? true: false);

        return $cache[$id];
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


    /**
     * Method to change the title.
     *
     * @param     integer    $parent_id    The parent directory
     * @param     string     $title        The directory title
     * @param     string     $alias        The current alias
     * @param     integer    $id           The directory id
     *
     * @return    string                   Contains the new title
     */
    protected function generateNewTitle($parent_id, $title, $alias = '', $id = 0)
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
              ->from('#__pf_repo_dirs')
              ->where('alias = ' . $this->_db->quote($alias))
              ->where('parent_id = ' . (int) $parent_id);

        if ($id) $query->where('id != ' . intval($id));

        $this->_db->setQuery($query);
        $count = (int) $this->_db->loadResult();

        // No duplicates found?
        if (!$count) return array($title, $alias);

        // Generate new title
        while ($this->aliasExists($parent_id, $alias))
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

        return array($title, $alias);
    }


    protected function aliasExists($parent_id = 0, $alias = '')
    {
        $query = $this->_db->getQuery(true);

        $query->select('id')
              ->from('#__pf_repo_dirs')
              ->where('parent_id = ' . (int) $parent_id)
              ->where('alias = ' . $this->_db->quote($alias));

        $this->_db->setQuery($query, 0, 1);
        $result = (int) $this->_db->loadResult();

        return ($result > 0 ? true : false);
    }
}
