<?php
defined('_JEXEC') or die();

foreach ($this->processes AS $i => $item) :
    $class = '';

    if ($item['active']) $class .= ' proc-active';
    if ($i > $this->process) $class .= ' proc-done';
    ?>
    <li id="proc_li_<?php echo $i; ?>">
        <span id="proc_<?php echo $i; ?>" class="proc<?php echo $class; ?>">
            <?php echo $item['title']; ?>
        </span>
    </li>
<?php endforeach; ?>