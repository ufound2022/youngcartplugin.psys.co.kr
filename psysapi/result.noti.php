<?php 
#error_reporting(E_ALL); 
#ini_set("display_errors", 1); 

include_once('../shop/_common.php');
require_once G5_PATH."/psysapi/psysapi.lib.php";
require_once G5_PATH."/psysapi/psysapi.migrate.php";

// 결제 결과 통지 수신 후 처리
#exit;

$psysapi = json_decode(file_get_contents('php://input'), true);

# 정기(반복) 결제 통지 수신(S) 
/*
([통지]수신)
{
    "CP_ID":"CTS18304",
    "API_ID":"sandbox_7fLAXEQa1L",
    "ORDERNO":"SRS20250508093947857860",
    "VENDOR_ORDER_NO":null,
    "AMOUNT":"200",
    "TID":"cTS25050809394695923",
    "USERID":"",
    "BUYERNAME":"\ucd5c\uace0\uad00\ub9ac\uc790",
    "BUYEREMAIL":"",
    "PRODUCTCODE":"2025050809",
    "PRODUCTNAME":"\uac10",
    "ACCEPT_DATE":"20250508093947",
    "ACCEPT_NO":"",
    "CARDCODE":"",
    "CARDNAME":"IBK\ube44\uc528\uccb4\ud06c",
    "CARDNO":"",
    "QUOTA":"",
    "ETC1":"",
    "ETC2":"",
    "ETC3":"",
    "ETC4":"",
    "ETC5":"",
    "ETC7":null,
    "PAY_METHOD":"CARD",
    "SMS_INPUT":"",
    "BILLKEY":"4degzpumk8owg00o33ij",
    "GENDATE":"2025-05-08 09:35:39",
    "BUYERADDRESS":"",
    "BUYERPHONE":"01076764624",
    "RESULTCODE":"0000",
    "RESULTMSG":"\uc0c1\uacf5",
    "RESERVE_ID":"8c96vu3u8ls84c8cocg02mf5lkrk",
    "RESERVE_ORDERNO":"2025050809341263",
    "PAY_CNT":"2",
    "LAST_PAY_CNT":"5",
    "TRY_CNT":"1",
    "NEXT_PAY_DATE":"2025-06-10"
}
*/
# 정기(반복) 결제 통지 수신(E) 

# (1) select * from psysapi_pg_subscribe_userlist where billkey='{RESERVE_ORDERNO}' > 기존 주문번호 검색
# (2) 해당 주문번호 조회된 값으로 거래내역 입력 > 주문번호는 생성한다.

@psysapi_payment_log("[통지]스타트", ' : OK', 3);
@psysapi_payment_log("[통지]수신", json_encode($psysapi), 3);

$params_sb['ORDERNO'] = isset($psysapi['ORDERNO']) && !empty($psysapi['ORDERNO']) ? $psysapi['ORDERNO'] : ''; 
$params_sb['AMOUNT'] = isset($psysapi['AMOUNT']) && !empty($psysapi['AMOUNT']) ? $psysapi['AMOUNT'] : '';
$params_sb['USERID'] = isset($psysapi['USERID']) && !empty($psysapi['USERID']) ? $psysapi['USERID'] : ''; 
$params_sb['BUYERNAME'] = isset($psysapi['BUYERNAME']) && !empty($psysapi['BUYERNAME']) ? $psysapi['BUYERNAME'] : ''; 
$params_sb['BUYEREMAIL'] = isset($psysapi['BUYEREMAIL']) && !empty($psysapi['BUYEREMAIL']) ? $psysapi['BUYEREMAIL'] : ''; 
$params_sb['PRODUCTNAME'] = isset($psysapi['PRODUCTNAME']) && !empty($psysapi['PRODUCTNAME']) ? $psysapi['PRODUCTNAME'] : '';  
$params_sb['PRODUCTCODE'] = isset($psysapi['PRODUCTCODE']) && !empty($psysapi['PRODUCTCODE']) ? $psysapi['PRODUCTCODE'] : ''; 
$params_sb['RESERVE_ORDERNO'] = isset($psysapi['RESERVE_ORDERNO']) && !empty($psysapi['RESERVE_ORDERNO']) ? $psysapi['RESERVE_ORDERNO'] : '';
$params_sb['PAY_CNT'] = isset($psysapi['PAY_CNT']) && !empty($psysapi['PAY_CNT']) ? $psysapi['PAY_CNT'] : '';
$params_sb['TID'] = isset($psysapi['TID']) && !empty($psysapi['TID']) ? $psysapi['TID'] : '';
$params_sb['ACCEPT_NO'] = isset($psysapi['ACCEPT_NO']) && !empty($psysapi['ACCEPT_NO']) ? $psysapi['ACCEPT_NO'] : '';
$params_sb['ACCEPT_DATE'] = isset($psysapi['ACCEPT_DATE']) && !empty($psysapi['ACCEPT_DATE']) ? $psysapi['ACCEPT_DATE'] : '';
$params_sb['RESERVE_ID'] = isset($psysapi['RESERVE_ID']) && !empty($psysapi['RESERVE_ID']) ? $psysapi['RESERVE_ID'] : '';
$params_sb['CARDNAME'] = isset($psysapi['CARDNAME']) && !empty($psysapi['CARDNAME']) ? $psysapi['CARDNAME'] : '';

$params_sb['BILLKEY'] = isset($psysapi['BILLKEY']) && !empty($psysapi['BILLKEY']) ? $psysapi['BILLKEY'] : '';
$params_sb['GENDATE'] = isset($psysapi['GENDATE']) && !empty($psysapi['GENDATE']) ? $psysapi['GENDATE'] : '';

$params_sb['RESULTCODE'] = isset($psysapi['RESULTCODE']) && !empty($psysapi['RESULTCODE']) ? $psysapi['RESULTCODE'] : '';
$params_sb['RESULTMSG'] = isset($psysapi['RESULTMSG']) && !empty($psysapi['RESULTMSG']) ? $psysapi['RESULTMSG'] : '';
$params_sb['NEXT_PAY_DATE'] = isset($psysapi['NEXT_PAY_DATE']) && !empty($psysapi['NEXT_PAY_DATE']) ? $psysapi['NEXT_PAY_DATE'] : '';

$params_sb['ETC1'] = isset($psysapi['ETC1']) && !empty($psysapi['ETC1']) ? $psysapi['ETC1'] : '';
$params_sb['ETC2'] = isset($psysapi['ETC2']) && !empty($psysapi['ETC2']) ? $psysapi['ETC2'] : '';
$params_sb['ETC3'] = isset($psysapi['ETC3']) && !empty($psysapi['ETC3']) ? $psysapi['ETC3'] : '';
$params_sb['ETC4'] = isset($psysapi['ETC4']) && !empty($psysapi['ETC4']) ? $psysapi['ETC4'] : '';
$params_sb['ETC5'] = isset($psysapi['ETC5']) && !empty($psysapi['ETC5']) ? $psysapi['ETC5'] : '';

// s: psysapi-plugin > 피시스 API
# 피시스 API 필요인자값 
$params_sb['VENDOR_ORDER_NO'] = isset($psysapi['VENDOR_ORDER_NO']) && !empty($psysapi['VENDOR_ORDER_NO']) ? $psysapi['VENDOR_ORDER_NO'] : ''; 
if(!empty($params_sb['ORDERNO'])) { 
    $psysapi['ORDERNO'] = $params_sb['VENDOR_ORDER_NO'];
}
// e: psysapi-plugin > 피시스 API

@psysapi_payment_log("[통지]결제결과 저장 성공1", $psysapi, 3);

$psysapi['ACCEPT_NO'] = isset($psysapi['ACCEPT_NO']) && !empty($psysapi['ACCEPT_NO']) ? $psysapi['ACCEPT_NO'] : '';
$psysapi['TID'] = isset($psysapi['TID']) && !empty($psysapi['TID']) ? $psysapi['TID'] : '';
$psysapi['ORDERNO'] = isset($psysapi['ORDERNO']) && !empty($psysapi['ORDERNO']) ? $psysapi['ORDERNO'] : '';

$resultMode = null;

if($psysapi['ACCEPT_NO'] == "00000000" || $psysapi['PAY_METHOD'] == "VACCOUNT") { 
    $psysapi['ACCEPT_NO'] = "11111111";
}


if(!empty($psysapi['ACCEPT_NO']) && !empty($psysapi['TID']) && !empty($psysapi['ORDERNO'])) {
    $pgResult = sql_fetch(" SELECT * FROM ".PSYSAPI_PG_RESULT." WHERE ORDERNO='{$psysapi['ORDERNO']}' ORDER BY `id` DESC LIMIT 1");
    $payStatus = isset($pgResult['pay_status']) && $pgResult['pay_status']>=0 ? $pgResult['pay_status'] : '';

    $psysapi['RESULTCODE'] = '0000';
    $psysapi['RESULTMSG'] = '성공';
    $psysapi['ACCEPTDATE'] = $psysapi['ACCEPT_DATE'];
    $psysapi['ACCEPTNO'] = $psysapi['ACCEPT_NO'];
    unset($psysapi['ACCEPT_DATE']);
    unset($psysapi['ACCEPT_NO']);

    if ($payStatus == '') {
        // insert
        $columnStr = implode(",", $pgResultColumns);
        $values = [];
        foreach ($pgResultColumns as $val) {
            $values[$val] = "''";
        }
        foreach ($psysapi as $key => $val) {
            if (array_key_exists($key, $values)) {
                $values[$key] = "'{$val}'";
            }
        }
        $values['PGNAME'] = "'{$default['de_pg_service']}'"; // pg사 추가
        if (isset($psysapi['ETC3']) && !empty($psysapi['ETC3'])) { // 응답전문에는 pay_type이 없으므로 pay_type을 etc3에 추가해 보내고 받음
            $values['pay_type'] = "'{$psysapi['ETC3']}'";
        }
        $values['pay_status'] = 1;
        $valueStr = implode(",", $values);

        $sql = " INSERT INTO ".PSYSAPI_PG_RESULT." ({$columnStr}) VALUES ({$valueStr}) ";
        $res = sql_query($sql, false);
        if ($res) {
            @psysapi_payment_log("[통지]결제결과 저장 성공1", $sql, 3);
            $resultMode = 'insert';
        } else {
            @psysapi_payment_log("[통지]결제결과 저장 실패1", $sql, 3);
        }
    } else if ($payStatus != 1) {
        // update
        $set = [];
        foreach ($psysapi as $key => $val) {
            if (in_array($key, $pgResultColumns)) {
                $set[$key] = "{$key}='{$val}'";
            }
        }
        $set['PGNAME'] = "PGNAME='{$default['de_pg_service']}'"; // pg사 추가
        if (isset($psysapi['ETC3']) && !empty($psysapi['ETC3'])) { // 응답전문에는 pay_type이 없으므로 pay_type을 etc3에 추가해 보내고 받음
            $set['pay_type'] = "pay_type='{$psysapi['ETC3']}'";
        }
        $set['pay_status'] = "pay_status=1";
        $setStr = implode(",", $set);

        $sql = "UPDATE ".PSYSAPI_PG_RESULT." SET {$setStr} WHERE ORDERNO='{$psysapi['ORDERNO']}'";

        $res = sql_query($sql, false);
        if ($res) {
            @psysapi_payment_log("[통지]결제결과 저장 성공2", $sql, 3);
            $resultMode = 'update';
        } else {
            @psysapi_payment_log("[통지]결제결과 저장 실패2", $sql, 3);
        }
    }

    if ($resultMode == 'insert' || $resultMode == 'update') {
        if (isset($psysapi['ETC3']) && !empty($psysapi['ETC3'])) {
            $psysapiApi = psysapi_get_api_account_info($default, $psysapi['ETC3']);
        } else {
            
            // s: psysapi-plugin > 피시스 API
            if(!empty($params_sb['VENDOR_ORDER_NO'])) { 
                $psysapiApi = psys_get_api_account_info_paytype($default, 3);
            } else { 
                $psysapiApi = psysapi_get_api_account_info($default, 3);
            }
            // e: psysapi-plugin > 피시스 API
        }

        // 결제 검증
        $headers = array(
            'Content-Type: application/json; charset=utf-8',
        );

        $token_url = PSYSAPI_TOKEN_URL;

        $request_data = array(
            'pay2_id' => $psysapiApi['api_id'],
            'pay2_key'=> $psysapiApi['api_key'],
        );

        $request_data = json_encode($request_data, JSON_UNESCAPED_UNICODE);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $token_url);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $resultJson = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($resultJson, true);

        if($result['RTN_CD'] == '0000') {

            $paycert_url = PSYSAPI_VERIFY_URL;

            $headers = array(
                'content-type: application/json; charset=utf-8',
                'TOKEN: ' . $result['TOKEN'],
            );

            $request_data = array(
                'tid' => $psysapi['TID'],
            );

            $request_data = json_encode($request_data, JSON_UNESCAPED_UNICODE);

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $paycert_url);
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            curl_close($ch);
            $verify = json_decode($response, true);

            $pgVerify = sql_fetch(" SELECT * FROM ".PSYSAPI_PG_VERIFY." WHERE ORDERNO='{$psysapi['ORDERNO']}' ORDER BY `id` DESC LIMIT 1");
            $verifyId = isset($pgVerify['id']) && !empty($pgVerify['id']) ? $pgVerify['id'] : null;

            // 결제 검증 결과 테이블에 저장
            if (is_null($verifyId)) {
                // column 쿼리 처리
                $columnStr = implode(",", $pgVerifyColumns);

                // values 쿼리 처리
                $values = [];
                foreach ($pgVerifyColumns as $val) {
                    $values[$val] = "''";
                }
                foreach ($verify as $key => $val) {
                    if (array_key_exists($key, $values)) {
                        $values[$key] = "'{$val}'";
                    }
                }
                $valueStr = implode(",", $values);

                $sql = " INSERT INTO ".PSYSAPI_PG_VERIFY." ({$columnStr}) VALUES ({$valueStr}) ";
                $res = sql_query($sql, false);
            } else {
                $set = [];
                foreach ($verify as $key => $val) {
                    if (in_array($key, $pgVerifyColumns)) {
                        $set[$key] = "{$key}='{$val}'";
                    }
                }
                $setStr = implode(",", $set);
                $sql = "UPDATE ".PSYSAPI_PG_VERIFY." SET {$setStr} WHERE ORDERNO='{$psysapi['ORDERNO']}'";
                $res = sql_query($sql, false);
            }

            if ($res) {
                @psysapi_payment_log("[통지]결제검증결과 저장 성공3", $sql, 3);
            } else {
                @psysapi_payment_log("[통지]결제검증결과 저장 실패3", $sql, 3);
            }

            if($verify['RESULTCODE'] == '0000') {
                
                // s: psysapi-plugin
                $exists_sql = "select od_id from {$g5['g5_shop_order_table']} where od_id = '{$psysapi['ORDERNO']}'";
                $exists_order = sql_fetch($exists_sql);
                if (!isset($exists_order['od_id']))
                {
                    $sql = "select * from ".PSYSAPI_SHOP_ORDER." where od_id = '{$psysapi['ORDERNO']}'";
                    $order_data = sql_fetch($sql);
                    if ($order_data)
                    {
                        $sql = "SELECT * FROM ".PSYSAPI_PG_RESULT." WHERE ORDERNO='{$psysapi['ORDERNO']}'";
                        $pg_data = sql_fetch($sql);
                        
                        $i_price = $order_data['od_price'] + $order_data['od_send_cost'] + $order_data['od_send_cost2'] - $order_data['od_temp_point'] - $order_data['od_send_coupon'];
                        $od_receipt_time = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3 \\4:\\5:\\6", $pg_data['ACCEPTDATE']);
                        $od_misu = $i_price - $pg_data['AMOUNT'];
                        if ($od_misu == 0)
                        {
                            $od_status = '입금';
                        }
                        else
                        {
                            $od_status = '주문';
                        }
                        
                        // 복합과세 금액
                        $od_tax_mny = round($i_price / 1.1);
                        $od_vat_mny = $i_price - $od_tax_mny;
                        $od_free_mny = 0;
                        if ($default['de_tax_flag_use'])
                        {
                            $od_tax_mny = $order_data['comm_tax_mny'] ? (int) $order_data['comm_tax_mny'] : 0;
                            $od_vat_mny = $order_data['comm_vat_mny'] ? (int) $order_data['comm_vat_mny'] : 0;
                            $od_free_mny = $order_data['comm_free_mny'] ? (int) $order_data['comm_free_mny'] : 0;
                        }
                        
                        $sql = " insert {$g5['g5_shop_order_table']}
                                    set od_id               = '{$order_data['od_id']}',
                                        mb_id               = '{$order_data['mb_id']}',
                                        od_name             = '{$order_data['od_name']}',
                                        od_email            = '{$order_data['od_email']}',
                                        od_tel              = '{$order_data['od_tel']}',
                                        od_hp               = '{$order_data['od_hp']}',
                                        od_zip1             = '{$order_data['od_zip1']}',
                                        od_zip2             = '{$order_data['od_zip2']}',
                                        od_addr1            = '{$order_data['od_addr1']}',
                                        od_addr2            = '{$order_data['od_addr2']}',
                                        od_addr3            = '{$order_data['od_addr3']}',
                                        od_addr_jibeon      = '{$order_data['od_addr_jibeon']}',
                                        od_deposit_name     = '{$order_data['od_deposit_name']}',
                                        od_b_name           = '{$order_data['od_b_name']}',
                                        od_b_tel            = '{$order_data['od_b_tel']}',
                                        od_b_hp             = '{$order_data['od_b_hp']}',
                                        od_b_zip1           = '{$order_data['od_b_zip1']}',
                                        od_b_zip2           = '{$order_data['od_b_zip2']}',
                                        od_b_addr1          = '{$order_data['od_b_addr1']}',
                                        od_b_addr2          = '{$order_data['od_b_addr2']}',
                                        od_b_addr3          = '{$order_data['od_b_addr3']}',
                                        od_b_addr_jibeon    = '{$order_data['od_b_addr_jibeon']}',
                                        od_memo             = '{$order_data['od_memo']}',
                                        od_cart_count       = '{$order_data['od_cart_count']}',
                                        od_cart_price       = '{$order_data['od_cart_price']}',
                                        od_cart_coupon      = '{$order_data['od_cart_coupon']}',
                                        od_send_cost        = '{$order_data['od_send_cost']}',
                                        od_send_cost2       = '{$order_data['od_send_cost2']}',
                                        od_send_coupon      = '{$order_data['od_send_coupon']}',
                                        od_receipt_price    = '{$pg_data['AMOUNT']}',
                                        od_cancel_price     = '{$order_data['od_cancel_price']}',
                                        od_receipt_point    = '{$order_data['od_temp_point']}',
                                        od_refund_price     = '{$order_data['od_refund_price']}',
                                        od_bank_account     = '{$pg_data['CARDCODE']}',
                                        od_receipt_time     = '{$od_receipt_time}',
                                        od_coupon           = '{$order_data['od_coupon']}',
                                        od_misu             = '{$od_misu}',
                                        od_shop_memo        = '{$order_data['od_shop_memo']}',
                                        od_mod_history      = '{$order_data['od_mod_history']}',
                                        od_status           = '{$od_status}',
                                        od_hope_date        = '{$order_data['od_hope_date']}',
                                        od_settle_case      = '{$order_data['od_settle_case']}',
                                        od_other_pay_type   = '{$order_data['od_other_pay_type']}',
                                        od_test             = '{$order_data['od_test']}',
                                        od_mobile           = '{$order_data['od_mobile']}',
                                        od_pg               = '{$pg_data['PGNAME']}',
                                        od_tno              = '{$pg_data['TID']}',
                                        od_app_no           = '{$pg_data['ACCEPTNO']}',
                                        od_escrow           = '{$order_data['od_escrow']}',
                                        od_casseqno         = '{$order_data['od_casseqno']}',
                                        od_tax_flag         = '{$order_data['od_tax_flag']}',
                                        od_tax_mny          = '{$od_tax_mny}',
                                        od_vat_mny          = '{$od_vat_mny}',
                                        od_free_mny         = '{$od_free_mny}',
                                        od_delivery_company = '{$order_data['od_delivery_company']}',
                                        od_invoice          = '{$order_data['od_invoice']}',
                                        od_invoice_time     = '{$order_data['od_invoice_time']}',
                                        od_cash             = '{$order_data['od_cash']}',
                                        od_cash_no          = '{$order_data['od_cash_no']}',
                                        od_cash_info        = '{$order_data['od_cash_info']}',
                                        od_time             = '".G5_TIME_YMDHIS."',
                                        od_pwd              = '{$order_data['od_pwd']}',
                                        od_ip               = '{$order_data['od_ip']}'
                                        ";
                        $result = sql_query($sql, false);
                        @psysapi_payment_log("[통지]주문정보 저장", $sql, 3);

                        # s: psysapi-plugin > 장바구니 업데이트 > v1.2 > 240321
                        if(!empty($psysapi['ETC5'])) { 
                            $cart_status      = '입금';

                            $sql_cart = "update {$g5['g5_shop_cart_table']}
                                    set od_id = '{$order_data['od_id']}',
                                        ct_status = '{$cart_status}'
                                    where od_id = '{$psysapi['ETC5']}'
                                    and ct_select = '1' ";
                            $result_cart = sql_query($sql_cart, false);
                            @psysapi_payment_log("[통지]카트정보 업데이트", $sql_cart, 3);

                        }
                        # e: psysapi-plugin > 장바구니 업데이트 > v1.2 > 240321
                                                
                    }
                } else {          
                    // s: psysapi-plugin > 240412
                    // 이곳에서 가상계좌라면 업데이트 처리한다. > Table name : g5_shop_order (S)
                    // 무통장 관련 체크 > 피시스API 테이블 부터 조회
                    # 1 : psysapi_pg_result.pay_status : 1 처리
                    # 2 : g5_shop_order.od_status : 입금처리

                    /*
                    {
                        "RESULTCODE":"0000",
                        "RESULTMSG":"\uc131\uacf5",
                        "ORDERNO":"2024041117142479",
                        "AMOUNT":"1050",
                        "BUYERNAME":"\ucd5c\uace0\uad00\ub9ac\uc790",
                        "BUYEREMAIL":"sales@psysapiments.com",
                        "PRODUCTNAME":"\ubca0\uc774\uc2a4 \ucee4\ubc84",
                        "PRODUCTCODE":"145000",
                        "PAYMETHOD":"VACCOUNT",
                        "BUYERID":"admin",
                        "ACCEPTNO":"",
                        "ACCEPTDATE":"20240411171624",
                        "TID":"XEH24041117161416188",
                        "CANCELDATE":"",
                        "CANCELMSG":"",
                        "ACCOUNTNO":"08201108797596",
                        "RECEIVERNAME":"(\uc8fc)\uc774\ub85c\ud640\ub529\uc2a4",
                        "DEPOSITENDDATE":"20240418235959",
                        "CARDNAME":"\uc911\uc18c\uae30\uc5c5\uc740\ud589",
                        "CARDCODE":"03"
                    }
                    */

                    if(!empty($psysapi['ORDERNO'])) { 
                        $sql_shop_order = "update {$g5['g5_shop_order_table']} 
                                set od_receipt_price='{$psysapi['AMOUNT']}', od_status = '입금', od_tno='{$pg_data['TID']}', od_misu=0, od_receipt_time=now() 
                                where od_id = '{$psysapi['ORDERNO']}'
                                limit 1 ";
                        $result_shop_order = sql_query($sql_shop_order, false);

                        //set od_receipt_price='{$psysapi['AMOUNT']}', od_status = '입금', od_receipt_time=now() 
                        $sql_shop_cart = "update {$g5['g5_shop_cart_table']}         
                                set ct_status = '입금' 
                                where od_id = '{$psysapi['ORDERNO']}'
                                ";
                        $result_shop_cart = sql_query($sql_shop_cart, false);

                    }

                    @psysapi_payment_log("[통지]가상계좌 결제완료 처리 Order SQL :  ----------> :", $sql_shop_order, 3);
                    @psysapi_payment_log("[통지]가상계좌 결제완료 처리 Cart SQL :  ----------> :", $sql_shop_cart, 3);

                    @psysapi_payment_log("[통지]수신 res ----------> :", $response, 3);
                    // 이곳에서 가상계좌라면 업데이트 처리한다. > Table name : g5_shop_order (E)
                    // e: psysapi-plugin
                }
                
                // e: psysapi-plugin
                
                // 결제 검증 성공
                @psysapi_payment_log("[통지]결제검증 성공4", $response, 3);
                echo '<html>
                        <body>
                        <RESULT>SUCCESS</RESULT>
                        </body>
                      </html>';
            } else {
                // 결제 검증 실패시 결제 취소 처리
                @psysapi_payment_log("[통지]결제검증 실패4", $response, 3);
            
                $ret = psysapi_cancel_payment($psysapiApi['api_id'], $psysapiApi['api_key'], $psysapi['TID'], $psysapi['CARDCODE'], $psysapi['ACCOUNTNO'], $psysapi['RECEIVERNAME']);

                if ($ret['status'] === true) {
                    @psysapi_payment_log("[통지]결제취소 성공5", $ret['data'], 3);
                    $payStatusRes = sql_query("UPDATE ".PSYSAPI_PG_RESULT." SET pay_status=2 WHERE ORDERNO='{$psysapi['ORDERNO']}'", false);
                } else {
                    @psysapi_payment_log("[통지]결제취소 실패5", $ret['data'], 3);
                }

                $cancelArr = json_decode($ret['data'], true);

                $sql = " INSERT INTO ".PSYSAPI_PG_CANCEL." (orderno, cancel_tid, cancel_code, cancel_msg, cancel_date, cancel_amt) VALUES ('{$psysapi['ORDERNO']}', '{$cancelArr['cancel_tid']}', '{$cancelArr['cancel_code']}', '{$cancelArr['cancel_msg']}', '{$cancelArr['cancel_date']}', '{$cancelArr['cancel_amt']}') ";
                $res = sql_query($sql, false);
                if ($res) {
                    @psysapi_payment_log("[통지]결제취소결과 저장 성공6", $sql, 3);
                } else {
                    @psysapi_payment_log("[통지]결제취소결과 저장 실패6", $sql, 3);
                }
            }
        } else {
            // 결제 검증 토큰 발행 실패시 결제 취소 처리
            @psysapi_payment_log("[통지]결제 검증 토큰 발행 실패7", $resultJson, 3);
            
            $ret = psysapi_cancel_payment($psysapiApi['api_id'], $psysapiApi['api_key'], $psysapi['TID'], $psysapi['CARDCODE'], $psysapi['ACCOUNTNO'], $psysapi['RECEIVERNAME']);

            if ($ret['status'] === true) {
                @psysapi_payment_log("[통지]결제취소 성공7", $ret['data'], 3);
                $payStatusRes = sql_query("UPDATE ".PSYSAPI_PG_RESULT." SET pay_status=2 WHERE ORDERNO='{$psysapi['ORDERNO']}'", false);
            } else {
                @psysapi_payment_log("[통지]결제취소 실패7", $ret['data'], 3);
            }

            $cancelArr = json_decode($ret['data'], true);

            $sql = " INSERT INTO ".PSYSAPI_PG_CANCEL." (orderno, cancel_tid, cancel_code, cancel_msg, cancel_date, cancel_amt) VALUES ('{$psysapi['ORDERNO']}', '{$cancelArr['cancel_tid']}', '{$cancelArr['cancel_code']}', '{$cancelArr['cancel_msg']}', '{$cancelArr['cancel_date']}', '{$cancelArr['cancel_amt']}') ";
            $res = sql_query($sql, false);
            if ($res) {
                @psysapi_payment_log("[통지]결제취소결과 저장 성공8", $sql, 3);
            } else {
                @psysapi_payment_log("[통지]결제취소결과 저장 실패8", $sql, 3);
            }
        }
    }
}