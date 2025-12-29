<?php

class View {
    public static function render($contentFile, $data = []) {
        extract($data); // 変数を展開できるようにする

        // ファイルパス（public/include/）などの位置に合わせて変更
        $header = __DIR__ . '/../public/include/header.php';
        $footer = __DIR__ . '/../public/include/footer.php';
        $content = __DIR__ . '/../public/' . $contentFile;

        // 出力開始
        include $header;
        include $content;
        include $footer;
    }
}
