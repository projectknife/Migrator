<?php
/**
 * @package      com_pfmigrator
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2013 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();


$ls    = '<span class="pfm-label" id="counter_limitstart">' . $this->limitstart . '</span>';
$t     = '<span class="pfm-label" id="counter_total">' . $this->total . '</span>';
$state = '<span id="jform_status" class="pfm-label">' . JText::_('COM_PFMIGRATOR_STATE_IDLE') . '</span>';

$record_status = JText::sprintf('COM_PFMIGRATOR_PROGRESS_RECORDS', $ls, $t);
$proc_status  = JText::sprintf('COM_PFMIGRATOR_PROGRESS_STATUS', $state);
?>
<p>
    <div style="float: left;"><?php echo $record_status; ?></div>
    <div style="float: right;"><?php echo $proc_status; ?></div>
    <div class="clr"></div>
</p>