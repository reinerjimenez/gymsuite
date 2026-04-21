<?php
session_start();
session_destroy();
header("Location: /gymsuite/index.php");
exit;
