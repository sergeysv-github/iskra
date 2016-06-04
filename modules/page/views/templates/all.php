<?php
use \core\html;
?>

<div class="page-header">
	<h1>Pages</h1>
</div>

<?php echo $this->nav ?>

<?php 
if (empty($this->count)) { 
    echo html::display_message(_('Nothing found.'), html::MSG_WARNING);
    return; 
} 
?>

<?php 
if (!empty(\core\app::$request->args)) {
    echo html::display_message(html::plural($this->count, 'entr', 'y', 'ies').' found.', html::MSG_NOTICE); 
}
?>
    
<table class="table table-condensed table-hover">
<thead>
<tr>
    <th><?php echo _('Name') ?></th>
    <th></th>
</tr>
</thead>
<tbody>
<?php foreach ($this->entries as $entry) { ?>
    <tr>
        <td><?php echo $entry->name; ?></td>
        <td>
            <div class="pull-right btn-group">
                <?php if (\core\app::$user->has_access_to('page/edit')) { ?>
                    <a class="btn btn-xs btn-default" href="<?php echo html::url('page/edit', $entry->id) ?>"><?php echo _('Edit') ?></a>
                <?php } ?>
                <?php if (\core\app::$user->has_access_to('page/delete')) { ?>
                    <a class="btn btn-xs btn-danger" href="<?php echo html::url('page/delete', $entry->id) ?>" onclick="return confirm('Are you sure?')"><?php echo _('Delete') ?></a>
                <?php } ?>
            </div>
        </td>
    </tr>
<?php } ?>
</table>
</tbody>
