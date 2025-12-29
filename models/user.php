<?php
require_once __DIR__ . '../core/Model.php';

class User extends Model {
    protected $table = "users"; // 対応するテーブル名
}
