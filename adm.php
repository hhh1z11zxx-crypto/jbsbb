<?php
// ======================================================================
// === 1. CONFIG CẤU HÌNH BẮT BUỘC CẦN THAY ĐỔI ===
// ======================================================================

// Mật khẩu Admin
const ADMIN_PASS = 'dmm'; 
// Tên file Key
const KEY_FILE = 'keys.json';

// ======================================================================

// --- HÀM XỬ LÝ FILE KEYS ---

/**
 * Đọc dữ liệu Keys từ file keys.json
 * @return array Object Key-Value (e.g., {"key_id": {expiry_date: "...", ...}})
 */
function fetchKeysFromFile() {
    if (!file_exists(KEY_FILE)) {
        // Nếu file không tồn tại, tạo file rỗng và trả về mảng rỗng
        file_put_contents(KEY_FILE, json_encode([]));
        return [];
    }
    $content = file_get_contents(KEY_FILE);
    $data = json_decode($content, true);
    // Đảm bảo dữ liệu là mảng/object
    return is_array($data) ? $data : [];
}

/**
 * Ghi dữ liệu Keys vào file keys.json
 * @param array $keysObject Object Key-Value mới
 * @return bool Trạng thái thành công
 */
function updateKeysToFile(array $keysObject) {
    $jsonContent = json_encode($keysObject, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    // Ghi nội dung vào file
    return file_put_contents(KEY_FILE, $jsonContent) !== false;
}

// Tạo Key 64 ký tự 
function generateRandomKey() {
    return bin2hex(random_bytes(32)); // 32 bytes = 64 ký tự hex
}

// Lấy trạng thái Key
function getStatus(string $expiryDate) {
    $statusClass = 'status-active';
    $statusText = 'Đang hoạt động';
    
    if (empty($expiryDate)) {
        return ['statusText' => 'Vĩnh viễn', 'statusClass' => 'status-active'];
    }

    $today = new DateTime('today');
    $expiry = new DateTime($expiryDate);
    $interval = $today->diff($expiry);

    if ($interval->invert == 1) { // invert = 1 nghĩa là ngày đã qua
        $statusClass = 'status-expired';
        $statusText = 'Hết hạn';
    } elseif ($interval->days <= 7) {
        $statusClass = 'status-expiring';
        $statusText = 'Sắp hết hạn';
    }
    
    return ['statusText' => $statusText, 'statusClass' => $statusClass];
}

// --- XỬ LÝ SUBMIT ADMIN ACTIONS ---

$message = '';
$error = '';

// 1. Xử lý Đăng nhập
if (isset($_POST['admin_password'])) {
    if ($_POST['admin_password'] === ADMIN_PASS) {
        $_SESSION['logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Mã xác thực không hợp lệ. Truy cập bị từ chối.';
    }
}

// 2. Xử lý Đăng xuất
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_start();
    session_destroy();
    header('Location: admin.php');
    exit;
}

session_start();
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Đã đăng nhập
    $keysObject = fetchKeysFromFile();
    
    // 3. Xử lý Tạo Key
    if (isset($_POST['generate_key'])) {
        $expiryDate = $_POST['expiry_date'] ?? '';
        $keyDate = $_POST['key_date'] ?? date('Y-m-d');
        
        $newKey = generateRandomKey();
        
        $keysObject[$newKey] = [
            'expiry_date' => $expiryDate,
            'created_at' => date('Y-m-d H:i:s'), 
            'apply_date' => $keyDate
        ];

        if (updateKeysToFile($keysObject)) {
            $message = "Đã tạo Key mới thành công! Key: <strong>{$newKey}</strong>";
        } else {
            $error = "Lỗi khi lưu Key vào file keys.json. Vui lòng kiểm tra quyền ghi (Write Permission) của file.";
        }
    }

    // 4. Xử lý Xóa Key
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['key'])) {
        $keyToDelete = $_GET['key'];
        
        if (isset($keysObject[$keyToDelete])) {
            unset($keysObject[$keyToDelete]);
            
            if (updateKeysToFile($keysObject)) {
                $message = "Đã xóa Key <strong>{$keyToDelete}</strong> thành công.";
                // Xóa tham số action và key khỏi URL
                header('Location: admin.php');
                exit;
            } else {
                $error = "Lỗi khi xóa Key khỏi file keys.json.";
            }
        } else {
            $error = "Key không tồn tại.";
        }
    }

    $keysArray = $keysObject; // Dữ liệu Key cho bảng hiển thị

}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>VIP Terminal - Quản Lý Key Truy Cập (PHP File)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@700&family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --dark-bg: #030318;
            --primary-neon: #00ffaa; 
            --secondary-neon: #3a00ff; 
            --glow-color: #00aaff;
            --text-light: #e0e0f0;
            --error-color: #ff416c;
            --form-bg: rgba(5, 5, 30, 0.95);
            --border-color: rgba(0, 255, 170, 0.2);
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--dark-bg);
            color: var(--text-light);
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: auto;
            background: var(--form-bg);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 30px rgba(0, 255, 170, 0.3);
            border: 1px solid var(--primary-neon);
            animation: fadeIn 1s ease-out;
        }
        h1 {
            font-family: 'Space Mono', monospace;
            text-align: center;
            color: var(--glow-color);
            text-shadow: 0 0 10px var(--glow-color);
            margin-bottom: 25px;
            font-size: 2.2rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }
        h2 {
            font-family: 'Space Mono', monospace;
            color: var(--primary-neon);
            margin-top: 30px;
            margin-bottom: 15px;
            border-left: 5px solid var(--primary-neon);
            padding-left: 10px;
            font-size: 1.5rem;
        }
        .logout-btn {
            float: right;
            background: var(--error-color);
            color: white;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .success, .error {
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            font-weight: 600;
            font-family: 'Space Mono', monospace;
            border-left: 5px solid;
        }
        .success {
            background: rgba(0, 255, 170, 0.1);
            color: var(--primary-neon);
            border-color: var(--primary-neon);
        }
        .error {
            background: rgba(255, 65, 108, 0.1);
            color: var(--error-color);
            border-color: var(--error-color);
        }
        form input[type="date"], form input[type="password"] {
            padding: 10px;
            width: 100%;
            border: 1px solid var(--secondary-neon);
            border-radius: 4px;
            margin-top: 8px;
            background-color: #0d0d2e;
            color: var(--text-light);
            font-family: 'Space Mono', monospace;
        }
        form button {
            margin-top: 25px;
            background: linear-gradient(90deg, var(--primary-neon), var(--secondary-neon));
            color: var(--dark-bg);
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 700;
            box-shadow: 0 0 10px rgba(0, 255, 170, 0.4);
            transition: all 0.3s;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border: 1px solid var(--border-color);
        }
        th, td {
            padding: 12px;
            border: 1px solid var(--border-color);
            text-align: center;
            font-size: 0.9rem;
        }
        th {
            background: var(--secondary-neon);
            color: white;
            font-family: 'Space Mono', monospace;
        }
        .status-active {background-color: rgba(0, 255, 170, 0.2); color: var(--primary-neon); font-weight: bold;}
        .status-expiring {background-color: rgba(255, 165, 0, 0.2); color: orange;}
        .status-expired {background-color: rgba(255, 65, 108, 0.2); color: var(--error-color); font-style: italic;}
        .delete-btn {
            background: var(--error-color);
            color: white;
            padding: 6px 10px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .login-container {
            font-family: 'Space Mono', monospace;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-box {
            background: var(--form-bg);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 30px rgba(0, 255, 170, 0.3);
            border: 1px solid var(--primary-neon);
            width: 380px;
            text-align: center;
        }
        .input-group {
            position: relative;
            margin-bottom: 25px;
        }
        .input-group i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--glow-color);
            font-size: 18px;
        }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true): ?>
    <div id="login-screen" class="login-container">
        <div class="login-box">
            <h2><i class="fas fa-terminal"></i> ADMIN ACCESS REQUIRED</h2>
            <?php if (!empty($error)): ?>
                <div id="login-error" class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="input-group">
                    <input type="password" id="admin-password" name="admin_password" placeholder="Mã xác thực Quản trị viên" required>
                    <i class="fas fa-key"></i>
                </div>
                <button type="submit"><i class="fas fa-lock"></i> ĐĂNG NHẬP HỆ THỐNG</button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div id="dashboard-screen" class="container">
        <a href="?action=logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        <h1>QUẢN LÝ KEY ACCESS POINT (Sử dụng <?php echo KEY_FILE; ?>)</h1>

        <?php if (!empty($message)): ?>
            <div id="system-success" class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div id="system-error" class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <h2><i class="fas fa-plus-circle"></i> TẠO MÃ TRUY CẬP MỚI</h2>
        <form method="post">
            <label for="key_date">Ngày áp dụng (Chỉ để tham khảo):</label>
            <input type="date" id="key_date" name="key_date" required value="<?php echo date('Y-m-d'); ?>">
            <label for="expiry_date">Ngày hết hạn (Định dạng YYYY-MM-DD, bỏ trống nếu Vĩnh viễn):</label>
            <input type="date" id="expiry_date" name="expiry_date">
            <button type="submit" name="generate_key"><i class="fas fa-unlock-alt"></i> GENERATE NEW KEY (64 Ký tự)</button>
        </form>

        <h2><i class="fas fa-list-alt"></i> DANH SÁCH KEY HOẠT ĐỘNG</h2>
        <table>
            <thead>
                <tr>
                    <th>Ngày áp dụng</th>
                    <th>Key ID</th>
                    <th>Ngày tạo</th>
                    <th>Ngày hết hạn</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody id="keys-table-body">
                <?php if (!empty($keysArray)): ?>
                    <?php foreach ($keysArray as $key => $data): ?>
                        <?php 
                            $status = getStatus($data['expiry_date'] ?? '');
                            $key_display = substr($key, 0, 8) . '...' . substr($key, -8);
                        ?>
                        <tr class="<?php echo $status['class']; ?>">
                            <td><?php echo $data['apply_date'] ?? 'N/A'; ?></td>
                            <td style="font-family: 'Space Mono', monospace; font-size: 0.8rem;"><?php echo $key; ?></td>
                            <td><?php echo $data['created_at'] ?? 'N/A'; ?></td>
                            <td><?php echo $data['expiry_date'] ?: 'Vĩnh viễn'; ?></td>
                            <td class="<?php echo $status['statusClass']; ?>"><?php echo $status['statusText']; ?></td>
                            <td>
                                <a href="?action=delete&key=<?php echo $key; ?>" class="delete-btn" onclick="return confirm('Bạn có chắc chắn muốn xóa key này? Key sẽ bị vô hiệu hóa ngay lập tức.')"><i class="fas fa-trash-alt"></i> Xóa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">Không có mã truy cập nào trong hệ thống.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

</body>
                    </html>
    
