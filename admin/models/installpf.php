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
 * Install Projectfork 4.1 migration model
 *
 */
class PFmigratorModelInstallPF extends JModelList
{
    protected $log     = array();
    protected $success = true;

    public function process($limitstart = 0)
    {
        require_once JPATH_ADMINISTRATOR . '/components/com_installer/helpers/installer.php';
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_installer/models');

        // Path to the pf 4 package
        $pkg  = JPATH_ADMINISTRATOR . '/components/com_pfmigrator/_install/pkg_projectfork_4.1.0.zip';
        $dest = JFactory::getConfig()->get('tmp_path') . '/pkg_projectfork_4.1.0.zip';

        // Check if the package exists
        if (!file_exists($pkg)) {
            $this->success = false;
            $this->log[] = JText::_('COM_PFMIGRATOR_PKG_NOT_FOUND');

            return false;
        }

        // Copy package to tmp dir
        if (!JFile::copy($pkg, $dest)) {
            $this->success = false;
            $this->log[] = JText::_('COM_PFMIGRATOR_PKG_COPY_FAILED');

            return false;
        }

        // Unpack the package file
        $package = JInstallerHelper::unpack($dest);

        if (!$package) {
            if (JFile::exists($dest)) JFile::delete($dest);

            $this->success = false;
            $this->log[] = JText::_('COM_PFMIGRATOR_PKG_EXTRACT_FAILED');

            return false;
        }

        $installer = JInstaller::getInstance();

        // Install the package
        if (!$installer->install($package['dir'])) {
            if (JFile::exists($dest)) JFile::delete($dest);

            $this->success = false;
            $this->log[] = JText::_('COM_PFMIGRATOR_PKG_INSTALL_FAILED') . ' ' . $installer->message . ' ' . $installer->get('extension_message');

            return false;
        }

        // Cleanup the install files
        if (!is_file($package['packagefile'])) {
            $config = JFactory::getConfig();
            $package['packagefile'] = $config->get('tmp_path') . '/' . $package['packagefile'];
        }

        JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

        $this->log[] = JText::_('COM_PFMIGRATOR_PKG_INSTALL_SUCCESS');

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
