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
 * PF Migrator Migrate View Class
 *
 */
class PFmigratorViewMigrate extends JViewLegacy
{
    protected $process = 0;
    protected $limit = 0;
    protected $limitstart = 0;
    protected $total = 0;

    protected $log = array();
    protected $processes = array();

    /**
     * Display the view
     *
     */
    public function display($tpl = null)
    {
        $app = JFactory::getApplication();

        $this->process    = 0;
        $this->limitstart = 0;
        $this->limit      = $this->get('Limit');
        $this->total      = $this->get('Total');
        $this->processes  = $this->get('Items');

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

        JRequest::setVar('hidemainmenu', 1);

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
    }
}
