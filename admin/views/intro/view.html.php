<?php
/**
 * @package      com_pfmigrator
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2013 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();


jimport('joomla.application.component.view');
JHtml::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_pfmigrator/helpers/html');


/**
 * PF Migrator Intro View Class
 *
 */
class PFmigratorViewIntro extends JViewLegacy
{
    public $checklist = array();

    /**
     * Display the view
     *
     */
    public function display($tpl = null)
    {
        // Populate check list
        $this->checklist = $this->getChecklist();

        // Check for errors
        $errors = $this->get('Errors');

        if (count($errors)) {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }

        // Add toolbar
        if ($this->getLayout() !== 'modal') {
            $this->addToolbar();
        }

        // Render
        parent::display($tpl);
    }


    /**
     * Add the page title and toolbar.
     *
     * @return  void
     */
    protected function addToolbar()
    {
        JToolBarHelper::title(JText::_('COM_PFMIGRATOR'), 'config.png');

        $can_continue = true;

        foreach ($this->checklist AS $i => $item)
        {
            if ($item['uinput'] || $item['optional']) continue;

            if ($item['success'] == false) {
                $can_continue = false;
                break;
            }
        }

        if ($can_continue) {
            JToolbarHelper::custom('migrate', 'forward', 'forward', 'COM_PFMIGRATOR_START', false);
        }
        else {
            JToolbarHelper::custom('refresh', 'refresh', 'refresh', 'COM_PFMIGRATOR_REFRESH', false);
            JToolbarHelper::custom('refresh', 'cancel', 'cancel', 'COM_PFMIGRATOR_CANNOT_START', false);
        }
    }


    protected function getCheckList()
    {
        $list   = array();
        $config = JFactory::getConfig();
        $db     = JFactory::getDbo();
        $query  = $db->getQuery(true);

        // Check Projectfork
        $query->clear()
              ->select('extension_id')
              ->from('#__extensions')
              ->where('element = ' . $db->quote('com_projectfork'));

        $db->setQuery($query);
        $success = (int) $db->loadResult();

        $item = array();
        $item['name']     = 'pf_installed';
        $item['title']    = JText::_('COM_PFMIGRATOR_CHECK_PF_INSTALLED_TITLE');
        $item['desc']     = JText::_('COM_PFMIGRATOR_CHECK_PF_INSTALLED_DESC');
        $item['optional'] = false;
        $item['uinput']   = false;
        $item['success']  = $success;

        $list[] = $item;

        // Check site online status
        $item = array();
        $item['name']     = 'site_offline';
        $item['title']    = JText::_('COM_PFMIGRATOR_CHECK_SITE_OFFLINE_TITLE');
        $item['desc']     = JText::_('COM_PFMIGRATOR_CHECK_SITE_OFFLINE_DESC');
        $item['optional'] = true;
        $item['uinput']   = false;
        $item['success']  = (int) $config->get('offline');

        $list[] = $item;

        // Check backup
        $item = array();
        $item['name']     = 'backup';
        $item['title']    = JText::_('COM_PFMIGRATOR_CHECK_BACKUP_TITLE');
        $item['desc']     = JText::_('COM_PFMIGRATOR_CHECK_BACKUP_DESC');
        $item['optional'] = false;
        $item['uinput']   = true;
        $item['success']  = false;

        $list[] = $item;

        // Check backup tested
        $item = array();
        $item['name']     = 'backup_test';
        $item['title']    = JText::_('COM_PFMIGRATOR_CHECK_BACKUP_TEST_TITLE');
        $item['desc']     = JText::_('COM_PFMIGRATOR_CHECK_BACKUP_TEST_DESC');
        $item['optional'] = false;
        $item['uinput']   = true;
        $item['success']  = false;

        $list[] = $item;

        // Return list
        return $list;
    }
}
