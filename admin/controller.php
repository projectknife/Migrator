<?php
/**
 * @package      com_pfmigrator
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2013 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();


jimport('joomla.application.component.controller');


class PFmigratorController extends JControllerLegacy
{
    protected $default_view = 'intro';


    public function display($cachable = false, $urlparams = false)
    {
        parent::display($cachable, $urlparams);

        return $this;
    }


    public function migrate()
    {
        $app    = JFactory::getApplication();
        $passed = JRequest::getUint('check_passed');

        if (!$passed) {
            $app->enqueueMessage(JText::_('COM_PFMIGRATOR_WARNING_CHECK'), 'error');
            $app->redirect('index.php?option=com_pfmigrator&view=intro');

            return $this;
        }

        $app->enqueueMessage(JText::_('COM_PFMIGRATOR_WARNING_NO_LEAVE'));
        $app->redirect('index.php?option=com_pfmigrator&view=migrate');

        return $this;
    }
}
