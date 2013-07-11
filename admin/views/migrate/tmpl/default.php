<?php
/**
 * @package      com_pfmigrator
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2013 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();


JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');
JHtml::_('stylesheet', 'com_pfmigrator/pfmigrator/styles.css', false, true, false, false, false);
JHtml::_('script', 'com_pfmigrator/pfmigrator/process.js', false, true, false, false, false);

$script = array();
$script[] = "window.addEvent('domready', function() {";
$script[] = "PFmigrator.process({";
$script[] = "txt_idle: '" . JText::_('COM_PFMIGRATOR_STATE_IDLE') . "',";
$script[] = "txt_proc: '" . JText::_('COM_PFMIGRATOR_STATE_PROC') . "',";
$script[] = "txt_err: '" . JText::_('COM_PFMIGRATOR_STATE_ERROR') . "',";
$script[] = "txt_cpl: '" . JText::_('COM_PFMIGRATOR_STATE_COMPLETE') . "',";
$script[] = "txt_upd: '" . JText::_('COM_PFMIGRATOR_STATE_UPDATE') . "'";
$script[] = "});";
$script[] = "});";

$cdata = PFmigratorHelper::getCustomData();

if (!$cdata->get('process')) {
    JFactory::getDocument()->addScriptDeclaration(implode('', $script));

    $cdata->set('process', 1);
    PFmigratorHelper::setCustomData($cdata);
}
?>
<form action="<?php echo JRoute::_('index.php?option=com_pfmigrator'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate" autocomplete="off">
    <input type="hidden" name="view" value="migrate" />
    <input type="hidden" id="jform_task" name="task" value="" />
    <input type="hidden" id="jform_process" name="process" value="<?php echo $this->process; ?>" />
    <input type="hidden" id="jform_processes" name="processes" value="<?php echo count($this->processes); ?>" />
    <input type="hidden" id="jform_limit" name="limit" value="<?php echo $this->limit; ?>" />
    <input type="hidden" id="jform_limitstart" name="limitstart" value="<?php echo $this->limitstart; ?>" />
    <input type="hidden" id="jform_total" name="total" value="<?php echo $this->total; ?>" />
    <input type="hidden" id="jform_stop" name="stop" value="0" />

    <?php echo JHtml::_('form.token'); ?>

    <div class="width-60 fltlft">
        <div class="width-100">
            <fieldset class="adminform">
                <legend><?php echo JText::_('COM_PFMIGRATOR_FIELDSET_CURRENT_PROGRESS'); ?></legend>
                <div id="jform_progress">
                    <?php echo $this->loadTemplate('bar'); ?>
                </div>
                <hr />
                <div id="jform_counter">
                    <?php echo $this->loadTemplate('counter'); ?>
                </div>
                <div id="jform_progress_done" style="display: none;">
                    <h3><?php echo JText::_('COM_PFMIGRATOR_COMPLETE');?></h3>
                    <p><a href="index.php?option=com_projectfork"><?php echo JText::_('COM_PFMIGRATOR_CONTINUE'); ?></a></p>
                </div>
                <div id="jform_exception" style="display: none;">
                    <h3><?php echo JText::_('COM_PFMIGRATOR_EXCEPTION'); ?></h3>
                    <strong><?php echo JText::_('COM_PFMIGRATOR_RSP'); ?></strong>
                    <div id="jform_exception_rsp"></div>
                    <strong><?php echo JText::_('COM_PFMIGRATOR_RSP_ERROR'); ?></strong>
                    <div id="jform_exception_rsp_err"></div>
                </div>
            </fieldset>
            <fieldset class="adminform">
                <legend><?php echo JText::_('COM_PFMIGRATOR_FIELDSET_PROCESS_LOG'); ?></legend>
                <div id="jform_log_container">
                    <ul id="jform_log" class="unstyled">
                        <?php echo $this->loadTemplate('log'); ?>
                    </ul>
                </div>
            </fieldset>
        </div>
    </div>

    <div class="width-40 fltrt">
        <div class="width-100">
            <fieldset class="adminform">
                <legend><?php echo JText::_('COM_PFMIGRATOR_FIELDSET_OVERALL_PROGRESS'); ?></legend>
                <ul id="jform_overall_progress" class="unstyled">
                    <?php echo $this->loadTemplate('prog'); ?>
                </ul>
            </fieldset>
        </div>
    </div>

    <div class="clr"></div>
</form>
