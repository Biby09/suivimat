<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

session_destroy();
header("Location: /auth/login/");
exit();