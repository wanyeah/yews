<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions.php';
ensure_session();
admin_log('退出登录', '管理员退出后台');
admin_logout();
redirect(url('admin/index.php'));
