<?php
// Lấy danh sách ngân hàng từ API VietQR
$banks = [];
$bankListUrl = "https://api.vietqr.io/v2/banks";
$responseBanks = @file_get_contents($bankListUrl);
if ($responseBanks !== false) {
    $bankData = json_decode($responseBanks, true);
    if (isset($bankData['code']) && $bankData['code'] === "00") {
        $banks = $bankData['data'];
    }
}
// Fallback nếu không lấy được danh sách ngân hàng
if (empty($banks)) {
    $banks = [
        ['bin' => '970436', 'shortName' => 'Vietcombank', 'name' => 'Vietcombank']
    ];
}

// Lấy ngân hàng được chọn (acqId) từ form, mặc định là ngân hàng đầu tiên
$acqId = isset($_POST['bank']) ? $_POST['bank'] : $banks[0]['bin'];
$selectedBankName = '';
foreach ($banks as $bank) {
    if ($bank['bin'] === $acqId) {
        $selectedBankName = $bank['shortName'] ?? $bank['name'];
        break;
    }
}

// Các trường nhập động
$accountNo   = isset($_POST['accountNo']) ? $_POST['accountNo'] : "";
$accountName = isset($_POST['accountName']) ? $_POST['accountName'] : "";
$amount      = "";
$addInfo     = "";
$qrImage     = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Xử lý số tiền: loại bỏ dấu phẩy trước khi ép về số
    $amount = str_replace(',', '', $_POST['amount']);
    $addInfo = $_POST['addInfo'];

    // Chuẩn bị dữ liệu gửi API VietQR
    $apiData = [
        "acqId"       => $acqId,
        "accountNo"   => $accountNo,
        "accountName" => $accountName,
        "amount"      => (int)$amount,
        "addInfo"     => $addInfo,
        "format"      => "text",
        "template"    => "print"
    ];

    // Gọi API tạo QR Code
    $url = "https://api.vietqr.io/v2/generate";
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\nAccept: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($apiData),
        ],
    ];
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result !== false) {
        $response = json_decode($result, true);
        if (isset($response['data']['qrDataURL'])) {
            $qrImage = $response['data']['qrDataURL'];
        } else {
            echo "<p class='text-danger'>Lỗi từ API: " . htmlspecialchars($response['message'] ?? 'Không rõ') . "</p>";
        }
    } else {
        echo "<p class='text-danger'>Lỗi: Không thể kết nối tới API.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>QR Thanh Toán HappyBook Travel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="https://api.happybook.com.vn/images/icon-crop-QR.png" type="image/x-icon">
    <style>
        body {
            background: #f7f9fc;
        }
        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .qr-container {
            position: relative;
            display: inline-block;
        }
        .qr-header {
            position: absolute;
            top: -4px;
            left: 51%;
            transform: translateX(-50%);
            width: 56%;
        }
        .qr-overlay {
            position: absolute;
            top: 48.5%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 10%;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="text-center">Tạo Mã QR Thanh Toán</h1>
    <div class="row mt-5">
        <!-- Cột trái: Form nhập liệu -->
        <div class="col-md-6">
            <form method="POST">
                <!-- Chọn Ngân hàng -->
                <div class="mb-3">
                    <label for="bank" class="form-label">Ngân hàng</label>
                    <select class="form-select" name="bank" id="bank" required>
                        <?php foreach ($banks as $bank): ?>
                            <option value="<?= htmlspecialchars($bank['bin']) ?>" <?= ($bank['bin'] === $acqId) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($bank['shortName'] ?? $bank['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Nhập số tài khoản -->
                <div class="mb-3">
                    <label for="accountNo" class="form-label">Số tài khoản</label>
                    <input type="text" class="form-control" id="accountNo" name="accountNo" value="<?= htmlspecialchars($accountNo) ?>" required>
                </div>

                <!-- Nhập tên chủ tài khoản -->
                <div class="mb-3">
                    <label for="accountName" class="form-label">Tên chủ tài khoản</label>
                    <input type="text" class="form-control" id="accountName" name="accountName" value="<?= htmlspecialchars($accountName) ?>" required>
                </div>

                <!-- Nhập số tiền -->
                <div class="mb-3">
                    <label for="amount" class="form-label">Số tiền</label>
                    <input type="text" class="form-control" id="amount" name="amount"
                           value="<?= htmlspecialchars(number_format((int)$amount, 0, ',', ',')) ?>" required
                           oninput="formatNumber(this)">
                </div>

                <!-- Nội dung chuyển khoản -->
                <div class="mb-3">
                    <label for="addInfo" class="form-label">Nội dung chuyển khoản</label>
                    <input type="text" class="form-control" id="addInfo" name="addInfo" value="<?= htmlspecialchars($addInfo) ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">Tạo QR</button>
            </form>
        </div>

        <!-- Cột phải: Hiển thị QR Code và thông tin giao dịch -->
        <div class="col-md-6 text-center">
            <?php if (!empty($qrImage)): ?>
                <div class="qr-container mt-4">
                    <!-- Logo được chèn bên trên QR -->
                    <img src="https://api.happybook.com.vn/images/logo-crop-QR.png" alt="Header Logo" class="qr-header">
                    <!-- QR Code được tạo động -->
                    <img src="<?= $qrImage ?>" alt="QR Code" class="img-fluid mb-3">
                    <!-- Icon overlay giữa QR -->
                    <img src="https://api.happybook.com.vn/images/icon-crop-QR.png" alt="Overlay Icon" class="qr-overlay">
                </div>
                <!-- Thông tin giao dịch -->
                <div class="mt-3 text-start d-inline-block">
                    <p><strong>Nội dung CK:</strong> <?= htmlspecialchars($addInfo) ?></p>
                    <p><strong>Tên chủ TK:</strong> <?= htmlspecialchars($accountName) ?></p>
                    <p><strong>Số TK:</strong> <?= htmlspecialchars($accountNo) ?></p>
                    <p><strong>Ngân hàng:</strong> <?= htmlspecialchars($selectedBankName) ?></p>
                    <p><strong>Số tiền:</strong> <?= number_format((int)$amount, 0, ',', ',') ?> VND</p>
                </div>
                <!-- Nút Share by Link xuất hiện dưới cùng -->
                <div class="mt-3">
                    <button type="button" class="btn btn-secondary" onclick="shareLink()">Share by Link</button>
                </div>
            <?php else: ?>
                <p class="mt-4 text-muted">Chưa có QR Code để hiển thị.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Hàm định dạng số: thêm dấu phẩy phân cách hàng nghìn
    function formatNumber(input) {
        let value = input.value.replace(/,/g, '');
        input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    // Hàm shareLink tạo quickLink theo định dạng của VietQR và chia sẻ hoặc copy link
    function shareLink() {
        const accountNo   = document.getElementById("accountNo").value;
        const accountName = document.getElementById("accountName").value;
        const amount      = document.getElementById("amount").value.replace(/,/g, '');
        const addInfo     = document.getElementById("addInfo").value;
        const bank        = document.getElementById("bank").value;
        // Tạo quickLink theo định dạng: 
        // https://img.vietqr.io/image/<BANK_ID>-<ACCOUNT_NO>-print.png?amount=<AMOUNT>&addInfo=<DESCRIPTION>&accountName=<ACCOUNT_NAME>
        const quickLinkUrl = `https://img.vietqr.io/image/${encodeURIComponent(bank)}-${encodeURIComponent(accountNo)}-print.png?amount=${encodeURIComponent(amount)}&addInfo=${encodeURIComponent(addInfo)}&accountName=${encodeURIComponent(accountName)}`;

        if (navigator.share) {
            navigator.share({
                title: 'QR Thanh Toán HappyBook Travel',
                text: 'Thực hiện thanh toán qua HappyBook Travel',
                url: quickLinkUrl
            }).then(() => {
                console.log('Chia sẻ thành công.');
            }).catch((error) => {
                console.error('Lỗi khi chia sẻ:', error);
            });
        } else {
            prompt("Copy link chia sẻ:", quickLinkUrl);
        }
    }
</script>
</body>
</html>
