<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Gelir Gider Takip Sistemi' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-wallet2"></i> Gelir Gider Takip
            </a>
            
            <?php if (isLoggedIn()): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Kontrol Paneli
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add-transaction.php">
                            <i class="bi bi-plus-circle"></i> Yeni İşlem
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage-categories.php">
                            <i class="bi bi-tags"></i> Kategoriler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bank-accounts.php">
                            <i class="bi bi-bank"></i> Banka Hesapları
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="recurring-transactions.php">
                            <i class="bi bi-arrow-repeat"></i> Tekrarlayan İşlemler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-graph-up"></i> Raporlar
                        </a>
                    </li>
                </ul>
            
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= e($_SESSION['username']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Çıkış Yap</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
            <?php else: ?>
            <div class="d-flex">
                <a href="login.php" class="btn btn-outline-light me-2">Giriş Yap</a>
                <a href="register.php" class="btn btn-light">Kayıt Ol</a>
            </div>
            <?php endif; ?>
        </div>
    </nav>
    
    <main class="container mt-4">