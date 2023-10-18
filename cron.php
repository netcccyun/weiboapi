<?php
if (substr(php_sapi_name(), 0, 3) != 'cli') {
    die("This Programe can only be run in CLI mode");
}
@chdir(dirname(__FILE__));
include("./includes/common.php");
define('IS_CRON', true);

$checktime = date("Y-m-d H:i:s",strtotime("-30 seconds"));
$list = $DB->getAll("SELECT * FROM weiboapi_account WHERE status=1 AND (checktime<'$checktime' OR checktime IS NULL) ORDER BY checktime ASC");
if(count($list) == 0) exit("[OK] 暂无需要更新的COOKIE\n");
foreach($list as $row){
    $nickname = $row['nickname'];
    $rescode = \lib\Logic::checkAccount($row['id']);
    if($rescode == 2){
        echo "[OK] {$nickname} 账号COOKIE更新成功\n";
    }elseif($rescode == 1){
        echo "[OK] {$nickname} 账号状态检测正常\n";
    }else{
        echo "[Warn] {$nickname} 账号状态已失效\n";
    }
}

if($conf['cache_time'] > 0 && $conf['cache_clean']!=date('Ymd')){
    $DB->exec("TRUNCATE TABLE `weiboapi_cache`");
    saveSetting('cache_clean', date('Ymd'));
    echo '[OK] 清空查询缓存成功!'."\n";
}

saveSetting('checktime', date("Y-m-d H:i:s"));

echo '[OK] '.date("Y-m-d H:i:s")."\n";
