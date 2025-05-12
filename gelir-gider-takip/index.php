<?php
require_once 'includes/functions.php';

// Giriş yapmışsa dashboard'a yönlendir
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Gelir Gider Takip Sistemi';
include 'includes/header.php';
?>

<div class="text-center py-5">
    <h1 class="display-4 mb-4">Gelir Gider Takip Sistemi</h1>
    <p class="lead mb-4">Finansal durumunuzu kolayca takip edin, raporlayın ve analiz edin.</p>
    
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Özellikler</h5>
                    <ul class="text-start">
                        <li>Gelir ve gider takibi</li>
                        <li>Kategori bazlı sınıflandırma</li>
                        <li>Detaylı raporlar ve grafikler</li>
                        <li>Aylık ve yıllık analizler</li>
                        <li>Güvenli kullanıcı sistemi</li>
                    </ul>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-block">
                <a href="register.php" class="btn btn-primary btn-lg">Hemen Başla</a>
                <a href="login.php" class="btn btn-outline-primary btn-lg">Giriş Yap</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>