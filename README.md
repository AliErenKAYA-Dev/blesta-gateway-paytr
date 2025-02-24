
# Blesta PayTR Ödeme Modülü

Blesta için geliştirilen PayTR (Iframe) ödeme modülü, müşterilerinizin PayTR altyapısını kullanarak güvenli ve hızlı bir şekilde ödeme yapmasını sağlar. Modül, Blesta'nın ödeme sistemine entegre edilerek otomatik ödeme işlemlerini destekler.

## ✨ Özellikler

-   PayTR ile güvenli ve hızlı ödeme alma
    
-   3D Secure desteği
    
-   Blesta ile tam uyumluluk
    
-   Kolay kurulum ve yapılandırma
    

## 🔧 Kurulum

1.  **Modül Dosyalarını Yükleyin:**
    
    -   `paytr` klasörünü Blesta'nın `components/gateways/nonmerchant/` dizinine kopyalayın.
        
2.  **Blesta Yönetici Panelinde Modülü Etkinleştirin:**
    
    -   **Ayarlar > Ödeme Ağ Geçitleri** bölümüne gidin.
        
    -   PayTR modülünü bulun ve etkinleştirin.
        
3.  **API Bilgilerinizi Girin:**
    
    -   PayTR'den aldığınız `Merchant ID`, `Merchant Key` ve `Merchant Salt` bilgilerini girerek modülü yapılandırın.
        

## 🛠 Yapılandırma

-   **Test Modu:** Test ortamında çalıştırmak için etkinleştirebilirsiniz. Aksi takdirde değeri 0 olacak şekilde ayarlamalısınız.
    
-   **Bildirim URL:** PayTR tarafından ödeme durumlarının güncellenmesi için Blesta'daki bildirim URL'sini doğru şekilde ayarlayın. (siteadresiniz.com/callback/mgw/1/paytr)
    

## ⚡ Kullanım

-   Müşteriler, ödeme işlemlerini PayTR üzerinden Iframe aracılığı ile gerçekleştirebilir.
    
-   Ödeme başarılı olursa faturalar otomatik olarak işlenir.
    
-   Ödeme başarısız olursa hata mesajları Blesta üzerinden görüntülenebilir.
    

## ❓ Sıkça Sorulan Sorular (SSS)

### 1. PayTR API bilgilerini nereden alabilirim?

PayTR resmi web sitesine giriş yaparak üye olup, **Mağaza Ayarları** sekmesinden API bilgilerinizi alabilirsiniz.

### 2. Ödeme başarısız olduğunda ne yapmalıyım?

Blesta'da **Ödeme Ağ Geçitleri** sekmesinden hata mesajlarını inceleyebilir, PayTR panelinizden işlemleri kontrol edebilirsiniz.

### 3. Modülü nasıl güncelleyebilirim?

Yeni sürümü indirerek eski dosyaların üzerine yazabilirsiniz. Güncellemeden önce yedek almanız önerilir.

## 👥 Destek

Herhangi bir sorun yaşarsanız, info@alierenkaya.com mail adresi üzerinden benimle irtibata geçebilirsiniz.

----------

**Not:** Bu modül resmi Blesta veya PayTR eklentisi değildir. Kullanıcı sorumluluğundadır.
