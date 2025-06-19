<?php
// Session settings HARUS dipanggil SEBELUM session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Aktifkan jika menggunakan HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', 1800); // 30 menit
?>