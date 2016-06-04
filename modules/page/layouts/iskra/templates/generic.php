<!DOCTYPE html>
<head>
    <meta http-equiv="expires" content="never">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">    
    
    <link rel="stylesheet" href="modules/page/layouts/iskra/templates/css/bootstrap.min.css">
    <link rel="stylesheet" href="modules/page/layouts/iskra/templates/css/app.css">
    
    <title><?php echo (!empty($this->title) ? $this->title.' - Iskra' : 'Iskra') ?></title>
</head>

<body>

<div class="container-fluid">
    
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
			
            <?php $this->view->render(); ?>
			
        </div>
    </div>
</div>
</body>
</html>