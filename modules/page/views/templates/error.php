<h1 class="text-danger">Error!</h1>

<div id="message-container">
    <div class="alert alert-danger"><?php echo $this->message ?></div>
    <?php if (\core\http::session('userid_loginas')) { ?>
        <p class="small">You are logged in as <b><?php echo \core\app::$user->fullname ?></b>. 
        <a href="<?php echo \core\html::url('users/logoutas') ?>">Log back in as yourself.</a></p>
    <?php } ?>
</div>

<?php if (!empty($this->backtrace)) { ?>
    <div class="alert alert-warning"><?php echo $this->backtrace ?></div>
<?php } ?>
