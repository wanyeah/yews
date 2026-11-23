<?php
/**
 * 数据库连接（PDO 预处理，防 SQL 注入）
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php';

/**
 * 获取 PDO 单例；连接失败时返回 null（由调用方给出友好提示）
 * @return PDO|null
 */
function db()
{
    static $pdo = null;
    static $failed = false;
    if ($pdo !== null) return $pdo;
    if ($failed) return null;

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ));
        return $pdo;
    } catch (Exception $ex) {
        $failed = true;
        $GLOBALS['__DB_ERROR__'] = $ex->getMessage();
        return null;
    }
}

/** 数据库不可用时，输出友好提示并终止 */
function db_or_die()
{
    $pdo = db();
    if ($pdo === null) {
        http_response_code(500);
        echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8">'
           . '<title>数据库连接失败</title><style>body{font-family:"Microsoft YaHei",sans-serif;background:#f7f8fa;color:#222;'
           . 'display:flex;align-items:center;justify-content:center;height:100vh;margin:0}'
           . '.box{background:#fff;border:1px solid #eee;border-radius:12px;padding:32px 40px;max-width:520px;line-height:1.9;'
           . 'box-shadow:0 8px 30px rgba(0,0,0,.06)}code{background:#f2f3f5;padding:2px 6px;border-radius:4px;color:#c0392b}</style></head>'
           . '<body><div class="box"><h2>数据库连接失败</h2>'
           . '<p>请检查 <code>includes/config.php</code> 中的数据库配置，并确认已经导入 <code>install/iswanye.sql</code>。</p>'
           . '<p style="color:#888;font-size:13px">' . (isset($GLOBALS['__DB_ERROR__']) ? '错误信息：' . htmlspecialchars($GLOBALS['__DB_ERROR__'], ENT_QUOTES, 'UTF-8') : '') . '</p>'
           . '</div></body></html>';
        exit;
    }
    return $pdo;
}

/**
 * 执行预处理查询
 * @param string $sql
 * @param array  $params
 * @return PDOStatement
 */
function db_query($sql, $params = array())
{
    $pdo = db_or_die();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/** 取一行 */
function db_one($sql, $params = array())
{
    $row = db_query($sql, $params)->fetch();
    return $row === false ? null : $row;
}

/** 取全部 */
function db_all($sql, $params = array())
{
    return db_query($sql, $params)->fetchAll();
}

/** 取单个值 */
function db_val($sql, $params = array(), $default = null)
{
    $row = db_one($sql, $params);
    if ($row === null) return $default;
    $v = array_shift($row);
    return $v === null ? $default : $v;
}
