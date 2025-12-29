<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'サイトタイトル' ?></title>
    <link rel="icon" type="image/png" href="style/favicon.png">
    <link rel="apple-touch-icon" href="style/apple-touch-icon.png">
    <link rel="manifest" href="manifest.json">

    <!-- キャッシュ防止のため time() を付与 -->
    <link rel="stylesheet" href="style/style.css?v=<?= time(); ?>">

</head>
<body>
