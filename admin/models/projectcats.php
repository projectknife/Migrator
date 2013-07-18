<?php
/**
 * @package      com_pfmigrator
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2013 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();


jimport('joomla.application.component.modellist');
JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/tables');
JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/models', 'CategoriesModel');


/**
 * Project Categories migration model
 *
 */
class PFmigratorModelProjectCats extends JModelList
{
    protected $log     = array();
    protected $success = true;

    public function process($limitstart = 0)
    {
        $cat_str = PFMigratorHelper::getConfig('cats', 'projects');

        if (empty($cat_str)) return true;

        $items  = explode("\n", $cat_str);
        $titles = array();

        foreach($items AS $str)
        {
            $str = trim($str);
            $pts = explode(':', $str);

            $title = trim(htmlspecialchars($pts[0], ENT_QUOTES));
            $alias = JFilterOutput::stringURLSafe($title);

            if (empty($title) || empty($alias)) continue;

            if ($this->migrate($title, $alias)) {
                $titles[] = $title;
            }
        }

        $titles = implode(', ', $titles);
        $this->log[] = JText::sprintf('COM_PFMIGRATOR_MIGRATE_PCATS_SUCCESS', $titles);

        return true;
    }


    protected function migrate($title, $alias)
    {
        $config = JFactory::getConfig();
        $model  = JModelLegacy::getInstance('Category', 'CategoriesModel');

        $model->setState('category.new', true);
        $model->setState('category.id', null);

        $data = array();
        $data['title']     = $title;
        $data['alias']     = $alias;
        $data['extension'] = 'com_pfprojects';
        $data['access']    = $config->get('access', 1);
        $data['parent_id'] = 1;
        $data['published'] = 1;

        if (!$model->save($data)) {
            $this->log[] = $model->getError();
            return false;
        }

        $cdata = PFmigratorHelper::getCustomData();

        $cdata->set('cat-' . $alias, $model->getState('category.id'));

        PFmigratorHelper::setCustomData($cdata);

        return true;
    }


    public function getTotal()
    {
        return 1;
    }


    public function getLimit()
    {
        return 1;
    }


    public function getLog()
    {
        return $this->log;
    }


    public function getSuccess()
    {
        return $this->success;
    }
}
