<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// view.php - 登録内容参照画面

// データベース接続ファイルを読み込む
require_once __DIR__ . '/includes/db_connect.php';

$userData = null;
$error_message = '';
$display_data = false; // データを表示するかどうかのフラグ
$initial_access_key = ''; // URLから受け取ったキーをフォームの初期値として設定する用

// GETまたはPOSTでアクセスキーが送信された場合
// フォームからの送信は 'access_key'、register.phpからのリダイレクトは 'key'
if (isset($_REQUEST['access_key']) && !empty($_REQUEST['access_key'])) {
    $access_key = $_REQUEST['access_key'];
} elseif (isset($_GET['key']) && !empty($_GET['key'])) { // register.phpからのリダイレクト用
    $access_key = $_GET['key'];
} else {
    $access_key = ''; // キーが指定されていない場合は空
}

// 初期表示用にURLから受け取ったキーを設定
$initial_access_key = htmlspecialchars($access_key);

if (!empty($access_key)) {
    try {
        // user_data テーブルからアクセスキーでデータを取得
        // キーは16文字固定なので、長さもチェックすることで不正な入力を早期に弾く
        if (strlen($access_key) !== 16 || !preg_match('/^[a-zA-Z0-9]+$/', $access_key)) {
            $error_message = "無効なアクセスキー形式です。16文字の英数字を入力してください。";
        } else {
            $stmt = $pdo->prepare("SELECT user_name, memo, owned_heroes_json, owned_skills_json, owned_warrants_count FROM user_data WHERE access_key = ?");
            $stmt->execute([$access_key]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userData) {
                $display_data = true;

                // 英傑とスキルのJSONデータをデコード
                $ownedHeroes = json_decode($userData['owned_heroes_json'], true);
                $ownedSkills = json_decode($userData['owned_skills_json'], true);

                // デコードに失敗した場合のハンドリング
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("参照画面: JSONデコードエラー - " . json_last_error_msg());
                    $ownedHeroes = [];
                    $ownedSkills = [];
                }
                if (!is_array($ownedHeroes)) $ownedHeroes = [];
                if (!is_array($ownedSkills)) $ownedSkills = [];


                // 英傑データの詳細を取得（重複を考慮して集計し、マスタデータを取得）
                $heroCounts = array_count_values($ownedHeroes); // ['h001' => 2, 'h002' => 1]
                $heroIds = array_keys($heroCounts); // ユニークなIDリスト

                $displayHeroes = [];
                if (!empty($heroIds)) {
                    // IN句で使うためにプレースホルダを生成
                    $placeholders = implode(',', array_fill(0, count($heroIds), '?'));
                    $stmt = $pdo->prepare("SELECT hero_id, name, image_filename FROM heroes WHERE hero_id IN ($placeholders)");
                    $stmt->execute($heroIds);
                    $masterHeroes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // hero_id をキーにした連想配列に変換
                    $masterHeroMap = [];
                    foreach ($masterHeroes as $hero) {
                        $masterHeroMap[$hero['hero_id']] = $hero;
                    }

                    // 表示用の英傑データを作成（カウントも付与）
                    foreach ($heroCounts as $id => $count) {
                        if (isset($masterHeroMap[$id])) {
                            $displayHeroes[] = [
                                'id' => $id,
                                'name' => $masterHeroMap[$id]['name'],
                                'image_filename' => $masterHeroMap[$id]['image_filename'],
                                'count' => $count
                            ];
                        }
                    }
                }

                // スキルデータの詳細を取得（英傑と同様のロジック）
                $skillCounts = array_count_values($ownedSkills);
                $skillIds = array_keys($skillCounts);

                $displaySkills = [];
                if (!empty($skillIds)) {
                    $placeholders = implode(',', array_fill(0, count($skillIds), '?'));
                    $stmt = $pdo->prepare("SELECT skill_id, name, image_filename FROM skills WHERE skill_id IN ($placeholders)");
                    $stmt->execute($skillIds);
                    $masterSkills = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $masterSkillMap = [];
                    foreach ($masterSkills as $skill) {
                        $masterSkillMap[$skill['skill_id']] = $skill;
                    }

                    foreach ($skillCounts as $id => $count) {
                        if (isset($masterSkillMap[$id])) {
                            $displaySkills[] = [
                                'id' => $id,
                                'name' => $masterSkillMap[$id]['name'],
                                'image_filename' => $masterSkillMap[$id]['image_filename'],
                                'count' => $count
                            ];
                        }
                    }
                }
            } else {
                $error_message = "指定されたキーの登録データは見つかりませんでした。";
            }
        } // End of strlen/preg_match check

    } catch (PDOException $e) {
        error_log("データベース参照エラー: " . $e->getMessage());
        $error_message = "データの読み込み中にエラーが発生しました。しばらくしてから再度お試しください。";
    }
}

// 画像のベースパス (index.php と同じパスを定義)
define('HERO_IMAGE_BASE_PATH', 'images/heroes/');
define('SKILL_IMAGE_BASE_PATH', 'images/skills/');
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登録内容参照</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 0;
            padding: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: #f4f4f4;
            color: #333;
        }

        h1,
        h2,
        h3 {
            color: #0056b3;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .container {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            width: 100%;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: center;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        .form-group input[type="text"] {
            width: calc(100% - 40px);
            /* ボタンとパディング考慮 */
            max-width: 300px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
            display: inline-block;
            vertical-align: middle;
        }

        .form-group button {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            vertical-align: middle;
            margin-left: 5px;
            transition: background-color 0.2s;
        }

        .form-group button:hover {
            background-color: #0056b3;
        }

        .error-message {
            color: red;
            font-weight: bold;
            margin-top: 10px;
            text-align: center;
        }

        .info-section {
            border-top: 1px dashed #eee;
            padding-top: 15px;
            margin-top: 20px;
        }

        .info-item {
            margin-bottom: 10px;
        }

        .item-display-list {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            /* アイテム間のスペース */
            padding: 10px;
            border: 1px dashed #ddd;
            min-height: 80px;
            align-items: center;
            /* 縦方向中央揃え */
        }

        .display-item {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 80px;
            /* アイテムのサイズ */
            text-align: center;
        }

        .display-item img {
            width: 70px;
            height: 70px;
            object-fit: contain;
            /* 英傑登録画面のCSSを調整するならここも合わせる */
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #e0e0e0;
        }

        .item-name {
            font-size: 0.85em;
            color: #555;
            margin-top: 5px;
            word-break: break-all;
            /* 長い名前がはみ出さないように */
        }

        .item-count-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #007bff;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7em;
            font-weight: bold;
            min-width: 18px;
            /* 一桁でも丸く見せる */
            text-align: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .no-items {
            color: #777;
            font-style: italic;
            margin: auto;
            /* 中央揃え */
        }

        textarea.memo-display {
            width: calc(100% - 20px);
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
            resize: vertical;
            min-height: 100px;
            background-color: #f9f9f9;
            color: #333;
        }

        .back-to-top {
            display: block;
            text-align: center;
            margin-top: 30px;
            color: #007bff;
            text-decoration: none;
            font-size: 1.1em;
        }

        .back-to-top:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <h1>登録内容参照</h1>

    <div class="container">

        <?php if ($display_data): ?>
            <div class="info-section">
                <h2>登録情報</h2>
                <div class="info-item">
                    <strong>名前:</strong> <?php echo htmlspecialchars($userData['user_name'] ?: '未入力'); ?>
                </div>
                <div class="info-item">
                    <strong>委任状の数:</strong> <?php echo htmlspecialchars($userData['owned_warrants_count']); ?> 枚
                </div>
                <div class="info-item">
                    <strong>メモ:</strong><br>
                    <textarea class="memo-display" readonly><?php echo htmlspecialchars($userData['memo'] ?: 'なし'); ?></textarea>
                </div>

                <h2>編成詳細</h2>
                <h3>英傑:</h3>
                <div class="item-display-list">
                    <?php if (empty($displayHeroes)): ?>
                        <p class="no-items">登録された英傑はいません。</p>
                    <?php else: ?>
                        <?php foreach ($displayHeroes as $hero): ?>
                            <div class="display-item">
                                <img src="<?php echo HERO_IMAGE_BASE_PATH . htmlspecialchars($hero['image_filename']); ?>"
                                    alt="<?php echo htmlspecialchars($hero['name']); ?>">
                                <span class="item-name"><?php echo htmlspecialchars($hero['name']); ?></span>
                                <?php if ($hero['count'] > 1): ?>
                                    <span class="item-count-badge">x<?php echo htmlspecialchars($hero['count']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <h3>スキル:</h3>
                <div class="item-display-list">
                    <?php if (empty($displaySkills)): ?>
                        <p class="no-items">登録されたスキルはありません。</p>
                    <?php else: ?>
                        <?php foreach ($displaySkills as $skill): ?>
                            <div class="display-item">
                                <img src="<?php echo SKILL_IMAGE_BASE_PATH . htmlspecialchars($skill['image_filename']); ?>"
                                    alt="<?php echo htmlspecialchars($skill['name']); ?>">
                                <span class="item-name"><?php echo htmlspecialchars($skill['name']); ?></span>
                                <?php if ($skill['count'] > 1): ?>
                                    <span class="item-count-badge">x<?php echo htmlspecialchars($skill['count']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif (isset($_REQUEST['access_key']) && !empty($_REQUEST['access_key'])): ?>
        <?php else: ?>
            <p style="text-align: center; margin-top: 20px;">
                上記に登録されたアクセスキーを入力して、編成内容を表示してください。
            </p>
        <?php endif; ?>

        <a href="index.php" class="back-to-top">新しい編成を登録する</a>
    </div>
</body>

</html>