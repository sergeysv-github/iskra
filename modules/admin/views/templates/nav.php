<?php
use core\html;
?>

<div class="panel panel-default actions-nav-container clearfix">
<?php 
foreach (['left', 'right'] as $side)
{
    if (!isset($this->menu[$side])) {
        continue;
    }
    ?>
    <div class="btn-group<?php echo (($side == 'right') ? ' pull-right' : '') ?>">
    <?php
    foreach ($this->menu[$side] as $item) {
        if (!$item instanceof \core\nav_button) {
            continue;
        }
        ?>
        <?php echo '<a class="btn '; ?>
        <?php echo (!empty($item->classes) ? $item->classes : 'btn-default'); ?>
        <?php echo ($item->url == \core\app::$request->url ? ' active' : ''); ?>
        <?php echo '" href="'. $item->url .'"'; ?>
        <?php
        if (!empty($item->attrs) && is_array($item->attrs)) {
            foreach ($item->attrs as $attr => $value) {
                 echo ' '.$attr .'="'. html::encode($value) .'"';
            }
        }
        echo '>'.$item->label;
        echo '</a>';
    }
    ?>
    </div>

    <div class="<?php echo (($side == 'right') ? ' pull-right' : 'pull-left') ?>">
    <?php
    if (!empty($this->menu[$side])) {
        foreach ($this->menu[$side] as $item) {
            if ($item instanceof \core\nav_view && !empty($item->view)) {
                $item->view->render();
            }
        }
    }
    ?>
    </div>
<?php
}
?>
</div>
