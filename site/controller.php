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


/**
 * Component main controller
 *
 * @see    jcontroller    
 */
class PFmigratorController extends JControllerLegacy
{
    /**
     * The default view
     *
     * @var    string    
     */
    protected $default_view = 'default';


    /**
     * Displays the current view
     *
     * @param     boolean        $cachable     If true, the view output will be cached  (Not Used!)
     * @param     array          $urlparams    An array of safe url parameters and their variable types (Not Used!)
     *
     * @return    jcontroller                  A JController object to support chaining.
     */
    public function display($cachable = false, $urlparams = false)
    {
        $view = JRequest::getCmd('view');

        // Inject default view if not set
        if (empty($view)) {
            JRequest::setVar('view', $this->default_view);
        }

        // Display the view
        parent::display($cachable, $urlparams);

        // Return own instance for chaining
        return $this;
    }
}
