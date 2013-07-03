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
 * Rename Folders migration model
 *
 */
class PFmigratorModelRenameFolders extends JModelList
{
    protected $log     = array();
    protected $success = true;

    public function process($limitstart = 0)
    {
        $folders = $this->getFolders();

        $target_folder = (isset($folders[$limitstart]) ? $folders[$limitstart] : null);

        if (!$target_folder) return true;

        $path = str_replace(JPATH_SITE, '', $target_folder);

        // Check if the target folder exists
        if (!JFolder::exists($target_folder)) {
            $this->success = false;
            $this->log[] = JText::sprintf('COM_PFMIGRATOR_FOLDER_NOT_FOUND', $path);

            return false;
        }

        // Check if the destination folder exists
        if(JFolder::exists($target_folder . '3')) {
            if (!JFolder::delete($target_folder . '3')) {
                $this->success = false;
                $this->log[] = JText::sprintf('COM_PFMIGRATOR_FOLDER_EXISTS', $path . '3');

                return false;
            }
        }

        if (!JFolder::move($target_folder, $target_folder . '3')) {
            $this->success = false;
            $this->log[] = JText::sprintf('COM_PFMIGRATOR_FOLDER_RENAME_FAILED', $path);

            return false;
        }

        $this->log[] = JText::sprintf('COM_PFMIGRATOR_RENAME_FOLDER_SUCCESS', $path, $path . '3');

        return true;
    }


    public function getTotal()
    {
        return count($this->getFolders());
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


    protected function getFolders()
    {
        $folders = array(
            JPATH_SITE . '/components/com_projectfork',
            JPATH_ADMINISTRATOR . '/components/com_projectfork'
        );

        return $folders;
    }
}
