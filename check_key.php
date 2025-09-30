<?php
// Thiết lập header
header('Content-Type: application/json');

// --- Cấu hình ---
$keysFile = 'keys.json';

// Lấy key, gán mặc định là chuỗi rỗng nếu không có POST, và LOẠI BỎ KHOẢNG TRẮNG DƯ THỪA
$enteredKey = trim($_POST['key'] ?? ''); 

$response = ['valid' => false];

// 1. Xử lý và kiểm tra tính toàn vẹn của file keys.json
if (!file_exists($keysFile)) {
    // Nếu file chưa tồn tại, tạo file rỗng với nội dung là mảng JSON rỗng
    file_put_contents($keysFile, json_encode([]));
    $keysData = [];
} else {
    // Đọc và giải mã JSON
    $keysData = json_decode(file_get_contents($keysFile), true);
    
    // Nếu file tồn tại nhưng nội dung không phải JSON hợp lệ, coi là mảng rỗng
    if ($keysData === null || !is_array($keysData)) {
        $keysData = [];
    }
}

// 2. Kiểm tra key nhập vào
if (!empty($enteredKey)) { // CHỈ CHẠY VÒNG LẶP NẾU KEY NHẬP KHÔNG RỖNG
    foreach ($keysData as $keyInfo) {
        // Đảm bảo đây là một đối tượng/mảng chứa thông tin key
        if (!is_array($keyInfo) || !isset($keyInfo['key'])) {
            continue;
        }
        
        // Kiểm tra key có khớp không
        if ($keyInfo['key'] === $enteredKey) {
            
            // Key vĩnh viễn (Không có expiry_date hoặc rỗng)
            if (!isset($keyInfo['expiry_date']) || empty($keyInfo['expiry_date'])) {
                $response['valid'] = true;
                break;
            } 
            
            // Key có hạn sử dụng
            else {
                // Lấy timestamp của ngày hết hạn, giả định Key hết hạn vào cuối ngày đó (23:59:59)
                $expiryTimestamp = strtotime($keyInfo['expiry_date'] . ' 23:59:59'); 
                
                // So sánh với thời gian hiện tại
                if ($expiryTimestamp >= time()) {
                    $response['valid'] = true;
                    break;
                }
            }
        }
    }
}

// 3. Trả về kết quả
echo json_encode($response);
?>
	