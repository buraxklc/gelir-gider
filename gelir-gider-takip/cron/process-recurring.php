<?php
require_once '../includes/recurring-functions.php';

// Tekrarlayan işlemleri işle
processRecurringTransactions();

echo "Recurring transactions processed at " . date('Y-m-d H:i:s') . "\n";
?>