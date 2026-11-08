<?php
require_once __DIR__ . '/../includes/auth.php';

require_post();

logout_user();

redirect_with('success', 'You have been logged out.', 'login.php');
