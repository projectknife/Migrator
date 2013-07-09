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
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.archive');


/**
 * Install Designs Extension model
 *
 */
class PFmigratorModelInstallDesigns extends JModelList
{
    protected $log     = array();
    protected $success = true;

    public function process($limitstart = 0)
    {
        if (!PFMigratorHelper::designsInstalled()) {
            $this->log[] = JText::_('COM_PFMIGRATOR_DESIGNS_NOT_INSTALLED');
            return true;
        }

        require_once JPATH_ADMINISTRATOR . '/components/com_installer/helpers/installer.php';
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_installer/models');

        // Path to the package
        $pkg  = JPATH_ADMINISTRATOR . '/components/com_pfmigrator/_install/com_pfdesigns_4.0.1.zip';
        $dest = JFactory::getConfig()->get('tmp_path') . '/com_pfdesigns_4.0.1.zip';

        // Check if the package exists
        if (!file_exists($pkg)) {
            $this->success = false;
            $this->log[] = JText::_('COM_PFMIGRATOR_PKG_DESIGNS_NOT_FOUND');

            return false;
        }

        // Copy package to tmp dir
        if (!JFile::copy($pkg, $dest)) {
            $this->success = false;
            $this->log[] = JText::_('COM_PFMIGRATOR_PKG_DESIGNS_COPY_FAILED');

            return false;
        }

        // Unpack the package file
        $package = JInstallerHelper::unpack($dest);

        if (!$package) {
            if (JFile::exists($dest)) JFile::delete($dest);

            $this->success = false;
            $this->log[] = JText::_('COM_PFMIGRATOR_PKG_DESIGNS_EXTRACT_FAILED');

            return false;
        }

        $installer = JInstaller::getInstance();

        // Install the package
        if (!$installer->install($package['dir'])) {
            if (JFile::exists($dest)) JFile::delete($dest);

            $this->success = false;
            $this->log[] = JText::_('COM_PFMIGRATOR_PKG_DESIGNS_INSTALL_FAILED');

            return false;
        }

        // Cleanup the install files
        if (!is_file($package['packagefile'])) {
            $config = JFactory::getConfig();
            $package['packagefile'] = $config->get('tmp_path') . '/' . $package['packagefile'];
        }

        JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

        // Update the config
        $path = $this->getUploadPath();

        if (!$path) {
            $this->success = false;
            $this->log[] = JText::_('COM_PFMIGRATOR_PREPDESIGNS_UPLOAD_DIR_ERROR');

            return false;
        }

        $query = $this->_db->getQuery(true);

        $query->select($this->_db->quoteName('params'))
              ->from('#__extensions')
              ->where($this->_db->quoteName('type') . ' = ' . $this->_db->quote('component'))
              ->where($this->_db->quoteName('element') . ' = ' . $this->_db->quote('com_pfdesigns'));

        $this->_db->setQuery($query, 0, 1);
        $params = $this->_db->loadResult();

        $base_path = str_replace(JPATH_SITE . DS, '', $path);

        $registry = new JRegistry();
        $registry->loadString($params);
        $registry->set('design_basepath', $base_path);

        $attribs = strval($registry);

        $query->clear();
        $query->update('#__extensions')
              ->set($this->_db->quoteName('params') . ' = ' . $this->_db->quote($attribs))
              ->where($this->_db->quoteName('type') . ' = ' . $this->_db->quote('component'))
              ->where($this->_db->quoteName('element') . ' = ' . $this->_db->quote('com_pfdesigns'));

        $this->_db->setQuery($query);

        if (!$this->_db->execute()) {
            $this->success = false;
            $this->log[] = $this->_db->getError();
            return false;
        }

        $this->log[] = JText::_('COM_PFMIGRATOR_PKG_DESIGNS_INSTALL_SUCCESS');

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


    protected function getUploadPath()
    {
        static $path = null;

        if (!is_null($path)) return $path;

        $cfg_path = PFmigratorHelper::getConfig('upload_path', 'design_review');

        if (empty($cfg_path)) {
            $path = false;
            return $path;
        }

        if (substr($cfg_path, 0, 1) != '/' && substr($cfg_path, 0, 1) != '\\') {
            $cfg_path = '/' . $cfg_path;
        }

        if (substr($cfg_path, -1) == '/' || substr($cfg_path, -1) == '\\') {
            $cfg_path = substr($cfg_path, 0, -1);
        }

        $path = JPath::clean(JPATH_SITE . $cfg_path);

        if (!JFolder::exists($path)) {
            if (!JFolder::create($path)) {
                $path = false;
            }
        }

        return $path;
    }
}
