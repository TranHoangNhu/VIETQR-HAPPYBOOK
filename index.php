<?php
// Lấy danh sách ngân hàng từ API VietQR (dùng cho tab "TK khác")
$banks = [];
$bankListUrl = "https://api.vietqr.io/v2/banks";
$responseBanks = @file_get_contents($bankListUrl);
if ($responseBanks !== false) {
    $bankData = json_decode($responseBanks, true);
    if (isset($bankData['code']) && $bankData['code'] === "00") {
        $banks = $bankData['data'];
    }
}
if (empty($banks)) {
    $banks = [
        ['bin' => '970436', 'shortName' => 'Vietcombank', 'name' => 'Vietcombank']
    ];
}

// Xác định tab hiện hành (mặc định là "happybook")
$tab = isset($_POST['tab']) ? $_POST['tab'] : 'happybook';

if ($tab === 'happybook') {
    // TK HappyBook cố định
    $acqId = "970436";
    $selectedBankName = "Vietcombank";
    $accountNo = "1052681483";
    $accountName = "CÔNG TY TNHH TM DV DU LỊCH HAPPYBOOK";
} else if ($tab === 'chivan') {
    // TK chị Văn cố định
    $acqId = "970422"; // Ở đây dùng chuỗi "MBBank" như được chỉ định
    $selectedBankName = "MBBank";
    $accountNo = "79379799999";
    $accountName = "VŨ THỊ VĂN";
} else {
    // TK khác: nhập động
    $acqId = isset($_POST['bank']) ? $_POST['bank'] : $banks[0]['bin'];
    $selectedBankName = "";
    foreach ($banks as $bank) {
        if ($bank['bin'] === $acqId) {
            $selectedBankName = $bank['shortName'] ?? $bank['name'];
            break;
        }
    }
    $accountNo = isset($_POST['accountNo']) ? $_POST['accountNo'] : "";
    $accountName = isset($_POST['accountName']) ? $_POST['accountName'] : "";
}

$amount = "";
$addInfo = "";
$qrImage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Xử lý số tiền: loại bỏ dấu phẩy
    $amount = str_replace(',', '', $_POST['amount']);
    $addInfo = $_POST['addInfo'];

    // Chuẩn bị dữ liệu gửi tới API VietQR
    $apiData = [
        "acqId"       => $acqId,
        "accountNo"   => $accountNo,
        "accountName" => $accountName,
        "amount"      => (int)$amount,
        "addInfo"     => $addInfo,
        "format"      => "text",
        "template"    => "compact"
    ];

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
  <title>QR Thanh Toán</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="shortcut icon" href="https://api.happybook.com.vn/images/icon-crop-QR.png" type="image/x-icon">
  <style>
    body { background: #f7f9fc; }
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
    <!-- Nav Tabs cho 3 loại tài khoản -->
    <ul class="nav nav-tabs" id="paymentTab" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link <?= $tab==='happybook' ? 'active' : '' ?>" id="happybook-tab" data-bs-toggle="tab" data-bs-target="#happybook" type="button" role="tab" aria-controls="happybook" aria-selected="<?= $tab==='happybook' ? 'true' : 'false' ?>">TK HappyBook</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link <?= $tab==='chivan' ? 'active' : '' ?>" id="chivan-tab" data-bs-toggle="tab" data-bs-target="#chivan" type="button" role="tab" aria-controls="chivan" aria-selected="<?= $tab==='chivan' ? 'true' : 'false' ?>">TK chị Văn</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link <?= $tab==='other' ? 'active' : '' ?>" id="other-tab" data-bs-toggle="tab" data-bs-target="#other" type="button" role="tab" aria-controls="other" aria-selected="<?= $tab==='other' ? 'true' : 'false' ?>">TK khác</button>
      </li>
    </ul>
    <div class="tab-content" id="paymentTabContent">
      <!-- Tab TK HappyBook -->
      <div class="tab-pane fade <?= $tab==='happybook' ? 'show active' : '' ?>" id="happybook" role="tabpanel" aria-labelledby="happybook-tab">
        <form method="POST" class="mt-3">
          <input type="hidden" name="tab" value="happybook">
          <div class="mb-3">
            <label class="form-label">Ngân hàng</label>
            <input type="text" class="form-control" value="Vietcombank" disabled>
            <input type="hidden" name="bank" value="970436">
          </div>
          <div class="mb-3">
            <label class="form-label">Số tài khoản</label>
            <input type="text" class="form-control" value="1052681483" disabled>
            <input type="hidden" name="accountNo" value="1052681483">
          </div>
          <div class="mb-3">
            <label class="form-label">Tên chủ TK</label>
            <input type="text" class="form-control" value="CÔNG TY TNHH TM DV DU LỊCH HAPPYBOOK" disabled>
            <input type="hidden" name="accountName" value="CÔNG TY TNHH TM DV DU LỊCH HAPPYBOOK">
          </div>
          <div class="mb-3">
            <label for="amountHappy" class="form-label">Số tiền</label>
            <input type="text" class="form-control" id="amountHappy" name="amount" value="<?= htmlspecialchars(number_format((int)$amount, 0, ',', ',')) ?>" required oninput="formatNumber(this)">
          </div>
          <div class="mb-3">
            <label for="addInfoHappy" class="form-label">Nội dung chuyển khoản</label>
            <input type="text" class="form-control" id="addInfoHappy" name="addInfo" value="<?= htmlspecialchars($addInfo) ?>" required>
          </div>
          <button type="submit" class="btn btn-primary">Tạo QR</button>
        </form>
      </div>
      <!-- Tab TK chị Văn -->
      <div class="tab-pane fade <?= $tab==='chivan' ? 'show active' : '' ?>" id="chivan" role="tabpanel" aria-labelledby="chivan-tab">
        <form method="POST" class="mt-3">
          <input type="hidden" name="tab" value="chivan">
          <div class="mb-3">
            <label class="form-label">Ngân hàng</label>
            <input type="text" class="form-control" value="MBBank" disabled>
            <input type="hidden" name="bank" value="MBBank">
          </div>
          <div class="mb-3">
            <label class="form-label">Số tài khoản</label>
            <input type="text" class="form-control" value="79379799999" disabled>
            <input type="hidden" name="accountNo" value="79379799999">
          </div>
          <div class="mb-3">
            <label class="form-label">Tên chủ TK</label>
            <input type="text" class="form-control" value="VŨ THỊ VĂN" disabled>
            <input type="hidden" name="accountName" value="VŨ THỊ VĂN">
          </div>
          <div class="mb-3">
            <label for="amountChivan" class="form-label">Số tiền</label>
            <input type="text" class="form-control" id="amountChivan" name="amount" value="<?= htmlspecialchars(number_format((int)$amount, 0, ',', ',')) ?>" required oninput="formatNumber(this)">
          </div>
          <div class="mb-3">
            <label for="addInfoChivan" class="form-label">Nội dung chuyển khoản</label>
            <input type="text" class="form-control" id="addInfoChivan" name="addInfo" value="<?= htmlspecialchars($addInfo) ?>" required>
          </div>
          <button type="submit" class="btn btn-primary">Tạo QR</button>
        </form>
      </div>
      <!-- Tab TK khác -->
      <div class="tab-pane fade <?= $tab==='other' ? 'show active' : '' ?>" id="other" role="tabpanel" aria-labelledby="other-tab">
        <form method="POST" class="mt-3">
          <input type="hidden" name="tab" value="other">
          <div class="mb-3">
            <label for="bankOther" class="form-label">Ngân hàng</label>
            <select class="form-select" name="bank" id="bankOther" required>
              <?php foreach ($banks as $bank): ?>
                <option value="<?= htmlspecialchars($bank['bin']) ?>" <?= ($bank['bin'] === $acqId) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($bank['shortName'] ?? $bank['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="accountNoOther" class="form-label">Số tài khoản</label>
            <input type="text" class="form-control" id="accountNoOther" name="accountNo" value="<?= htmlspecialchars($accountNo) ?>" required>
          </div>
          <div class="mb-3">
            <label for="accountNameOther" class="form-label">Tên chủ TK</label>
            <input type="text" class="form-control" id="accountNameOther" name="accountName" value="<?= htmlspecialchars($accountName) ?>" required>
          </div>
          <div class="mb-3">
            <label for="amountOther" class="form-label">Số tiền</label>
            <input type="text" class="form-control" id="amountOther" name="amount" value="<?= htmlspecialchars(number_format((int)$amount, 0, ',', ',')) ?>" required oninput="formatNumber(this)">
          </div>
          <div class="mb-3">
            <label for="addInfoOther" class="form-label">Nội dung chuyển khoản</label>
            <input type="text" class="form-control" id="addInfoOther" name="addInfo" value="<?= htmlspecialchars($addInfo) ?>" required>
          </div>
          <button type="submit" class="btn btn-primary">Tạo QR</button>
        </form>
      </div>
    </div>
    <!-- Phần hiển thị QR Code và thông tin giao dịch -->
    <div class="row mt-4">
      <div class="col-md-6 offset-md-3 text-center">
        <?php if (!empty($qrImage)): ?>
          <div class="qr-container">
            <img src="https://api.happybook.com.vn/images/logo-crop-QR.png" alt="Header Logo" class="qr-header">
            <img src="<?= $qrImage ?>" alt="QR Code" class="img-fluid mb-3">
            <img src="https://api.happybook.com.vn/images/icon-crop-QR.png" alt="Overlay Icon" class="qr-overlay">
          </div>
          <div class="mt-3 text-start">
            <p><strong>Nội dung CK:</strong> <?= htmlspecialchars($addInfo) ?></p>
            <p><strong>Tên chủ TK:</strong> <?= htmlspecialchars($accountName) ?></p>
            <p><strong>Số TK:</strong> <?= htmlspecialchars($accountNo) ?></p>
            <p><strong>Ngân hàng:</strong> <?= htmlspecialchars($selectedBankName) ?></p>
            <p><strong>Số tiền:</strong> <?= number_format((int)$amount, 0, ',', ',') ?> VND</p>
          </div>
          <!-- Nút Share by Link sử dụng quickLink của VietQR -->
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
    // Hàm shareLink tạo quickLink theo định dạng của VietQR
    // Format: https://img.vietqr.io/image/<BANK_ID>-<ACCOUNT_NO>-print.png?amount=<AMOUNT>&addInfo=<DESCRIPTION>&accountName=<ACCOUNT_NAME>
    function shareLink() {
      let activeTab = document.querySelector('.tab-pane.show.active').id;
      let bank, accountNo, accountName, amount, addInfo;
      if (activeTab === 'happybook') {
        bank = "970436";
        accountNo = "1052681483";
        accountName = "CÔNG TY TNHH TM DV DU LỊCH HAPPYBOOK";
        amount = document.getElementById("amountHappy").value.replace(/,/g, '');
        addInfo = document.getElementById("addInfoHappy").value;
      } else if (activeTab === 'chivan') {
        bank = "MBBank";
        accountNo = "79379799999";
        accountName = "VŨ THỊ VĂN";
        amount = document.getElementById("amountChivan").value.replace(/,/g, '');
        addInfo = document.getElementById("addInfoChivan").value;
      } else {
        bank = document.getElementById("bankOther").value;
        accountNo = document.getElementById("accountNoOther").value;
        accountName = document.getElementById("accountNameOther").value;
        amount = document.getElementById("amountOther").value.replace(/,/g, '');
        addInfo = document.getElementById("addInfoOther").value;
      }
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
