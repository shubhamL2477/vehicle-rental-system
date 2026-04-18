<?php

require_once __DIR__ . '/../includes/bootstrap.php';

logout_user();
session_start();
set_flash('You have been logged out.', 'info');
redirect('login.php');


