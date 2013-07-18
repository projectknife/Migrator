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
        $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_RENAME_TABLES_DESC');
        $item['model']  = 'RenameTables';
        $item['active'] = false;

        $items[] = $item;

        // 2. Rename PF 3 folders
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_RENAME_FOLDERS');
        $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_RENAME_FOLDERS_DESC');
        $item['model']  = 'RenameFolders';
        $item['active'] = false;

        $items[] = $item;

        // 3. Unregister PF 3
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_UNREG_PF3');
        $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_UNREG_PF3_DESC');
        $item['model']  = 'UnregisterPF';
        $item['active'] = false;

        $items[] = $item;

        // 4. Install PF 4
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_INSTALL_PF');
        $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_INSTALL_PF_DESC');
        $item['model']  = 'InstallPF';
        $item['active'] = false;

        $items[] = $item;

        // 5. Migrate Project Categories
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_CATS');
        $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_CATS_DESC');
        $item['model']  = 'projectcats';
        $item['active'] = false;

        $items[] = $item;

        // 6. Migrate projects
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_PROJECTS');
        $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_PROJECTS_DESC');
        $item['model']  = 'projects';
        $item['active'] = false;

        $items[] = $item;

        // 7. Prepare Access migration
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_PREP_ACCESS');
        $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_PREP_ACCESS_DESC');
        $item['model']  = 'prepaccess';
        $item['active'] = false;

        $items[] = $item;

        // 8. Access migration
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_ACCESS');
        $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_ACCESS_DESC');
        $item['model']  = 'access';
        $item['active'] = false;

        $items[] = $item;

        // 9. Milestones migration
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_MILESTONES');
        $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_MILESTONES_DESC');
        $item['model']  = 'milestones';
        $item['active'] = false;

        $items[] = $item;

        // 10. Tasks migration
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_TASKS');
        $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_TASKS_DESC');
        $item['model']  = 'tasks';
        $item['active'] = false;

        $items[] = $item;

        // 11. Forum migration
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_FORUM');
        $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_FORUM_DESC');
        $item['model']  = 'forum';
        $item['active'] = false;

        $items[] = $item;

        // 12. Time sheet migration
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_TIME');
        $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_TIME_DESC');
        $item['model']  = 'time';
        $item['active'] = false;

        $items[] = $item;

        // 13. Prepare repo
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_PREP_REPO');
        $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_PREP_REPO_DESC');
        $item['model']  = 'preprepo';
        $item['active'] = false;

        $items[] = $item;

        // 14. Migrate folders
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_REPODIRS');
        $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_REPODIRS_DESC');
        $item['model']  = 'repodirs';
        $item['active'] = false;

        $items[] = $item;

        // 15. Migrate notes
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_REPONOTES');
        $item['desc']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_REPONOTES_DESC');
        $item['model']  = 'reponotes';
        $item['active'] = false;

        $items[] = $item;

        // 16. Migrate files
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_REPOFILES');
        $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_REPOFILES_DESC');
        $item['model']  = 'repofiles';
        $item['active'] = false;

        $items[] = $item;

        if (PFMigratorHelper::designsInstalled()) {
            // 17. Prepare FM Pro migration
            $item = array();
            $item['title']  = JText::_('COM_PFMIGRATOR_PROC_PREP_FMPRO');
            $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_PREP_FMPRO_DESC');
            $item['model']  = 'prepfmpro';
            $item['active'] = false;

            $items[] = $item;

            // 18. Migrate note revisions
            $item = array();
            $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_REPONOTEREVS');
            $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_REPONOTEREVS_DESC');
            $item['model']  = 'reponoterevs';
            $item['active'] = false;

            $items[] = $item;

            // 19. Migrate file revisions
            $item = array();
            $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_REPOFILEREVS');
            $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_REPOFILEREVS_DESC');
            $item['model']  = 'repofilerevs';
            $item['active'] = false;

            $items[] = $item;
        }


        if (PFMigratorHelper::designsInstalled()) {
            // 20. Prepare Designs migration
            $item = array();
            $item['title']  = JText::_('COM_PFMIGRATOR_PROC_PREP_DESIGNS');
            $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_PREP_DESIGNS_DESC');
            $item['model']  = 'prepdesigns';
            $item['active'] = false;

            $items[] = $item;

            // 21. Install Designs dummy extension
            $item = array();
            $item['title']  = JText::_('COM_PFMIGRATOR_PROC_INSTALL_DESIGNS');
            $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_INSTALL_DESIGNS_DESC');
            $item['model']  = 'installdesigns';
            $item['active'] = false;

            $items[] = $item;

            // 22. Migrate Designs
            $item = array();
            $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_DESIGNS');
            $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_DESIGNS_DESC');
            $item['model']  = 'designs';
            $item['active'] = false;

            $items[] = $item;
        }

        // 23. Comments
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_COMMENTS');
        $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_COMMENTS_DESC');
        $item['model']  = 'comments';
        $item['active'] = false;

        $items[] = $item;

        // 24. Attachments
        $item = array();
        $item['title']  = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_ATTACHMENTS');
        $item['desc']   = JText::_('COM_PFMIGRATOR_PROC_MIGRATE_ATTACHMENTS_DESC');
        $item['model']  = 'attachments';
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
