<?php
require_once '../includes/recurring-functions.php';

// Bu script her gün bir kez çalıştırılmalı (örn: gece yarısı)

// Tekrarlayan işlemleri işle
processRecurringTransactions();

// Hatırlatıcıları kontrol et
checkReminders();

echo "Recurring transactions processed at " . date('Y-m-d H:i:s') . "\n";
?>