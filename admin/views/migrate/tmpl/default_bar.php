<?php
/**
 * @package      com_pfmigrator
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2013 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();


// Calculate progress
$progress = ($this->total == 0) ? 0 : round($this->limitstart * (100 / $this->total));
?>
<div class="pfm-progress pfm-active" id="jform_prgcontainer">
    <div class="pfm-bar pfm-bar-success" id="progress_bar" style="width: <?php echo ($progress > 0) ? $progress . "%": "24px";?>">
        <span class="pfm-label pfm-label-success fltrt" id="progress_label">
            <?php echo $progress;?>%
        </span>
    </div>
</div>