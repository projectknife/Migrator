<?php
defined('_JEXEC') or die();

foreach ($this->processes AS $i => $item) :
    $class = '';

    if ($item['active']) $class .= ' proc-active';
    if ($i < $this->process && $class == '') $class .= ' proc-done';
    ?>
    <li id="proc_li_<?php echo $i; ?>">
        <span id="proc_<?php echo $i; ?>" class="proc<?php echo $class; ?> hasTip"
            title="<?php echo $item['title'] . '::' . $item['desc']; ?>"
            style="cursor: help;"
            >
            <?php echo ($i + 1) . '. ' . $item['title']; ?>
        </span>
    </li>
<?php endforeach; ?>