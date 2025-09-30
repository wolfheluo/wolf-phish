<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/utils/Session.php';

Session::start();

echo "Session Debug:\n";
echo "Session ID: " . session_id() . "\n";
echo "Session data: " . print_r($_SESSION, true) . "\n";
echo "User ID: " . Session::getUserId() . "\n";
echo "Is Logged In: " . (Session::isLoggedIn() ? 'Yes' : 'No') . "\n";
echo "User Role: " . Session::getUserRole() . "\n";