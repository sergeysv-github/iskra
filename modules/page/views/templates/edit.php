<?php use \core\html; ?>

<div class="page-header"><h1><?php echo _('New page') ?></h1></div>

<?php //echo $this->controller_nav ?>

<form class="container-fluid dynamic-validation" method="post">
    <div class="row">
        
        <div class="col-md-8">
            <div class="row">
                <div class="form-group col-xs-12">
                    <label class="control-label"><?php echo _('Name') ?></label>
                    <input type="text" class="form-control" name="entry[name]" value="<?php echo html::encode($this->entry->name) ?>">
                </div>
            </div>
        </div>

    </div>
    <div class="row">
        <div class="control-buttons col-xs-12">
            <button type="submit" class="btn btn-primary"><?php echo _('Save') ?></button>
            <a class="btn btn-default" href="<?php echo html::url('admin/pages') ?>"><?php echo _('Cancel') ?></a>
        </div>
    </div>
</form>