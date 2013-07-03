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
 * Prepare access migration model
 *
 */
class PFmigratorModelPrepAccess extends JModelList
{
    protected $log     = array();
    protected $success = true;
    protected $data    = null;

    public function process($limitstart = 0)
    {
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_users/models', 'UsersModel');

        $this->data = PFmigratorHelper::getCustomData();

        $pf_group = (int) $this->data->get('pf_group');

        // Create Projectfork container group
        if (!$pf_group) {
            $group = array();
            $model = JModelLegacy::getInstance('Group', 'UsersModel');

            $group['id']        = null;
            $group['title']     = 'Projectfork';
            $group['parent_id'] = 1;

            if (!$model->save($group)) {
                $this->success = false;
                $this->log[] = JText::_('COM_PFMIGRATOR_CREATE_BASE_GROUP_FAILED');

                return false;
            }

            $pf_group = (int) $model->getState('group.id');

            $this->data->set('pf_group', $pf_group);
            PFmigratorHelper::setCustomData($this->data);

            $this->log[] = JText::_('COM_PFMIGRATOR_CREATE_BASE_GROUP_SUCCESS');
        }

        return true;
    }


    public function getTotal()
    {
        return 1;
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
}
