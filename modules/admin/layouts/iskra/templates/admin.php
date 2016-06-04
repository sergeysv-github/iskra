<?php
use \core\html;
?>
<!DOCTYPE html>
<head>
    <meta http-equiv="expires" content="never">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">    
    
    <link rel="stylesheet" href="modules/page/layouts/iskra/templates/css/bootstrap.min.css">
    <link rel="stylesheet" href="modules/admin/layouts/iskra/templates/css/admin.css">
    
    <script src="modules/page/layouts/iskra/templates/js/jquery.js"></script>
    <script src="modules/page/layouts/iskra/templates/js/bootstrap.min.js"></script>
    <script src="modules/admin/layouts/iskra/templates/js/admin.js"></script>
    
    <title><?php echo (!empty($this->title) ? $this->title.' - Iskra' : 'Iskra') ?></title>
</head>

<body>

<div class="admin-nav">
	<ul class="nav nav-pills nav-stacked">
		<li class="active"><a href="<?php echo html::url('admin/home') ?>">Home</a></li>
		<li><a href="<?php echo html::url('page/all') ?>">Pages</a></li>
	</ul>
</div>
	
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 col-md-offset-2 admin-body">
			<div class="row">
			
            <?php $this->view->render(); ?>
			
			</div>
        </div>
    </div>
</div>
</body>
</html>