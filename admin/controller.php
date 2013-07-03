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


    public function process()
    {
        $app  = JFactory::getApplication();
        $json = array();
        $ls   = JRequest::getUInt('limitstart', 0);
        $proc = JRequest::getUInt('process', 0);

        // Process request
        $model = JModelLegacy::getInstance('Migrate', 'PFmigratorModel');
        $model->setProcess($proc);
        $model->process($ls);

        // Prepare response
        $json['limitstart'] = $ls;
        $json['process']    = $proc;
        $json['success']    = $model->getSuccess();
        $json['limit']      = $model->getLimit();
        $json['total']      = $model->getTotal();
        $json['proclog']    = $model->getLog();

        $next  = ($json['limitstart'] + $json['limit']);

        if ($next > $json['total']) $next = $json['total'];

        $json['limitstart'] = $next;

        $this->sendJSON($json);
    }


    protected function sendJSON($data)
    {
        // Set the MIME type for JSON output.
        JFactory::getDocument()->setMimeEncoding('application/json');

        // Change the suggested filename.
        JResponse::setHeader('Content-Disposition', 'attachment;filename="migrate.json"');

        foreach($data AS $key => $value)
        {
            if (is_array($value)) {
                if (count($value) == 0) {
                    unset($data[$key]);
                }
            }
        }

        // Output the JSON data.
        echo json_encode($data);

        jexit();
    }
}
