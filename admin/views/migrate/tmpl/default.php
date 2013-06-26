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
$script[] = "PFmigrator.process();";
$script[] = "});";

JFactory::getDocument()->addScriptDeclaration(implode('', $script));
?>
<form action="<?php echo JRoute::_('index.php?option=com_pfmigrator&view=migrate'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate" autocomplete="off">
    <input type="hidden" name="option" value="com_pfmigrator" />
    <input type="hidden" name="view" value="migrate" />
    <input type="hidden" id="jform_task" name="task" value="" />
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
            </fieldset>
            <fieldset class="adminform">
                <legend><?php echo JText::_('COM_PFMIGRATOR_FIELDSET_PROCESS_LOG'); ?></legend>
                <ul id="jform_log" class="unstyled">
                    <?php echo $this->loadTemplate('log'); ?>
                </ul>
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

    <input type="hidden" name="option" value="com_pfmigrator" />
</form>
