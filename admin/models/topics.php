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
 * Tasks migration model
 *
 */
class PFmigratorModelTasks extends JModelList
{
    protected $log     = array();
    protected $success = true;
    protected $access  = 1;

    public function process($limitstart = 0)
    {
        $config = JFactory::getConfig();
        $this->access = $config->get('access', 1);

        $query = $this->_db->getQuery(true);
        $query->select('a.*, p.state, p.access')
              ->from('#__pf_tasks_tmp AS a')
              ->join('left', '#__pf_projects AS p ON p.id = a.project')
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

            $titles[] = $row->title;
        }

        $projects = implode(', ', $titles);
        $this->log[] = JText::sprintf('COM_PFMIGRATOR_MIGRATE_MILESTONES_SUCCESS', $projects);

        return true;
    }


    public function getTotal()
    {
        $query = $this->_db->getQuery(true);

        $query->select('COUNT(*)')
              ->from('#__pf_tasks_tmp');

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
        $nd  = $this->_db->getNullDate();
        $obj = new stdClass();

        $obj->id           = $row->id;
        $obj->title        = $row->title;
        $obj->alias        = JApplication::stringURLSafe($row->title);
        $obj->description  = $row->content;
        $obj->created_by   = $row->author;
        $obj->state        = $row->state;
        $obj->access       = ($row->access ? $row->access : $this->access);
        $obj->ordering     = $row->ordering;
        $obj->priority     = $row->priority;
        $obj->milestone_id = $row->milestone;
        $obj->project_id   = $row->project;

        // Set creation date
        if ($row->cdate) {
            $date = new JDate($row->cdate);
            $obj->created    = $date->toSql();
            $obj->start_date = $obj->created;
        }

        // Set end date
        if ($row->edate) {
            $date = new JDate($row->edate);
            $obj->end_date = $date->toSql();
        }

        // Set state
        if ($row->archived) {
            $obj->state = 2;
        }
        elseif (!$row->approved) {
            $obj->state = 0;
        }

        // Set the progress
        if ($row->progress >= 100) {
            $obj->complete = 1;

            if ($row->mdate) {
                $date = new JDate($row->mdate);
                $obj->completed = $date->toSql();
            }
            elseif (isset($obj->end_date)) {
                $obj->completed = $obj->end_date;
            }
            elseif (isset($obj->created)) {
                $obj->completed = $obj->created;
            }
        }

        // Set attribs
        $obj->attribs = '{}';

        // Store base item
        if (!$this->_db->insertObject('#__pf_tasks', $obj)) {
            $this->success = false;
            $this->log[] = $this->_db->getError();
            return false;
        }

        // Create asset
        $nulls  = false;
        $parent = $this->getParentAsset();
        $asset  = JTable::getInstance('Asset', 'JTable', array('dbo' => $this->_db));

        $asset->loadByName('com_pftasks.task.' . $row->id);

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
            $asset->name      = 'com_pftasks.task.' . $row->id;
            $asset->title     = $row->title;
            $asset->rules     = '{}';

            if (!$asset->check() || !$asset->store($nulls)) {
                $this->log[] = $asset->getError();
            }
            else {
                $query = $this->_db->getQuery(true);

                $query->update('#__pf_tasks')
                      ->set('asset_id = ' . (int) $asset->id)
                      ->where('id = ' . (int) $row->id);

                $this->_db->setQuery($query);

                if (!$this->_db->execute()) {
                    $this->log[] = JText::sprintf('JLIB_DATABASE_ERROR_STORE_FAILED_UPDATE_ASSET_ID', $this->_db->getErrorMsg());
                }
            }
        }

        // Assign users
        $query = $this->_db->getQuery(true);

        $query->select('user_id')
              ->from('#__pf_task_users_tmp')
              ->where('task_id = ' . (int) $row->id);

        $this->_db->setQuery($query);
        $users = $this->_db->loadColumn();

        if (empty($users) || !is_array($users)) $users = array();

        foreach ($users AS $uid)
        {
            $obj = new stdClass();

            $obj->id        = null;
            $obj->item_type = 'com_pftasks.task';
            $obj->item_id   = $row->id;
            $obj->user_id   = $uid;

            if (!$this->_db->insertObject('#__pf_ref_users', $obj)) {
                $this->log[] = $this->_db->getError();
            }
        }

        return true;
    }


    protected function getParentAsset()
    {
        static $parent = null;

        if (!is_null($parent)) return $parent;

        $query = $this->_db->getQuery(true);

        $query->select('id')
              ->from('#__assets')
              ->where('name = ' . $this->_db->quote('com_pftasks'));

        $this->_db->setQuery($query);
        $parent = (int) $this->_db->loadResult();

        if (!$parent) $parent = 1;

        return $parent;
    }
}
