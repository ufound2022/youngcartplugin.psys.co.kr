<?php
include_once('../shop/_common.php');

// PG 결제 결과 영카트 변수에 매칭

require_once G5_PATH."/psysapi/psysapi.lib.php";

// $psysapiApi = psysapi_get_api_account_info($default, 3);

$pay_type = '';
$tno = '';
$amount = '';
$app_time = '';
$app_no = '';
$bank_name = $bankname = '';
$depositor = '';
$account = '';
$commid = '';
$mobile_no = '';
$card_name = '';
$escw_yn = '';
$od_other_pay_type = '';
$cash_yn = '';
$cash_authno = '';
$cash_tr_code = '';

$postOrderno = isset($_POST['ORDERNO']) ? clean_xss_tags($_POST['ORDERNO'], 1, 1) : '';

// s: psysapi-plugin
if (!$postOrderno && isset($_POST['od_id']))
{
    $postOrderno = isset($_POST['od_id']) ? clean_xss_tags($_POST['od_id'], 1, 1) : '';
}
// e: psysapi-plugin

$sql = " select * from ".PSYSAPI_PG_RESULT." where ORDERNO='{$postOrderno}' ";
$psysapiPgResult = sql_fetch($sql);

if ($psysapiPgResult) {
    $pay_type = $psysapiPgResult['PAYMETHOD']; // paymethod

    $tno = $psysapiPgResult['TID']; // 거래 고유 번호
    $amount = $psysapiPgResult['AMOUNT']; // 실제 거래 금액
    $app_time = $psysapiPgResult['ACCEPTDATE']; // 승인 시간
    $app_no = $psysapiPgResult['ACCEPTNO']; // 승인 번호
    $bank_name = $bankname = $psysapiPgResult['CARDNAME']; // 은행명
    $depositor = $psysapiPgResult['RECEIVERNAME']; // 입금할 계좌 예금주
    $account = $psysapiPgResult['ACCOUNTNO']; // 입금할 계좌 번호
    $commid = ''; // 통신사 코드
    $mobile_no = ''; // 휴대폰 번호
    $card_name = $psysapiPgResult['CARDCODE']; // 은행코드
    $escw_yn = ''; // 에스크로 여부
    $od_other_pay_type = ''; // 간편결제유형

    $cash_yn = ''; // 현금영수증 등록여부
    $cash_authno = ''; // 현금 영수증 승인 번호
    $cash_tr_code = ''; // 현금영수증 등록구분

    $reserve_id = $psysapiPgResult['RESERVE_ID'];

    // s: psysapi-plugin > 피시스 API
    $pay_type = $psysapiPgResult['pay_type'];
    // e: psysapi-plugin > 피시스 API
}
