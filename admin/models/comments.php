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

JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_pfcomments/tables');


/**
 * Comments migration model
 *
 */
class PFmigratorModelComments extends JModelList
{
    protected $log     = array();
    protected $success = true;
    protected $access  = 1;

    public function process($limitstart = 0)
    {
        $config = JFactory::getConfig();

        $this->access = $config->get('access', 1);

        if (!$limitstart) {
            if (!$this->freeRoot()) return false;
        }

        $query = $this->_db->getQuery(true);
        $query->select('*')
              ->from('#__pf_comments_tmp')
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

        $titles = implode(', ', $titles);
        $this->log[] = JText::sprintf('COM_PFMIGRATOR_MIGRATE_COMMENTS_SUCCESS', $titles);

        return true;
    }


    public function getTotal()
    {
        $query = $this->_db->getQuery(true);

        $query->select('COUNT(*)')
              ->from('#__pf_comments_tmp');

        $this->_db->setQuery($query);
        $total = (int) $this->_db->loadResult();

        return $total;
    }


    public function getLimit()
    {
        return 20;
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
        switch ($row->scope)
        {
            case 'tasks':
                $context = 'com_pftasks.task';
                $item    = $this->getTask($row->item_id);
                break;

            case 'notes':
                $context = 'com_pfrepo.note';
                $item    = $this->getNote($row->item_id);
                break;

            case 'design_review':
                $context = 'com_pfdesigns.design';
                $item    = $this->getDesign($row->item_id);
                break;

            default:
                $context = null;
                $item    = false;
                break;
        }

        if (!$item) return true;

        $data = array();
        $data['project_id']  = $item->project_id;
        $data['item_id']     = $row->item_id;
        $data['context']     = $context;
        $data['title']       = $row->title;
        $data['alias']       = JApplication::stringURLSafe($row->title);
        $data['description'] = $row->content;

        if ($row->cdate) {
            $date = new JDate($row->cdate);
            $data['created'] = $date->toSql();
        }

        $data['created_by'] = $row->author;
        $data['state']      = $item->state;
        $data['parent_id']  = 1;

        if (!$this->store($data)) {
            return false;
        }

        return true;
    }


    protected function getTask($id)
    {
        static $cache = array();

        if (isset($cache[$id])) return $cache[$id];

        $query = $this->_db->getQuery(true);

        $query->select('project_id, state, access')
              ->from('#__pf_tasks')
              ->where('id = ' . (int) $id);

        $this->_db->setQuery($query);
        $object = $this->_db->loadObject();

        if (empty($object)) {
            $cache[$id] = false;
        }
        else {
            $cache[$id] = $object;
        }

        return $cache[$id];
    }


    protected function getNote($id)
    {
        static $cache = array();

        if (isset($cache[$id])) return $cache[$id];

        $query = $this->_db->getQuery(true);

        $query->select('a.project_id, a.access, p.state')
              ->from('#__pf_repo_notes AS a')
              ->join('left', '#__pf_projects AS p ON p.id = a.project_id')
              ->where('a.id = ' . (int) $id);

        $this->_db->setQuery($query);
        $object = $this->_db->loadObject();

        if (empty($object)) {
            $cache[$id] = false;
        }
        else {
            $cache[$id] = $object;
        }

        return $cache[$id];
    }


    protected function getDesign($id)
    {
        static $cache = array();

        if (isset($cache[$id])) return $cache[$id];

        if (!PFMigratorHelper::designsInstalled()) return false;

        $query = $this->_db->getQuery(true);

        $query->select('project_id, state, access')
              ->from('#__pf_designs')
              ->where('id = ' . (int) $id);

        $this->_db->setQuery($query);
        $object = $this->_db->loadObject();

        if (empty($object)) {
            $cache[$id] = false;
        }
        else {
            $cache[$id] = $object;
        }

        return $cache[$id];
    }


    protected function store($data)
    {
        $tbl = JTable::getInstance('Comment', 'PFTable');

        if (!$tbl) {
            $this->success = false;
            $this->log[]   = JText::_('COM_PFMIGRATOR_MIGRATE_COMMENTS_TBL_INSTANCE_ERROR');

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

        return true;
    }


    protected function freeRoot()
    {
        $query = $this->_db->getQuery(true);

        $query->select('*')
              ->from('#__pf_comments_tmp')
              ->where('id = 1');

        $this->_db->setQuery($query);
        $root = $this->_db->loadObject();

        if (empty($root)) return true;

        $new_id = $this->getNewId(1);

        if (!$new_id) return false;

        $query->clear();
        $query->update('#__pf_comments_tmp')
              ->set('id = ' . $new_id)
              ->where('id = 1');

        $this->_db->setQuery($query);

        if (!$this->_db->execute()) {
            $this->success = false;
            $this->log[] = $this->_db->getError();
            return false;
        }

        return true;
    }


    protected function getNewId($id)
    {
        $obj = new stdClass();
        $obj->id    = null;
        $obj->title = 'migration_tmp_' . $id;

        if (!$this->_db->insertObject('#__pf_comments_tmp', $obj, 'id')) {
            $this->success = false;
            $this->log[] = $this->_db->getError();
            return false;
        }

        $query = $this->_db->getQuery(true);
        $query->delete('#__pf_comments_tmp')
              ->where('id = ' . $obj->id);

        $this->_db->setQuery($query);
        $this->_db->execute();

        return $obj->id;
    }
}
