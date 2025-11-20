<?php
// register.php - 登録処理

// データベース接続ファイルを読み込む
require_once __DIR__ . '/includes/db_connect.php';

// ヘルパー関数: ランダムな英数字文字列を生成
// PHP 7 以降を想定 (random_bytes 関数を使用)
function generateRandomString($length = 16) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        // 安全な乱数生成器を使用
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}

// キー生成と重複チェックのループ
$access_key = '';
$max_attempts = 5; // 最大試行回数
$attempt = 0;
do {
    $access_key = generateRandomString(16);
    $attempt++;

    // データベースに同じキーが存在しないか確認
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_data WHERE access_key = ?");
    $stmt->execute([$access_key]);
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        // 重複がなければループを抜ける
        break;
    }
} while ($count > 0 && $attempt < $max_attempts); // 重複がある、または試行回数上限に達していない場合

if ($attempt >= $max_attempts && $count > 0) {
    // 最大試行回数に達してもユニークなキーが生成できなかった場合
    error_log("ユニークなアクセスキーの生成に失敗しました。");
    die("登録に失敗しました。時間をおいて再度お試しください。");
}


// POSTデータの取得とサニタイズ/バリデーション
$input = filter_input_array(INPUT_POST, [
    'selected_hero_ids' => [
        'filter' => FILTER_UNSAFE_RAW, // JSON文字列として取得
        'options' => ['default' => '[]']
    ],
    'selected_skill_ids' => [
        'filter' => FILTER_UNSAFE_RAW, // JSON文字列として取得
        'options' => ['default' => '[]']
    ],
    'warrants_count' => [
        'filter' => FILTER_VALIDATE_INT, // 整数としてバリデート
        'options' => ['min_range' => 0, 'default' => 0]
    ],
    'user_name' => [
        'filter' => FILTER_UNSAFE_RAW, // まずは生で取得し、後でエスケープ
        'options' => ['default' => '']
    ],
    'memo' => [
        'filter' => FILTER_UNSAFE_RAW, // まずは生で取得し、後でエスケープ
        'options' => ['default' => '']
    ]
]);

$selected_hero_ids_json = $input['selected_hero_ids'];
$selected_skill_ids_json = $input['selected_skill_ids'];
$warrants_count = $input['warrants_count'];
$user_name = $input['user_name'];
$memo = $input['memo'];

// JSON文字列が正しくデコードできるか確認
$decoded_heroes = json_decode($selected_hero_ids_json);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("英雄IDのJSONデコードエラー: " . json_last_error_msg());
    $decoded_heroes = []; // エラーの場合は空配列として扱う
}
$valid_hero_ids = [];
if (is_array($decoded_heroes)) {
    foreach ($decoded_heroes as $hero_id) {
        if (is_string($hero_id) && preg_match('/^[a-z0-9]+$/', $hero_id)) {
            $valid_hero_ids[] = $hero_id;
        }
    }
}
$selected_hero_ids_json = json_encode($valid_hero_ids);

$decoded_skills = json_decode($selected_skill_ids_json);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("スキルIDのJSONデコードエラー: " . json_last_error_msg());
    $decoded_skills = []; // エラーの場合は空配列として扱う
}
$valid_skill_ids = [];
if (is_array($decoded_skills)) {
    foreach ($decoded_skills as $skill_id) {
        if (is_string($skill_id) && preg_match('/^[a-z0-9]+$/', $skill_id)) {
            $valid_skill_ids[] = $skill_id;
        }
    }
}
$selected_skill_ids_json = json_encode($valid_skill_ids);


// データベースへの挿入
try {
    $stmt = $pdo->prepare("
        INSERT INTO user_data (access_key, user_name, memo, owned_heroes_json, owned_skills_json, owned_warrants_count)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $access_key,
        $user_name,
        $memo,
        $selected_hero_ids_json,
        $selected_skill_ids_json,
        $warrants_count
    ]);

    // **ここから修正点**
    // 登録成功メッセージとアクセスキー、アクセス用URLを表示
    // 現在のプロトコル (http/https) とホスト名を取得
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    // 現在のスクリプトがあるディレクトリパスを取得 (例: /civmova/)
    $current_dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';
    
    // view.php への完全なURLを構築
    $access_url = $protocol . $host . $current_dir . 'view.php?key=' . urlencode($access_key);

    ?>
    <!DOCTYPE html>
    <html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>登録完了</title>
        <style>
            body { font-family: sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background-color: #f4f4f4; color: #333; text-align: center; padding: 20px; box-sizing: border-box; }
            .container { background-color: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 30px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); max-width: 600px; width: 100%; }
            h1 { color: #28a745; margin-bottom: 20px; }
            p { font-size: 1.1em; line-height: 1.6; margin-bottom: 15px; }
            .access-key-display {
                font-size: 1.4em; /* 少し小さくしてURLと区別 */
                font-weight: bold;
                color: #007bff;
                background-color: #e9f5ff;
                padding: 10px 15px;
                border-radius: 5px;
                display: inline-block;
                margin: 10px 0 20px;
                word-break: break-all;
            }
            .access-url-container {
                background-color: #f0f8ff; /* 別の背景色 */
                border: 1px dashed #a0d0ff;
                border-radius: 5px;
                padding: 15px;
                margin: 20px 0;
            }
            .access-url {
                font-size: 1.1em;
                word-break: break-all; /* 長いURLがはみ出さないように */
                color: #333;
                background-color: #f0f8ff;
                border: none;
                width: 100%;
                box-sizing: border-box;
                cursor: text; /* テキスト選択可能にする */
                padding: 0; /* paddingをなくす */
                line-height: 1.5; /* 行の高さを調整 */
            }
            .copy-button {
                background-color: #6c757d;
                color: white;
                padding: 10px 15px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 1em;
                margin-top: 10px;
                transition: background-color 0.2s;
            }
            .copy-button:hover {
                background-color: #5a6268;
            }
            .back-link {
                display: block;
                margin-top: 30px;
                color: #007bff;
                text-decoration: none;
                font-size: 1em;
            }
            .back-link:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>登録が完了しました！</h1>
            <p>あなたの登録内容にアクセスするためのキーとURLです。</p>
            <p>このキーまたは **URL全体を控えてください。**</p>
            <p>紛失すると、登録内容を見ることができなくなります。</p>

            <p>アクセスキー:</p>
            <div class="access-key-display"><?php echo htmlspecialchars($access_key); ?></div>
            
            <p>アクセスURL:</p>
            <div class="access-url-container">
                <input type="text" id="accessUrlDisplay" class="access-url"
                       value="<?php echo htmlspecialchars($access_url); ?>" readonly>
                <button class="copy-button" onclick="copyAccessUrl()">URLをコピー</button>
            </div>

            <a href="index.php" class="back-link">続けて登録する</a>
        </div>

        <script>
            function copyAccessUrl() {
                const accessUrlElement = document.getElementById('accessUrlDisplay');
                accessUrlElement.select(); // input要素のテキストを選択
                accessUrlElement.setSelectionRange(0, 99999); // モバイルデバイス対応

                // クリップボードAPIがサポートされているか確認
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(accessUrlElement.value)
                        .then(() => {
                            alert('アクセスURLがクリップボードにコピーされました！');
                        })
                        .catch(err => {
                            console.error('コピーに失敗しました:', err);
                            alert('URLのコピーに失敗しました。手動でコピーしてください。');
                        });
                } else {
                    // 古いブラウザの場合のフォールバック
                    try {
                        document.execCommand('copy');
                        alert('アクセスURLがクリップボードにコピーされました！');
                    } catch (err) {
                        console.error('古い方法でのコピーに失敗しました:', err);
                        alert('URLのコピーに失敗しました。手動でコピーしてください。');
                    }
                }
            }
        </script>
    </body>
    </html>
    <?php

} catch (PDOException $e) {
    // データベース挿入エラーが発生した場合
    error_log("データベースへの挿入エラー: " . $e->getMessage());
    // ユーザーには一般的なエラーメッセージを表示
    die("登録処理中にエラーが発生しました。時間をおいて再度お試しください。");
}
?>