<?php
  include('../config.php');
  $output = shell_exec("DB_HOST={$config['db_host']} python main.py");
  header('Location: /');
?>
