<?php
// Cửa duy nhất của hệ thống: mọi địa chỉ đều chạy qua file này.
require __DIR__ . '/../core/bootstrap.php';
Router::dispatch();
