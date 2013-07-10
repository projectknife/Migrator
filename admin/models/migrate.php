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
 * Main migration model
 *
 */
class PFmigratorModelMigrate extends JModelList
{
    protected $process = 0;

    /**
     * Method to set the current process
     *
     * @return   void
     */
    public function setProcess($process = 0)
    {
        $this->process = (int) $process;
    }


    /**
     * Method to get the list of processes
     *
     * @return    array
     */
    public function getItems()
    {
        $app   = JFactory::getApplication();
        $proc  = $this->process;
        $items = array();

        // 1. Rename PF 3 tables
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_RENAME_TABLES');
        $item['model']  = 'RenameTables';
        $item['active'] = false;

        $items[] = $item;

        // 2. Rename PF 3 folders
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_RENAME_FOLDERS');
        $item['model']  = 'RenameFolders';
        $item['active'] = false;

        $items[] = $item;

        // 3. Unregister PF 3
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_UNREG_PF3');
        $item['model']  = 'UnregisterPF';
        $item['active'] = false;

        $items[] = $item;

        // 4. Install PF 4
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_INSTALL_PF');
        $item['model']  = 'InstallPF';
        $item['active'] = false;

        $items[] = $item;

        // 5. Migrate projects
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_PROJECTS');
        $item['model']  = 'projects';
        $item['active'] = false;

        $items[] = $item;

        // 6. Prepare Access migration
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_PREP_ACCESS');
        $item['model']  = 'prepaccess';
        $item['active'] = false;

        $items[] = $item;

        // 7. Access migration
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_ACCESS');
        $item['model']  = 'access';
        $item['active'] = false;

        $items[] = $item;

        // 8. Milestones migration
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_MILESTONES');
        $item['model']  = 'milestones';
        $item['active'] = false;

        $items[] = $item;

        // 9. Tasks migration
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_TASKS');
        $item['model']  = 'tasks';
        $item['active'] = false;

        $items[] = $item;

        // 10. Forum migration
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_FORUM');
        $item['model']  = 'forum';
        $item['active'] = false;

        $items[] = $item;

        // 11. Time sheet migration
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_TIME');
        $item['model']  = 'time';
        $item['active'] = false;

        $items[] = $item;

        // 12. Prepare repo
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_PREP_REPO');
        $item['model']  = 'preprepo';
        $item['active'] = false;

        $items[] = $item;

        // 13. Migrate folders
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_REPODIRS');
        $item['model']  = 'repodirs';
        $item['active'] = false;

        $items[] = $item;

        // 14. Migrate notes
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_REPONOTES');
        $item['model']  = 'reponotes';
        $item['active'] = false;

        $items[] = $item;

        // 15. Migrate files
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_REPOFILES');
        $item['model']  = 'repofiles';
        $item['active'] = false;

        $items[] = $item;

        // 16. Prepare FM Pro migration
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_PREP_FMPRO');
        $item['model']  = 'prepfmpro';
        $item['active'] = false;

        $items[] = $item;

        // 17. Migrate note revisions
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_REPONOTEREVS');
        $item['model']  = 'reponoterevs';
        $item['active'] = false;

        $items[] = $item;

        // 18. Migrate file revisions
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_REPOFILEREVS');
        $item['model']  = 'repofilerevs';
        $item['active'] = false;

        $items[] = $item;

        // 19. Prepare Designs migration
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_PREP_DESIGNS');
        $item['model']  = 'prepdesigns';
        $item['active'] = false;

        $items[] = $item;

        // 20. Install Designs dummy extension
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_INSTALL_DESIGNS');
        $item['model']  = 'installdesigns';
        $item['active'] = false;

        $items[] = $item;

        // 21. Migrate Designs
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_DESIGNS');
        $item['model']  = 'designs';
        $item['active'] = false;

        $items[] = $item;

        // 22. Comments
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_COMMENTS');
        $item['model']  = 'comments';
        $item['active'] = false;

        $items[] = $item;


        // Set active process
        foreach ($items AS $i => $item)
        {
            if ($i == $proc) {
                $items[$i]['active'] = true;
            }
        }

        return $items;
    }


    public function process($limitstart = 0)
    {
        $model = $this->getProcessModel();

        if (!$model) return false;

        return $model->process($limitstart);
    }


    public function getTotal()
    {
        $model = $this->getProcessModel();

        if (!$model) return 0;

        return $model->getTotal();
    }


    public function getLimit()
    {
        $model = $this->getProcessModel();

        if (!$model) return 1;

        return $model->getLimit();
    }


    public function getSuccess()
    {
        $model = $this->getProcessModel();

        if (!$model) return false;

        return $model->getSuccess();
    }


    public function getLog()
    {
        $model = $this->getProcessModel();

        if (!$model) return array();

        return $model->getLog();
    }


    protected function getProcessModel()
    {
        static $cache = array();

        $app  = JFactory::getApplication();
        $proc = (int) $this->process;

        if (isset($cache[$proc])) {
            return $cache[$proc];
        }

        $processes = $this->getItems();

        $process    = $processes[$proc];
        $model_name = $process['model'];

        $cache[$proc] = JModelLegacy::getInstance($model_name, 'PFmigratorModel');

        return $cache[$proc];
    }
}
