<!DOCTYPE html>
<head>
    <meta http-equiv="expires" content="never">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">    
    
    <link rel="stylesheet" href="modules/page/layouts/iskra/templates/css/bootstrap.min.css">
    <link rel="stylesheet" href="modules/page/layouts/iskra/templates/css/app.css">
    
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="modules/page/layouts/iskra/templates/js/bootstrap.min.js"></script>
    <script src="modules/page/layouts/iskra/templates/js/app.js"></script>
    <script><?php echo 'app_request = '.json_encode(\core\app::$request).';'; ?></script>
    
    <title>Quixotic</title>
</head>

<body id="login">
    
<div class="container">

<?php $this->view->render(); ?>

</div>
</body>
</html>