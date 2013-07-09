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
 * Repo note revisions migration model
 *
 */
class PFmigratorModelRepoNoteRevs extends JModelList
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
        $query->select('a.*, n.project_id')
              ->from('#__pf_note_versions_tmp AS a')
              ->join('LEFT', '#__pf_repo_notes AS n ON n.id = a.note_id')
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

        $titles = implode(', ', $titles);
        $this->log[] = JText::sprintf('COM_PFMIGRATOR_MIGRATE_REPO_NOTE_REVS_SUCCESS', $titles);

        return true;
    }


    public function getTotal()
    {
        if (!PFMigratorHelper::fmProInstalled()) {
            return 1;
        }

        $query = $this->_db->getQuery(true);

        $query->select('COUNT(*)')
              ->from('#__pf_note_versions_tmp');

        $this->_db->setQuery($query);
        $total = (int) $this->_db->loadResult();

        return $total;
    }


    public function getLimit()
    {
        return 25;
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

        $nd  = $this->_db->getNullDate();
        $obj = new stdClass();

        $title = $row->title;
        $alias = JApplication::stringURLSafe($row->title);
        list($title, $alias) = $this->generateNewTitle($title, $alias, $row->note_id);

        $obj->id          = $row->id;
        $obj->title       = $title;
        $obj->alias       = $alias;
        $obj->description = $row->content;
        $obj->created_by  = $row->author;
        $obj->project_id  = $row->project_id;
        $obj->parent_id   = $row->note_id;
        $obj->ordering    = $this->getOrdering($row->note_id);

        // Set creation date
        if ($row->cdate) {
            $date = new JDate($row->cdate);
            $obj->created = $date->toSql();
        }

        // Set attribs
        $obj->attribs = '{}';

        // Store base item
        if (!$this->_db->insertObject('#__pf_repo_note_revs', $obj)) {
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
              ->from('#__pf_repo_note_revs')
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
            while ($this->aliasExists($parent, $alias))
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


    protected function aliasExists($parent = 0, $alias = '')
    {
        $query = $this->_db->getQuery(true);

        $query->select('id')
              ->from('#__pf_repo_note_revs')
              ->where('parent_id = ' . (int) $parent)
              ->where('alias = ' . $this->_db->quote($alias));

        $this->_db->setQuery($query, 0, 1);
        $result = (int) $this->_db->loadResult();

        return ($result > 0 ? true : false);
    }


    protected function getOrdering($parent = 0)
    {
        $query = $this->_db->getQuery(true);

        $query->select('COUNT(*)')
              ->from('#__pf_repo_note_revs')
              ->where('parent_id = ' . (int) $parent);

        $this->_db->setQuery($query);
        $count = (int) $this->_db->loadResult();

        $count++;

        return $count;
    }
}
