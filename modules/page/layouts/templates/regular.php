<?php 
use core\app; 
use core\html; 
?>
<!DOCTYPE html>
<head>
    <meta http-equiv="expires" content="never">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">    
    
    <link href='//fonts.googleapis.com/css?family=Oswald:300,400,700|Open+Sans:400,700,300' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="modules/page/layouts/templates/css/bootstrap.min.css">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="modules/page/layouts/templates/css/app.css">
    <link rel="stylesheet" href="modules/page/layouts/templates/css/bootstrap-toggle.min.css" >
    
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="modules/page/layouts/templates/js/bootstrap.min.js"></script>
    <script src="modules/page/layouts/templates/js/app.js"></script>
    <script src="modules/page/layouts/templates/js/bootstrap-toggle.min.js"></script>
    <script><?php echo 'app_request = '.json_encode(app::$request).';'; ?></script>
    
    <title><?php echo (!empty($this->title) ? $this->title.' - Iskra' : 'Iskra') ?></title>
</head>

<body>
    
<?php //echo new \views\navs\main_nav(); ?>
<?php echo 'menu here' ?>

<div class="container-fluid">
    
    <?php if (\core\http::session('userid_loginas')) { ?>
        <div class="row">
            <div class="alert alert-info small">You are logged in as <b><?php echo app::$user->fullname ?></b>. <a href="<?php echo html::url('users/logoutas') ?>">Log back in as yourself.</a></div>
        </div>
    <?php } ?>
    
    <div class="row">

        <div class="col-md-2 side-menu">
            <div id="message-container"><?php echo html::get_messages() ?></div>
            <?php echo $this->side_nav ?>
        </div>
        <div class="col-md-8">
            
            <?php $this->view->render(); ?>
            
        </div>
        <div class="col-md-2"><?php echo $this->nav_instance ?></div>
    </div>
    <div class="row footer">
        <!--<div class="footer col-md-8 col-md-offset-2">-->
            <p class="small pull-right">&copy; 2016 <a href="http://www.androgogic.com" target="_blank">Androgogic Pty Ltd.</a></p>
    </div>
</div>
</body>
</html>            