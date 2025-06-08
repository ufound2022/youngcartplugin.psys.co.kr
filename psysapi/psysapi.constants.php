<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
if (!defined('G5_USE_SHOP') || !G5_USE_SHOP) return;

if (!defined('PSYSAPI_USE_HTTPS')) {
    define('PSYSAPI_USE_HTTPS', true); // https 사용시 true
}

$psysapiIsTestPayment = isset($default['de_card_test']) && !empty($default['de_card_test']) ? $default['de_card_test'] : 0;

// Tables
define('PSYSAPI_SESSION', 'psysapi_session'); // 세션 임시 저장
define('PSYSAPI_PG_RESULT', 'psysapi_pg_result'); // 결제 결과
define('PSYSAPI_PG_VERIFY', 'psysapi_pg_verify'); // 결제 검증 결과
define('PSYSAPI_PG_CANCEL', 'psysapi_pg_cancel'); // 결제 취소 결과

// s: psysapi-plugin
define('PSYSAPI_SHOP_ORDER', 'psysapi_shop_order'); // 주문정보 임시 저장
// e: psysapi-plugin

// 피시스API 연동 PG
define('PSYSAPI_PG', 
[
    "PSYS_KW" => "키움페이", 
    "PSYS_AL" => "모빌페이", 
]);

// s: psysapi-plugin > 피시스 API
define('PSYS_PG', 
[
    "PSYS_KW" => "키움페이", 
    "PSYS_AL" => "모빌페이", 
]);
// e: psysapi-plugin > 피시스 API

$psysapiProtocol = PSYSAPI_USE_HTTPS === true ? "https://" : "http://";

$psysapiReturnUrl = PSYSAPI_USE_HTTPS === true ? str_replace("http://", "https://", G5_URL) : G5_URL;

if ($psysapiIsTestPayment == 0) { // 실결제
    $psysApiUrl = "{$psysapiProtocol}api.psys.co.kr"; ## 피시스 API
} else { // 테스트결제
    $psysApiUrl = "{$psysapiProtocol}sandbox.psys.co.kr"; ## 피시스 API
}

// 피시스API 결제 응답 url
define('PSYSAPI_RETURN_URL', $psysapiReturnUrl."/psysapi/pgresult.php");

// 피시스API PG 가입신청 링크
define('PSYSAPI_JOIN_URL', "https://api.psys.co.kr/join/apply");

// 피시스API 플러그인 경로
define('PSYSAPI_PATH', G5_PATH."/psysapi");

// 피시스API 플러그인 url
define('PSYSAPI_URL', "/psysapi");

// 피시스API 실결제 url
define('PSYSAPI_PAY_URL', "{$psysApiUrl}/pay/ready");

// 피시스API 테스트결제 url
define('PSYSAPI_TESTPAY_URL', "{$psysApiUrl}/pay/ready");

// 피시스API 수기결제 실결제 url
define('PSYSAPI_KEYIN_URL', "{$psysApiUrl}/keyin/payment");

// 피시스API 수기결제 테스트결제 url
define('PSYSAPI_TESTKEYIN_URL', "{$psysApiUrl}/keyin/payment");

// 피시스API 토큰 발행 url
define('PSYSAPI_TOKEN_URL', "{$psysApiUrl}/payAuth/token");

// 피시스API 결제 검증 url
define('PSYSAPI_VERIFY_URL', "{$psysApiUrl}/api/paycert");

// 피시스API 결제 취소 url
define('PSYSAPI_CANCEL_URL', "{$psysApiUrl}/api/cancel");

// 피시스API 전표 출력 url
define('PSYSAPI_RECEIPT_URL', "{$psysApiUrl}/api/receipt");

// 피시스API 결제 내역 url
define('PSYSAPI_SEARCH_URL', "{$psysApiUrl}/api/paysearch");

// s: psysapi-plugin > 피시스 API
define('PSYS_API_JOIN_URL', "https://www.psys.co.kr/board/list?bId=contact");
define('PSYS_PG', 
[
    "PSYS_KW" => "키움페이", 
    "PSYS_AL" => "모빌페이", 
]);

define('PSYS_API_URL', "{$psysApiUrl}/outvendnew/vendor/input");
define('PSYS_API_TEST_URL', "{$psysApiUrl}/outvendnew/vendor/input");

define('PSYS_RETURN_URL', $psysapiReturnUrl."/psysapi/psys_pgresult.php"); // 피시스API 결제 응답 url
// e: psysapi-plugin > 피시스 API