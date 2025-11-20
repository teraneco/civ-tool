<?php
require_once __DIR__ . '/includes/db_connect.php';

$message = '';

// 追加処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'], $_POST['name'])) {
  $type = $_POST['type']; // hero or skill
  $name = trim($_POST['name']);

  // 画像アップロード処理
  if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/images/' . ($type === 'hero' ? 'heroes/' : 'skills/');
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    // ファイル名のユニーク化（上書き防止）
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    $targetFile = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
      try {
        if ($type === 'hero') {
          // 既存の最後の hero_id を取得
          $lastHeroId = $pdo->query("SELECT hero_id FROM heroes ORDER BY id DESC LIMIT 1")->fetchColumn();
          if ($lastHeroId) {
            $nextNum = (int)substr($lastHeroId, 1) + 1;
          } else {
            $nextNum = 1;
          }
          $heroId = 'h' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

          $stmt = $pdo->prepare("INSERT INTO heroes (hero_id, name, image_filename) VALUES (?, ?, ?)");
          $stmt->execute([$heroId, $name, $filename]);
          $message = "英傑を追加しました（ID: $heroId）。";
        } else {
          // 既存の最後の skill_id を取得
          $lastSkillId = $pdo->query("SELECT skill_id FROM skills ORDER BY id DESC LIMIT 1")->fetchColumn();
          if ($lastSkillId) {
            $nextNum = (int)substr($lastSkillId, 1) + 1;
          } else {
            $nextNum = 1;
          }
          $skillId = 's' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

          $stmt = $pdo->prepare("INSERT INTO skills (skill_id, name, image_filename) VALUES (?, ?, ?)");
          $stmt->execute([$skillId, $name, $filename]);
          $message = "スキルを追加しました（ID: $skillId）。";
        }
      } catch (PDOException $e) {
        $message = "データベースエラー: " . $e->getMessage();
      }
    } else {
      $message = "画像のアップロードに失敗しました。";
    }
  } else {
    $message = "画像ファイルを選択してください。";
  }
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <title>英傑・スキル追加</title>
  <style>
    body {
      font-family: sans-serif;
      padding: 20px;
    }

    form {
      margin-bottom: 20px;
    }

    input[type=text],
    input[type=file],
    select {
      padding: 5px;
      margin: 5px 0;
      width: 300px;
    }

    button {
      padding: 5px 10px;
    }

    .message {
      margin: 10px 0;
      color: green;
      font-weight: bold;
    }
  </style>
</head>

<body>
  <h1>英傑・スキル追加</h1>

  <?php if ($message): ?>
    <div class="message"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <label>種類：
      <select name="type">
        <option value="hero">英傑</option>
        <option value="skill">スキル</option>
      </select>
    </label><br>

    <label>名前：
      <input type="text" name="name" required>
    </label><br>

    <label>画像：
      <input type="file" name="image" accept="image/*" required>
    </label><br>

    <button type="submit">追加する</button>
  </form>

  <a href="admin_manage.php">管理画面に戻る</a>
</body>

</html>