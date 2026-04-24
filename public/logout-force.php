<?php
session_start();
session_destroy();
header('Location: /auto-parts-management/public/index.php?action=login');
exit;