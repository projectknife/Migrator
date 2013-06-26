<?php
/**
 * @package      com_pfmigrator
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2013 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();


jimport('joomla.application.component.controlleradmin');


/**
 * Migration controller class.
 *
 */
class PFmigratorControllerMigrate extends JControllerAdmin
{
    /**
     * Proxy for getModel.
     *
     * @param     string    $name      The name of the model.
     * @param     string    $prefix    The prefix for the class name.
     * @param     array     $config    Configuration array for model. Optional.
     *
     * @return    object
     */
    public function getModel($name = 'Migrate', $prefix = 'PFmigratorModel', $config = array('ignore_request' => true))
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function process()
    {
        $app  = JFactory::getApplication();
        $json = array();

        $json['success']    = "true";
        $json['messages']   = array();
        $json['limitstart'] = JRequest::getUInt('limitstart', 0);
        $json['limit']      = JRequest::getUInt('limit', 30);
        $json['process']    = JRequest::getUInt('process', 0);
        $json['proclog']    = array();
        $json['data']       = array();

        $this->sendResponse($json);
    }


    protected function sendResponse($data)
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
