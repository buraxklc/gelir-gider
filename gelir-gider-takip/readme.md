# 💸 KasaPro - Gelir Gider Takip Scripti

Bu proje, küçük işletmeler veya bireyler için geliştirilmiş basit bir gelir-gider takip scriptidir. Panel üzerinden gelir ve gider kayıtları eklenebilir, düzenlenebilir ve özet raporlar görüntülenebilir.

---

## 🧪 Demo Giriş Bilgileri

- **Kullanıcı Adı:** admin
- **Şifre:** 123456

---

## 🔧 Kurulum Talimatları

1. **Veritabanı Oluşturun**

   Hosting veya localhost kontrol panelinizden boş bir MySQL veritabanı oluşturun (örnek: `gelir_gider_db`).

2. **Veritabanı Tablolarını Yükleyin**

   - Proje dosyalarının içindeki `database.sql` dosyasını phpMyAdmin üzerinden içe aktarın.
     - phpMyAdmin → Veritabanınızı seçin → "İçe Aktar" sekmesine tıklayın
     - `database.sql` dosyasını seçin ve yükleyin

3. **Veritabanı Bağlantısını Ayarlayın**

   - `includes/db.php` dosyasını açın ve veritabanı bilgilerinizi aşağıdaki gibi düzenleyin:


4. **Giriş Yapın**

   - Scripti tarayıcınızda açarak `admin` kullanıcı adı ve `123456` şifresiyle giriş yapabilirsiniz.

---


---

## 📌 Notlar

- Kurulum sonrası güvenlik için `admin` şifresini değiştirmeniz önerilir.

---

## 📃 Lisans

Bu projenin satışı kesinlikle yasaktır. Tespiti halinde şikayet konusu açılacaktır r10 nickim: buraxklc.

