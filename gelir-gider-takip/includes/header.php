
<!DOCTYPE html>
<html lang="tr" data-theme="<?= isset($_SESSION['user_id']) && getUserTheme($_SESSION['user_id']) ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Gelir Gider Takip Sistemi' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dark-theme.css">
</head>
<body>
    <!-- Mevcut navbar kodu aynı kalır -->
    <!-- Sabit pozisyonlu, kesinlikle görünür hamburger butonu -->
    <div style="position: fixed; top: 15px; right: 15px; z-index: 9999; font-size: 30px;">
        <button onclick="toggleMenu()" style="background: #4F46E5; color: white; border: none; border-radius: 5px; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-list"></i>
        </button>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-wallet2"></i> KasaPro
            </a>
            
            <?php if (isLoggedIn()): ?>
            <!-- Bootstrap toggle butonu gizlendi -->
            <button class="navbar-toggler d-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Panel
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add-transaction.php">
                            <i class="bi bi-plus-circle"></i> Yeni
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage-categories.php">
                            <i class="bi bi-tags"></i> Kategoriler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bank-accounts.php">
                            <i class="bi bi-bank"></i> Banka 
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
    
    <script>
    // Özel menü fonksiyonu
    function toggleMenu() {
        const navbarNav = document.getElementById('navbarNav');
        if (navbarNav.classList.contains('show')) {
            navbarNav.classList.remove('show');
        } else {
            navbarNav.classList.add('show');
        }
    }
    
    // Masaüstü modunda butonu gizle
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.querySelector('[onclick="toggleMenu()"]').parentNode;
        
        const mediaQuery = window.matchMedia('(min-width: 992px)');
        
        function handleScreenChange(e) {
            if (e.matches) {
                mobileMenuBtn.style.display = 'none';
            } else {
                mobileMenuBtn.style.display = 'block';
            }
        }
        
        // İlk yükleme
        handleScreenChange(mediaQuery);
        
        // Ekran boyutu değiştiğinde
        mediaQuery.addEventListener('change', handleScreenChange);
    });
    </script>
    
    <main class="container mt-4">