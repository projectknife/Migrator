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
?>
<script type="text/javascript">
Joomla.submitbutton = function(task)
{
    if (task == 'refresh') {
        Joomla.submitform(task, document.getElementById('adminForm'));
    }
    else {
        var passed = true;
        <?php
        foreach ($this->checklist AS $i => $item)
        {
            if ($item['optional'] || $item['success']) {
                continue;
            }
            ?>
            if ($('jform_check_<?php echo $i; ?>').checked == false) passed = false;
            <?php
        }
        ?>
        if (passed) {
            $('jform_check_passed').value = '1';
            Joomla.submitform(task, document.getElementById('adminForm'));
        }
        else {
            alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
        }
    }
}
</script>
<form action="<?php echo JRoute::_('index.php?option=com_pfmigrator'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate" autocomplete="off">
    <div class="width-60 fltlft">
        <div class="width-100">
            <fieldset class="adminform">
                <legend><?php echo JText::_('COM_PFMIGRATOR_INTRO_WELCOME'); ?></legend>
                <p><?php echo JText::_('COM_PFMIGRATOR_INTRO_TXT_1'); ?></p>
                <h3><?php echo JText::_('COM_PFMIGRATOR_INTRO_BACKUP'); ?></h3>
                <p>
                    <?php
                        echo JText::_('COM_PFMIGRATOR_INTRO_TXT_2') . ' ';
                        echo JText::_('COM_PFMIGRATOR_INTRO_TXT_3') . ' ';
                        echo JText::_('COM_PFMIGRATOR_INTRO_TXT_4');
                    ?>
                </p>
                <h3><?php echo JText::_('COM_PFMIGRATOR_INTRO_MIGRATION_PROCESS'); ?></h3>
                <p>
                    <?php
                        echo JText::_('COM_PFMIGRATOR_INTRO_TXT_7') . ' ';
                        echo JText::_('COM_PFMIGRATOR_INTRO_TXT_8') . ' ';
                    ?>
                </p>
                <h3><?php echo JText::_('COM_PFMIGRATOR_INTRO_AFTER_THE_MIGRATION'); ?></h3>
                <p>
                    <?php
                        echo JText::_('COM_PFMIGRATOR_INTRO_TXT_9') . ' ';
                        echo JText::_('COM_PFMIGRATOR_INTRO_TXT_10') . ' ';
                    ?>
                </p>
                <h3><?php echo JText::_('COM_PFMIGRATOR_INTRO_TROUBLESHOOTING'); ?></h3>
                <p>
                    <?php
                        echo JText::_('COM_PFMIGRATOR_INTRO_TXT_5') . ' ';
                        echo JText::_('COM_PFMIGRATOR_INTRO_TXT_6') . ' ';
                    ?>
                </p>
            </fieldset>
        </div>
    </div>

    <div class="width-40 fltrt">
        <div class="width-100">
            <fieldset class="adminform">
                <legend><?php echo JText::_('COM_PFMIGRATOR_FIELDSET_CHECKLIST'); ?></legend>
                <table class="adminlist">
                    <tbody>
                        <?php
                        foreach ($this->checklist AS $i => $item)
                        {
                            ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td style="width: 20px;" class="nowrap">
                                    <?php
                                        if (!$item['optional']) {
                                            $attr = ' class="required validate-checkbox" aria-required="true" required="required"';
                                        }
                                        else {
                                            $attr = '';
                                        }

                                        if ($item['uinput']) :
                                        ?>
                                        <input id="jform_check_<?php echo $i; ?>" name="check_<?php echo $i; ?>" type="checkbox"<?php if ($item['success']) echo ' checked="checked"'; echo $attr; ?>/>
                                        <?php
                                    else :
                                        echo '<span class="icon-16-' . ($item['success'] ? 'allowed' : 'unset') . '">&nbsp;</span>';
                                        ?>
                                        <input style="display: none;" id="jform_check_<?php echo $i; ?>" name="check_<?php echo $i; ?>" type="checkbox" disabled="disabled"<?php if ($item['success']) echo ' checked="checked"'; echo $attr; ?>/>
                                        <?php
                                    endif; ?>
                                </td>
                                <td>
                                    <label class="hasTip<?php if (!$item['optional']) echo ' required'; ?>"
                                        title="<?php echo $item['title'] . '::' . $item['desc']; ?>"
                                        style="cursor: help;"
                                        id="jform_check_<?php echo $i; ?>-lbl"
                                        for="jform_check_<?php echo $i; ?>"
                                    >
                                        <?php
                                            echo $item['title'];

                                            if ($item['optional']) {
                                                echo  ' (' . JText::_('COM_PFMIGRATOR_OPTIONAL') . ')';
                                            }
                                        ?>
                                    </label>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </fieldset>
            <fieldset class="adminform">
                <legend><?php echo JText::_('COM_PFMIGRATOR_FIELDSET_PROCESSES_PREVIEW'); ?></legend>
                <p><?php echo JText::_('COM_PFMIGRATOR_FIELDSET_PROCESSES_PREVIEW_DESC'); ?></p>
                <table class="adminlist">
                    <tbody>
                        <?php
                        foreach ($this->processes AS $i => $item)
                        {
                            ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td style="width: 20px;" class="nowrap">
                                    <?php echo $i + 1;?>.
                                </td>
                                <td>
                                    <label class="hasTip" style="cursor: help;"
                                        title="<?php echo $item['title'] . '::' . $item['desc']; ?>"
                                        >
                                        <?php echo $item['title']; ?>
                                    </label>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </fieldset>
        </div>
    </div>

    <div class="clr"></div>

    <input type="hidden" name="option" value="com_pfmigrator" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="check_passed" id="jform_check_passed" value="0" />
    <?php echo JHtml::_('form.token'); ?>
</form>
