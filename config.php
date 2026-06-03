<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// 1. HÀM KHỞI TẠO & TIỆN ÍCH (Định nghĩa trước)
// ==========================================

if (!function_exists('initDB')) {
    function initDB($pdo) {
        $pdo->exec("SET NAMES utf8mb4");
        $tables = ['users', 'products', 'orders', 'reviews', 'vouchers', 'transactions', 'global_chat', 'music', 'settings'];
        foreach ($tables as $tbl) {
            try { $pdo->exec("ALTER TABLE `$tbl` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); } catch (Exception $e) {}
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            balance INT DEFAULT 0,
            role VARCHAR(20) DEFAULT 'user',
            is_verified TINYINT DEFAULT 0,
            is_vip TINYINT DEFAULT 0,
            avatar VARCHAR(255) DEFAULT '',
            is_banned TINYINT DEFAULT 0,
            banned_ip VARCHAR(100) DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // --- AUTO FIX DATABASE ---
        $checkColBanned = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_banned'");
        if ($checkColBanned && $checkColBanned->rowCount() == 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN is_banned TINYINT DEFAULT 0 AFTER avatar, ADD COLUMN banned_ip VARCHAR(100) DEFAULT '' AFTER is_banned, ADD COLUMN last_ip VARCHAR(100) DEFAULT '' AFTER banned_ip");
        }

        $checkColLastIP = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_ip'");
        if ($checkColLastIP && $checkColLastIP->rowCount() == 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN last_ip VARCHAR(100) DEFAULT '' AFTER banned_ip");
        }

        $checkColAvatar = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'");
        if ($checkColAvatar && $checkColAvatar->rowCount() == 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT '' AFTER is_vip");
        }

        $checkColEmail = $pdo->query("SHOW COLUMNS FROM users LIKE 'email'");
        if ($checkColEmail && $checkColEmail->rowCount() == 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(100) DEFAULT '' AFTER username");
        }
        try { $pdo->exec("ALTER TABLE users DROP INDEX email"); } catch (Exception $e) {}

        $checkColUserVerify = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
        if ($checkColUserVerify->rowCount() == 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN is_verified TINYINT DEFAULT 0 AFTER role");
        }

        $checkColUserVip = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_vip'");
        if ($checkColUserVip->rowCount() == 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN is_vip TINYINT DEFAULT 0 AFTER is_verified");
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT 0,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            price INT DEFAULT 0,
            demo_url VARCHAR(255) DEFAULT '',
            image_url VARCHAR(255) DEFAULT '',
            gallery TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $checkColAuth = $pdo->query("SHOW COLUMNS FROM products LIKE 'user_id'");
        if ($checkColAuth->rowCount() == 0) {
            $pdo->exec("ALTER TABLE products ADD COLUMN user_id INT DEFAULT 0 AFTER id");
        }

        $checkCol = $pdo->query("SHOW COLUMNS FROM products LIKE 'demo_url'");
        if ($checkCol->rowCount() == 0) {
            $pdo->exec("ALTER TABLE products ADD COLUMN demo_url VARCHAR(255) DEFAULT '' AFTER price");
        }
        
        $checkColImg = $pdo->query("SHOW COLUMNS FROM products LIKE 'image_url'");
        if ($checkColImg->rowCount() == 0) {
            $pdo->exec("ALTER TABLE products ADD COLUMN image_url VARCHAR(255) DEFAULT '' AFTER demo_url");
        }

        $checkColGallery = $pdo->query("SHOW COLUMNS FROM products LIKE 'gallery'");
        if ($checkColGallery->rowCount() == 0) {
            $pdo->exec("ALTER TABLE products ADD COLUMN gallery TEXT AFTER image_url");
        }

        $checkColCategory = $pdo->query("SHOW COLUMNS FROM products LIKE 'category'");
        if ($checkColCategory->rowCount() == 0) {
            $pdo->exec("ALTER TABLE products ADD COLUMN category VARCHAR(50) DEFAULT 'PHP' AFTER gallery");
        }

        $checkColDownload = $pdo->query("SHOW COLUMNS FROM products LIKE 'download_url'");
        if ($checkColDownload->rowCount() == 0) {
            $pdo->exec("ALTER TABLE products ADD COLUMN download_url VARCHAR(255) DEFAULT '' AFTER category");
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            product_id INT,
            price INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            product_id INT,
            rating INT DEFAULT 5,
            comment TEXT,
            reply TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS vouchers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) UNIQUE NOT NULL,
            discount INT DEFAULT 0,
            usage_limit INT DEFAULT 1,
            used_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            amount INT,
            type VARCHAR(50),
            ref_id VARCHAR(100) UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS global_chat (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS music (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            artist VARCHAR(255) DEFAULT '',
            file_path VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        try { $pdo->exec("ALTER TABLE users DROP INDEX email"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users DROP INDEX email_2"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users MODIFY email VARCHAR(100) DEFAULT ''"); } catch (Exception $e) {}

        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            s_key VARCHAR(100) PRIMARY KEY,
            s_value TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $defaultSettings = [
            'bank_name' => 'MB Bank',
            'bank_account' => '1234567890',
            'bank_owner' => 'PHAM DUC TRI',
            'bank_prefix' => 'NAP CODE',
            'sepay_token' => '',
            'vip_price' => '500000',
            'vip_discount' => '30',
            'enable_bank' => '1',
            'enable_card' => '0',
            'card24h_partner_id' => '',
            'card24h_partner_key' => '',
            'card_fee' => '20',
            'page_terms' => '<h2>1. Chấp thuận điều khoản</h2><p>Bằng việc sử dụng dịch vụ của chúng tôi, bạn đồng ý tuân thủ các điều khoản này...</p>',
            'page_guide' => '<h2>Hướng dẫn mua hàng</h2><p>Bước 1: Nạp tiền vào tài khoản...</p><p>Bước 2: Chọn sản phẩm ưng ý.</p><p>Bước 3: Nhận code tức thì.</p>',
            'page_privacy' => '<h2>Chính sách bảo mật</h2><p>Chúng tôi cam kết bảo vệ thông tin cá nhân của bạn. Dữ liệu được mã hóa và bảo mật tuyệt đối.</p>',
            'site_name' => 'TAIGAMENHANH - Sàn Code Premium',
            'site_description' => 'Chuyên cung cấp mã nguồn Website chất lượng cao, giao dịch tự động, nhận code tức thì.',
            'site_logo' => 'https://i.imgur.com/yourlogo.png',
            'site_favicon' => '',
            'admin_path' => 'admin',
            'seo_keywords' => 'mua ban source code, dich vu dark, share code php, ban code tu dong',
            'popup_content' => 'Chào mừng bạn đến với hệ thống sàn giao dịch mã nguồn tự động TAIGAMENHANH!',
            'enable_maintenance' => '0',
            'footer_about' => 'Hệ thống phân phối source code tự động, bảo mật và uy tín hàng đầu. Hỗ trợ thanh toán 24/7.',
            'footer_email' => 'support@taigamenhanh.com',
            'footer_telegram' => '@taigamenhanh_support',
            'enable_support' => '1',
            'support_zalo' => 'https://zalo.me/0559240506',
            'support_zalo_enable' => '1',
            'support_fb' => 'https://facebook.com/your_page',
            'support_fb_enable' => '1',
            'support_ai' => 'https://your_ai_chat_link',
            'support_ai_enable' => '1',
            'groq_api_key' => '',
            'ai_system_prompt' => 'Bạn là trợ lý ảo chuyên nghiệp. Hãy hỗ trợ khách hàng tìm kiếm source code phù hợp dựa trên danh sách sản phẩm của cửa hàng.',
            'bot_enable' => '1',
            'bot_frequency' => '10',
            'bot_fake_users' => 'Tuấn Anh, Hoàng Nam, Minh Tú, Phương Thảo, Khánh Linh, Quốc Cường, Thanh Hải, Ngọc Ánh, Duy Mạnh, Bảo Ngọc',
            'bot_fake_products' => 'Mã nguồn Website Bán Code, API SePay Cho Laravel, Source Shop Clone, Code Web Tin Tức, Template Landing Page Luxury',
            'stat_online_base' => '1000',
            'stat_user_base' => '5000'
        ];
        foreach ($defaultSettings as $key => $val) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO settings (s_key, s_value) VALUES (?, ?)");
            $stmt->execute([$key, $val]);
        }

        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("INSERT INTO products (title, description, price) VALUES 
                ('Mã nguồn Website Bán Code', 'Giao diện PHP thuần, tối ưu SEO, Auto Bank cực mượt.', 500000),
                ('API SePay Cho Laravel', 'Module tích hợp SePay tự động nạp tiền cho Framework Laravel.', 200000)
            ");
        }
    }
}

if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('checkCsrfToken')) {
    function checkCsrfToken($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            die("CSRF Token Invalid. Vui lòng tải lại trang.");
        }
        return true;
    }
}

if (!function_exists('get_upload_error')) {
    function get_upload_error($code) {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE: return "File vượt quá dung lượng cho phép của server.";
            case UPLOAD_ERR_FORM_SIZE: return "File vượt quá dung lượng cho phép của form.";
            case UPLOAD_ERR_PARTIAL: return "File chỉ được tải lên một phần.";
            case UPLOAD_ERR_NO_FILE: return "Không có file nào được tải lên.";
            case UPLOAD_ERR_NO_TMP_DIR: return "Thiếu thư mục tạm trên server.";
            case UPLOAD_ERR_CANT_WRITE: return "Không thể ghi file vào đĩa.";
            case UPLOAD_ERR_EXTENSION: return "Một extension PHP đã ngăn chặn việc tải file.";
            default: return "Lỗi không xác định.";
        }
    }
}

if (!function_exists('log_debug')) {
    function log_debug($msg) {
        file_put_contents(__DIR__ . '/debug_upload.txt', date('Y-m-d H:i:s') . ' - ' . $msg . "\n", FILE_APPEND);
    }
}

if (!function_exists('handleMusicUpload')) {
    function handleMusicUpload($inputName = 'm_file') {
        if (!isset($_FILES[$inputName])) return null;
        $file = $_FILES[$inputName];
        if ($file['error'] !== UPLOAD_ERR_OK) return null;
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'mp3') return null;
        $upDir = __DIR__ . '/uploads/music/';
        if (!is_dir($upDir)) mkdir($upDir, 0777, true);
        $fileName = uniqid('music_') . '.mp3';
        $target = $upDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $target)) {
            chmod($target, 0644);
            return 'uploads/music/' . $fileName;
        }
        return null;
    }
}

if (!function_exists('handleImageUpload')) {
    function handleImageUpload($inputName = "image", $fixedName = null) {
        $uploadedPaths = [];
        if (!isset($_FILES[$inputName])) return [];
        $files = $_FILES[$inputName];
        if (!is_array($files["name"])) { 
            $files = ["name" => [$files["name"]], "type" => [$files["type"]], "tmp_name" => [$files["tmp_name"]], "error" => [$files["error"]], "size" => [$files["size"]]]; 
        }
        $uploadDir = __DIR__ . "/uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        for ($i = 0; $i < count($files["name"]); $i++) {
            if ($files["error"][$i] === UPLOAD_ERR_OK) {
                $tmpFile = $files["tmp_name"][$i];
                $fileName = $files["name"][$i];
                if (getimagesize($tmpFile) !== false) {
                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    if (in_array($fileExt, ["jpg", "jpeg", "png", "gif", "webp"])) {
                        if ($fixedName !== null) {
                            $newFileName = $fixedName . "." . $fileExt;
                            foreach (["jpg", "jpeg", "png", "gif", "webp"] as $ext) {
                                if ($ext !== $fileExt && file_exists($uploadDir . $fixedName . "." . $ext)) {
                                    @unlink($uploadDir . $fixedName . "." . $ext);
                                }
                            }
                        } else {
                            $newFileName = uniqid("img_") . "_" . $i . "." . $fileExt;
                        }
                        
                        $targetPath = $uploadDir . $newFileName;
                        if (move_uploaded_file($tmpFile, $targetPath)) {
                            @chmod($targetPath, 0644);
                            $uploadedPaths[] = "uploads/" . $newFileName;
                        }
                    }
                }
            }
        }
        return $uploadedPaths;
    }
}

if (!function_exists('redirect')) {
    function redirect($path) {
        global $basePath;
        header("Location: " . $basePath . $path);
        exit;
    }
}

if (!function_exists('renderAvatar')) {
    function renderAvatar($user, $sizeClass = 'w-12 h-12', $textClass = 'text-xl') {
        global $basePath;
        if (!$user) return '<div class="'.$sizeClass.' bg-slate-100 rounded-2xl flex items-center justify-center text-slate-300"><i class="ph ph-user"></i></div>';
        $username = $user['username'] ?? $user['author_name'] ?? 'Admin';
        $isVip = (isset($user['is_vip']) && $user['is_vip'] == 1) || (isset($user['author_vip']) && $user['author_vip'] == 1);
        $isAdmin = (isset($user['role']) && $user['role'] == 'admin') || (isset($user['author_role']) && $user['author_role'] == 'admin');
        $avatarPath = $user['avatar'] ?? $user['author_avatar'] ?? '';
        $avatar = !empty($avatarPath) ? rtrim($basePath, '/') . '/' . ltrim($avatarPath, '/') : null;
        $initial = strtoupper(substr($username, 0, 1));
        $frameClass = $isAdmin ? 'admin-frame' : ($isVip ? 'vip-frame' : '');
        $html = '<div class="relative '.$sizeClass.' '.$frameClass.' shrink-0"><div class="w-full h-full bg-gradient-to-br from-blue-600 to-indigo-700 rounded-[inherit] flex items-center justify-center text-white font-black '.$textClass.' shadow-xl relative z-10 overflow-hidden">';
        if ($avatar) $html .= '<img src="'.$avatar.'" class="w-full h-full object-cover">'; else $html .= $initial;
        $html .= '</div></div>';
        return $html;
    }
}

if (!function_exists('renderUsername')) {
    function renderUsername($user) {
        if (!$user) return 'Khách';
        $username = htmlspecialchars($user['username'] ?? $user['author_name'] ?? 'Admin');
        $isVip = (isset($user['is_vip']) && $user['is_vip'] == 1) || (isset($user['author_vip']) && $user['author_vip'] == 1);
        $isAdmin = (isset($user['role']) && $user['role'] == 'admin') || (isset($user['author_role']) && $user['author_role'] == 'admin');
        if ($isAdmin) return '<span class="inline-flex items-center gap-1"><span class="font-black bg-gradient-to-r from-amber-400 via-yellow-500 to-amber-600 text-transparent bg-clip-text">'.$username.'</span><i class="ph-fill ph-check-circle text-yellow-500 text-lg"></i></span>';
        if ($isVip) return '<span class="inline-flex items-center gap-1"><span class="font-black bg-gradient-to-r from-blue-400 via-blue-600 to-indigo-600 text-transparent bg-clip-text">'.$username.'</span><i class="ph-fill ph-check-circle text-blue-500 text-lg"></i></span>';
        return '<span class="font-bold text-slate-700">'.$username.'</span>';
    }
}

// ==========================================
// 2. CẤU HÌNH DATABASE & BASE PATH
// ==========================================
// --- DB CONFIG START ---
$dsn = 'mysql:host=localhost;dbname=devbypdt;charset=utf8mb4';
$db_user = 'root';
$db_pass = '';
// --- DB CONFIG END ---
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/'); 

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);

    // Khởi chạy initDB và cấu hình động khi kết nối thành công
    initDB($pdo);
    
    $currentUser = null;
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $currentUser = $stmt->fetch();
    }

    $sys_settings = [];
    foreach($pdo->query("SELECT * FROM settings")->fetchAll() as $s) {
        $sys_settings[$s['s_key']] = $s['s_value'];
    }

    $bank_config = [
        'name' => $sys_settings['bank_name'] ?? 'MB Bank',
        'account_number' => $sys_settings['bank_account'] ?? '1234567890',
        'account_holder' => $sys_settings['bank_owner'] ?? 'PHAM DUC TRI',
        'prefix' => $sys_settings['bank_prefix'] ?? 'NAP CODE',
    ];
    $sepay_token = $sys_settings['sepay_token'] ?? '';
    $product_categories = ['Html - Css - Js', 'Web Designs', 'Design & Creative', 'React & PHP', 'Php Script', 'PHP Script (Laravel)', 'NEXTJS', 'Khác'];

} catch (Exception $e) {
    if (basename($_SERVER['PHP_SELF']) !== 'install.php') {
        if (file_exists(__DIR__ . '/install.php')) {
            header("Location: install.php");
            exit;
        }
    }
    if (basename($_SERVER['PHP_SELF']) === 'install.php') {
        throw $e;
    }
    die("Lỗi kết nối CSDL: Vui lòng kiểm tra lại cấu hình. Chi tiết lỗi: " . $e->getMessage());
}
