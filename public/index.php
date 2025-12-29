<?php
require_once '../core/View.php';
require_once '../models/performer.php';

$performerModel = new Performer();
$performers = $performerModel->all();

View::render('content/home.php', [
    'title' => 'パフォーマー一覧',
    'performers' => $performers
]);