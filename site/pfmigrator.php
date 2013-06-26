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
jimport('joomla.application.component.helper');

$controller = JControllerLegacy::getInstance('PFmigrator');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
