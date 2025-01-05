<?php
// Cấu hình cố định thông tin tài khoản
$accountNo = "1052681483";
$accountName = "CÔNG TY TNHH TM DV DU LỊCH HAPPYBOOK";
$acqId = "970436"; // Mã ngân hàng Vietcombank
$qrImage = "";
$amount = $addInfo = "";
$showModal = false;

// Xử lý khi người dùng submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = str_replace(',', '', $_POST['amount']); // Xóa dấu phẩy trước khi xử lý
    $addInfo = $_POST['addInfo'];

    // Dữ liệu gửi tới API
    $apiData = [
        "acqId" => $acqId,
        "accountNo" => $accountNo,
        "accountName" => $accountName,
        "amount" => (int)$amount,
        "addInfo" => $addInfo,
        "format" => "text",
        "template" => "compact" // Giá trị cố định cho template
    ];

    // Gọi API để tạo QR Code
    $url = "https://api.vietqr.io/v2/generate";
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($apiData),
        ],
    ];
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result !== false) {
        $response = json_decode($result, true);
        if (isset($response['data']['qrDataURL'])) {
            $qrImage = $response['data']['qrDataURL'];
            $showModal = true;
        } else {
            echo "<p class='text-danger'>Lỗi từ API: " . htmlspecialchars($response['message'] ?? 'Không rõ') . "</p>";
        }
    } else {
        echo "<p class='text-danger'>Lỗi: Không thể kết nối tới API.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="https://api.happybook.com.vn/images/icon-crop-QR.png" type="image/x-icon">
    <title>QR Thanh Toán HappyBook Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .qr-container {
            position: relative;
            display: inline-block;
        }
        .qr-overlay {
            position: absolute;
            top: 48.5%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 10%;
        }
        .qr-header {
            position: absolute;
            top: -4px;
            left: 51%;
            transform: translateX(-50%);
            width: 56%;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">QR Thanh Toán HappyBook Travel</h1>
    <form method="POST" class="mt-4">
        <div class="mb-3">
            <label for="amount" class="form-label">Số tiền</label>
            <input type="text" class="form-control" id="amount" name="amount" value="<?= htmlspecialchars(number_format((int)$amount, 0, ',', ',')) ?>" required oninput="formatNumber(this)">
        </div>
        <div class="mb-3">
            <label for="addInfo" class="form-label">Nội dung chuyển khoản</label>
            <input type="text" class="form-control" id="addInfo" name="addInfo" value="<?= htmlspecialchars($addInfo) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Tạo QR</button>
    </form>

    <!-- Modal hiển thị QR Code -->
    <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qrModalLabel">Mã QR Thanh Toán</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <?php if (!empty($qrImage)): ?>
                        <div class="qr-container">
                            <img src="https://api.happybook.com.vn/images/logo-crop-QR.png" alt="Header Logo" class="qr-header">
                            <img src="<?= $qrImage ?>" alt="QR Code" class="img-fluid mb-3">
                            <img src="https://api.happybook.com.vn/images/icon-crop-QR.png" alt="Overlay Icon" class="qr-overlay">
                        </div>
                        <h5>Nội dung CK: <strong><?= htmlspecialchars($addInfo) ?></strong></h5>
                        <h5>Tên chủ TK: <strong><?= htmlspecialchars($accountName) ?></strong></h5>
                        <h5>Số TK: <strong><?= htmlspecialchars($accountNo) ?></strong></h5>
                        <h5>Ngân hàng: <strong>Vietcombank</strong></h5>
                        <h5>Số tiền: <strong><?= number_format((int)$amount, 0, ',', ',') ?> VND</strong></h5>
                    <?php else: ?>
                        <p class="text-danger">QR Code chưa được tạo.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function formatNumber(input) {
        // Xóa tất cả dấu phẩy
        let value = input.value.replace(/,/g, '');
        // Thêm lại dấu phẩy phân cách theo nghìn
        input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
</script>
<?php if ($showModal): ?>
<script>
    const qrModal = new bootstrap.Modal(document.getElementById('qrModal'));
    qrModal.show();
</script>
<?php endif; ?>
</body>
</html>
