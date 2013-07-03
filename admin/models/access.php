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
 * Access migration model
 *
 */
class PFmigratorModelAccess extends JModelList
{
    protected $log     = array();
    protected $success = true;
    protected $data    = null;

    public function process($limitstart = 0)
    {
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_users/models', 'UsersModel');

        $this->data = PFmigratorHelper::getCustomData();

        $group_model = JModelLegacy::getInstance('Group', 'UsersModel');
        $pf_group    = (int) $this->data->get('pf_group');
        $query       = $this->_db->getQuery(true);

        $query->select('id, title')
              ->from('#__pf_projects_tmp')
              ->order('id ASC');

        $this->_db->setQuery($query, $limitstart, $this->getLimit());
        $projects = $this->_db->loadObjectList();

        if (empty($projects) || !is_array($projects)) {
            return true;
        }

        $query->clear();
        $query->select('ordering')
              ->from('#__viewlevels')
              ->order('ordering DESC');

        $this->_db->setQuery($query, 0, 1);
        $ordering = $this->_db->loadResult();

        $base_groups = array();
        $titles      = array();

        foreach ($projects AS $project)
        {
            // First, create a group for each project
            $group_model->setState('group.id',  null);
            $group_model->setState('group.new', true);

            $group['id']        = null;
            $group['title']     = $project->title;
            $group['parent_id'] = $pf_group;

            if (!$group_model->save($group)) {
                $this->success = false;
                $this->log[] = JText::sprintf('COM_PFMIGRATOR_CREATE_PROJECT_GROUP_FAILED', $project->title);

                return false;
            }

            // Get the group id
            $base_groups[$project->id] = $group_model->getState('group.id');

            // Get all project members
            $query->clear();

            $query->select('user_id')
                  ->from('#__pf_project_members_tmp')
                  ->where('project_id = ' . (int) $base_groups[$project->id])
                  ->where('approved = 1');

            $this->_db->setQuery($query);
            $users = $this->_db->loadColumn();

            if (empty($users) || !is_array($users)) $users = array();

            // Add users to group
            $this->addUsersToGroup($users, $base_groups[$project->id]);

            // Get all project groups
            $query->clear();
            $query->select('id, title')
                  ->from('#__pf_groups_tmp')
                  ->where('project = ' . (int) $base_groups[$project->id]);

            $this->_db->setQuery($query);
            $groups = $this->_db->loadObjectList();

            if (empty($groups) || !is_array($groups)) $groups = array();

            // Process each group
            foreach ($groups AS $pgroup)
            {
                $group_model->setState('group.id',  null);
                $group_model->setState('group.new', true);

                $group['id']        = null;
                $group['title']     = $pgroup->title;
                $group['parent_id'] = $base_groups[$project->id];

                // Create the group
                if (!$group_model->save($group)) {
                    $this->success = false;
                    $this->log[] = JText::sprintf('COM_PFMIGRATOR_CREATE_PROJECT_SUB_GROUP_FAILED', $pgroup->title, $project->title);

                    return false;
                }

                $new_id = $group_model->getState('group.id');

                // Get all group members
                $query->clear();
                $query->select('user_id')
                      ->from('#__pf_group_users_tmp')
                      ->where('group_id = ' . (int) $new_id);

                $this->_db->setQuery($query);
                $gusers = $this->_db->loadColumn();

                if (empty($gusers) || !is_array($gusers)) $gusers = array();

                // Add users to group
                $this->addUsersToGroup($gusers, $new_id);
            }

            // Create access level
            $ordering++;

            $lvl = new stdClass();

            $lvl->id       = null;
            $lvl->title    = $project->title;
            $lvl->ordering = $ordering;
            $lvl->rules    = '[' . $base_groups[$project->id] . ']';

            $this->_db->insertObject('#__viewlevels', $lvl, 'id');

            // Update the access of the project
            if ($lvl->id) {
                $query->clear();
                $query->update('#__pf_projects')
                      ->set('access = ' . (int) $lvl->id)
                      ->where('id = ' . (int) $project->id);

                $this->_db->setQuery($query);
                $this->_db->execute();
            }

            $titles[] = $project->title;
        }

        $projects = implode(', ', $titles);
        $this->log[] = JText::sprintf('COM_PFMIGRATOR_MIGRATE_ACCESS_SUCCESS', $projects);

        return true;
    }


    public function getTotal()
    {
        $query = $this->_db->getQuery(true);

        $query->select('COUNT(*)')
              ->from('#__pf_projects_tmp');

        $this->_db->setQuery($query);
        $total = (int) $this->_db->loadResult();

        return $total;
    }


    public function getLimit()
    {
        return 5;
    }


    public function getLog()
    {
        return $this->log;
    }


    public function getSuccess()
    {
        return $this->success;
    }


    protected function addUsersToGroup($users, $group)
    {
        foreach ($users AS $uid)
        {
            $obj = new stdClass();
            $obj->user_id  = $uid;
            $obj->group_id = $group;

            $this->_db->insertObject('#__user_usergroup_map', $obj);
        }
    }
}
