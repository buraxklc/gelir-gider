<?php
require_once 'includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Dışa aktarma parametrelerini al
$format = $_GET['format'] ?? 'csv';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$type = $_GET['type'] ?? '';
$categoryId = $_GET['category_id'] ?? '';

// Filtreleri uygula
$filters = [
    'start_date' => $startDate,
    'end_date' => $endDate,
    'type' => $type,
    'category_id' => $categoryId
];

// İşlemleri getir
$transactions = getTransactions($userId, $filters);

// Dosya adını oluştur
$filenameParts = [
    'islemler',
    $startDate ? date('d-m-Y', strtotime($startDate)) : 'baslangic',
    $endDate ? date('d-m-Y', strtotime($endDate)) : 'bitis',
    $type ? ($type == 'income' ? 'gelir' : 'gider') : 'tumu'
];
$filename = implode('_', $filenameParts);

// İstenilen formatta dışa aktar
switch ($format) {
    case 'csv':
        exportCSV($transactions, $filename);
        break;
        
    case 'excel':
        exportExcel($transactions, $filename);
        break;
        
    case 'pdf':
        exportPDF($transactions, $filename, $startDate, $endDate);
        break;
        
    default:
        // Geçersiz format
        header('Location: transactions.php');
        exit;
}

// CSV olarak dışa aktar
function exportCSV($transactions, $filename) {
    // Çıktı başlıklarını ayarla
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    // Çıktı dosyası olarak PHP çıktı akışını kullan
    $output = fopen('php://output', 'w');
    
    // BOM (Byte Order Mark) ekle - Excel'de Türkçe karakterleri düzgün göstermek için
    fwrite($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Başlık satırını ekle
    fputcsv($output, [
        'Tarih',
        'Kategori',
        'Açıklama',
        'Tip',
        'Tutar'
    ]);
    
    // İşlemleri ekle
    foreach ($transactions as $transaction) {
        fputcsv($output, [
            date('d.m.Y', strtotime($transaction['transaction_date'])),
            $transaction['category_name'] ?? 'Kategori Yok',
            $transaction['description'],
            $transaction['type'] == 'income' ? 'Gelir' : 'Gider',
            $transaction['amount']
        ]);
    }
    
    fclose($output);
    exit;
}

// Excel olarak dışa aktar
function exportExcel($transactions, $filename) {
    // PhpSpreadsheet kütüphanesini kullan veya basit HTML tablosu oluştur
    
    // Bu örnek, PhpSpreadsheet olmadan basit bir Excel dosyası oluşturur
    // Not: Gerçek bir projede PhpSpreadsheet kullanmanız önerilir
    
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    
    echo '
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <!--[if gte mso 9]>
        <xml>
            <x:ExcelWorkbook>
                <x:ExcelWorksheets>
                    <x:ExcelWorksheet>
                        <x:Name>İşlemler</x:Name>
                        <x:WorksheetOptions>
                            <x:DisplayGridlines/>
                        </x:WorksheetOptions>
                    </x:ExcelWorksheet>
                </x:ExcelWorksheets>
            </x:ExcelWorkbook>
        </xml>
        <![endif]-->
    </head>
    <body>
        <table border="1">
            <tr>
                <th>Tarih</th>
                <th>Kategori</th>
                <th>Açıklama</th>
                <th>Tip</th>
                <th>Tutar</th>
            </tr>';
    
    foreach ($transactions as $transaction) {
        echo '<tr>';
        echo '<td>' . date('d.m.Y', strtotime($transaction['transaction_date'])) . '</td>';
        echo '<td>' . htmlspecialchars($transaction['category_name'] ?? 'Kategori Yok') . '</td>';
        echo '<td>' . htmlspecialchars($transaction['description']) . '</td>';
        echo '<td>' . ($transaction['type'] == 'income' ? 'Gelir' : 'Gider') . '</td>';
        echo '<td>' . ($transaction['type'] == 'income' ? '' : '-') . $transaction['amount'] . '</td>';
        echo '</tr>';
    }
    
    echo '
        </table>
    </body>
    </html>';
    
    exit;
}

// PDF olarak dışa aktar
function exportPDF($transactions, $filename, $startDate, $endDate) {
    // TCPDF veya MPDF gibi bir kütüphane gerekir
    // Bu örnek, geçici olarak tarayıcıda görüntülemek üzere basit bir HTML çıktısı oluşturur
    
    header('Content-Type: text/html; charset=utf-8');
    //header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
    
    // Gerçek bir projede TCPDF veya MPDF kullanmanız önerilir
    // Şimdilik basit bir HTML tablosu gösterelim
    
    echo '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>İşlemler</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { text-align: center; font-size: 24px; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .text-right { text-align: right; }
            .income { color: green; }
            .expense { color: red; }
            .footer { text-align: center; font-size: 12px; color: #777; margin-top: 30px; }
        </style>
    </head>
    <body>
        <h1>İşlem Raporu</h1>
        
        <p>
            <strong>Tarih Aralığı:</strong> ' . 
            ($startDate ? date('d.m.Y', strtotime($startDate)) : 'Başlangıç') . ' - ' . 
            ($endDate ? date('d.m.Y', strtotime($endDate)) : 'Bitiş') . '
        </p>
        
        <table>
            <tr>
                <th>Tarih</th>
                <th>Kategori</th>
                <th>Açıklama</th>
                <th>Tip</th>
                <th class="text-right">Tutar</th>
            </tr>';
    
    $totalIncome = 0;
    $totalExpense = 0;
    
    foreach ($transactions as $transaction) {
        if ($transaction['type'] == 'income') {
            $totalIncome += $transaction['amount'];
        } else {
            $totalExpense += $transaction['amount'];
        }
        
        echo '<tr>';
        echo '<td>' . date('d.m.Y', strtotime($transaction['transaction_date'])) . '</td>';
        echo '<td>' . htmlspecialchars($transaction['category_name'] ?? 'Kategori Yok') . '</td>';
        echo '<td>' . htmlspecialchars($transaction['description']) . '</td>';
        echo '<td>' . ($transaction['type'] == 'income' ? 'Gelir' : 'Gider') . '</td>';
        echo '<td class="text-right ' . ($transaction['type'] == 'income' ? 'income' : 'expense') . '">' . 
              ($transaction['type'] == 'income' ? '+' : '-') . formatMoney($transaction['amount']) . '</td>';
        echo '</tr>';
    }
    
    $netBalance = $totalIncome - $totalExpense;
    
    echo '
            <tr>
                <th colspan="4" class="text-right">Toplam Gelir:</th>
                <th class="text-right income">' . formatMoney($totalIncome) . '</th>
            </tr>
            <tr>
                <th colspan="4" class="text-right">Toplam Gider:</th>
                <th class="text-right expense">' . formatMoney($totalExpense) . '</th>
            </tr>
            <tr>
                <th colspan="4" class="text-right">Net:</th>
                <th class="text-right ' . ($netBalance >= 0 ? 'income' : 'expense') . '">' . formatMoney($netBalance) . '</th>
            </tr>
        </table>
        
        <div class="footer">
            Bu rapor ' . date('d.m.Y H:i') . ' tarihinde oluşturulmuştur.<br>
            Gelir Gider Takip Sistemi
        </div>
        
        <script>
            // PDF indirmek için popup yazdırma diyaloğunu göster
            window.addEventListener("load", function() {
                window.print();
            });
        </script>
    </body>
    </html>';
    
    exit;
}
?>