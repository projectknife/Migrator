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
 * Attachments migration model
 *
 */
class PFmigratorModelAttachments extends JModelList
{
    protected $log     = array();
    protected $success = true;

    public function process($limitstart = 0)
    {
        $config = JFactory::getConfig();

        $query = $this->_db->getQuery(true);
        $query->select('a.*, t.project_id')
              ->from('#__pf_task_attachments_tmp AS a')
              ->join('left', '#__pf_tasks AS t ON t.id = a.task_id')
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

            $titles[] = $row->attach_type . '.' . $row->attach_id;
        }

        $titles = implode(', ', $titles);
        $this->log[] = JText::sprintf('COM_PFMIGRATOR_MIGRATE_ATTACHMENTS_SUCCESS', $titles);

        return true;
    }


    public function getTotal()
    {
        $query = $this->_db->getQuery(true);

        $query->select('COUNT(*)')
              ->from('#__pf_task_attachments_tmp');

        $this->_db->setQuery($query);
        $total = (int) $this->_db->loadResult();

        return $total;
    }


    public function getLimit()
    {
        return 30;
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
        if (!$row->project_id) return true;

        switch ($row->attach_type)
        {
            case 'folder':
                $attachment = 'directory.' . $row->attach_id;
                break;

            case 'directory':
            case 'note':
            case 'file':
                $attachment = $row->attach_type . '.' . $row->attach_id;
                break;

            default:
                $attachment = false;
                break;
        }

        if (!$attachment) return true;

        $obj = new stdClass();
        $obj->id         = $row->id;
        $obj->item_type  = 'com_pftasks.task';
        $obj->item_id    = $row->task_id;
        $obj->project_id = $row->project_id;
        $obj->attachment = $attachment;

        if ($this->attachExists($obj->attachment, $obj->item_id, $obj->item_type)) {
            return true;
        }

        // Store base item
        if (!$this->_db->insertObject('#__pf_ref_attachments', $obj)) {
            $this->success = false;
            $this->log[] = $this->_db->getError();
            return false;
        }

        return true;
    }

    protected function attachExists($attachment, $item_id, $item_type)
    {
        $query = $this->_db->getQuery(true);

        $query->select('COUNT(*)')
              ->from('#__pf_ref_attachments')
              ->where('attachment = ' . $this->_db->quote($attachment))
              ->where('item_id = ' . (int) $item_id)
              ->where('item_type = ' . $this->_db->quote($item_type));

        $this->_db->setQuery($query);
        $exists = (int) $this->_db->loadResult();

        return $exists;
    }
}
