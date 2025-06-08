<?php exit;
$sub_menu = '990100';
include_once('./_common.php');
require_once G5_PATH."/psysapi/psysapi.lib.php";

check_demo();

auth_check_menu($auth, $sub_menu, "w");

check_admin_token();

$check_sanitize_keys = array(
    'de_pg_service',                		        //결제대행사
);

foreach( $check_sanitize_keys as $key ){
    $$key = isset($_POST[$key]) ? clean_xss_tags($_POST[$key], 1, 1) : '';
}

$updateSet = [];

foreach ($check_sanitize_keys as $column) {
    array_push($updateSet, "{$column}='{$$column}'");
}

$updateSetSql = implode(",", $updateSet);

$sql = " UPDATE {$g5['g5_shop_default_table']} SET {$updateSetSql} ";

sql_query($sql);

goto_url("./psysapi.pgconfig.php");
