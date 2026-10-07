<?php
require __DIR__.'/common.php'; require_login();
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
require_csrf(); $_SESSION=[]; session_destroy(); redirect('/admin/login.php');
