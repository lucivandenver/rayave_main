<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require_once 'config.php';

function appUrl($path = '')
{
    $base = rtrim(APP_URL, '/');
    $path = trim((string) $path);

    if ($path === '') {
        return $base;
    }

    return $base . '/' . ltrim($path, '/');
}

function getAvailableLanguages()
{
    return [
        'en' => ['label' => 'English', 'native' => 'English'],
        'es' => ['label' => 'Español', 'native' => 'Español'],
        'it' => ['label' => 'Italiano', 'native' => 'Italiano'],
        'de' => ['label' => 'Deutsch', 'native' => 'Deutsch'],
        'fr' => ['label' => 'Français', 'native' => 'Français'],
        'ja' => ['label' => '日本語', 'native' => '日本語'],
        'nl' => ['label' => 'Nederlands', 'native' => 'Nederlands'],
        'tl' => ['label' => 'Tagalog', 'native' => 'Tagalog'],
        'pt' => ['label' => 'Português', 'native' => 'Português'],
        'ru' => ['label' => 'Русский', 'native' => 'Русский'],
        'zh' => ['label' => '中文', 'native' => '中文'],
        'ko' => ['label' => '한국어', 'native' => '한국어'],
        'ar' => ['label' => 'العربية', 'native' => 'العربية'],
        'hi' => ['label' => 'हिन्दी', 'native' => 'हिन्दी'],
        'tr' => ['label' => 'Türkçe', 'native' => 'Türkçe'],
        'id' => ['label' => 'Bahasa Indonesia', 'native' => 'Bahasa Indonesia'],
        'vi' => ['label' => 'Tiếng Việt', 'native' => 'Tiếng Việt'],
        'th' => ['label' => 'ไทย', 'native' => 'ไทย'],
        'pl' => ['label' => 'Polski', 'native' => 'Polski'],
    ];
}

function getDefaultLanguage()
{
    return 'en';
}

function getCurrentLanguage()
{
    if (!empty($_SESSION['site_language'])) {
        $lang = strtolower(trim((string) $_SESSION['site_language']));
        $available = getAvailableLanguages();
        if (isset($available[$lang])) {
            return $lang;
        }
    }

    return getDefaultLanguage();
}

function setCurrentLanguage($language)
{
    $language = strtolower(trim((string) $language));
    $available = getAvailableLanguages();
    if (!isset($available[$language])) {
        $language = getDefaultLanguage();
    }

    $_SESSION['site_language'] = $language;
    return $language;
}

function getTranslationDictionary()
{
    return [
        'en' => [
            'Home' => 'Home',
            'Products' => 'Products',
            'Cart' => 'Cart',
            'Notifications' => 'Notifications',
            'Orders' => 'Orders',
            'Profile' => 'Profile',
            'Admin' => 'Admin',
            'Logout' => 'Logout',
            'Login' => 'Login',
            'Register' => 'Register',
            'View Shop' => 'View Shop',
            'Language' => 'Language',
            'Search language' => 'Search language',
            'Success' => 'Success',
            'Action Needed' => 'Action Needed',
            'Notifications Center' => 'Notifications Center',
            'Loading notifications...' => 'Loading notifications...',
            'Mark all as read' => 'Mark all as read',
            'Delete all' => 'Delete all',
        ],
        'es' => [
            'Home' => 'Inicio',
            'Products' => 'Productos',
            'Cart' => 'Carrito',
            'Notifications' => 'Notificaciones',
            'Orders' => 'Pedidos',
            'Profile' => 'Perfil',
            'Admin' => 'Admin',
            'Logout' => 'Cerrar sesión',
            'Login' => 'Iniciar sesión',
            'Register' => 'Registrarse',
            'View Shop' => 'Ver tienda',
            'Language' => 'Idioma',
            'Search language' => 'Buscar idioma',
            'Success' => 'Éxito',
            'Action Needed' => 'Acción requerida',
            'Notifications Center' => 'Centro de notificaciones',
            'Loading notifications...' => 'Cargando notificaciones...',
            'Mark all as read' => 'Marcar todo como leído',
            'Delete all' => 'Eliminar todo',
        ],
        'it' => [
            'Home' => 'Home',
            'Products' => 'Prodotti',
            'Cart' => 'Carrello',
            'Notifications' => 'Notifiche',
            'Orders' => 'Ordini',
            'Profile' => 'Profilo',
            'Admin' => 'Admin',
            'Logout' => 'Esci',
            'Login' => 'Accedi',
            'Register' => 'Registrati',
            'View Shop' => 'Visualizza negozio',
            'Language' => 'Lingua',
            'Search language' => 'Cerca lingua',
            'Success' => 'Successo',
            'Action Needed' => 'Azione richiesta',
            'Notifications Center' => 'Centro notifiche',
            'Loading notifications...' => 'Caricamento notifiche...',
            'Mark all as read' => 'Segna tutto come letto',
            'Delete all' => 'Elimina tutto',
        ],
        'de' => [
            'Home' => 'Startseite',
            'Products' => 'Produkte',
            'Cart' => 'Warenkorb',
            'Notifications' => 'Benachrichtigungen',
            'Orders' => 'Bestellungen',
            'Profile' => 'Profil',
            'Admin' => 'Admin',
            'Logout' => 'Abmelden',
            'Login' => 'Anmelden',
            'Register' => 'Registrieren',
            'View Shop' => 'Shop ansehen',
            'Language' => 'Sprache',
            'Search language' => 'Sprache suchen',
            'Success' => 'Erfolg',
            'Action Needed' => 'Aktion erforderlich',
            'Notifications Center' => 'Benachrichtigungszentrale',
            'Loading notifications...' => 'Benachrichtigungen werden geladen...',
            'Mark all as read' => 'Alle als gelesen markieren',
            'Delete all' => 'Alle löschen',
        ],
        'fr' => [
            'Home' => 'Accueil',
            'Products' => 'Produits',
            'Cart' => 'Panier',
            'Notifications' => 'Notifications',
            'Orders' => 'Commandes',
            'Profile' => 'Profil',
            'Admin' => 'Admin',
            'Logout' => 'Déconnexion',
            'Login' => 'Connexion',
            'Register' => 'S’inscrire',
            'View Shop' => 'Voir la boutique',
            'Language' => 'Langue',
            'Search language' => 'Rechercher une langue',
            'Success' => 'Succès',
            'Action Needed' => 'Action requise',
            'Notifications Center' => 'Centre de notifications',
            'Loading notifications...' => 'Chargement des notifications...',
            'Mark all as read' => 'Tout marquer comme lu',
            'Delete all' => 'Tout supprimer',
        ],
        'ja' => [
            'Home' => 'ホーム',
            'Products' => '商品',
            'Cart' => 'カート',
            'Notifications' => '通知',
            'Orders' => '注文',
            'Profile' => 'プロフィール',
            'Admin' => '管理',
            'Logout' => 'ログアウト',
            'Login' => 'ログイン',
            'Register' => '登録',
            'View Shop' => 'ショップを見る',
            'Language' => '言語',
            'Search language' => '言語を検索',
            'Success' => '成功',
            'Action Needed' => '対応が必要です',
            'Notifications Center' => '通知センター',
            'Loading notifications...' => '通知を読み込んでいます...',
            'Mark all as read' => 'すべて既読にする',
            'Delete all' => 'すべて削除',
        ],
        'nl' => [
            'Home' => 'Home',
            'Products' => 'Producten',
            'Cart' => 'Winkelwagen',
            'Notifications' => 'Meldingen',
            'Orders' => 'Bestellingen',
            'Profile' => 'Profiel',
            'Admin' => 'Beheer',
            'Logout' => 'Uitloggen',
            'Login' => 'Inloggen',
            'Register' => 'Registreren',
            'View Shop' => 'Winkel bekijken',
            'Language' => 'Taal',
            'Search language' => 'Taal zoeken',
            'Success' => 'Succes',
            'Action Needed' => 'Actie vereist',
            'Notifications Center' => 'Meldingenoverzicht',
            'Loading notifications...' => 'Meldingen laden...',
            'Mark all as read' => 'Alles als gelezen markeren',
            'Delete all' => 'Alles verwijderen',
        ],
        'tl' => [
            'Home' => 'Home',
            'Products' => 'Mga Produkto',
            'Cart' => 'Kariton',
            'Notifications' => 'Mga Abiso',
            'Orders' => 'Mga Order',
            'Profile' => 'Profile',
            'Admin' => 'Admin',
            'Logout' => 'Mag-log out',
            'Login' => 'Mag-log in',
            'Register' => 'Magrehistro',
            'View Shop' => 'Tingnan ang Shop',
            'Language' => 'Wika',
            'Search language' => 'Hanapin ang wika',
            'Success' => 'Tagumpay',
            'Action Needed' => 'Kailangang aksyon',
            'Notifications Center' => 'Sentro ng Mga Abiso',
            'Loading notifications...' => 'Naglo-load ng mga abiso...',
            'Mark all as read' => 'Markahan ang lahat bilang nabasa na',
            'Delete all' => 'Burahin lahat',
        ],
        'pt' => [
            'Home' => 'Início',
            'Products' => 'Produtos',
            'Cart' => 'Carrinho',
            'Notifications' => 'Notificações',
            'Orders' => 'Pedidos',
            'Profile' => 'Perfil',
            'Admin' => 'Admin',
            'Logout' => 'Sair',
            'Login' => 'Entrar',
            'Register' => 'Registrar',
            'View Shop' => 'Ver loja',
            'Language' => 'Idioma',
            'Search language' => 'Pesquisar idioma',
            'Success' => 'Sucesso',
            'Action Needed' => 'Ação necessária',
            'Notifications Center' => 'Central de notificações',
            'Loading notifications...' => 'Carregando notificações...',
            'Mark all as read' => 'Marcar tudo como lido',
            'Delete all' => 'Excluir tudo',
        ],
        'ru' => [
            'Home' => 'Главная',
            'Products' => 'Товары',
            'Cart' => 'Корзина',
            'Notifications' => 'Уведомления',
            'Orders' => 'Заказы',
            'Profile' => 'Профиль',
            'Admin' => 'Админ',
            'Logout' => 'Выйти',
            'Login' => 'Войти',
            'Register' => 'Регистрация',
            'View Shop' => 'Перейти в магазин',
            'Language' => 'Язык',
            'Search language' => 'Поиск языка',
            'Success' => 'Успешно',
            'Action Needed' => 'Требуется действие',
            'Notifications Center' => 'Центр уведомлений',
            'Loading notifications...' => 'Загрузка уведомлений...',
            'Mark all as read' => 'Отметить все как прочитанные',
            'Delete all' => 'Удалить все',
        ],
        'zh' => [
            'Home' => '首页',
            'Products' => '商品',
            'Cart' => '购物车',
            'Notifications' => '通知',
            'Orders' => '订单',
            'Profile' => '个人资料',
            'Admin' => '管理',
            'Logout' => '退出登录',
            'Login' => '登录',
            'Register' => '注册',
            'View Shop' => '查看商店',
            'Language' => '语言',
            'Search language' => '搜索语言',
            'Success' => '成功',
            'Action Needed' => '需要操作',
            'Notifications Center' => '通知中心',
            'Loading notifications...' => '正在加载通知...',
            'Mark all as read' => '全部标记为已读',
            'Delete all' => '全部删除',
        ],
        'ko' => [
            'Home' => '홈',
            'Products' => '상품',
            'Cart' => '장바구니',
            'Notifications' => '알림',
            'Orders' => '주문',
            'Profile' => '프로필',
            'Admin' => '관리',
            'Logout' => '로그아웃',
            'Login' => '로그인',
            'Register' => '회원가입',
            'View Shop' => '쇼핑몰 보기',
            'Language' => '언어',
            'Search language' => '언어 검색',
            'Success' => '성공',
            'Action Needed' => '조치 필요',
            'Notifications Center' => '알림 센터',
            'Loading notifications...' => '알림을 불러오는 중...',
            'Mark all as read' => '모두 읽음으로 표시',
            'Delete all' => '모두 삭제',
        ],
        'ar' => [
            'Home' => 'الرئيسية',
            'Products' => 'المنتجات',
            'Cart' => 'السلة',
            'Notifications' => 'الإشعارات',
            'Orders' => 'الطلبات',
            'Profile' => 'الملف الشخصي',
            'Admin' => 'الإدارة',
            'Logout' => 'تسجيل الخروج',
            'Login' => 'تسجيل الدخول',
            'Register' => 'إنشاء حساب',
            'View Shop' => 'عرض المتجر',
            'Language' => 'اللغة',
            'Search language' => 'البحث عن لغة',
            'Success' => 'نجاح',
            'Action Needed' => 'مطلوب إجراء',
            'Notifications Center' => 'مركز الإشعارات',
            'Loading notifications...' => 'جارٍ تحميل الإشعارات...',
            'Mark all as read' => 'تمييز الكل كمقروء',
            'Delete all' => 'حذف الكل',
        ],
        'hi' => [
            'Home' => 'होम',
            'Products' => 'उत्पाद',
            'Cart' => 'कार्ट',
            'Notifications' => 'सूचनाएँ',
            'Orders' => 'ऑर्डर',
            'Profile' => 'प्रोफ़ाइल',
            'Admin' => 'एडमिन',
            'Logout' => 'लॉग आउट',
            'Login' => 'लॉग इन',
            'Register' => 'रजिस्टर',
            'View Shop' => 'दुकान देखें',
            'Language' => 'भाषा',
            'Search language' => 'भाषा खोजें',
            'Success' => 'सफलता',
            'Action Needed' => 'कार्रवाई आवश्यक',
            'Notifications Center' => 'सूचना केंद्र',
            'Loading notifications...' => 'सूचनाएँ लोड हो रही हैं...',
            'Mark all as read' => 'सभी को पढ़ा हुआ चिह्नित करें',
            'Delete all' => 'सभी हटाएँ',
        ],
        'tr' => [
            'Home' => 'Ana Sayfa',
            'Products' => 'Ürünler',
            'Cart' => 'Sepet',
            'Notifications' => 'Bildirimler',
            'Orders' => 'Siparişler',
            'Profile' => 'Profil',
            'Admin' => 'Yönetim',
            'Logout' => 'Çıkış yap',
            'Login' => 'Giriş yap',
            'Register' => 'Kayıt ol',
            'View Shop' => 'Mağazayı görüntüle',
            'Language' => 'Dil',
            'Search language' => 'Dil ara',
            'Success' => 'Başarılı',
            'Action Needed' => 'İşlem gerekli',
            'Notifications Center' => 'Bildirim Merkezi',
            'Loading notifications...' => 'Bildirimler yükleniyor...',
            'Mark all as read' => 'Tümünü okundu işaretle',
            'Delete all' => 'Tümünü sil',
        ],
        'id' => [
            'Home' => 'Beranda',
            'Products' => 'Produk',
            'Cart' => 'Keranjang',
            'Notifications' => 'Notifikasi',
            'Orders' => 'Pesanan',
            'Profile' => 'Profil',
            'Admin' => 'Admin',
            'Logout' => 'Keluar',
            'Login' => 'Masuk',
            'Register' => 'Daftar',
            'View Shop' => 'Lihat Toko',
            'Language' => 'Bahasa',
            'Search language' => 'Cari bahasa',
            'Success' => 'Berhasil',
            'Action Needed' => 'Perlu tindakan',
            'Notifications Center' => 'Pusat notifikasi',
            'Loading notifications...' => 'Memuat notifikasi...',
            'Mark all as read' => 'Tandai semua sebagai sudah dibaca',
            'Delete all' => 'Hapus semua',
        ],
        'vi' => [
            'Home' => 'Trang chủ',
            'Products' => 'Sản phẩm',
            'Cart' => 'Giỏ hàng',
            'Notifications' => 'Thông báo',
            'Orders' => 'Đơn hàng',
            'Profile' => 'Hồ sơ',
            'Admin' => 'Quản trị',
            'Logout' => 'Đăng xuất',
            'Login' => 'Đăng nhập',
            'Register' => 'Đăng ký',
            'View Shop' => 'Xem cửa hàng',
            'Language' => 'Ngôn ngữ',
            'Search language' => 'Tìm ngôn ngữ',
            'Success' => 'Thành công',
            'Action Needed' => 'Cần thao tác',
            'Notifications Center' => 'Trung tâm thông báo',
            'Loading notifications...' => 'Đang tải thông báo...',
            'Mark all as read' => 'Đánh dấu tất cả là đã đọc',
            'Delete all' => 'Xóa tất cả',
        ],
        'th' => [
            'Home' => 'หน้าแรก',
            'Products' => 'สินค้า',
            'Cart' => 'ตะกร้า',
            'Notifications' => 'การแจ้งเตือน',
            'Orders' => 'คำสั่งซื้อ',
            'Profile' => 'โปรไฟล์',
            'Admin' => 'ผู้ดูแล',
            'Logout' => 'ออกจากระบบ',
            'Login' => 'เข้าสู่ระบบ',
            'Register' => 'สมัครสมาชิก',
            'View Shop' => 'ดูร้านค้า',
            'Language' => 'ภาษา',
            'Search language' => 'ค้นหาภาษา',
            'Success' => 'สำเร็จ',
            'Action Needed' => 'ต้องดำเนินการ',
            'Notifications Center' => 'ศูนย์การแจ้งเตือน',
            'Loading notifications...' => 'กำลังโหลดการแจ้งเตือน...',
            'Mark all as read' => 'ทำเครื่องหมายทั้งหมดว่าอ่านแล้ว',
            'Delete all' => 'ลบทั้งหมด',
        ],
        'pl' => [
            'Home' => 'Strona główna',
            'Products' => 'Produkty',
            'Cart' => 'Koszyk',
            'Notifications' => 'Powiadomienia',
            'Orders' => 'Zamówienia',
            'Profile' => 'Profil',
            'Admin' => 'Admin',
            'Logout' => 'Wyloguj',
            'Login' => 'Zaloguj',
            'Register' => 'Zarejestruj',
            'View Shop' => 'Zobacz sklep',
            'Language' => 'Język',
            'Search language' => 'Szukaj języka',
            'Success' => 'Sukces',
            'Action Needed' => 'Wymagane działanie',
            'Notifications Center' => 'Centrum powiadomień',
            'Loading notifications...' => 'Ładowanie powiadomień...',
            'Mark all as read' => 'Oznacz wszystko jako przeczytane',
            'Delete all' => 'Usuń wszystko',
        ],
    ];
}

function t($key)
{
    $language = getCurrentLanguage();
    $dictionary = getTranslationDictionary();
    if (isset($dictionary[$language][$key])) {
        return $dictionary[$language][$key];
    }

    if (isset($dictionary['en'][$key])) {
        return $dictionary['en'][$key];
    }

    return $key;
}

function currentLanguageLabel()
{
    $languages = getAvailableLanguages();
    $current = getCurrentLanguage();
    return $languages[$current]['native'] ?? 'English';
}

function ensureUserSecurityColumns()
{
    global $pdo;

    static $ensured = false;
    if ($ensured) {
        return;
    }

    try {
        $columns = [
            'google_id' => "ALTER TABLE users ADD COLUMN google_id VARCHAR(255) DEFAULT NULL AFTER role",
            'email_verified_at' => "ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER google_id",
            'password_reset_token' => "ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(255) DEFAULT NULL AFTER email_verified_at",
            'password_reset_expires_at' => "ALTER TABLE users ADD COLUMN password_reset_expires_at DATETIME NULL AFTER password_reset_token",
            'email_opt_in' => "ALTER TABLE users ADD COLUMN email_opt_in TINYINT(1) NOT NULL DEFAULT 1 AFTER password_reset_expires_at",
            'avatar_url' => "ALTER TABLE users ADD COLUMN avatar_url VARCHAR(512) NULL AFTER email_opt_in",
            'birthday' => "ALTER TABLE users ADD COLUMN birthday DATE NULL AFTER avatar_url",
            'is_disabled' => "ALTER TABLE users ADD COLUMN is_disabled TINYINT(1) NOT NULL DEFAULT 0 AFTER birthday"
        ];

        foreach ($columns as $columnName => $alterSql) {
            $stmt = $pdo->prepare('SHOW COLUMNS FROM users LIKE ?');
            $stmt->execute([$columnName]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $pdo->exec($alterSql);
            }
        }

        $indexes = [
            'users_google_id_unique' => "ALTER TABLE users ADD UNIQUE INDEX users_google_id_unique (google_id)",
            'users_password_reset_token_index' => "ALTER TABLE users ADD INDEX users_password_reset_token_index (password_reset_token)"
        ];

        foreach ($indexes as $indexName => $alterSql) {
            $stmt = $pdo->prepare('SHOW INDEX FROM users WHERE Key_name = ?');
            $stmt->execute([$indexName]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $pdo->exec($alterSql);
            }
        }
    } catch (PDOException $e) {
        error_log('Failed to ensure user security columns: ' . $e->getMessage());
    }

    $ensured = true;
}


function ensureSecurityTables()
{
    global $pdo;

    static $ensured = false;
    if ($ensured) {
        return;
    }

    try {
        // activity_logs
        $tableStmt = $pdo->query("SHOW TABLES LIKE 'activity_logs'");
        if (!$tableStmt->fetch()) {
            $sql = "CREATE TABLE activity_logs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                event VARCHAR(50) NOT NULL,
                ip VARCHAR(45) NULL,
                user_agent VARCHAR(1024) NULL,
                meta JSON NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_activity_user (user_id),
                INDEX idx_activity_event (event),
                INDEX idx_activity_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            try { $pdo->exec($sql); } catch (PDOException $e) { /* ignore */ }
        }

        // flagged_messages
        $tableStmt = $pdo->query("SHOW TABLES LIKE 'flagged_messages'");
        if (!$tableStmt->fetch()) {
            $sql = "CREATE TABLE flagged_messages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                source VARCHAR(50) NOT NULL,
                content TEXT NOT NULL,
                detected_words VARCHAR(512) NULL,
                review_state VARCHAR(32) NOT NULL DEFAULT 'new',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_flagged_user (user_id),
                INDEX idx_flagged_source (source),
                INDEX idx_flagged_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            try { $pdo->exec($sql); } catch (PDOException $e) { /* ignore */ }
        }

        // banned_words - simple admin-managed list
        $tableStmt = $pdo->query("SHOW TABLES LIKE 'banned_words'");
        if (!$tableStmt->fetch()) {
            $sql = "CREATE TABLE banned_words (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                word VARCHAR(255) NOT NULL UNIQUE,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_banned_word (word)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            try { $pdo->exec($sql); } catch (PDOException $e) { /* ignore */ }
        }
        
        // banned_words_audit - record changes to banned words
        $tableStmt = $pdo->query("SHOW TABLES LIKE 'banned_words_audit'");
        if (!$tableStmt->fetch()) {
            $sql = "CREATE TABLE banned_words_audit (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                word_id INT UNSIGNED NULL,
                action VARCHAR(32) NOT NULL,
                admin_id INT UNSIGNED NULL,
                note VARCHAR(255) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_bwa_word (word_id),
                INDEX idx_bwa_admin (admin_id),
                INDEX idx_bwa_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            try { $pdo->exec($sql); } catch (PDOException $e) { /* ignore */ }
        }
    } catch (PDOException $e) {
        error_log('Failed to ensure security tables: ' . $e->getMessage());
    }

    $ensured = true;
}


function logUserActivity($userId, $event, $meta = [])
{
    global $pdo;
    ensureSecurityTables();

    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, event, ip, user_agent, meta) VALUES (?, ?, ?, ?, ?)");
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $metaJson = !empty($meta) ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
        $stmt->execute([$userId, $event, $ip, $ua, $metaJson]);
    } catch (PDOException $e) {
        error_log('Failed to log user activity: ' . $e->getMessage());
    }
}

function getActivityLogs($limit = 200)
{
    global $pdo;
    ensureSecurityTables();
    try {
        $stmt = $pdo->prepare("SELECT al.*, u.username, u.full_name FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT ?");
        $stmt->execute([(int)$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Failed to fetch activity logs: ' . $e->getMessage());
        return [];
    }
}

function countActivityLogsFiltered($filters = [])
{
    global $pdo;
    ensureSecurityTables();
    $where = [];
    $params = [];

    if (!empty($filters['username'])) {
        $where[] = 'u.username LIKE ?';
        $params[] = '%' . $filters['username'] . '%';
    }
    if (!empty($filters['event'])) {
        $where[] = 'al.event = ?';
        $params[] = $filters['event'];
    }
    if (!empty($filters['date_from'])) {
        $where[] = 'al.created_at >= ?';
        $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $where[] = 'al.created_at <= ?';
        $params[] = $filters['date_to'];
    }

    $sql = 'SELECT COUNT(*) FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id';
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Failed to count activity logs: ' . $e->getMessage());
        return 0;
    }
}

function getActivityLogsFiltered($filters = [], $limit = 50, $offset = 0)
{
    global $pdo;
    ensureSecurityTables();
    $where = [];
    $params = [];

    if (!empty($filters['username'])) {
        $where[] = 'u.username LIKE ?';
        $params[] = '%' . $filters['username'] . '%';
    }
    if (!empty($filters['event'])) {
        $where[] = 'al.event = ?';
        $params[] = $filters['event'];
    }
    if (!empty($filters['date_from'])) {
        $where[] = 'al.created_at >= ?';
        $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $where[] = 'al.created_at <= ?';
        $params[] = $filters['date_to'];
    }

    $sql = 'SELECT al.*, u.username, u.full_name FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id';
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY al.created_at DESC LIMIT ? OFFSET ?';

    try {
        $stmt = $pdo->prepare($sql);
        $params[] = (int)$limit;
        $params[] = (int)$offset;
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Failed to fetch filtered activity logs: ' . $e->getMessage());
        return [];
    }
}

function scanForInappropriateWords($text)
{
    if (!is_string($text) || trim($text) === '') {
        return [];
    }

    // Prefer database-managed banned words when available
    global $pdo;
    $banned = [];
    try {
        ensureSecurityTables();
        $stmt = $pdo->query("SELECT word FROM banned_words ORDER BY word");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $w = trim((string)($r['word'] ?? ''));
            if ($w !== '') $banned[] = mb_strtolower($w);
        }
    } catch (Exception $e) {
        // fallback to hardcoded list if DB not available
        $banned = ['badword1','badword2','inappropriate','offensive'];
    }

    $found = [];
    $textToCheck = $text;

    foreach ($banned as $w) {
        if ($w === '') continue;
        $pattern = '/' . preg_quote($w, '/') . '/iu';
        // try whole-word match first
        $wordPattern = '/\b' . preg_quote($w, '/') . '\b/iu';
        if (@preg_match($wordPattern, $textToCheck)) {
            $found[] = $w;
            continue;
        }
        // fallback: plain substring match (for phrases or punctuation)
        if (@preg_match($pattern, $textToCheck)) {
            $found[] = $w;
        }
    }

    return array_values(array_unique($found));
}

// Banned words management
function getBannedWords()
{
    global $pdo;
    ensureSecurityTables();
    try {
        $stmt = $pdo->query("SELECT * FROM banned_words ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Failed to fetch banned words: ' . $e->getMessage());
        return [];
    }
}

function addBannedWord($word, $adminId = null)
{
    global $pdo;
    $word = trim((string)$word);
    if ($word === '') return false;
    ensureSecurityTables();
    try {
        $stmt = $pdo->prepare("INSERT INTO banned_words (word) VALUES (?)");
        $ok = (bool)$stmt->execute([$word]);
        if ($ok) {
            $id = (int)$pdo->lastInsertId();
            logBannedWordAudit($id, 'add', $adminId, $word);
        }
        return $ok;
    } catch (PDOException $e) {
        error_log('Failed to add banned word: ' . $e->getMessage());
        return false;
    }
}

function removeBannedWord($id, $adminId = null)
{
    global $pdo;
    ensureSecurityTables();
    try {
        // capture word for audit
        $q = $pdo->prepare("SELECT word FROM banned_words WHERE id = ?");
        $q->execute([(int)$id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        $word = $row['word'] ?? null;

        $stmt = $pdo->prepare("DELETE FROM banned_words WHERE id = ?");
        $ok = (bool)$stmt->execute([(int)$id]);
        if ($ok) {
            logBannedWordAudit($id, 'delete', $adminId, $word);
        }
        return $ok;
    } catch (PDOException $e) {
        error_log('Failed to remove banned word: ' . $e->getMessage());
        return false;
    }
}

function logBannedWordAudit($wordId, $action, $adminId = null, $note = null)
{
    global $pdo;
    ensureSecurityTables();
    try {
        $stmt = $pdo->prepare("INSERT INTO banned_words_audit (word_id, action, admin_id, note) VALUES (?, ?, ?, ?)");
        $stmt->execute([$wordId ?: null, $action, $adminId ?: null, $note ?: null]);
    } catch (PDOException $e) {
        error_log('Failed to log banned words audit: ' . $e->getMessage());
    }
}

function getBannedWordsAudit($limit = 200)
{
    global $pdo;
    ensureSecurityTables();
    try {
        $stmt = $pdo->prepare("SELECT bwa.*, bw.word, u.username FROM banned_words_audit bwa LEFT JOIN banned_words bw ON bwa.word_id = bw.id LEFT JOIN users u ON bwa.admin_id = u.id ORDER BY bwa.created_at DESC LIMIT ?");
        $stmt->execute([(int)$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Failed to fetch banned words audit: ' . $e->getMessage());
        return [];
    }
}

function flagMessage($userId, $source, $content, $detectedWords = [])
{
    global $pdo;
    ensureSecurityTables();
    try {
        $stmt = $pdo->prepare("INSERT INTO flagged_messages (user_id, source, content, detected_words) VALUES (?, ?, ?, ?)");
        $detected = !empty($detectedWords) ? implode(', ', $detectedWords) : null;
        $stmt->execute([$userId, $source, $content, $detected]);
    } catch (PDOException $e) {
        error_log('Failed to flag message: ' . $e->getMessage());
    }
}

function getFlaggedMessages($limit = 200)
{
    global $pdo;
    ensureSecurityTables();
    try {
        $stmt = $pdo->prepare("SELECT fm.*, u.username, u.full_name FROM flagged_messages fm LEFT JOIN users u ON fm.user_id = u.id ORDER BY fm.created_at DESC LIMIT ?");
        $stmt->execute([(int)$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Failed to fetch flagged messages: ' . $e->getMessage());
        return [];
    }
}

function countFlaggedMessagesFiltered($filters = [])
{
    global $pdo;
    ensureSecurityTables();
    $where = [];
    $params = [];

    if (!empty($filters['username'])) {
        $where[] = 'u.username LIKE ?';
        $params[] = '%' . $filters['username'] . '%';
    }
    if (!empty($filters['source'])) {
        $where[] = 'fm.source = ?';
        $params[] = $filters['source'];
    }
    if (!empty($filters['review_state'])) {
        $where[] = 'fm.review_state = ?';
        $params[] = $filters['review_state'];
    }
    if (!empty($filters['date_from'])) {
        $where[] = 'fm.created_at >= ?';
        $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $where[] = 'fm.created_at <= ?';
        $params[] = $filters['date_to'];
    }

    $sql = 'SELECT COUNT(*) FROM flagged_messages fm LEFT JOIN users u ON fm.user_id = u.id';
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Failed to count flagged messages: ' . $e->getMessage());
        return 0;
    }
}

function getFlaggedMessagesFiltered($filters = [], $limit = 50, $offset = 0)
{
    global $pdo;
    ensureSecurityTables();
    $where = [];
    $params = [];

    if (!empty($filters['username'])) {
        $where[] = 'u.username LIKE ?';
        $params[] = '%' . $filters['username'] . '%';
    }
    if (!empty($filters['source'])) {
        $where[] = 'fm.source = ?';
        $params[] = $filters['source'];
    }
    if (!empty($filters['review_state'])) {
        $where[] = 'fm.review_state = ?';
        $params[] = $filters['review_state'];
    }
    if (!empty($filters['date_from'])) {
        $where[] = 'fm.created_at >= ?';
        $params[] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $where[] = 'fm.created_at <= ?';
        $params[] = $filters['date_to'];
    }

    $sql = 'SELECT fm.*, u.username, u.full_name FROM flagged_messages fm LEFT JOIN users u ON fm.user_id = u.id';
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY fm.created_at DESC LIMIT ? OFFSET ?';

    try {
        $stmt = $pdo->prepare($sql);
        $params[] = (int)$limit;
        $params[] = (int)$offset;
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Failed to fetch flagged messages filtered: ' . $e->getMessage());
        return [];
    }
}

function disableUser($userId)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_disabled = 1 WHERE id = ?");
        return $stmt->execute([$userId]);
    } catch (PDOException $e) {
        error_log('Failed to disable user: ' . $e->getMessage());
        return false;
    }
}

function enableUser($userId)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_disabled = 0 WHERE id = ?");
        return $stmt->execute([$userId]);
    } catch (PDOException $e) {
        error_log('Failed to enable user: ' . $e->getMessage());
        return false;
    }
}

function generateUniqueUsername($seed)
{
    global $pdo;

    $base = strtolower(preg_replace('/[^a-z0-9]+/', '', (string) $seed));
    if ($base === '') {
        $base = 'user' . random_int(1000, 9999);
    }

    $candidate = $base;
    $suffix = 1;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');

    while (true) {
        $stmt->execute([$candidate]);
        if ((int) $stmt->fetchColumn() === 0) {
            return $candidate;
        }
        $candidate = $base . $suffix;
        $suffix++;
    }
}

function ensureEmailCampaignLogsTable()
{
    global $pdo;

    static $logsEnsured = false;
    if ($logsEnsured) {
        return;
    }

    try {
        $tableExistsStmt = $pdo->query("SHOW TABLES LIKE 'email_campaign_logs'");
        if ($tableExistsStmt->fetch()) {
            $logsEnsured = true;
            return;
        }

        $createSql = "CREATE TABLE email_campaign_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            admin_id INT UNSIGNED NULL,
            subject VARCHAR(255) NOT NULL,
            segments VARCHAR(255) NOT NULL,
            sent_count INT UNSIGNED NOT NULL DEFAULT 0,
            fail_count INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email_campaign_admin (admin_id),
            INDEX idx_email_campaign_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $pdo->exec($createSql);
        } catch (PDOException $inner) {
            // Retry without enforcing engine/charset/foreign keys for hosts that block them
            $fallbackSql = "CREATE TABLE IF NOT EXISTS email_campaign_logs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                admin_id INT UNSIGNED NULL,
                subject VARCHAR(255) NOT NULL,
                segments VARCHAR(255) NOT NULL,
                sent_count INT UNSIGNED NOT NULL DEFAULT 0,
                fail_count INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email_campaign_admin (admin_id),
                INDEX idx_email_campaign_created_at (created_at)
            )";
            $pdo->exec($fallbackSql);
        }
    } catch (PDOException $e) {
        error_log('Failed to ensure email campaign logs table: ' . $e->getMessage());
    }

    $logsEnsured = true;
}

function getProducts($category_id = null, $featured = false, $limit = null)
{
    global $pdo;

    ensureProductAvailabilityColumn();

    $sql = "SELECT p.*, c.name as category_name FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE 1=1";
    $params = [];

    if ($category_id) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category_id;
    }

    if ($featured) {
        $sql .= " AND p.is_featured = 1";
    }

    $sql .= " ORDER BY p.created_at DESC";

    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = (int) $limit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProduct($id)
{
    global $pdo;

    ensureProductAvailabilityColumn();

    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE p.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getCategories()
{
    global $pdo;

    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCustomizeCategoryId()
{
    static $customizeCategoryId = null;
    static $resolved = false;

    if ($resolved) {
        return $customizeCategoryId;
    }

    $resolved = true;
    $categories = getCategories();
    foreach ($categories as $category) {
        if (!isset($category['name'])) {
            continue;
        }

        if (strtolower(trim($category['name'])) === 'customize') {
            $customizeCategoryId = (int) $category['id'];
            break;
        }
    }

    return $customizeCategoryId;
}

function addToCart($user_id, $product_id, $quantity = 1)
{
    global $pdo;

    // Check if item already in cart
    $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([$quantity, $user_id, $product_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        return $stmt->execute([$user_id, $product_id, $quantity]);
    }
}

function getCartItems($user_id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.image, p.stock_quantity, p.category_id, cat.name AS category_name 
                          FROM cart c 
                          JOIN products p ON c.product_id = p.id 
                          LEFT JOIN categories cat ON p.category_id = cat.id 
                          WHERE c.user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCartTotal($user_id)
{
    $items = getCartItems($user_id);
    $total = 0;

    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    return $total;
}

function getSequentialDeliveryDateOptions($startDate = null, int $count = 3): array
{
    try {
        if ($startDate instanceof DateTimeInterface) {
            $start = new DateTimeImmutable($startDate->format('Y-m-d H:i:s'));
        } elseif (is_string($startDate) && trim($startDate) !== '') {
            $start = new DateTimeImmutable($startDate);
        } else {
            $start = new DateTimeImmutable('now');
        }
    } catch (Exception $e) {
        $start = new DateTimeImmutable('now');
    }

    $start = $start->modify('+1 day');
    $options = [];
    $count = max(1, $count);

    for ($i = 0; $i < $count; $i++) {
        $options[] = [
            'value' => $start->format('Y-m-d'),
            'label' => $start->format('D, M j'),
        ];
        $start = $start->modify('+1 day');
    }

    return $options;
}

function getSequentialDeliveryDateRange($startDate = null, int $count = 3): string
{
    $options = getSequentialDeliveryDateOptions($startDate, $count);
    if (empty($options)) {
        return '';
    }

    $first = DateTimeImmutable::createFromFormat('Y-m-d', $options[0]['value']);
    $last = DateTimeImmutable::createFromFormat('Y-m-d', $options[count($options) - 1]['value']);

    if (!$first || !$last) {
        return $options[0]['label'];
    }

    if ($first->format('F') === $last->format('F')) {
        return $first->format('F j') . '-' . $last->format('j');
    }

    return $first->format('F j') . ' - ' . $last->format('F j');
}

function getProductReviews($product_id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT pr.*, u.username, u.full_name 
                          FROM product_reviews pr 
                          JOIN users u ON pr.user_id = u.id 
                          WHERE pr.product_id = ? 
                          ORDER BY pr.created_at DESC");
    $stmt->execute([$product_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAverageRating($product_id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
                          FROM product_reviews 
                          WHERE product_id = ?");
    $stmt->execute([$product_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function addProductReview($product_id, $user_id, $rating, $review_text)
{
    global $pdo;

    // Check if user already reviewed this product
    $stmt = $pdo->prepare("SELECT id FROM product_reviews WHERE product_id = ? AND user_id = ?");
    $stmt->execute([$product_id, $user_id]);

    if ($stmt->fetch()) {
        return false; // User already reviewed
    }

    $stmt = $pdo->prepare("INSERT INTO product_reviews (product_id, user_id, rating, review_text) 
                          VALUES (?, ?, ?, ?)");
    return $stmt->execute([$product_id, $user_id, $rating, $review_text]);
}

function getOrderTracking($order_id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM order_tracking 
                          WHERE order_id = ? 
                          ORDER BY created_at DESC");
    $stmt->execute([$order_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllowedOrderStatuses()
{
    return ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];
}

function normalizeOrderStatus($status)
{
    $cleanStatus = strtolower(trim((string) $status));

    foreach (getAllowedOrderStatuses() as $allowed) {
        if ($cleanStatus === $allowed) {
            return $cleanStatus;
        }
    }

    return null;
}

function getOrderStatusDescriptions($trackingNumber = '')
{
    $trackingNumber = trim((string) $trackingNumber);
    $shippedMessage = 'Order has been shipped';

    if ($trackingNumber !== '') {
        $shippedMessage .= ' with tracking: ' . $trackingNumber;
    }

    return [
        'pending' => 'Order placed successfully',
        'confirmed' => 'Order confirmed and payment verified',
        'processing' => 'Order is being processed and prepared for shipment',
        'shipped' => $shippedMessage,
        'delivered' => 'Order has been delivered successfully',
        'completed' => 'Order has been completed successfully',
        'cancelled' => 'Order has been cancelled'
    ];
}

function ensureOrderStatusSchema()
{
    global $pdo;

    static $schemaEnsured = false;
    if ($schemaEnsured) {
        return;
    }

    $requiredStatuses = getAllowedOrderStatuses();
    $tables = [
        'orders' => ['column' => 'status', 'default' => 'pending'],
        'order_tracking' => ['column' => 'status', 'default' => 'pending']
    ];

    foreach ($tables as $table => $info) {
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
            $stmt->execute([$info['column']]);
            $column = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$column) {
                continue;
            }

            $type = strtolower($column['Type'] ?? '');
            if (strpos($type, 'enum(') !== 0) {
                continue;
            }

            preg_match_all("/'([^']+)'/", $column['Type'], $matches);
            $currentValues = $matches[1] ?? [];

            $enumValues = $currentValues;
            foreach ($requiredStatuses as $status) {
                if (!in_array($status, $enumValues, true)) {
                    $enumValues[] = $status;
                }
            }

            if ($enumValues === $currentValues) {
                continue;
            }

            $enumList = "'" . implode("','", $enumValues) . "'";
            $default = in_array($info['default'], $enumValues, true) ? $info['default'] : $enumValues[0];
            $pdo->exec("ALTER TABLE {$table} MODIFY COLUMN {$info['column']} ENUM({$enumList}) NOT NULL DEFAULT '{$default}'");
        } catch (PDOException $e) {
            error_log('Failed to ensure order status enum for ' . $table . ': ' . $e->getMessage());
        }
    }

    $schemaEnsured = true;
}

function updateOrderStatus($order_id, $status, $description = '', $manageTransaction = true, $trackingNumber = false)
{
    global $pdo;

    ensureOrderStatusSchema();

    $normalizedStatus = normalizeOrderStatus($status);
    if ($normalizedStatus === null) {
        error_log('Invalid order status supplied: ' . print_r($status, true));
        return false;
    }

    $status = $normalizedStatus;
    $shouldManageTransaction = $manageTransaction && !$pdo->inTransaction();
    $shouldUpdateTracking = $trackingNumber !== false;
    $trackingValue = null;

    try {
        if ($shouldManageTransaction) {
            $pdo->beginTransaction();
        }

        $stmt = $pdo->prepare("SELECT user_id, status FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            if ($shouldManageTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }

        $previousStatus = $order['status'] ?? null;

        if ($shouldUpdateTracking) {
            $trackingValue = $trackingNumber === null ? null : trim((string) $trackingNumber);
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, tracking_number = ? WHERE id = ?");
            $stmt->execute([$status, $trackingValue, $order_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $order_id]);
            $trackingValue = '';
        }

        if ($description === '') {
            $descriptions = getOrderStatusDescriptions($trackingValue === null ? '' : $trackingValue);
            $description = $descriptions[$status] ?? 'Order status updated';
        }

        $stmt = $pdo->prepare("INSERT INTO order_tracking (order_id, status, description) 
                              VALUES (?, ?, ?)");
        $stmt->execute([$order_id, $status, $description]);

        if ($shouldManageTransaction) {
            $pdo->commit();
        }

        if ($status === 'cancelled' && $previousStatus !== 'cancelled') {
            releaseInventoryForOrder($order_id);
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction() && $shouldManageTransaction) {
            $pdo->rollBack();
        }
        error_log('Order status update failed: ' . $e->getMessage());
        if (!$manageTransaction) {
            throw $e;
        }
        return false;
    }

    $title = 'Order #' . $order_id . ' ' . ucfirst($status);
    $messageText = $description ?: 'Your order status changed to ' . ucfirst($status) . '.';
    createNotification($order['user_id'], $title, $messageText, 'order', $order_id);

    return true;
}

function autoCancelPendingOrders($minHours = AUTO_CANCEL_PENDING_MIN_HOURS, $maxHours = AUTO_CANCEL_PENDING_MAX_HOURS)
{
    global $pdo;

    static $autoCancelRan = false;
    if ($autoCancelRan) {
        return;
    }
    $autoCancelRan = true;

    $minHours = max(1, (int) $minHours);
    $maxHours = max($minHours, (int) $maxHours);

    try {
        $stmt = $pdo->prepare(
            "SELECT id FROM orders 
             WHERE status = 'pending' 
               AND TIMESTAMPDIFF(HOUR, created_at, NOW()) >= :cutoff
             LIMIT 50"
        );
        $stmt->execute([':cutoff' => $maxHours]);
        $staleOrders = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($staleOrders as $staleOrderId) {
            updateOrderStatus(
                $staleOrderId,
                'cancelled',
                'Order automatically cancelled after pending for more than ' . $maxHours . ' hours without admin confirmation.'
            );
        }
    } catch (PDOException $e) {
        error_log('Auto cancel pending orders failed: ' . $e->getMessage());
    }
}

// ----------------------------
// Coupon helpers
// ----------------------------

function ensureOrderCouponColumns()
{
    global $pdo;

    static $couponColumnsEnsured = false;
    if ($couponColumnsEnsured) {
        return;
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'coupon_code'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE orders ADD coupon_code VARCHAR(100) NULL AFTER payment_method");
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'coupon_discount'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE orders ADD coupon_discount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER coupon_code");
        }
    } catch (PDOException $e) {
        error_log('Ensure order coupon columns failed: ' . $e->getMessage());
    }

    $couponColumnsEnsured = true;
}

function getCouponByCode($code)
{
    global $pdo;

    if (trim($code) === '') {
        return null;
    }

    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE UPPER(code) = UPPER(?) LIMIT 1");
    $stmt->execute([$code]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function hasUserUsedCoupon($couponId, $userId)
{
    global $pdo;

    if (!$couponId || !$userId) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM coupon_usage WHERE coupon_id = ? AND user_id = ?");
    $stmt->execute([$couponId, $userId]);

    return (int) $stmt->fetchColumn() > 0;
}

function calculateCouponDiscount($coupon, $orderSubtotal)
{
    if (!$coupon || $orderSubtotal <= 0) {
        return 0;
    }

    $discount = 0;

    if ($coupon['discount_type'] === 'percentage') {
        $discount = $orderSubtotal * ((float) $coupon['discount_value'] / 100);
        if (!empty($coupon['max_discount_amount']) && $coupon['max_discount_amount'] > 0) {
            $discount = min($discount, (float) $coupon['max_discount_amount']);
        }
    } else {
        $discount = (float) $coupon['discount_value'];
    }

    return max(0, min($discount, $orderSubtotal));
}

/**
 * @return array{valid:bool,error:string,coupon:?array,discount_amount:float}
 */
function validateCouponForOrder($code, $userId, $orderSubtotal): array
{
    $result = [
        'valid' => false,
        'error' => '',
        'coupon' => null,
        'discount_amount' => 0
    ];

    $coupon = getCouponByCode($code);

    if (!$coupon || !(int) $coupon['is_active']) {
        $result['error'] = 'Coupon code is invalid or inactive.';
        return $result;
    }

    $now = new DateTime();

    if (!empty($coupon['valid_from']) && $now < new DateTime($coupon['valid_from'])) {
        $result['error'] = 'Coupon is not yet valid.';
        return $result;
    }

    if (!empty($coupon['valid_until']) && $now > new DateTime($coupon['valid_until'])) {
        $result['error'] = 'Coupon has already expired.';
        return $result;
    }

    if (!empty($coupon['usage_limit']) && $coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
        $result['error'] = 'Coupon usage limit has been reached.';
        return $result;
    }

    if ($userId && hasUserUsedCoupon($coupon['id'], $userId)) {
        $result['error'] = 'You have already used this coupon.';
        return $result;
    }

    if (!empty($coupon['min_order_amount']) && $orderSubtotal < $coupon['min_order_amount']) {
        $result['error'] = 'Minimum order amount for this coupon is ₱' . number_format($coupon['min_order_amount'], 2);
        return $result;
    }

    $discount = calculateCouponDiscount($coupon, $orderSubtotal);

    if ($discount <= 0) {
        $result['error'] = 'Coupon does not apply to this order.';
        return $result;
    }

    $result['valid'] = true;
    $result['coupon'] = $coupon;
    $result['discount_amount'] = $discount;

    return $result;
}

function recordCouponUsage($couponId, $userId, $orderId)
{
    global $pdo;

    if (!$couponId || !$userId || !$orderId) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO coupon_usage (coupon_id, user_id, order_id, used_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$couponId, $userId, $orderId]);

        $stmt = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
        $stmt->execute([$couponId]);

        return true;
    } catch (PDOException $e) {
        error_log('Record coupon usage error: ' . $e->getMessage());
        return false;
    }
}

function getCoupons($onlyActive = false)
{
    global $pdo;

    $sql = "SELECT * FROM coupons";
    if ($onlyActive) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCouponById($couponId)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->execute([$couponId]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function couponCodeExists($code, $excludeId = null)
{
    global $pdo;

    $params = [$code];
    $sql = "SELECT COUNT(*) FROM coupons WHERE UPPER(code) = UPPER(?)";

    if ($excludeId) {
        $sql .= " AND id <> ?";
        $params[] = $excludeId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
}

function saveCoupon(array $data, $couponId = null)
{
    global $pdo;

    $fields = [
        'code' => strtoupper(trim($data['code'] ?? '')),
        'discount_type' => $data['discount_type'] ?? 'percentage',
        'discount_value' => (float) ($data['discount_value'] ?? 0),
        'min_order_amount' => (float) ($data['min_order_amount'] ?? 0),
        'max_discount_amount' => (float) ($data['max_discount_amount'] ?? 0),
        'usage_limit' => (int) ($data['usage_limit'] ?? 0),
        'valid_from' => $data['valid_from'] ?? null,
        'valid_until' => $data['valid_until'] ?? null,
        'is_active' => !empty($data['is_active']) ? 1 : 0
    ];

    if ($couponId) {
        $stmt = $pdo->prepare("UPDATE coupons SET code = ?, discount_type = ?, discount_value = ?, min_order_amount = ?, max_discount_amount = ?, usage_limit = ?, valid_from = ?, valid_until = ?, is_active = ? WHERE id = ?");
        return $stmt->execute([
            $fields['code'],
            $fields['discount_type'],
            $fields['discount_value'],
            $fields['min_order_amount'],
            $fields['max_discount_amount'],
            $fields['usage_limit'],
            $fields['valid_from'],
            $fields['valid_until'],
            $fields['is_active'],
            $couponId
        ]);
    }

    $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, max_discount_amount, usage_limit, valid_from, valid_until, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([
        $fields['code'],
        $fields['discount_type'],
        $fields['discount_value'],
        $fields['min_order_amount'],
        $fields['max_discount_amount'],
        $fields['usage_limit'],
        $fields['valid_from'],
        $fields['valid_until'],
        $fields['is_active']
    ]);
}

function deleteCoupon($couponId)
{
    global $pdo;

    $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
    return $stmt->execute([$couponId]);
}

// ----------------------------
// Rewards helpers
// ----------------------------

function ensureRewardsSystem()
{
    global $pdo;

    static $rewardsEnsured = false;
    if ($rewardsEnsured) {
        return;
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'reward_points'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE users ADD reward_points INT UNSIGNED NOT NULL DEFAULT 0 AFTER birthday");
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS reward_tiers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            required_orders INT UNSIGNED NOT NULL,
            discount_percent DECIMAL(5,2) NOT NULL,
            points_cost INT UNSIGNED NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_required_orders (required_orders)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS reward_point_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            order_id INT UNSIGNED NULL,
            points_change INT NOT NULL,
            balance_after INT UNSIGNED NOT NULL,
            reason VARCHAR(50) NOT NULL,
            meta_json TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_reward_logs_user (user_id, created_at),
            UNIQUE KEY uniq_user_order_reason (user_id, order_id, reason)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        foreach ([
            'reward_discount_percent' => "ALTER TABLE orders ADD reward_discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER coupon_discount",
            'reward_discount_amount' => "ALTER TABLE orders ADD reward_discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER reward_discount_percent",
            'reward_points_earned' => "ALTER TABLE orders ADD reward_points_earned INT UNSIGNED NOT NULL DEFAULT 0 AFTER reward_discount_amount",
            'reward_points_spent' => "ALTER TABLE orders ADD reward_points_spent INT UNSIGNED NOT NULL DEFAULT 0 AFTER reward_points_earned"
        ] as $column => $statement) {
            $columnStmt = $pdo->query("SHOW COLUMNS FROM orders LIKE '" . $column . "'");
            if (!$columnStmt->fetch()) {
                $pdo->exec($statement);
            }
        }
    } catch (PDOException $e) {
        error_log('Ensure rewards system failed: ' . $e->getMessage());
    }

    $rewardsEnsured = true;
}

function getRewardsPointsPerOrder()
{
    return 10;
}

function getUserRewardPoints($userId)
{
    global $pdo;

    ensureRewardsSystem();

    if (empty($userId)) {
        return 0;
    }

    try {
        $stmt = $pdo->prepare("SELECT reward_points FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([(int) $userId]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Get user reward points failed: ' . $e->getMessage());
        return 0;
    }
}

function getUserQualifyingOrderCount($userId)
{
    global $pdo;

    if (empty($userId)) {
        return 0;
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status <> 'cancelled'");
        $stmt->execute([(int) $userId]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Get qualifying order count failed: ' . $e->getMessage());
        return 0;
    }
}

function getRewardTiers($onlyActive = false)
{
    global $pdo;

    ensureRewardsSystem();

    $sql = "SELECT * FROM reward_tiers";
    if ($onlyActive) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY required_orders ASC, id ASC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log('Get reward tiers failed: ' . $e->getMessage());
        return [];
    }
}

function getRewardTierById($tierId)
{
    global $pdo;

    ensureRewardsSystem();

    if (empty($tierId)) {
        return null;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM reward_tiers WHERE id = ? LIMIT 1");
        $stmt->execute([(int) $tierId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log('Get reward tier by id failed: ' . $e->getMessage());
        return null;
    }
}

function rewardTierMinOrdersExists($requiredOrders, $excludeId = null)
{
    global $pdo;

    ensureRewardsSystem();

    $params = [(int) $requiredOrders];
    $sql = "SELECT COUNT(*) FROM reward_tiers WHERE required_orders = ?";

    if ($excludeId) {
        $sql .= " AND id <> ?";
        $params[] = (int) $excludeId;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log('Check reward tier duplicate failed: ' . $e->getMessage());
        return true;
    }
}

function saveRewardTier(array $data, $tierId = null)
{
    global $pdo;

    ensureRewardsSystem();

    $requiredOrders = max(1, (int) ($data['required_orders'] ?? 0));
    $discountPercent = max(0, min(100, (float) ($data['discount_percent'] ?? 0)));
    $pointsCost = max(0, (int) ($data['points_cost'] ?? 0));
    $isActive = !empty($data['is_active']) ? 1 : 0;

    try {
        if ($tierId) {
            $stmt = $pdo->prepare("UPDATE reward_tiers SET required_orders = ?, discount_percent = ?, points_cost = ?, is_active = ? WHERE id = ?");
            return $stmt->execute([$requiredOrders, $discountPercent, $pointsCost, $isActive, (int) $tierId]);
        }

        $stmt = $pdo->prepare("INSERT INTO reward_tiers (required_orders, discount_percent, points_cost, is_active) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$requiredOrders, $discountPercent, $pointsCost, $isActive]);
    } catch (PDOException $e) {
        error_log('Save reward tier failed: ' . $e->getMessage());
        return false;
    }
}

function deleteRewardTier($tierId)
{
    global $pdo;

    ensureRewardsSystem();

    if (empty($tierId)) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM reward_tiers WHERE id = ?");
        return $stmt->execute([(int) $tierId]);
    } catch (PDOException $e) {
        error_log('Delete reward tier failed: ' . $e->getMessage());
        return false;
    }
}

function getCurrentRewardTierForOrders($orderCount)
{
    $orderCount = max(0, (int) $orderCount);
    $tiers = getRewardTiers(true);
    $current = null;

    foreach ($tiers as $tier) {
        if ((int) $tier['required_orders'] <= $orderCount) {
            $current = $tier;
        }
    }

    return $current;
}

function getNextRewardTierForOrders($orderCount)
{
    $orderCount = max(0, (int) $orderCount);
    $tiers = getRewardTiers(true);

    foreach ($tiers as $tier) {
        if ((int) $tier['required_orders'] > $orderCount) {
            return $tier;
        }
    }

    return null;
}

function calculateRewardDiscountAmount($tier, $orderSubtotal)
{
    if (!$tier || $orderSubtotal <= 0) {
        return 0;
    }

    $discountPercent = (float) ($tier['discount_percent'] ?? 0);
    if ($discountPercent <= 0) {
        return 0;
    }

    $discount = $orderSubtotal * ($discountPercent / 100);
    return max(0, min($discount, $orderSubtotal));
}

/**
 * @return array{valid:bool,error:string,tier:?array,discount_amount:float,points_to_spend:int}
 */
function validateRewardTierForUser($tierId, $userId, $orderSubtotal): array
{
    $result = [
        'valid' => false,
        'error' => '',
        'tier' => null,
        'discount_amount' => 0,
        'points_to_spend' => 0
    ];

    $tier = getRewardTierById($tierId);
    if (!$tier || empty($tier['is_active'])) {
        $result['error'] = 'Selected reward tier is unavailable.';
        return $result;
    }

    $orderCount = getUserQualifyingOrderCount($userId);
    if ($orderCount < (int) $tier['required_orders']) {
        $result['error'] = 'You have not unlocked this reward tier yet.';
        return $result;
    }

    $pointsBalance = getUserRewardPoints($userId);
    $pointsToSpend = (int) ($tier['points_cost'] ?? 0);
    if ($pointsToSpend > $pointsBalance) {
        $result['error'] = 'You do not have enough reward points for this tier.';
        return $result;
    }

    $discountAmount = calculateRewardDiscountAmount($tier, $orderSubtotal);
    if ($discountAmount <= 0) {
        $result['error'] = 'Reward discount does not apply to this order.';
        return $result;
    }

    $result['valid'] = true;
    $result['tier'] = $tier;
    $result['discount_amount'] = $discountAmount;
    $result['points_to_spend'] = $pointsToSpend;

    return $result;
}

function createRewardPointLog($userId, $orderId, $pointsChange, $balanceAfter, $reason, array $meta = [])
{
    global $pdo;

    $metaJson = !empty($meta) ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null;

    $stmt = $pdo->prepare("INSERT INTO reward_point_logs (user_id, order_id, points_change, balance_after, reason, meta_json, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())");

    return $stmt->execute([
        (int) $userId,
        $orderId ? (int) $orderId : null,
        (int) $pointsChange,
        max(0, (int) $balanceAfter),
        (string) $reason,
        $metaJson
    ]);
}

function awardRewardPointsForOrder($userId, $orderId, $points = null)
{
    global $pdo;

    ensureRewardsSystem();

    $userId = (int) $userId;
    $orderId = (int) $orderId;
    $pointsToAward = $points === null ? getRewardsPointsPerOrder() : (int) $points;

    if ($userId <= 0 || $orderId <= 0 || $pointsToAward <= 0) {
        return 0;
    }

    try {
        $startedTransaction = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }

        $dupStmt = $pdo->prepare("SELECT COUNT(*) FROM reward_point_logs WHERE user_id = ? AND order_id = ? AND reason = 'order_earned'");
        $dupStmt->execute([$userId, $orderId]);
        if ((int) $dupStmt->fetchColumn() > 0) {
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->commit();
            }
            return 0;
        }

        $balanceStmt = $pdo->prepare("SELECT reward_points FROM users WHERE id = ? FOR UPDATE");
        $balanceStmt->execute([$userId]);
        $currentBalance = (int) $balanceStmt->fetchColumn();
        $newBalance = $currentBalance + $pointsToAward;

        $updateStmt = $pdo->prepare("UPDATE users SET reward_points = ? WHERE id = ?");
        $updateStmt->execute([$newBalance, $userId]);

        createRewardPointLog($userId, $orderId, $pointsToAward, $newBalance, 'order_earned', [
            'points_per_order' => $pointsToAward
        ]);

        if ($startedTransaction && $pdo->inTransaction()) {
            $pdo->commit();
        }

        return $pointsToAward;
    } catch (PDOException $e) {
        if (!empty($startedTransaction) && $startedTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Award reward points failed: ' . $e->getMessage());
        return 0;
    }
}

function spendRewardPointsForOrder($userId, $orderId, $pointsToSpend, array $meta = [])
{
    global $pdo;

    ensureRewardsSystem();

    $userId = (int) $userId;
    $orderId = (int) $orderId;
    $pointsToSpend = (int) $pointsToSpend;

    if ($userId <= 0 || $orderId <= 0 || $pointsToSpend <= 0) {
        return false;
    }

    try {
        $startedTransaction = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }

        $dupStmt = $pdo->prepare("SELECT COUNT(*) FROM reward_point_logs WHERE user_id = ? AND order_id = ? AND reason = 'order_redeem'");
        $dupStmt->execute([$userId, $orderId]);
        if ((int) $dupStmt->fetchColumn() > 0) {
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->commit();
            }
            return true;
        }

        $balanceStmt = $pdo->prepare("SELECT reward_points FROM users WHERE id = ? FOR UPDATE");
        $balanceStmt->execute([$userId]);
        $currentBalance = (int) $balanceStmt->fetchColumn();

        if ($currentBalance < $pointsToSpend) {
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }

        $newBalance = $currentBalance - $pointsToSpend;

        $updateStmt = $pdo->prepare("UPDATE users SET reward_points = ? WHERE id = ?");
        $updateStmt->execute([$newBalance, $userId]);

        createRewardPointLog($userId, $orderId, -$pointsToSpend, $newBalance, 'order_redeem', $meta);

        if ($startedTransaction && $pdo->inTransaction()) {
            $pdo->commit();
        }

        return true;
    } catch (PDOException $e) {
        if (!empty($startedTransaction) && $startedTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Spend reward points failed: ' . $e->getMessage());
        return false;
    }
}

function getUserRewardPointHistory($userId, $limit = 20)
{
    global $pdo;

    ensureRewardsSystem();

    $userId = (int) $userId;
    $limit = max(1, (int) $limit);

    if ($userId <= 0) {
        return [];
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM reward_point_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT {$limit}");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log('Get reward point history failed: ' . $e->getMessage());
        return [];
    }
}

function getUserAddresses($user_id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM user_addresses 
                          WHERE user_id = ? 
                          ORDER BY is_default DESC, created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserAddressById($addressId, $userId = null)
{
    global $pdo;

    if (!$addressId) {
        return null;
    }

    $sql = "SELECT * FROM user_addresses WHERE id = ?";
    $params = [$addressId];

    if ($userId !== null) {
        $sql .= " AND user_id = ?";
        $params[] = $userId;
    }

    $stmt = $pdo->prepare($sql . " LIMIT 1");
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function addUserAddress($user_id, $address_data)
{
    global $pdo;

    ensureUserAddressCoordinateColumns();

    $address_type = $address_data['address_type'] ?? 'home';
    $street = $address_data['street_address'] ?? '';
    $city = $address_data['city'] ?? '';
    $state = $address_data['state'] ?? '';
    $zip = $address_data['zip_code'] ?? '';
    $country = $address_data['country'] ?? 'USA';
    $is_default = !empty($address_data['is_default']) ? 1 : 0;

    // If this is set as default, remove default from other addresses
    if ($is_default) {
        $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = FALSE WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }

    $latitude = null;
    $longitude = null;
    $geocodedAt = null;

    $mapsApiKey = resolveGoogleMapsApiKey();
    if ($mapsApiKey !== '') {
        $formatted = formatUserAddressLine([
            'street_address' => $street,
            'city' => $city,
            'state' => $state,
            'zip_code' => $zip,
            'country' => $country
        ]);

        $geo = geocodeAddressString($formatted, $mapsApiKey);
        if ($geo) {
            $latitude = $geo['lat'];
            $longitude = $geo['lng'];
            $geocodedAt = date('Y-m-d H:i:s');
        }
    }

    $stmt = $pdo->prepare("INSERT INTO user_addresses 
                          (user_id, address_type, street_address, city, state, zip_code, country, is_default, latitude, longitude, geocoded_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    return $stmt->execute([
        $user_id,
        $address_type,
        $street,
        $city,
        $state,
        $zip,
        $country,
        $is_default,
        $latitude,
        $longitude,
        $geocodedAt
    ]);
}

function ensureUserAddressCoordinateColumns()
{
    global $pdo;

    static $addressCoordsEnsured = false;
    if ($addressCoordsEnsured) {
        return;
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM user_addresses LIKE 'latitude'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE user_addresses ADD latitude DECIMAL(10,7) NULL AFTER country");
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM user_addresses LIKE 'longitude'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE user_addresses ADD longitude DECIMAL(10,7) NULL AFTER latitude");
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM user_addresses LIKE 'geocoded_at'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE user_addresses ADD geocoded_at DATETIME NULL AFTER longitude");
        }
    } catch (PDOException $e) {
        error_log('Failed to ensure user address coordinate columns: ' . $e->getMessage());
    }

    $addressCoordsEnsured = true;
}

function ensureInventoryCoreTables()
{
    global $pdo;

    static $inventorySchemaEnsured = false;
    if ($inventorySchemaEnsured) {
        return;
    }

    $createInventoryItemsSql = "CREATE TABLE IF NOT EXISTS inventory_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            sku_code VARCHAR(100) NULL,
            category VARCHAR(100) NULL,
            unit VARCHAR(32) NOT NULL DEFAULT 'pc',
            current_quantity DECIMAL(12,4) NOT NULL DEFAULT 0,
            reorder_point DECIMAL(12,4) NOT NULL DEFAULT 0,
            unit_cost DECIMAL(12,2) NULL,
            notes VARCHAR(255) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY sku_unique (sku_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $createInventoryLogsSql = "CREATE TABLE IF NOT EXISTS inventory_item_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            item_id INT UNSIGNED NOT NULL,
            change_quantity DECIMAL(12,4) NOT NULL,
            resulting_quantity DECIMAL(12,4) NOT NULL,
            change_type ENUM('restock','usage','waste','adjustment','return') NOT NULL DEFAULT 'adjustment',
            source VARCHAR(50) NULL,
            reference_code VARCHAR(100) NULL,
            notes VARCHAR(255) NULL,
            created_by INT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_inventory_logs_item (item_id, created_at),
            CONSTRAINT fk_inventory_logs_item FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $createProductComponentsSql = "CREATE TABLE IF NOT EXISTS product_inventory_components (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id INT UNSIGNED NOT NULL,
            item_id INT UNSIGNED NOT NULL,
            quantity_per_unit DECIMAL(12,4) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_product_component (product_id, item_id),
            INDEX idx_component_item (item_id),
            CONSTRAINT fk_component_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            CONSTRAINT fk_component_item FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $fallbackProductComponentsSql = "CREATE TABLE IF NOT EXISTS product_inventory_components (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id INT UNSIGNED NOT NULL,
            item_id INT UNSIGNED NOT NULL,
            quantity_per_unit DECIMAL(12,4) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_product_component (product_id, item_id),
            INDEX idx_component_item (item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $createUsageSql = "CREATE TABLE IF NOT EXISTS order_inventory_usage (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT UNSIGNED NOT NULL,
            item_id INT UNSIGNED NOT NULL,
            quantity_used DECIMAL(12,4) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_order_item (order_id, item_id),
            INDEX idx_usage_order (order_id),
            CONSTRAINT fk_usage_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            CONSTRAINT fk_usage_item FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $fallbackUsageSql = "CREATE TABLE IF NOT EXISTS order_inventory_usage (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT UNSIGNED NOT NULL,
            item_id INT UNSIGNED NOT NULL,
            quantity_used DECIMAL(12,4) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_order_item (order_id, item_id),
            INDEX idx_usage_order (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $ensureColumn = function (string $table, string $column, string $definition) use ($pdo) {
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
            $stmt->execute([$column]);
            $exists = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$exists) {
                try {
                    $pdo->exec("ALTER TABLE {$table} ADD {$column} {$definition}");
                } catch (PDOException $inner) {
                    if (stripos($definition, 'after') !== false) {
                        $fallbackDefinition = preg_replace('/\s+AFTER\s+[^ ]+/i', '', $definition);
                        try {
                            $pdo->exec("ALTER TABLE {$table} ADD {$column} {$fallbackDefinition}");
                            return;
                        } catch (PDOException $fallback) {
                            error_log("Failed fallback column add for {$column} on {$table}: " . $fallback->getMessage());
                        }
                    }
                    throw $inner;
                }
            }
        } catch (PDOException $e) {
            error_log("Failed ensuring column {$column} on {$table}: " . $e->getMessage());
        }
    };

    foreach ([
        'inventory_items' => $createInventoryItemsSql,
        'inventory_item_logs' => $createInventoryLogsSql,
        'product_inventory_components' => $createProductComponentsSql,
        'order_inventory_usage' => $createUsageSql
    ] as $table => $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            error_log("Inventory schema bootstrap failed for {$table}: " . $e->getMessage());

            if ($table === 'product_inventory_components') {
                try {
                    $pdo->exec($fallbackProductComponentsSql);
                } catch (PDOException $fallbackException) {
                    error_log('Fallback creation for product_inventory_components failed: ' . $fallbackException->getMessage());
                }
            } elseif ($table === 'order_inventory_usage') {
                try {
                    $pdo->exec($fallbackUsageSql);
                } catch (PDOException $fallbackUsageException) {
                    error_log('Fallback creation for order_inventory_usage failed: ' . $fallbackUsageException->getMessage());
                }
            }
        }
    }

    // retro-fit critical columns for legacy tables even if creation succeeded
    $ensureColumn('inventory_items', 'is_active', "TINYINT(1) NOT NULL DEFAULT 1 AFTER notes");
    $ensureColumn('inventory_items', 'reorder_point', "DECIMAL(12,4) NOT NULL DEFAULT 0 AFTER current_quantity");
    $ensureColumn('inventory_items', 'unit', "VARCHAR(32) NOT NULL DEFAULT 'pc' AFTER category");
    $ensureColumn('inventory_items', 'image_path', "VARCHAR(255) NULL AFTER sku_code");

    $inventorySchemaEnsured = true;
}

function inventoryItemsTableHasColumn(string $column): bool
{
    global $pdo;

    ensureInventoryCoreTables();

    static $columnCache = [];
    if (array_key_exists($column, $columnCache)) {
        return $columnCache[$column];
    }

    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM inventory_items LIKE ?");
        $stmt->execute([$column]);
        $columnCache[$column] = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Unable to inspect inventory_items schema: ' . $e->getMessage());
        $columnCache[$column] = false;
    }

    return $columnCache[$column];
}

function getInventoryUnitsCatalog(): array
{
    return [
        'pc' => 'Pieces',
        'stem' => 'Stems',
        'bundle' => 'Bundles',
        'set' => 'Sets',
        'kg' => 'Kilograms',
        'g' => 'Grams',
        'm' => 'Meters',
        'cm' => 'Centimeters'
    ];
}

function saveInventoryItem(array $data, ?int $itemId = null)
{
    global $pdo;

    ensureInventoryCoreTables();

    $executeWithDuplicateGuard = function (PDOStatement $statement, array $params = []) {
        try {
            return $statement->execute($params);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $_SESSION['error'] = $_SESSION['error'] ?? 'An inventory item with that name or SKU already exists.';
                return false;
            }
            throw $e;
        }
    };

    $name = trim($data['name'] ?? '');
    if ($name === '') {
        return false;
    }

    $sku = trim($data['sku_code'] ?? '') ?: null;
    $category = trim($data['category'] ?? '') ?: null;
    $unit = trim($data['unit'] ?? 'pc');
    $reorderPoint = max(0, (float) ($data['reorder_point'] ?? 0));
    $unitCost = $data['unit_cost'] === '' ? null : (float) $data['unit_cost'];
    $notes = trim($data['notes'] ?? '') ?: null;
    $isActive = isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1;
    $hasIsActiveColumn = inventoryItemsTableHasColumn('is_active');
    $hasImageColumn = inventoryItemsTableHasColumn('image_path');
    $hasImageData = array_key_exists('image_path', $data);
    $imagePath = $hasImageData ? trim((string) $data['image_path']) : null;
    if ($imagePath === '') {
        $imagePath = null;
    }

    if ($itemId) {
        $updateFields = "name = ?, sku_code = ?, category = ?, unit = ?, reorder_point = ?, unit_cost = ?, notes = ?";
        $params = [$name, $sku, $category, $unit, $reorderPoint, $unitCost, $notes];

        if ($hasIsActiveColumn) {
            $updateFields .= ", is_active = ?";
            $params[] = $isActive;
        }

        if ($hasImageColumn && $hasImageData) {
            $updateFields .= ", image_path = ?";
            $params[] = $imagePath;
        }

        $updateFields .= ", updated_at = NOW()";
        $params[] = $itemId;

        $stmt = $pdo->prepare("UPDATE inventory_items SET {$updateFields} WHERE id = ?");
        return $executeWithDuplicateGuard($stmt, $params);
    }

    $initialQuantity = (float) ($data['initial_quantity'] ?? 0);
    $columns = [
        'name', 'sku_code', 'category', 'unit', 'reorder_point', 'unit_cost', 'notes', 'current_quantity'
    ];
    $placeholders = array_fill(0, count($columns), '?');
    $params = [$name, $sku, $category, $unit, $reorderPoint, $unitCost, $notes, max(0, $initialQuantity)];

    if ($hasIsActiveColumn) {
        $columns[] = 'is_active';
        $placeholders[] = '?';
        $params[] = $isActive;
    }

    if ($hasImageColumn) {
        $columns[] = 'image_path';
        $placeholders[] = '?';
        $params[] = $hasImageData ? $imagePath : null;
    }

    $columnsList = implode(', ', $columns);
    $placeholderList = implode(', ', $placeholders);

    $stmt = $pdo->prepare("INSERT INTO inventory_items ({$columnsList}) VALUES ({$placeholderList})");
    if (!$executeWithDuplicateGuard($stmt, $params)) {
        return false;
    }

    $itemId = (int) $pdo->lastInsertId();
    if ($initialQuantity > 0) {
        adjustInventoryQuantity($itemId, $initialQuantity, 'restock', 'Initial quantity', 'onboarding', null, $_SESSION['user_id'] ?? null);
    }

    return $itemId;
}

function getInventoryItems(array $filters = []): array
{
    global $pdo;

    ensureInventoryCoreTables();

    $conditions = [];
    $params = [];
    $hasIsActiveColumn = inventoryItemsTableHasColumn('is_active');

    if (!empty($filters['search'])) {
        $conditions[] = '(i.name LIKE :search OR i.sku_code LIKE :search)';
        $params['search'] = '%' . trim($filters['search']) . '%';
    }

    if (!empty($filters['category'])) {
        $conditions[] = 'i.category = :category';
        $params['category'] = trim($filters['category']);
    }

    if ($hasIsActiveColumn) {
        if (isset($filters['status']) && $filters['status'] === 'inactive') {
            $conditions[] = 'i.is_active = 0';
        } elseif (empty($filters['include_inactive'])) {
            $conditions[] = 'i.is_active = 1';
        }
    }

    if (!empty($filters['hide_empty'])) {
        $conditions[] = 'i.current_quantity > 0';
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $sql = "SELECT i.*, COUNT(DISTINCT pic.product_id) AS linked_products
            FROM inventory_items i
            LEFT JOIN product_inventory_components pic ON pic.item_id = i.id
            $where
            GROUP BY i.id
            ORDER BY i.name ASC";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getInventorySummary(): array
{
    global $pdo;

    ensureInventoryCoreTables();

    $hasIsActiveColumn = inventoryItemsTableHasColumn('is_active');
    $hasReorderPointColumn = inventoryItemsTableHasColumn('reorder_point');

    $selectParts = [
        'COUNT(*) AS total_items',
        'COALESCE(SUM(current_quantity), 0) AS total_units',
        $hasReorderPointColumn
            ? 'SUM(CASE WHEN current_quantity <= reorder_point THEN 1 ELSE 0 END) AS at_risk'
            : '0 AS at_risk',
        'SUM(CASE WHEN current_quantity <= 0 THEN 1 ELSE 0 END) AS out_of_stock'
    ];

    $whereParts = [];
    if ($hasIsActiveColumn) {
        $whereParts[] = 'is_active = 1';
    }

    $sql = 'SELECT ' . implode(",\n            ", $selectParts) . ' FROM inventory_items';
    if ($whereParts) {
        $sql .= ' WHERE ' . implode(' AND ', $whereParts);
    }

    $stmt = $pdo->query($sql);

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'total_items' => (int) ($row['total_items'] ?? 0),
        'total_units' => (float) ($row['total_units'] ?? 0),
        'at_risk' => (int) ($row['at_risk'] ?? 0),
        'out_of_stock' => (int) ($row['out_of_stock'] ?? 0)
    ];
}

function getLowStockInventoryItems(?int $limit = null): array
{
    global $pdo;

    ensureInventoryCoreTables();

    if (!inventoryItemsTableHasColumn('reorder_point')) {
        return [];
    }

    $conditions = [
        'current_quantity > 0',
        'current_quantity <= reorder_point'
    ];

    if (inventoryItemsTableHasColumn('is_active')) {
        $conditions[] = 'is_active = 1';
    }

    $sql = 'SELECT * FROM inventory_items WHERE ' . implode(' AND ', $conditions) . ' ORDER BY current_quantity ASC, name ASC';
    if ($limit !== null) {
        $sql .= ' LIMIT ' . max(1, (int) $limit);
    }

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOutOfStockInventoryItems(?int $limit = null): array
{
    global $pdo;

    ensureInventoryCoreTables();

    $conditions = ['current_quantity <= 0'];

    if (inventoryItemsTableHasColumn('is_active')) {
        $conditions[] = 'is_active = 1';
    }

    $sql = 'SELECT * FROM inventory_items WHERE ' . implode(' AND ', $conditions) . ' ORDER BY name ASC';
    if ($limit !== null) {
        $sql .= ' LIMIT ' . max(1, (int) $limit);
    }

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getInventoryItemLogsByType(string $changeType, int $limit = 10): array
{
    global $pdo;

    ensureInventoryCoreTables();

    $limit = max(1, $limit);

    $stmt = $pdo->prepare("SELECT l.*, i.name AS item_name, i.unit
        FROM inventory_item_logs l
        JOIN inventory_items i ON l.item_id = i.id
        WHERE l.change_type = :type
        ORDER BY l.created_at DESC
        LIMIT :limit");
    $stmt->bindValue(':type', $changeType);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getInventoryActivity($limit = 15): array
{
    global $pdo;

    ensureInventoryCoreTables();

    $limit = max(1, (int) $limit);

    $stmt = $pdo->prepare("SELECT l.*, i.name AS item_name, i.unit
        FROM inventory_item_logs l
        JOIN inventory_items i ON l.item_id = i.id
        ORDER BY l.created_at DESC
        LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getInventoryItemById($itemId)
{
    global $pdo;

    ensureInventoryCoreTables();

    $stmt = $pdo->prepare("SELECT * FROM inventory_items WHERE id = ? LIMIT 1");
    $stmt->execute([(int) $itemId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function adjustInventoryQuantity($itemId, $changeQuantity, $changeType, $notes = '', $source = 'manual', $referenceCode = null, $performedBy = null)
{
    global $pdo;

    ensureInventoryCoreTables();

    $itemId = (int) $itemId;
    $changeQuantity = (float) $changeQuantity;

    if ($itemId <= 0 || abs($changeQuantity) < 0.0001) {
        return ['success' => false, 'message' => 'Invalid inventory adjustment request.'];
    }

    $allowedTypes = ['restock', 'usage', 'waste', 'adjustment', 'return'];
    if (!in_array($changeType, $allowedTypes, true)) {
        $changeType = 'adjustment';
    }

    $manageTransaction = !$pdo->inTransaction();
    if ($manageTransaction) {
        $pdo->beginTransaction();
    }

    try {
        $stmt = $pdo->prepare("SELECT current_quantity FROM inventory_items WHERE id = ? FOR UPDATE");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            throw new RuntimeException('Inventory item not found.');
        }

        $newQuantity = (float) $item['current_quantity'] + $changeQuantity;
        if ($newQuantity < 0) {
            if ($manageTransaction) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Not enough stock to complete this adjustment.'];
        }

        $stmt = $pdo->prepare("UPDATE inventory_items SET current_quantity = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newQuantity, $itemId]);

        $stmt = $pdo->prepare("INSERT INTO inventory_item_logs (item_id, change_quantity, resulting_quantity, change_type, source, reference_code, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $itemId,
            $changeQuantity,
            $newQuantity,
            $changeType,
            $source,
            $referenceCode,
            $notes,
            $performedBy
        ]);

        if ($manageTransaction) {
            $pdo->commit();
        }
    } catch (Exception $e) {
        if ($manageTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Inventory adjustment failed: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Unable to update inventory right now.'];
    }

    recalculateProductsForInventoryItem($itemId);

    return ['success' => true, 'new_quantity' => $newQuantity];
}

function getProductInventoryComponents($productId)
{
    global $pdo;

    ensureInventoryCoreTables();

    $stmt = $pdo->prepare("SELECT pic.*, ii.name AS item_name, ii.unit, ii.current_quantity
        FROM product_inventory_components pic
        JOIN inventory_items ii ON pic.item_id = ii.id
        WHERE pic.product_id = ?
        ORDER BY ii.name ASC");
    $stmt->execute([(int) $productId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getInventoryItemLinkedProducts(): array
{
    global $pdo;

    ensureInventoryCoreTables();

    $sql = "SELECT pic.item_id, pic.product_id, pic.quantity_per_unit, p.name AS product_name, c.name AS category_name
            FROM product_inventory_components pic
            JOIN products p ON pic.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            ORDER BY p.name ASC";

    $stmt = $pdo->query($sql);
    $map = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $itemId = (int) ($row['item_id'] ?? 0);
        if (!isset($map[$itemId])) {
            $map[$itemId] = [];
        }
        $map[$itemId][] = $row;
    }

    return $map;
}

function saveProductInventoryComponents($productId, array $components)
{
    global $pdo;

    ensureInventoryCoreTables();

    $productId = (int) $productId;
    if ($productId <= 0) {
        return false;
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM product_inventory_components WHERE product_id = ?")->execute([$productId]);

        if (!empty($components)) {
            $insert = $pdo->prepare("INSERT INTO product_inventory_components (product_id, item_id, quantity_per_unit) VALUES (?, ?, ?)");
            foreach ($components as $component) {
                $itemId = (int) ($component['item_id'] ?? 0);
                $quantity = (float) ($component['quantity'] ?? 0);
                if ($itemId <= 0 || $quantity <= 0) {
                    continue;
                }
                $insert->execute([$productId, $itemId, $quantity]);
            }
        }

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Failed to save product components: ' . $e->getMessage());
        return false;
    }

    recalculateProductStockFromInventory($productId);
    return true;
}

function saveInventoryItemProductLinks($itemId, array $links)
{
    global $pdo;

    ensureInventoryCoreTables();

    $itemId = (int) $itemId;
    if ($itemId <= 0) {
        return false;
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM product_inventory_components WHERE item_id = ?")->execute([$itemId]);

        if (!empty($links)) {
            $insert = $pdo->prepare("INSERT INTO product_inventory_components (product_id, item_id, quantity_per_unit) VALUES (?, ?, ?)");
            foreach ($links as $link) {
                $productId = (int) ($link['product_id'] ?? 0);
                $quantity = (float) ($link['quantity'] ?? 0);
                if ($productId <= 0 || $quantity <= 0) {
                    continue;
                }
                $insert->execute([$productId, $itemId, $quantity]);
            }
        }

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Failed to save inventory item links: ' . $e->getMessage());
        return false;
    }

    recalculateProductsForInventoryItem($itemId);
    return true;
}

function recalculateProductStockFromInventory($productId)
{
    global $pdo;

    ensureInventoryCoreTables();

    $productId = (int) $productId;
    if ($productId <= 0) {
        return 0;
    }

    $components = getProductInventoryComponents($productId);
    if (empty($components)) {
        return null;
    }

    $buildable = null;
    foreach ($components as $component) {
        $perUnit = (float) $component['quantity_per_unit'];
        if ($perUnit <= 0) {
            continue;
        }
        $available = (float) $component['current_quantity'];
        $componentCapacity = (int) floor($available / $perUnit);
        $buildable = $buildable === null ? $componentCapacity : min($buildable, $componentCapacity);
    }

    if ($buildable === null) {
        $buildable = 0;
    }

    $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
    $stmt->execute([$buildable, $productId]);

    return $buildable;
}

function recalculateProductsForInventoryItem($itemId)
{
    global $pdo;

    ensureInventoryCoreTables();

    $stmt = $pdo->prepare("SELECT DISTINCT product_id FROM product_inventory_components WHERE item_id = ?");
    $stmt->execute([(int) $itemId]);
    $productIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($productIds as $productId) {
        recalculateProductStockFromInventory((int) $productId);
    }
}

function consumeInventoryForOrder($orderId)
{
    global $pdo;

    ensureInventoryCoreTables();

    $orderId = (int) $orderId;
    if ($orderId <= 0) {
        return ['success' => false, 'message' => 'Invalid order reference.'];
    }

    if (isOutOfStockOrderingEnabled()) {
        return ['success' => true, 'message' => 'Stock override active. Inventory reservation skipped.'];
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_inventory_usage WHERE order_id = ?");
    $stmt->execute([$orderId]);
    if ((int) $stmt->fetchColumn() > 0) {
        return ['success' => true, 'message' => 'Inventory already reserved for this order.'];
    }

    $itemsStmt = $pdo->prepare("SELECT oi.product_id, oi.quantity, p.name AS product_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?");
    $itemsStmt->execute([$orderId]);
    $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$orderItems) {
        return ['success' => true, 'message' => 'No order items to process.'];
    }

    $requirements = [];
    $touchedProducts = [];
    foreach ($orderItems as $orderItem) {
        $productId = (int) $orderItem['product_id'];
        $quantity = (int) $orderItem['quantity'];
        $components = getProductInventoryComponents($productId);
        if (empty($components)) {
            continue;
        }
        $touchedProducts[$productId] = $productId;
        foreach ($components as $component) {
            $perUnit = (float) $component['quantity_per_unit'];
            if ($perUnit <= 0) {
                continue;
            }
            $required = $perUnit * $quantity;
            $itemId = (int) $component['item_id'];
            $requirements[$itemId] = ($requirements[$itemId] ?? 0) + $required;
        }
    }

    if (empty($requirements)) {
        return ['success' => true, 'message' => 'None of the ordered items are linked to inventory components.'];
    }

    $manageTransaction = !$pdo->inTransaction();
    if ($manageTransaction) {
        $pdo->beginTransaction();
    }

    try {
        foreach ($requirements as $itemId => $neededQty) {
            $result = adjustInventoryQuantity(
                $itemId,
                -$neededQty,
                'usage',
                'Order #' . $orderId,
                'order',
                'order:' . $orderId,
                $_SESSION['user_id'] ?? null
            );

            if (empty($result['success'])) {
                throw new RuntimeException($result['message'] ?? 'Insufficient stock for components.');
            }
        }

        $insert = $pdo->prepare("INSERT INTO order_inventory_usage (order_id, item_id, quantity_used) VALUES (?, ?, ?)");
        foreach ($requirements as $itemId => $neededQty) {
            $insert->execute([$orderId, $itemId, $neededQty]);
        }

        if ($manageTransaction) {
            $pdo->commit();
        }
    } catch (Exception $e) {
        if ($manageTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('consumeInventoryForOrder failed: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage() ?: 'Unable to reserve inventory for this order.'];
    }

    foreach ($touchedProducts as $productId) {
        recalculateProductStockFromInventory((int) $productId);
    }

    return ['success' => true];
}

function releaseInventoryForOrder($orderId)
{
    global $pdo;

    ensureInventoryCoreTables();

    $orderId = (int) $orderId;
    if ($orderId <= 0) {
        return ['success' => false, 'message' => 'Invalid order reference.'];
    }

    $stmt = $pdo->prepare("SELECT * FROM order_inventory_usage WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        return ['success' => true, 'message' => 'No reserved inventory for this order.'];
    }

    $manageTransaction = !$pdo->inTransaction();
    if ($manageTransaction) {
        $pdo->beginTransaction();
    }

    try {
        foreach ($rows as $usage) {
            $result = adjustInventoryQuantity(
                (int) $usage['item_id'],
                (float) $usage['quantity_used'],
                'return',
                'Order #' . $orderId . ' cancellation',
                'order',
                'order:' . $orderId,
                $_SESSION['user_id'] ?? null
            );

            if (empty($result['success'])) {
                throw new RuntimeException($result['message'] ?? 'Unable to restore inventory.');
            }
        }

        $pdo->prepare("DELETE FROM order_inventory_usage WHERE order_id = ?")->execute([$orderId]);

        if ($manageTransaction) {
            $pdo->commit();
        }
    } catch (Exception $e) {
        if ($manageTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('releaseInventoryForOrder failed: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage() ?: 'Unable to restore inventory for this order.'];
    }

    return ['success' => true];
}

function ensureOrderShippingColumns()
{
    global $pdo;

    static $orderShippingEnsured = false;
    if ($orderShippingEnsured) {
        return;
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'shipping_fee'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE orders ADD shipping_fee DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER total_amount");
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'shipping_distance_km'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE orders ADD shipping_distance_km DECIMAL(10,2) NULL AFTER shipping_fee");
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'shipping_distance_miles'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE orders ADD shipping_distance_miles DECIMAL(10,2) NULL AFTER shipping_distance_km");
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'shipping_details'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE orders ADD shipping_details TEXT NULL AFTER shipping_distance_miles");
        }
    } catch (PDOException $e) {
        error_log('Order shipping column migration failed: ' . $e->getMessage());
    }

    $orderShippingEnsured = true;
}

function ensureOrderRecipientColumns()
{
    global $pdo;

    static $orderRecipientEnsured = false;
    if ($orderRecipientEnsured) {
        return;
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'recipient_is_alternate'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE orders ADD recipient_is_alternate TINYINT(1) NOT NULL DEFAULT 0 AFTER shipping_details");
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'recipient_name'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE orders ADD recipient_name VARCHAR(255) NULL AFTER recipient_is_alternate");
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'recipient_contact'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE orders ADD recipient_contact VARCHAR(100) NULL AFTER recipient_name");
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'recipient_address'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE orders ADD recipient_address TEXT NULL AFTER recipient_contact");
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'recipient_notes'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE orders ADD recipient_notes TEXT NULL AFTER recipient_address");
        }
    } catch (PDOException $e) {
        error_log('Order recipient column migration failed: ' . $e->getMessage());
    }

    $orderRecipientEnsured = true;
}

function ensureOrderArchiveColumn()
{
    global $pdo;

    static $orderArchiveEnsured = false;
    if ($orderArchiveEnsured) {
        return;
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'is_archived'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE orders ADD is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
        }
    } catch (PDOException $e) {
        error_log('Order archive column migration failed: ' . $e->getMessage());
    }

    $orderArchiveEnsured = true;
}

function archiveOrder($orderId, $archived = true)
{
    global $pdo;

    ensureOrderArchiveColumn();

    $orderId = (int) $orderId;
    if ($orderId <= 0) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("UPDATE orders SET is_archived = ? WHERE id = ?");
        return $stmt->execute([$archived ? 1 : 0, $orderId]);
    } catch (PDOException $e) {
        error_log('Order archive toggle failed: ' . $e->getMessage());
        return false;
    }
}

function ensureShippingSettingsTable()
{
    global $pdo;

    static $shippingSettingsEnsured = false;
    if ($shippingSettingsEnsured) {
        return;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS shipping_settings (
        id TINYINT UNSIGNED PRIMARY KEY,
        per_km_rate DECIMAL(10,2) NOT NULL DEFAULT 10.00,
        per_mile_rate DECIMAL(10,2) NOT NULL DEFAULT 16.09,
        base_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        minimum_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        zone_roxas_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        zone_oriental_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        zone_out_province_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        calculation_unit ENUM('km','mile') NOT NULL DEFAULT 'km',
        google_maps_api_key VARCHAR(255) NULL,
        allow_out_of_stock_orders TINYINT(1) NOT NULL DEFAULT 0,
        updated_by INT UNSIGNED NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $columnsToEnsure = [
        'google_maps_api_key' => "ALTER TABLE shipping_settings ADD google_maps_api_key VARCHAR(255) NULL AFTER calculation_unit",
        'zone_roxas_fee' => "ALTER TABLE shipping_settings ADD zone_roxas_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER minimum_fee",
        'zone_oriental_fee' => "ALTER TABLE shipping_settings ADD zone_oriental_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER zone_roxas_fee",
        'zone_out_province_fee' => "ALTER TABLE shipping_settings ADD zone_out_province_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER zone_oriental_fee",
        'allow_out_of_stock_orders' => "ALTER TABLE shipping_settings ADD allow_out_of_stock_orders TINYINT(1) NOT NULL DEFAULT 0 AFTER google_maps_api_key"
    ];

    foreach ($columnsToEnsure as $column => $statement) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM shipping_settings LIKE '" . $column . "'");
            if (!$stmt->fetch()) {
                $pdo->exec($statement);
            }
        } catch (PDOException $e) {
            error_log('Failed to ensure ' . $column . ' column: ' . $e->getMessage());
        }
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM shipping_settings WHERE id = 1");
    $stmt->execute();
    if ((int) $stmt->fetchColumn() === 0) {
        $insert = $pdo->prepare("INSERT INTO shipping_settings (id) VALUES (1)");
        $insert->execute();
    }

    $shippingSettingsEnsured = true;
}

function getShippingSettings()
{
    global $pdo;

    ensureShippingSettingsTable();

    $stmt = $pdo->prepare("SELECT * FROM shipping_settings WHERE id = 1 LIMIT 1");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$settings) {
        return [
            'per_km_rate' => 10.00,
            'per_mile_rate' => 16.09,
            'base_fee' => 0.00,
            'minimum_fee' => 0.00,
            'zone_roxas_fee' => 0.00,
            'zone_oriental_fee' => 0.00,
            'zone_out_province_fee' => 0.00,
            'calculation_unit' => 'km',
            'google_maps_api_key' => '',
            'allow_out_of_stock_orders' => 0,
            'updated_by' => null,
            'updated_at' => null
        ];
    }

    foreach ([
        'google_maps_api_key' => '',
        'zone_roxas_fee' => 0.00,
        'zone_oriental_fee' => 0.00,
        'zone_out_province_fee' => 0.00,
        'allow_out_of_stock_orders' => 0
    ] as $key => $defaultValue) {
        if (!array_key_exists($key, $settings)) {
            $settings[$key] = $defaultValue;
        }
    }

    return $settings;
}

function updateShippingSettings(array $data, $adminId = null)
{
    global $pdo;

    ensureShippingSettingsTable();

    $roxasFee = max(0, (float) ($data['zone_roxas_fee'] ?? 0));
    $orientalFee = max(0, (float) ($data['zone_oriental_fee'] ?? 0));
    $outProvinceFee = max(0, (float) ($data['zone_out_province_fee'] ?? 0));
    $apiKey = trim((string) ($data['google_maps_api_key'] ?? ''));

    $stmt = $pdo->prepare("UPDATE shipping_settings 
        SET zone_roxas_fee = ?, zone_oriental_fee = ?, zone_out_province_fee = ?, google_maps_api_key = ?, updated_by = ? 
        WHERE id = 1");

    return $stmt->execute([
        $roxasFee,
        $orientalFee,
        $outProvinceFee,
        $apiKey,
        $adminId
    ]);
}

function resolveGoogleMapsApiKey(?array $shippingSettings = null): string
{
    if (defined('GOOGLE_MAPS_API_KEY')) {
        $constantKey = trim((string) GOOGLE_MAPS_API_KEY);
        if ($constantKey !== '') {
            return $constantKey;
        }
    }

    if ($shippingSettings !== null) {
        $settingsKey = trim((string) ($shippingSettings['google_maps_api_key'] ?? ''));
        if ($settingsKey !== '') {
            return $settingsKey;
        }
    }

    static $cachedKey = null;
    if ($cachedKey !== null) {
        return $cachedKey;
    }

    $settings = getShippingSettings();
    $cachedKey = trim((string) ($settings['google_maps_api_key'] ?? ''));

    return $cachedKey;
}

function isOutOfStockOrderingEnabled(): bool
{
    if (array_key_exists('__allow_out_of_stock_orders', $GLOBALS)) {
        return (bool) $GLOBALS['__allow_out_of_stock_orders'];
    }

    $settings = getShippingSettings();
    $enabled = !empty($settings['allow_out_of_stock_orders']);
    $GLOBALS['__allow_out_of_stock_orders'] = $enabled;

    return $enabled;
}

function updateOutOfStockOrderingToggle(bool $enabled, $adminId = null): bool
{
    global $pdo;

    ensureShippingSettingsTable();

    $stmt = $pdo->prepare("UPDATE shipping_settings SET allow_out_of_stock_orders = ?, updated_by = ? WHERE id = 1");
    $result = $stmt->execute([$enabled ? 1 : 0, $adminId]);

    if ($result) {
        $GLOBALS['__allow_out_of_stock_orders'] = $enabled;
    }

    return $result;
}

function formatUserAddressLine(array $address): string
{
    $parts = [];
    foreach (['street_address', 'city', 'state'] as $key) {
        if (!empty($address[$key])) {
            $parts[] = $address[$key];
        }
    }

    $zipChunk = trim(($address['zip_code'] ?? '') . ' ' . ($address['country'] ?? ''));
    if ($zipChunk !== '') {
        $parts[] = $zipChunk;
    }

    return trim(implode(', ', array_filter($parts)));
}

function googleMapsApiGet(string $path, array $params, ?string $apiKey = null): ?array
{
    if ($apiKey === null && defined('GOOGLE_MAPS_API_KEY')) {
        $apiKey = GOOGLE_MAPS_API_KEY;
    }

    $apiKey = trim((string) $apiKey);
    if ($apiKey === '') {
        return null;
    }

    $params['key'] = $apiKey;
    $url = 'https://maps.googleapis.com/maps/api/' . ltrim($path, '/') . '?' . http_build_query($params);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_FOLLOWLOCATION => true
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        error_log('Google Maps API request failed: ' . curl_error($ch));
        curl_close($ch);
        return null;
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($statusCode !== 200) {
        error_log('Google Maps API responded with status ' . $statusCode . ' for path ' . $path);
        return null;
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return null;
    }

    return $decoded;
}

function geocodeAddressString(string $fullAddress, ?string $apiKey = null): ?array
{
    if ($fullAddress === '') {
        return null;
    }

    $payload = googleMapsApiGet('geocode/json', ['address' => $fullAddress], $apiKey);

    if (!$payload || ($payload['status'] ?? '') !== 'OK') {
        return null;
    }

    $location = $payload['results'][0]['geometry']['location'] ?? null;
    if (!$location || !isset($location['lat'], $location['lng'])) {
        return null;
    }

    return ['lat' => (float) $location['lat'], 'lng' => (float) $location['lng']];
}

function ensureAddressHasCoordinates(array $address, ?string $apiKey = null): array
{
    global $pdo;

    ensureUserAddressCoordinateColumns();

    if (!empty($address['latitude']) && !empty($address['longitude'])) {
        return $address;
    }

    $formatted = formatUserAddressLine($address);
    if ($formatted === '') {
        return $address;
    }

    $geo = geocodeAddressString($formatted, $apiKey);
    if (!$geo) {
        return $address;
    }

    $address['latitude'] = $geo['lat'];
    $address['longitude'] = $geo['lng'];

    if (!empty($address['id'])) {
        $stmt = $pdo->prepare("UPDATE user_addresses SET latitude = ?, longitude = ?, geocoded_at = NOW() WHERE id = ?");
        $stmt->execute([$geo['lat'], $geo['lng'], $address['id']]);
    }

    return $address;
}

function calculateShippingQuote(array $address, string $fulfillmentMethod = 'delivery'): array
{
    ensureShippingSettingsTable();

    $baseResult = [
        'success' => false,
        'fee' => null,
        'distance_km' => null,
        'distance_miles' => null,
        'unit' => null,
        'rate_used' => null,
        'base_fee' => null,
        'minimum_fee' => null,
        'distance_text' => null,
        'duration_text' => null,
        'messages' => [],
        'address_label' => formatUserAddressLine($address),
        'zone' => null,
        'rule' => null
    ];

    if ($fulfillmentMethod !== 'delivery') {
        $baseResult['success'] = true;
        $baseResult['fee'] = 0.00;
        $baseResult['messages'][] = 'Store pickup selected. No shipping fee applied.';
        return $baseResult;
    }

    $shippingSettings = getShippingSettings();

    $addressLabel = $baseResult['address_label'];
    $searchSource = strtolower(trim(
        ($addressLabel ? $addressLabel . ' ' : '') .
        (($address['city'] ?? '') . ' ') .
        (($address['state'] ?? '') . ' ') .
        ($address['country'] ?? '')
    ));
    $searchText = preg_replace('/\s+/', ' ', preg_replace('/[^a-z\s]/', ' ', $searchSource));

    if ($searchText === '') {
        $baseResult['messages'][] = 'This address is incomplete. Please edit it before requesting delivery.';
        return $baseResult;
    }

    $hasOriental = strpos($searchText, 'oriental mindoro') !== false;
    $hasRoxas = $hasOriental && strpos($searchText, 'roxas') !== false;

    $zone = 'out_province';
    $zoneLabel = 'Outside Oriental Mindoro';
    $ruleLabel = 'No "Oriental Mindoro" keyword found';
    $fee = (float) ($shippingSettings['zone_out_province_fee'] ?? 0);

    if ($hasOriental && $hasRoxas) {
        $zone = 'roxas';
        $zoneLabel = 'Roxas, Oriental Mindoro';
        $ruleLabel = 'Contains both "Roxas" and "Oriental Mindoro"';
        $fee = (float) ($shippingSettings['zone_roxas_fee'] ?? 0);
    } elseif ($hasOriental) {
        $zone = 'oriental';
        $zoneLabel = 'Outside Roxas (Oriental Mindoro)';
        $ruleLabel = 'Contains "Oriental Mindoro" but not "Roxas"';
        $fee = (float) ($shippingSettings['zone_oriental_fee'] ?? 0);
    }

    $baseResult['success'] = true;
    $baseResult['fee'] = round($fee, 2);
    $baseResult['distance_km'] = null;
    $baseResult['distance_miles'] = null;
    $baseResult['unit'] = 'zone';
    $baseResult['rate_used'] = $zoneLabel;
    $baseResult['base_fee'] = null;
    $baseResult['minimum_fee'] = null;
    $baseResult['distance_text'] = $zoneLabel;
    $baseResult['duration_text'] = 'Zone-based rate';
    $baseResult['zone'] = $zone;
    $baseResult['rule'] = $ruleLabel;
    $baseResult['messages'][] = 'Applied ' . $zoneLabel . ' shipping fee.';

    return $baseResult;
}

function ensureProductAvailabilityColumn()
{
    global $pdo;

    static $availabilityChecked = false;
    if ($availabilityChecked) {
        return;
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'availability_status'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE products ADD availability_status VARCHAR(20) NOT NULL DEFAULT 'available' AFTER stock_quantity");
            $pdo->exec("UPDATE products SET availability_status = 'available' WHERE availability_status IS NULL OR availability_status = ''");
        }
    } catch (PDOException $e) {
        error_log('Availability status migration failed: ' . $e->getMessage());
    }

    $availabilityChecked = true;
}

function ensureLowStockThresholdColumn()
{
    global $pdo;

    static $thresholdChecked = false;
    if ($thresholdChecked) {
        return;
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'low_stock_threshold'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE products ADD low_stock_threshold INT NOT NULL DEFAULT 5");
            $pdo->exec("UPDATE products SET low_stock_threshold = 5 WHERE low_stock_threshold IS NULL");
        }
    } catch (PDOException $e) {
        error_log('Low stock threshold migration failed: ' . $e->getMessage());
    }

    $thresholdChecked = true;
}

function ensureCustomizeProductColumns()
{
    global $pdo;

    static $customizeColumnsChecked = false;
    if ($customizeColumnsChecked) {
        return;
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'customize_step'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE products ADD customize_step TINYINT UNSIGNED NULL DEFAULT NULL AFTER tags");
        }
    } catch (PDOException $e) {
        error_log('Customize step migration failed: ' . $e->getMessage());
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'customize_archived'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE products ADD customize_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER customize_step");
        }
    } catch (PDOException $e) {
        error_log('Customize archive migration failed: ' . $e->getMessage());
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'customize_filter_id'");
        if (!$stmt->fetch()) {
            ensureCustomizeFiltersTable();
            $pdo->exec("ALTER TABLE products ADD customize_filter_id INT UNSIGNED NULL AFTER customize_step");
        }
    } catch (PDOException $e) {
        error_log('Customize filter column migration encountered an issue: ' . $e->getMessage());
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'canvas_image'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE products ADD canvas_image VARCHAR(255) NULL DEFAULT NULL AFTER image");
        }
    } catch (PDOException $e) {
        error_log('Canvas image column migration encountered an issue: ' . $e->getMessage());
    }

    $customizeColumnsChecked = true;
}

function ensureCustomBouquetDesignsTable()
{
    global $pdo;

    static $designsTableEnsured = false;
    if ($designsTableEnsured) {
        return;
    }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS custom_bouquet_designs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            order_id INT NULL DEFAULT NULL,
            status ENUM('in_cart', 'ordered', 'discarded') NOT NULL DEFAULT 'in_cart',
            preview_image VARCHAR(255) NOT NULL,
            canvas_json LONGTEXT NULL,
            items_json LONGTEXT NULL,
            design_notes TEXT NULL,
            total_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_custom_design_user_status (user_id, status),
            INDEX idx_custom_design_order (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (PDOException $e) {
        error_log('Failed to ensure custom_bouquet_designs table: ' . $e->getMessage());
    }

    $designsTableEnsured = true;
}

function getActiveCartCustomDesign($userId)
{
    global $pdo;

    $userId = (int) $userId;
    if ($userId <= 0) {
        return null;
    }

    ensureCustomBouquetDesignsTable();

    try {
        $stmt = $pdo->prepare("SELECT * FROM custom_bouquet_designs WHERE user_id = ? AND status = 'in_cart' AND order_id IS NULL ORDER BY updated_at DESC, id DESC LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (PDOException $e) {
        error_log('Failed to load active custom bouquet design: ' . $e->getMessage());
        return null;
    }
}

function getOrderCustomDesign($orderId)
{
    global $pdo;

    $orderId = (int) $orderId;
    if ($orderId <= 0) {
        return null;
    }

    ensureCustomBouquetDesignsTable();

    try {
        $stmt = $pdo->prepare("SELECT * FROM custom_bouquet_designs WHERE order_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (PDOException $e) {
        error_log('Failed to load order custom bouquet design: ' . $e->getMessage());
        return null;
    }
}

function attachCustomDesignToOrder($userId, $orderId)
{
    global $pdo;

    $userId = (int) $userId;
    $orderId = (int) $orderId;
    if ($userId <= 0 || $orderId <= 0) {
        return false;
    }

    ensureCustomBouquetDesignsTable();

    try {
        $design = getActiveCartCustomDesign($userId);
        if (!$design) {
            return false;
        }

        $stmt = $pdo->prepare("UPDATE custom_bouquet_designs SET order_id = ?, status = 'ordered' WHERE id = ?");
        return $stmt->execute([$orderId, (int) $design['id']]);
    } catch (PDOException $e) {
        error_log('Failed to attach custom bouquet design to order: ' . $e->getMessage());
        return false;
    }
}

function discardActiveCartCustomDesign($userId)
{
    global $pdo;

    $userId = (int) $userId;
    if ($userId <= 0) {
        return false;
    }

    ensureCustomBouquetDesignsTable();

    try {
        $stmt = $pdo->prepare("UPDATE custom_bouquet_designs SET status = 'discarded' WHERE user_id = ? AND status = 'in_cart' AND order_id IS NULL");
        return $stmt->execute([$userId]);
    } catch (PDOException $e) {
        error_log('Failed to discard active custom bouquet design: ' . $e->getMessage());
        return false;
    }
}

function getCustomizeStepDefinitions()
{
    static $definitions = null;

    if ($definitions === null) {
        $definitions = [
            1 => ['label' => 'Step 1 - Container Base', 'key' => 'container'],
            2 => ['label' => 'Step 2 - Hero Flowers', 'key' => 'flowers'],
            3 => ['label' => 'Step 3 - Fillers & Greens', 'key' => 'fillers'],
            4 => ['label' => 'Step 4 - Ribbon / Wrap', 'key' => 'ribbon']
        ];
    }

    return $definitions;
}

function generateSlug($string, $delimiter = '-')
{
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9]+/', $delimiter, $slug);
    $slug = preg_replace('/' . preg_quote($delimiter, '/') . '+/', $delimiter, $slug);
    return trim($slug, $delimiter);
}

function ensureCustomizeFiltersTable()
{
    global $pdo;

    static $filtersEnsured = false;
    if ($filtersEnsured) {
        return;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS customize_step_filters (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        step_key VARCHAR(50) NOT NULL,
        label VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        display_order INT UNSIGNED NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY step_slug (step_key, slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $defaultFilters = [
        'container' => [
            ['label' => 'Pot', 'slug' => 'pot', 'display_order' => 1],
            ['label' => 'Bouquet', 'slug' => 'bouquet', 'display_order' => 2],
            ['label' => 'Basket / Box', 'slug' => 'basket-box', 'display_order' => 3]
        ],
        'flowers' => [
            ['label' => 'Warm', 'slug' => 'warm', 'display_order' => 1],
            ['label' => 'Cool', 'slug' => 'cool', 'display_order' => 2],
            ['label' => 'Pastel', 'slug' => 'pastel', 'display_order' => 3],
            ['label' => 'White / Neutral', 'slug' => 'white-neutral', 'display_order' => 4]
        ]
    ];

    $insertStmt = $pdo->prepare("INSERT IGNORE INTO customize_step_filters (step_key, label, slug, display_order) VALUES (?, ?, ?, ?)");
    foreach ($defaultFilters as $stepKey => $filters) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM customize_step_filters WHERE step_key = ?");
        $stmt->execute([$stepKey]);
        $existingCount = (int) $stmt->fetchColumn();
        if ($existingCount === 0) {
            foreach ($filters as $filter) {
                $insertStmt->execute([$stepKey, $filter['label'], $filter['slug'], $filter['display_order']]);
            }
        }
    }

    $filtersEnsured = true;
}

function getCustomizeFiltersGrouped()
{
    global $pdo;

    ensureCustomizeFiltersTable();

    $stmt = $pdo->query("SELECT * FROM customize_step_filters ORDER BY step_key ASC, display_order ASC, label ASC");
    $grouped = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $grouped[$row['step_key']][] = $row;
    }
    return $grouped;
}

function getLowStockProducts($limit = null)
{
    global $pdo;

    ensureLowStockThresholdColumn();

    $sql = "SELECT p.*, c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.stock_quantity <= p.low_stock_threshold
            ORDER BY p.stock_quantity ASC, p.name ASC";

    if ($limit !== null) {
        $limit = max(0, (int) $limit);
        $sql .= " LIMIT $limit";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOutOfStockProducts()
{
    global $pdo;

    ensureLowStockThresholdColumn();

    $stmt = $pdo->query("SELECT p.*, c.name AS category_name
                          FROM products p
                          LEFT JOIN categories c ON p.category_id = c.id
                          WHERE p.stock_quantity <= 0
                          ORDER BY p.name ASC");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getInventoryLogsByReason($reason, $limit = 10)
{
    global $pdo;

    $limit = max(1, (int) $limit);

    $stmt = $pdo->prepare("SELECT l.*, p.name AS product_name
                           FROM inventory_logs l
                           LEFT JOIN products p ON l.product_id = p.id
                           WHERE l.reason = ?
                           ORDER BY l.id DESC
                           LIMIT $limit");
    $stmt->execute([$reason]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getInventoryOverview()
{
    global $pdo;

    ensureLowStockThresholdColumn();

    $overview = [
        'total_skus' => 0,
        'total_units' => 0,
        'low_stock' => 0,
        'out_of_stock' => 0
    ];

    $stmt = $pdo->query("SELECT COUNT(*) AS total_skus,
                                 COALESCE(SUM(stock_quantity), 0) AS total_units,
                                 SUM(CASE WHEN stock_quantity <= low_stock_threshold THEN 1 ELSE 0 END) AS low_stock,
                                 SUM(CASE WHEN stock_quantity <= 0 THEN 1 ELSE 0 END) AS out_of_stock
                          FROM products");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $overview = $result;
    }

    return $overview;
}

function subscribeToNewsletter($email)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("INSERT INTO newsletter_subscriptions (email) VALUES (?) 
                              ON DUPLICATE KEY UPDATE is_active = TRUE");
        return $stmt->execute([$email]);
    } catch (PDOException $e) {
        return false;
    }
}

function getRelatedProducts($product_id, $category_id, $limit = 4)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM products 
                              WHERE category_id = ? AND id != ? AND status = 'active' 
                              ORDER BY RAND() 
                              LIMIT ?");
        $stmt->execute([$category_id, $product_id, (int) $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Related products error: " . $e->getMessage());
        return []; // Return empty array on error
    }
}

function getBestSellingProducts($limit = 8)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT p.*, SUM(oi.quantity) as total_sold 
                              FROM products p 
                              JOIN order_items oi ON p.id = oi.product_id 
                              JOIN orders o ON oi.order_id = o.id 
                              WHERE o.status != 'cancelled' 
                              GROUP BY p.id 
                              ORDER BY total_sold DESC 
                              LIMIT ?");
        $stmt->execute([(int) $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Best selling products error: " . $e->getMessage());
        return [];
    }
}

function getNewArrivals($limit = 8)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM products 
                              ORDER BY created_at DESC 
                              LIMIT ?");
        $stmt->execute([(int) $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("New arrivals error: " . $e->getMessage());
        return [];
    }
}

// ----------------------------
// Support messaging helpers
// ----------------------------

function ensureSupportMessagingTables()
{
    global $pdo;

    static $supportTablesEnsured = false;
    if ($supportTablesEnsured) {
        return;
    }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS contact_threads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            status ENUM('open','pending','closed') NOT NULL DEFAULT 'open',
            last_message_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_status (status),
            CONSTRAINT fk_contact_thread_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS contact_thread_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            thread_id INT NOT NULL,
            sender_type ENUM('customer','admin') NOT NULL,
            sender_id INT NULL,
            message TEXT NOT NULL,
            attachment_path VARCHAR(255) NULL,
            attachment_name VARCHAR(255) NULL,
            attachment_mime VARCHAR(100) NULL,
            attachment_size INT NULL,
            is_read_by_customer TINYINT(1) NOT NULL DEFAULT 0,
            is_read_by_admin TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_thread (thread_id),
            INDEX idx_sender (sender_type),
            CONSTRAINT fk_contact_thread_messages_thread FOREIGN KEY (thread_id) REFERENCES contact_threads(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $attachmentColumns = [
            'attachment_path' => "ALTER TABLE contact_thread_messages ADD COLUMN attachment_path VARCHAR(255) NULL AFTER message",
            'attachment_name' => "ALTER TABLE contact_thread_messages ADD COLUMN attachment_name VARCHAR(255) NULL AFTER attachment_path",
            'attachment_mime' => "ALTER TABLE contact_thread_messages ADD COLUMN attachment_mime VARCHAR(100) NULL AFTER attachment_name",
            'attachment_size' => "ALTER TABLE contact_thread_messages ADD COLUMN attachment_size INT NULL AFTER attachment_mime"
        ];

        foreach ($attachmentColumns as $column => $sql) {
            if (!supportMessageColumnExists($column)) {
                $pdo->exec($sql);
            }
        }

        $supportTablesEnsured = true;
    } catch (PDOException $e) {
        error_log('Support messaging table creation failed: ' . $e->getMessage());
    }
}

function supportMessageColumnExists($column)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM contact_thread_messages LIKE ?");
        $stmt->execute([$column]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Support column check failed: ' . $e->getMessage());
    }

    return false;
}

function getSupportAttachmentDirectory()
{
    return dirname(__DIR__) . '/storage/support_attachments';
}

function ensureSupportAttachmentDirectory()
{
    $directory = getSupportAttachmentDirectory();

    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    return $directory;
}

function processSupportAttachmentUpload($file, &$errors)
{
    if (!$file || !is_array($file)) {
        return null;
    }

    $fileError = $file['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($fileError === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($fileError !== UPLOAD_ERR_OK) {
        $errors[] = 'Unable to upload the file. Please try again.';
        return null;
    }

    $allowedMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!$mimeType || !isset($allowedMime[$mimeType])) {
        $errors[] = 'Please upload a valid image (JPG, PNG, GIF, or WEBP).';
        return null;
    }

    $maxSize = 5 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxSize) {
        $errors[] = 'Image attachments must be 5MB or smaller.';
        return null;
    }

    $uploadDir = ensureSupportAttachmentDirectory();
    $extension = $allowedMime[$mimeType];
    $uniqueName = uniqid('support_', true) . '.' . $extension;
    $fullPath = $uploadDir . '/' . $uniqueName;

    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        $errors[] = 'We could not save the attachment. Please try again.';
        return null;
    }

    $relativePath = 'storage/support_attachments/' . $uniqueName;
    $originalName = basename($file['name']) ?: ('attachment.' . $extension);
    $originalName = preg_replace('/[^A-Za-z0-9._ -]/', '', $originalName);

    return [
        'path' => $relativePath,
        'name' => $originalName,
        'mime' => $mimeType,
        'size' => (int) $file['size']
    ];
}

function getContactThreadByUser($userId)
{
    global $pdo;

    if (!$userId) {
        return null;
    }

    ensureSupportMessagingTables();

    $stmt = $pdo->prepare("SELECT * FROM contact_threads WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function getContactThread($threadId)
{
    global $pdo;

    ensureSupportMessagingTables();

    $stmt = $pdo->prepare("SELECT * FROM contact_threads WHERE id = ?");
    $stmt->execute([$threadId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function createContactThread($userId, $name, $email, $subject)
{
    global $pdo;

    ensureSupportMessagingTables();

    $subject = trim($subject) === '' ? 'General Inquiry' : trim($subject);
    $name = trim($name);
    $email = trim($email);

    $stmt = $pdo->prepare("INSERT INTO contact_threads (user_id, name, email, subject)
                           VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        $name,
        $email,
        $subject
    ]);

    return getContactThread($pdo->lastInsertId());
}

function addContactThreadMessage($threadId, $senderType, $senderId, $message, ?array $attachment = null)
{
    global $pdo;

    ensureSupportMessagingTables();

    $senderType = $senderType === 'admin' ? 'admin' : 'customer';
    $message = trim($message);
    $hasAttachment = is_array($attachment) && !empty($attachment['path']);

    if (!$threadId || ($message === '' && !$hasAttachment)) {
        return false;
    }

    $attachmentPath = $hasAttachment ? $attachment['path'] : null;
    $attachmentName = $hasAttachment ? ($attachment['name'] ?? null) : null;
    $attachmentMime = $hasAttachment ? ($attachment['mime'] ?? null) : null;
    $attachmentSize = $hasAttachment ? ($attachment['size'] ?? null) : null;

    $isReadCustomer = $senderType === 'customer' ? 1 : 0;
    $isReadAdmin = $senderType === 'admin' ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO contact_thread_messages
            (thread_id, sender_type, sender_id, message, attachment_path, attachment_name, attachment_mime, attachment_size, is_read_by_customer, is_read_by_admin)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        $threadId,
        $senderType,
        $senderId,
        $message,
        $attachmentPath,
        $attachmentName,
        $attachmentMime,
        $attachmentSize,
        $isReadCustomer,
        $isReadAdmin
    ]);

    if ($result) {
        $status = $senderType === 'customer' ? 'open' : 'pending';
        $stmt = $pdo->prepare("UPDATE contact_threads
                               SET status = ?, last_message_at = NOW(), updated_at = NOW()
                               WHERE id = ?");
        $stmt->execute([$status, $threadId]);

        if ($senderType === 'customer') {
            $thread = getContactThread($threadId);
            if ($thread) {
                $title = 'New message from ' . $thread['name'];
                $body = $message !== '' ? substr($message, 0, 140) : '[Image attachment]';
                notifyAdmins($title, $body, 'support', $threadId);
            }
            // scan message for inappropriate words and flag if found
            if (trim($message) !== '' && function_exists('scanForInappropriateWords') && function_exists('flagMessage')) {
                $detected = scanForInappropriateWords($message);
                if (!empty($detected)) {
                    flagMessage($senderId, 'contact', $message, $detected);
                    if (function_exists('logUserActivity')) {
                        logUserActivity($senderId, 'flagged_message', [
                            'source' => 'contact',
                            'thread_id' => $threadId,
                            'detected_words' => implode(', ', $detected),
                        ]);
                    }
                }
            }

            if (function_exists('logUserActivity')) {
                logUserActivity($senderId, 'contact_message', [
                    'thread_id' => $threadId,
                    'source' => 'contact',
                    'has_attachment' => $hasAttachment ? 1 : 0,
                ]);
            }
        }
    }

    return $result;
}

function getContactThreadMessages($threadId, $limit = 0)
{
    global $pdo;
    ensureSupportMessagingTables();

    $sql = "SELECT * FROM contact_thread_messages WHERE thread_id = ? ORDER BY created_at ASC";
    if ($limit > 0) {
        $sql .= " LIMIT " . (int) $limit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$threadId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function markThreadMessagesRead($threadId, $viewerType)
{
    global $pdo;
    ensureSupportMessagingTables();

    if ($viewerType === 'customer') {
        $stmt = $pdo->prepare("UPDATE contact_thread_messages
                               SET is_read_by_customer = 1
                               WHERE thread_id = ?");
        return $stmt->execute([$threadId]);
    }

    if ($viewerType === 'admin') {
        $stmt = $pdo->prepare("UPDATE contact_thread_messages
                               SET is_read_by_admin = 1
                               WHERE thread_id = ?");
        return $stmt->execute([$threadId]);
    }

    return false;
}

function getContactThreads($status = null)
{
    global $pdo;
    ensureSupportMessagingTables();

    $sql = "SELECT ct.*, (
                SELECT COUNT(*) FROM contact_thread_messages m
                WHERE m.thread_id = ct.id AND m.sender_type = 'customer' AND m.is_read_by_admin = 0
            ) AS unread_for_admin
            FROM contact_threads ct";
    $params = [];

    if ($status && in_array($status, ['open', 'pending', 'closed'], true)) {
        $sql .= " WHERE ct.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY ct.last_message_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateContactThreadStatus($threadId, $status)
{
    global $pdo;
    ensureSupportMessagingTables();

    $allowed = ['open', 'pending', 'closed'];
    if (!in_array($status, $allowed, true)) {
        return false;
    }

    $stmt = $pdo->prepare("UPDATE contact_threads SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $threadId]);
}

function countContactThreadsByStatus()
{
    global $pdo;
    ensureSupportMessagingTables();

    $counts = [
        'all' => 0,
        'open' => 0,
        'pending' => 0,
        'closed' => 0
    ];

    $stmt = $pdo->query("SELECT status, COUNT(*) AS total FROM contact_threads GROUP BY status");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($counts[$row['status']])) {
            $counts[$row['status']] = (int) $row['total'];
            $counts['all'] += (int) $row['total'];
        }
    }

    return $counts;
}

// ----------------------------
// Sales analytics helper data
// ----------------------------

function getDailyRevenueSeries($days = 7)
{
    global $pdo;

    $days = max(1, min(30, (int) $days));
    $end = new DateTime('today');
    $start = (clone $end)->modify('-' . ($days - 1) . ' days');

    $stmt = $pdo->prepare("SELECT DATE(created_at) AS day_label, COALESCE(SUM(total_amount), 0) AS revenue
        FROM orders
        WHERE status IN ('delivered','completed') AND created_at BETWEEN ? AND ?
        GROUP BY DATE(created_at)");
    $stmt->execute([
        $start->format('Y-m-d 00:00:00'),
        $end->format('Y-m-d 23:59:59')
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $map = [];
    foreach ($rows as $row) {
        $map[$row['day_label']] = (float) $row['revenue'];
    }

    $series = ['labels' => [], 'values' => []];
    $cursor = clone $start;
    while ($cursor <= $end) {
        $key = $cursor->format('Y-m-d');
        $series['labels'][] = $cursor->format('M j');
        $series['values'][] = isset($map[$key]) ? $map[$key] : 0;
        $cursor->modify('+1 day');
    }

    return $series;
}

function getMonthlyRevenueSeries($year = null)
{
    global $pdo;

    $year = $year ? (int) $year : (int) date('Y');
    $start = new DateTime($year . '-01-01 00:00:00');
    $end = new DateTime($year . '-12-31 23:59:59');

    $stmt = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_label, COALESCE(SUM(total_amount), 0) AS revenue
        FROM orders
        WHERE status IN ('delivered','completed') AND created_at BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')");
    $stmt->execute([
        $start->format('Y-m-d H:i:s'),
        $end->format('Y-m-d H:i:s')
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $map = [];
    foreach ($rows as $row) {
        $map[$row['month_label']] = (float) $row['revenue'];
    }

    $series = ['labels' => [], 'values' => []];
    for ($m = 1; $m <= 12; $m++) {
        $key = sprintf('%04d-%02d', $year, $m);
        $series['labels'][] = date('M', mktime(0, 0, 0, $m, 1));
        $series['values'][] = isset($map[$key]) ? $map[$key] : 0;
    }

    return $series;
}

function getYearlyRevenueSeries($years = 5)
{
    global $pdo;

    $years = max(1, min(10, (int) $years));
    $endYear = (int) date('Y');
    $startYear = $endYear - ($years - 1);

    $stmt = $pdo->prepare("SELECT YEAR(created_at) AS year_label, COALESCE(SUM(total_amount), 0) AS revenue
        FROM orders
        WHERE status IN ('delivered','completed') AND created_at BETWEEN ? AND ?
        GROUP BY YEAR(created_at)");
    $stmt->execute([
        $startYear . '-01-01 00:00:00',
        $endYear . '-12-31 23:59:59'
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $map = [];
    foreach ($rows as $row) {
        $map[$row['year_label']] = (float) $row['revenue'];
    }

    $series = ['labels' => [], 'values' => []];
    for ($year = $startYear; $year <= $endYear; $year++) {
        $series['labels'][] = (string) $year;
        $series['values'][] = isset($map[$year]) ? $map[$year] : 0;
    }

    return $series;
}

function getTopSellingProductsSeries($limit = 5)
{
    global $pdo;

    $limit = max(1, min(10, (int) $limit));

    $sql = "SELECT p.name, SUM(oi.quantity) AS units_sold
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE o.status IN ('delivered','completed')
            GROUP BY p.id
            ORDER BY units_sold DESC
            LIMIT :limit";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $labels = [];
    $values = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $labels[] = $row['name'];
        $values[] = (int) $row['units_sold'];
    }

    return ['labels' => $labels, 'values' => $values];
}

// ----------------------------
// Reporting helper utilities
// ----------------------------

function resolveReportRange($period = 'monthly', $customStart = null, $customEnd = null)
{
    $end = new DateTime('today');
    $start = clone $end;
    $label = 'This Month';

    switch ($period) {
        case 'daily':
            $label = 'Today';
            break;
        case 'weekly':
            $start = (clone $end)->modify('-6 days');
            $label = 'Last 7 Days';
            break;
        case 'monthly':
            $start = (clone $end)->modify('first day of this month');
            $label = $end->format('F Y');
            break;
        case 'yearly':
            $start = new DateTime($end->format('Y') . '-01-01');
            $label = $end->format('Y');
            break;
        case 'custom':
            if ($customStart && $customEnd) {
                try {
                    $start = new DateTime($customStart);
                    $end = new DateTime($customEnd);
                    $label = $start->format('M j, Y') . ' - ' . $end->format('M j, Y');
                } catch (Exception $e) {
                    $start = (clone $end)->modify('first day of this month');
                    $label = $end->format('F Y');
                }
            }
            break;
        default:
            $start = (clone $end)->modify('first day of this month');
            $label = $end->format('F Y');
            break;
    }

    if ($end < $start) {
        $tmp = $start;
        $start = $end;
        $end = $tmp;
    }

    $startDate = (clone $start)->setTime(0, 0, 0);
    $endDate = (clone $end)->setTime(23, 59, 59);

    return [
        'start' => $startDate->format('Y-m-d H:i:s'),
        'end' => $endDate->format('Y-m-d H:i:s'),
        'start_date' => $startDate->format('Y-m-d'),
        'end_date' => $endDate->format('Y-m-d'),
        'label' => $label
    ];
}

function getSalesReportData($startDate, $endDate)
{
    global $pdo;

    $summary = [
        'total_revenue' => 0,
        'total_orders' => 0,
        'unique_customers' => 0,
        'avg_order_value' => 0
    ];

    $stmt = $pdo->prepare("SELECT
            COALESCE(SUM(total_amount), 0) AS total_revenue,
            COUNT(*) AS total_orders,
            COUNT(DISTINCT user_id) AS unique_customers
        FROM orders
        WHERE status IN ('delivered','completed')
            AND created_at BETWEEN ? AND ?");
    $stmt->execute([$startDate, $endDate]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $summary['total_revenue'] = (float) $row['total_revenue'];
        $summary['total_orders'] = (int) $row['total_orders'];
        $summary['unique_customers'] = (int) $row['unique_customers'];
        $summary['avg_order_value'] = $summary['total_orders'] > 0
            ? $summary['total_revenue'] / $summary['total_orders']
            : 0;
    }

    $stmt = $pdo->prepare("SELECT status, COUNT(*) as total
        FROM orders
        WHERE created_at BETWEEN ? AND ?
        GROUP BY status");
    $stmt->execute([$startDate, $endDate]);
    $statusRows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $ordersStmt = $pdo->prepare("SELECT o.*, COALESCE(NULLIF(u.full_name,''), u.username) AS customer_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status IN ('delivered','completed')
            AND o.created_at BETWEEN ? AND ?
        ORDER BY o.created_at DESC");
    $ordersStmt->execute([$startDate, $endDate]);
    $orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'summary' => $summary,
        'orders' => $orders,
        'status_breakdown' => $statusRows
    ];
}

function getProductPerformanceReport($startDate, $endDate, $limit = null)
{
    global $pdo;

    $sql = "SELECT p.name,
                SUM(oi.quantity) AS units_sold,
                SUM(oi.quantity * oi.price) AS revenue,
                COUNT(DISTINCT o.id) AS order_count
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE o.status IN ('delivered','completed')
                AND o.created_at BETWEEN ? AND ?
            GROUP BY p.id
            ORDER BY units_sold DESC";

    if ($limit !== null) {
        $limit = max(1, (int) $limit);
        $sql .= " LIMIT $limit";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startDate, $endDate]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPerPieceProductsReport($startDate, $endDate, $limit = null)
{
    global $pdo;

    $sql = "SELECT i.id,
                i.name,
                i.category,
                i.unit,
                COALESCE(c.id, 0) AS product_category_id,
                COALESCE(c.name, 'Uncategorized') AS product_category_name,
                COUNT(DISTINCT p.id) AS linked_product_count,
                GROUP_CONCAT(DISTINCT p.name ORDER BY p.name SEPARATOR ', ') AS linked_products,
                COALESCE(SUM(oi.quantity * pic.quantity_per_unit), 0) AS units_consumed,
                COUNT(DISTINCT o.id) AS order_count,
                COALESCE(SUM(oi.quantity * oi.price), 0) AS revenue,
                MAX(o.created_at) AS last_used_at
            FROM inventory_items i
            JOIN product_inventory_components pic ON pic.item_id = i.id
            JOIN products p ON pic.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            JOIN order_items oi ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status IN ('delivered','completed')
                AND o.created_at BETWEEN ? AND ?
            GROUP BY i.id, i.name, i.category, i.unit, c.id, c.name
            ORDER BY COALESCE(c.name, 'Uncategorized') ASC, units_consumed DESC, i.name ASC";

    if ($limit !== null) {
        $limit = max(1, (int) $limit);
        $sql .= " LIMIT $limit";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startDate, $endDate]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $categories = [];
    $summary = [
        'total_items' => 0,
        'total_categories' => 0,
        'total_linked_products' => 0,
        'total_units_consumed' => 0,
        'total_revenue' => 0
    ];

    foreach ($rows as $row) {
        $categoryId = (int) ($row['product_category_id'] ?? 0);
        $categoryName = trim((string) ($row['product_category_name'] ?? 'Uncategorized')) ?: 'Uncategorized';
        if (!isset($categories[$categoryId])) {
            $categories[$categoryId] = [
                'category_id' => $categoryId,
                'category_name' => $categoryName,
                'items' => []
            ];
            $summary['total_categories']++;
        }

        $categories[$categoryId]['items'][] = $row;
        $summary['total_items']++;
        $summary['total_linked_products'] += (int) ($row['linked_product_count'] ?? 0);
        $summary['total_units_consumed'] += (float) ($row['units_consumed'] ?? 0);
        $summary['total_revenue'] += (float) ($row['revenue'] ?? 0);
    }

    return [
        'summary' => $summary,
        'categories' => array_values($categories),
        'rows' => $rows
    ];
}

function getCustomerInsightsReport($startDate, $endDate, $limit = null)
{
    global $pdo;

    $sql = "SELECT COALESCE(NULLIF(u.full_name,''), u.username) AS customer_name,
                COUNT(o.id) AS order_count,
                SUM(o.total_amount) AS total_spent,
                AVG(o.total_amount) AS avg_order_value,
                MAX(o.created_at) AS last_order_date
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.status IN ('delivered','completed')
                AND o.created_at BETWEEN ? AND ?
            GROUP BY u.id
            ORDER BY total_spent DESC";

    if ($limit !== null) {
        $limit = max(1, (int) $limit);
        $sql .= " LIMIT $limit";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startDate, $endDate]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCancelledRefundedOrdersReport($startDate, $endDate)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT o.*, COALESCE(NULLIF(u.full_name,''), u.username) AS customer_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status IN ('cancelled','refunded')
            AND o.created_at BETWEEN ? AND ?
        ORDER BY o.created_at DESC");
    $stmt->execute([$startDate, $endDate]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function searchProducts($query, $category_id = null, $min_price = null, $max_price = null, $sort = 'name')
{
    global $pdo;

    ensureProductAvailabilityColumn();

    try {
        $sql = "SELECT p.*, c.name as category_name FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE (p.name LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)";
        $params = ["%$query%", "%$query%", "%$query%"];

        if ($category_id) {
            $sql .= " AND p.category_id = ?";
            $params[] = $category_id;
        }

        if ($min_price !== null) {
            $sql .= " AND p.price >= ?";
            $params[] = $min_price;
        }

        if ($max_price !== null) {
            $sql .= " AND p.price <= ?";
            $params[] = $max_price;
        }

        // Sorting
        $sort_options = [
            'name' => 'p.name ASC',
            'name_desc' => 'p.name DESC',
            'price' => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'newest' => 'p.created_at DESC',
            'rating' => '(SELECT AVG(rating) FROM product_reviews WHERE product_id = p.id) DESC'
        ];

        $sql .= " ORDER BY " . ($sort_options[$sort] ?? 'p.name ASC');

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Search products error: " . $e->getMessage());
        return [];
    }
}

// Helper function to check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Helper function to check if user is admin
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function getUserById($userId)
{
    global $pdo;

    $userId = (int) $userId;
    if ($userId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Helper function to redirect
function redirect($url)
{
    header("Location: $url");
    exit();
}

// Helper function to display price format
function formatPrice($price)
{
    return '$' . number_format($price, 2);
}

// Helper function to get cart item count
function getCartItemCount($user_id)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("Cart item count error: " . $e->getMessage());
        return 0;
    }
}

// Helper function to check if product exists
function productExists($product_id)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Product exists check error: " . $e->getMessage());
        return false;
    }
}

// Helper function to get category name
function getCategoryName($category_id)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['name'] ?? 'Unknown Category';
    } catch (PDOException $e) {
        error_log("Get category name error: " . $e->getMessage());
        return 'Unknown Category';
    }
}

function getCustomerFeedback($limit = 6, $onlyFeatured = true)
{
    global $pdo;

    try {
        $limit = max(1, (int) $limit); // ensure a safe numeric limit

        $sql = "SELECT cf.*, p.name AS product_name, u.full_name, u.username, oi.price
                FROM customer_feedback cf
                JOIN products p ON cf.product_id = p.id
                JOIN users u ON cf.user_id = u.id
                LEFT JOIN order_items oi ON cf.order_item_id = oi.id
                WHERE cf.status = 'published'";

        if ($onlyFeatured) {
            $sql .= " AND cf.is_featured = 1";
        }

        $sql .= " ORDER BY cf.created_at DESC LIMIT $limit";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Get customer feedback error: ' . $e->getMessage());
        return [];
    }
}

function getRecentFeedbackForAdmin($limit = 10)
{
    global $pdo;

    try {
        $limit = max(1, (int) $limit);

        $sql = "SELECT cf.*, p.name AS product_name, u.full_name
            FROM customer_feedback cf
            JOIN products p ON cf.product_id = p.id
            JOIN users u ON cf.user_id = u.id
            ORDER BY cf.created_at DESC
            LIMIT $limit";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Get recent feedback error: ' . $e->getMessage());
        return [];
    }
}

function updateFeedbackStatus($feedbackId, $status, $isFeatured = null)
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE customer_feedback SET status = ? WHERE id = ?");
        $stmt->execute([$status, $feedbackId]);

        if ($isFeatured !== null) {
            $stmt = $pdo->prepare("UPDATE customer_feedback SET is_featured = ? WHERE id = ?");
            $stmt->execute([(int) $isFeatured, $feedbackId]);
        }

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Update feedback status error: ' . $e->getMessage());
        return false;
    }
}

function saveCustomerFeedback($orderId, $orderItemId, $productId, $userId, $rating, $comment)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("INSERT INTO customer_feedback
            (order_id, order_item_id, product_id, user_id, rating, comment)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), status = 'pending', updated_at = CURRENT_TIMESTAMP");
        $ok = $stmt->execute([$orderId, $orderItemId, $productId, $userId, $rating, $comment]);

        // Scan for inappropriate words and flag if found
        if ($ok) {
            if (function_exists('logUserActivity')) {
                logUserActivity($userId, 'feedback_submitted', [
                    'product_id' => $productId,
                    'rating' => $rating,
                    'comment_preview' => mb_substr($comment, 0, 160),
                ]);
            }

            if (function_exists('scanForInappropriateWords') && function_exists('flagMessage')) {
                $found = scanForInappropriateWords($comment);
                if (!empty($found)) {
                    flagMessage($userId, 'feedback', $comment, $found);
                    if (function_exists('logUserActivity')) {
                        logUserActivity($userId, 'flagged_message', [
                            'source' => 'feedback',
                            'product_id' => $productId,
                            'detected_words' => implode(', ', $found),
                        ]);
                    }
                }
            }
        }

        return $ok;
    } catch (PDOException $e) {
        error_log('Save customer feedback error: ' . $e->getMessage());
        return false;
    }
}

function getFeedbackForProduct($productId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT cf.*, u.full_name, u.username
            FROM customer_feedback cf
            JOIN users u ON cf.user_id = u.id
            WHERE cf.product_id = ? AND cf.status = 'published'
            ORDER BY cf.created_at DESC");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Get feedback for product error: ' . $e->getMessage());
        return [];
    }
}

function getFeedbackForOrderItem($orderItemId, $userId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM customer_feedback WHERE order_item_id = ? AND user_id = ?");
        $stmt->execute([$orderItemId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Get feedback for order item error: ' . $e->getMessage());
        return null;
    }
}

// ----------------------------
// Notification helper methods
// ----------------------------

function ensureNotificationTypeSupports($type)
{
    global $pdo;

    static $ensuredTypes = [];
    if ($type === '' || isset($ensuredTypes[$type])) {
        return;
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM notifications LIKE 'type'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($column && !empty($column['Type'])) {
            if (strpos($column['Type'], "'{$type}'") === false) {
                preg_match_all("/'([^']+)'/", $column['Type'], $matches);
                $allowed = $matches[1] ?? [];
                $allowed[] = $type;
                $allowed = array_values(array_unique($allowed));
                $enumList = "'" . implode("','", array_map('addslashes', $allowed)) . "'";
                $pdo->exec("ALTER TABLE notifications MODIFY type ENUM($enumList) DEFAULT 'system'");
            }
        }
    } catch (PDOException $e) {
        error_log('Ensure notification type enum failed: ' . $e->getMessage());
    }

    $ensuredTypes[$type] = true;
}

function getAdminUserIds()
{
    global $pdo;

    static $adminIds = null;
    if ($adminIds !== null) {
        return $adminIds;
    }

    try {
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
        $adminIds = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (PDOException $e) {
        error_log('Get admin IDs error: ' . $e->getMessage());
        $adminIds = [];
    }

    return $adminIds;
}

function createNotification($userId, $title, $message, $type = 'system', $relatedId = null)
{
    global $pdo;

    if (empty($userId) || empty($title) || empty($message)) {
        return false;
    }

    ensureNotificationTypeSupports($type);

    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, related_id)
                               VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $title, $message, $type, $relatedId]);
    } catch (PDOException $e) {
        error_log('Create notification error: ' . $e->getMessage());
        return false;
    }
}

function notifyAdmins($title, $message, $type = 'system', $relatedId = null)
{
    ensureNotificationTypeSupports($type);

    $adminIds = getAdminUserIds();
    if (empty($adminIds)) {
        return false;
    }

    $result = true;
    foreach ($adminIds as $adminId) {
        $result = createNotification($adminId, $title, $message, $type, $relatedId) && $result;
    }

    return $result;
}

function getUserDisplayName(array $user)
{
    $fullName = trim((string) ($user['full_name'] ?? ''));
    if ($fullName !== '') {
        return $fullName;
    }

    return trim((string) ($user['username'] ?? 'Customer'));
}

function getFirstNameFromDisplayName($displayName)
{
    $displayName = trim((string) $displayName);
    if ($displayName === '') {
        return 'Customer';
    }

    $parts = preg_split('/\s+/', $displayName);
    return $parts[0] ?? $displayName;
}

function notificationExistsForDay($userId, $type, $relatedId, $dateYmd)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT id FROM notifications
            WHERE user_id = ?
            AND type = ?
            AND related_id <=> ?
            AND DATE(created_at) = ?
            LIMIT 1");
        $stmt->execute([(int) $userId, (string) $type, $relatedId !== null ? (int) $relatedId : null, $dateYmd]);
        return (bool) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Notification dedupe check failed: ' . $e->getMessage());
        return false;
    }
}

function runDailyBirthdayNotifications()
{
    global $pdo;

    static $ran = false;
    if ($ran) {
        return;
    }
    $ran = true;

    ensureUserSecurityColumns();

    $today = date('Y-m-d');

    try {
        $stmt = $pdo->query("SELECT id, username, full_name
            FROM users
            WHERE role = 'customer'
            AND birthday IS NOT NULL
            AND DATE_FORMAT(birthday, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')");
        $birthdayUsers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (empty($birthdayUsers)) {
            return;
        }

        foreach ($birthdayUsers as $birthdayUser) {
            $birthdayUserId = (int) ($birthdayUser['id'] ?? 0);
            if ($birthdayUserId <= 0) {
                continue;
            }

            $displayName = getUserDisplayName($birthdayUser);
            $firstName = getFirstNameFromDisplayName($displayName);
            $username = trim((string) ($birthdayUser['username'] ?? ''));

            $userTitle = '🎉🌸 Happy Birthday, ' . $firstName . '! 🌸🎉';
            $userMessage = "Warmest birthday wishes from Raymundo FlowerShop! 💐\n"
                . "May your special day be filled with happiness, love, and beautiful moments-just like the flowers we proudly offer.\n\n"
                . "Thank you for choosing us to be part of your celebrations. We hope your day blooms with joy and your year ahead is as vibrant and wonderful as a fresh bouquet!\n\n"
                . "🌷 Stay blooming and have an amazing birthday, {$displayName}! 🌷";

            if (!notificationExistsForDay($birthdayUserId, 'birthday', $birthdayUserId, $today)) {
                createNotification($birthdayUserId, $userTitle, $userMessage, 'birthday', $birthdayUserId);
            }

            $adminTitle = 'Customer Birthday Today';
            $adminMessage = $displayName . ($username !== '' ? ' (@' . $username . ')' : '')
                . ' is celebrating a birthday today.';

            $adminIds = getAdminUserIds();
            foreach ($adminIds as $adminId) {
                $adminId = (int) $adminId;
                if ($adminId <= 0) {
                    continue;
                }

                if (!notificationExistsForDay($adminId, 'birthday', $birthdayUserId, $today)) {
                    createNotification($adminId, $adminTitle, $adminMessage, 'birthday', $birthdayUserId);
                }
            }
        }
    } catch (PDOException $e) {
        error_log('Run daily birthday notifications failed: ' . $e->getMessage());
    }
}

function getUserNotifications($userId, $onlyUnread = false, $limit = 0)
{
    global $pdo;

    if (empty($userId)) {
        return [];
    }

    $sql = "SELECT * FROM notifications WHERE user_id = ?";
    $params = [$userId];

    if ($onlyUnread) {
        $sql .= " AND is_read = 0";
    }

    $sql .= " ORDER BY created_at DESC";

    if ($limit > 0) {
        $sql .= " LIMIT " . (int) $limit;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Get user notifications error: ' . $e->getMessage());
        return [];
    }
}

function markNotificationRead($notificationId, $userId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notificationId, $userId]);
    } catch (PDOException $e) {
        error_log('Mark notification read error: ' . $e->getMessage());
        return false;
    }
}

function markAllNotificationsRead($userId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$userId]);
    } catch (PDOException $e) {
        error_log('Mark all notifications read error: ' . $e->getMessage());
        return false;
    }
}

function deleteNotification($notificationId, $userId)
{
    global $pdo;

    $notificationId = (int) $notificationId;
    $userId = (int) $userId;

    if ($notificationId <= 0 || $userId <= 0) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notificationId, $userId]);
    } catch (PDOException $e) {
        error_log('Delete notification error: ' . $e->getMessage());
        return false;
    }
}

function deleteAllNotifications($userId)
{
    global $pdo;

    $userId = (int) $userId;

    if ($userId <= 0) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
        return $stmt->execute([$userId]);
    } catch (PDOException $e) {
        error_log('Delete all notifications error: ' . $e->getMessage());
        return false;
    }
}

function getUnreadNotificationCount($userId)
{
    global $pdo;

    if (empty($userId)) {
        return 0;
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Unread notification count error: ' . $e->getMessage());
        return 0;
    }
}

function renderBrandEmail($title, $bodyHtml, $ctaLabel = null, $ctaUrl = null, $footerText = '')
{
    $titleSafe = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $footer = trim($footerText) !== '' ? $footerText : 'Thanks for staying connected to Raymundo Avenue.';

    $ctaBlock = '';
    if ($ctaLabel && $ctaUrl) {
        $ctaBlock = '<div style="text-align:center;margin:32px 0;">'
            . '<a href="' . htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8') . '" '
            . 'style="background:#0d8f4c;color:#ffffff;text-decoration:none;padding:14px 32px;'
            . 'border-radius:999px;font-weight:600;display:inline-block;">'
            . htmlspecialchars($ctaLabel, ENT_QUOTES, 'UTF-8')
            . '</a></div>';
    }

    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . $titleSafe . '</title></head>'
        . '<body style="background:#f3f4f6;margin:0;padding:32px;font-family:Helvetica,Arial,sans-serif;">'
        . '<table role="presentation" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:20px;'
        . 'overflow:hidden;box-shadow:0 25px 70px rgba(15,23,42,0.12);">'
        . '<tr><td style="background:#0f5132;padding:28px 32px;color:#ffffff;font-size:20px;font-weight:600;">'
        . $titleSafe . '</td></tr>'
        . '<tr><td style="padding:32px;font-size:16px;line-height:1.6;color:#111827;">'
        . $bodyHtml . $ctaBlock . '</td></tr>'
        . '<tr><td style="padding:20px 32px;background:#f8fafc;color:#475569;font-size:13px;text-align:center;">'
        . htmlspecialchars($footer, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '</table></body></html>';
}

function buildMailerInstance()
{
    if (!class_exists(PHPMailer::class)) {
        throw new RuntimeException('PHPMailer is missing. Run composer install to continue.');
    }

    if (MAIL_SMTP_USER === '' || MAIL_SMTP_PASS === '') {
        throw new RuntimeException('Email transport is not configured. Set MAIL_SMTP_USER and MAIL_SMTP_PASS.');
    }

    $mailer = new PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = MAIL_SMTP_HOST;
    $mailer->SMTPAuth = true;
    $mailer->Username = MAIL_SMTP_USER;
    $mailer->Password = MAIL_SMTP_PASS;
    $mailer->Port = (int) MAIL_SMTP_PORT ?: 587;
    $mailer->CharSet = 'UTF-8';

    $encryption = strtolower((string) MAIL_SMTP_ENCRYPTION);
    if (in_array($encryption, ['ssl', 'smtps'], true)) {
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($encryption === 'none') {
        $mailer->SMTPSecure = false;
    } else {
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }

    $fromAddress = MAIL_FROM_ADDRESS !== '' ? MAIL_FROM_ADDRESS : MAIL_SMTP_USER;
    $fromName = MAIL_FROM_NAME !== '' ? MAIL_FROM_NAME : $fromAddress;
    $mailer->setFrom($fromAddress, $fromName);

    return $mailer;
}

function sendEmailMessage($recipientEmail, $recipientName, $subject, $htmlBody, $textBody = '')
{
    try {
        $mailer = buildMailerInstance();
    } catch (RuntimeException $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }

    try {
        $mailer->clearAllRecipients();
        $mailer->addAddress($recipientEmail, $recipientName ?: '');
        $mailer->Subject = $subject;
        $mailer->isHTML(true);
        $mailer->Body = $htmlBody;
        $mailer->AltBody = $textBody !== '' ? $textBody : strip_tags($htmlBody);
        $mailer->send();
        return ['success' => true];
    } catch (PHPMailerException $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function sendPasswordResetEmail(array $user, $plainToken)
{
    $fullName = $user['full_name'] ?: $user['username'];
    $resetUrl = appUrl('reset-password.php?token=' . urlencode($plainToken));
    $subject = 'Reset your Raymundo Avenue password';

    $body = '<p>Hi ' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p>We received a request to reset the password for your account. '
        . 'Click the button below to choose a new password. This link will expire in '
        . (int) PASSWORD_RESET_EXPIRY_MINUTES . ' minutes.</p>'
        . '<p style="margin:24px 0 0 0;">If you did not request a password reset, '
        . 'you can safely ignore this email.</p>';

    $html = renderBrandEmail($subject, $body, 'Reset password', $resetUrl);
    $text = "Hi {$fullName},\n\nUse the link below to reset your password (valid for "
        . (int) PASSWORD_RESET_EXPIRY_MINUTES . " minutes):\n" . $resetUrl . "\n\n"
        . "If you did not make this request, you can ignore this email.";

    return sendEmailMessage($user['email'], $fullName, $subject, $html, $text);
}

// ----------------------------
// Pages management helpers
// ----------------------------

function ensurePagesTable()
{
    global $pdo;

    static $pagesTableEnsured = false;
    if ($pagesTableEnsured) {
        return;
    }

    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'pages'");
        if (!$stmt->fetch()) {
            $sql = "CREATE TABLE IF NOT EXISTS `pages` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `slug` VARCHAR(255) NOT NULL UNIQUE,
                `title` VARCHAR(255) NOT NULL,
                `content` LONGTEXT NOT NULL,
                `is_published` TINYINT(1) NOT NULL DEFAULT 1,
                `created_by` INT UNSIGNED NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `updated_by` INT UNSIGNED NULL,
                INDEX `pages_slug_index` (`slug`),
                INDEX `pages_is_published_index` (`is_published`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $pdo->exec($sql);
        }
    } catch (PDOException $e) {
        error_log('Failed to ensure pages table: ' . $e->getMessage());
    }

    $pagesTableEnsured = true;
}

function getPageBySlug($slug)
{
    global $pdo;
    ensurePagesTable();

    try {
        $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND is_published = 1 LIMIT 1");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log('Failed to fetch page: ' . $e->getMessage());
        return null;
    }
}

function getPageById($pageId)
{
    global $pdo;
    ensurePagesTable();

    try {
        $stmt = $pdo->prepare("SELECT p.*, u1.username AS created_by_username, u2.username AS updated_by_username 
                              FROM pages p
                              LEFT JOIN users u1 ON p.created_by = u1.id
                              LEFT JOIN users u2 ON p.updated_by = u2.id
                              WHERE p.id = ? LIMIT 1");
        $stmt->execute([$pageId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log('Failed to fetch page by id: ' . $e->getMessage());
        return null;
    }
}

function getAllPages($onlyPublished = false)
{
    global $pdo;
    ensurePagesTable();

    try {
        $sql = "SELECT p.*, u1.username AS created_by_username, u2.username AS updated_by_username
                FROM pages p
                LEFT JOIN users u1 ON p.created_by = u1.id
                LEFT JOIN users u2 ON p.updated_by = u2.id";

        if ($onlyPublished) {
            $sql .= " WHERE p.is_published = 1";
        }

        $sql .= " ORDER BY p.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log('Failed to fetch all pages: ' . $e->getMessage());
        return [];
    }
}

function savePage($pageData, $pageId = null)
{
    global $pdo;
    ensurePagesTable();

    try {
        $slug = generateSlug($pageData['slug'] ?? $pageData['title'] ?? '');
        $title = trim($pageData['title'] ?? '');
        $content = $pageData['content'] ?? '';
        $isPublished = !empty($pageData['is_published']) ? 1 : 0;
        $userId = $_SESSION['user_id'] ?? null;

        if (!$slug || !$title) {
            return ['success' => false, 'message' => 'Title and slug are required.'];
        }

        if ($pageId) {
            // Check if slug already exists (for other pages)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pages WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $pageId]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'This slug is already in use by another page.'];
            }

            $stmt = $pdo->prepare("UPDATE pages 
                                  SET slug = ?, title = ?, content = ?, is_published = ?, updated_by = ?, updated_at = NOW()
                                  WHERE id = ?");
            $result = $stmt->execute([$slug, $title, $content, $isPublished, $userId, $pageId]);

            if ($result) {
                return ['success' => true, 'message' => 'Page updated successfully.', 'page_id' => $pageId];
            }
        } else {
            // Check if slug already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pages WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'This slug is already in use.'];
            }

            $stmt = $pdo->prepare("INSERT INTO pages (slug, title, content, is_published, created_by)
                                  VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$slug, $title, $content, $isPublished, $userId]);

            if ($result) {
                $pageId = $pdo->lastInsertId();
                return ['success' => true, 'message' => 'Page created successfully.', 'page_id' => $pageId];
            }
        }

        return ['success' => false, 'message' => 'Failed to save page.'];
    } catch (PDOException $e) {
        error_log('Failed to save page: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function deletePage($pageId)
{
    global $pdo;
    ensurePagesTable();

    try {
        $stmt = $pdo->prepare("DELETE FROM pages WHERE id = ?");
        return $stmt->execute([$pageId]);
    } catch (PDOException $e) {
        error_log('Failed to delete page: ' . $e->getMessage());
        return false;
    }
}

function checkInappropriateContent($text, $logIfFound = true)
{
    $detectedWords = scanForInappropriateWords($text);
    
    if (!empty($detectedWords) && $logIfFound) {
        flagMessage($_SESSION['user_id'] ?? null, 'content_submission', $text, $detectedWords);
    }

    return [
        'has_inappropriate' => !empty($detectedWords),
        'detected_words' => $detectedWords
    ];
}
?>