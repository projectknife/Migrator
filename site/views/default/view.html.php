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


/**
 * Default view class.
 *
 */
class PFmigratorViewDefault extends JViewLegacy
{
    /**
     * Display the view
     *
     * @return    void    
     */
    public function display($tpl = null)
    {
        // Check for errors.
        $errors = $this->get('Errors');

        if (count($errors)) {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }

        // Display the view
        parent::display($tpl);
    }
}
