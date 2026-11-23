<?php
/**
 * 后台图片上传接口（返回 JSON）
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '_auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('ok' => false, 'msg' => '非法请求'));
    exit;
}
// 上传接口允许用 POST 表单里的 token 校验（异步 FormData 场景）
$token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
if (!hash_equals(csrf_token(), $token)) {
    echo json_encode(array('ok' => false, 'msg' => '校验失败，请刷新页面'));
    exit;
}

$ret = handle_image_upload('file');
if ($ret['ok']) {
    admin_log('上传图片', $ret['url']);
}
echo json_encode($ret, JSON_UNESCAPED_UNICODE);
exit;
