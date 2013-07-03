<?php
/**
 * @package      com_pfmigrator
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2013 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();


// Access check (Super Admins only)
if (!JFactory::getUser()->authorise('core.admin')) {
	return JError::raiseWarning(403, JText::_('JERROR_ALERTNOAUTHOR'));
}

// Include dependencies
jimport('joomla.application.component.controller');
jimport('joomla.application.component.helper');

require_once JPATH_ADMINISTRATOR . '/components/com_pfmigrator/helpers/pfmigrator.php';

$controller = JControllerLegacy::getInstance('PFmigrator');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
