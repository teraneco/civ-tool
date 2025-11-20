<?php
require_once __DIR__ . '/includes/db_connect.php';

$type = $_GET['type'] ?? ''; // hero or skill
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';

if (!$type || !$id) {
  die('不正なアクセスです');
}

// 現在のデータを取得
if ($type === 'hero') {
  $stmt = $pdo->prepare("SELECT * FROM heroes WHERE id = ?");
} elseif ($type === 'skill') {
  $stmt = $pdo->prepare("SELECT * FROM skills WHERE id = ?");
} else {
  die('不正なアクセスです');
}

$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
  die('対象データが存在しません');
}

// 更新処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name']);

  // 画像アップロード処理
  $image_filename = $item['image_filename'];
  if (!empty($_FILES['image']['name'])) {
    $uploadDir = $type === 'hero' ? 'images/heroes/' : 'images/skills/';
    $targetFile = $uploadDir . basename($_FILES['image']['name']);
    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
      // 古い画像を削除
      if ($item['image_filename'] && file_exists($uploadDir . $item['image_filename'])) {
        unlink($uploadDir . $item['image_filename']);
      }
      $image_filename = $_FILES['image']['name'];
    } else {
      $message = '画像アップロードに失敗しました';
    }
  }

  // DB更新
  if ($type === 'hero') {
    $stmt = $pdo->prepare("UPDATE heroes SET name = ?, image_filename = ? WHERE id = ?");
  } else {
    $stmt = $pdo->prepare("UPDATE skills SET name = ?, image_filename = ? WHERE id = ?");
  }
  if ($stmt->execute([$name, $image_filename, $id])) {
    $message = '更新しました';
    // データを再取得
    $stmt = $type === 'hero' ? $pdo->prepare("SELECT * FROM heroes WHERE id = ?") : $pdo->prepare("SELECT * FROM skills WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
  } else {
    $message = '更新に失敗しました';
  }
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <title>編集 - <?php echo htmlspecialchars($type); ?></title>
  <style>
    body {
      font-family: sans-serif;
      padding: 20px;
    }

    label {
      display: block;
      margin-top: 10px;
    }

    input[type=text] {
      width: 300px;
      padding: 5px;
    }

    button {
      margin-top: 10px;
      padding: 5px 10px;
    }

    img {
      margin-top: 5px;
      width: 100px;
      height: 100px;
      object-fit: contain;
    }

    .message {
      color: green;
      font-weight: bold;
      margin-top: 10px;
    }
  </style>
</head>

<body>
  <h1>編集 - <?php echo $type === 'hero' ? '英傑' : 'スキル'; ?></h1>

  <?php if ($message) echo "<div class='message'>" . htmlspecialchars($message) . "</div>"; ?>

  <form method="post" enctype="multipart/form-data">
    <label>名前：
      <input type="text" name="name" value="<?php echo htmlspecialchars($item['name']); ?>" required>
    </label>
    <label>画像：
      <input type="file" name="image">
      <?php if ($item['image_filename']): ?>
        <br><img src="<?php echo ($type === 'hero' ? 'images/heroes/' : 'images/skills/') . htmlspecialchars($item['image_filename']); ?>" alt="">
      <?php endif; ?>
    </label>
    <button type="submit">更新する</button>
  </form>

  <p><a href="admin_manage.php">管理画面に戻る</a></p>
</body>

</html>