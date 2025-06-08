<?php
include_once('./_common.php');
include_once(G5_PATH."/psysapi/psysapi.lib.php");

$orderno = $od_id;

// $psysapiPgResultSql = "SELECT * FROM ".PSYSAPI_PG_RESULT." WHERE ORDERNO='$orderno' ORDER BY ACCEPTDATE DESC LIMIT 1";
$psysapiPgResultSql = "SELECT CPR.*, (SELECT PAYMETHOD FROM ".PSYSAPI_PG_VERIFY." WHERE ORDERNO=CPR.ORDERNO) AS pay_method FROM ".PSYSAPI_PG_RESULT." AS CPR WHERE CPR.ORDERNO='$orderno' ORDER BY CPR.ACCEPTDATE DESC LIMIT 1";

$psysapiPgResultRes = sql_fetch($psysapiPgResultSql);
if (isset($psysapiPgResultRes['PGNAME']) && !empty($psysapiPgResultRes['PGNAME'])) {
    
    if(strpos($psysapiPgResultRes['PGNAME'], "PSYS") !== false) {
            $psysapiApi = psys_get_api_account_info_paytype($default, $psysapiPgResultRes['pay_type']);
    }

    // s: psysapi-plugin
    $psysapi_cancel_count = count($psysapi_order_status_info);
    if($psysapi_full_cancel == 1 || !$psysapi_cancel_count) { 
        $psysapi_cancel_count = 1;
    }
    // e: psysapi-plugin

    for($c = 0; $c < $psysapi_cancel_count; $c++) { // i : psysapi-plugin

        // s: psysapi-plugin
        if($psysapi_full_cancel != "1") {
            # 1이 아니면 > 부분취소
            $PSYSAPI_CANCEL_AMOUNT = $psysapi_order_status_info[$c]['ct_price'];   
        }
        // e: psysapi-plugin

        $api_id = $psysapiApi['api_id'];
        $api_key = $psysapiApi['api_key'];

        $tid = $psysapiPgResultRes['TID'];
        $bank = $psysapiPgResultRes['CARDNAME'];
        $accountno = $psysapiPgResultRes['ACCOUNTNO'];
        $accountname = $psysapiPgResultRes['RECEIVERNAME'];
        // $amount = $psysapiPgResultRes['AMOUNT'];

        // s : psysapi-plugin
        if(!empty($PSYSAPI_CANCEL_AMOUNT) && $PSYSAPI_CANCEL_AMOUNT > 0) { 
            $amount = $PSYSAPI_CANCEL_AMOUNT;
        }
        // e : psysapi-plugin

        $tokenheaders = array(); 
        array_push($tokenheaders, "content-type: application/json; charset=utf-8");

        $token_url = PSYSAPI_TOKEN_URL;

        $token_request_data = array(
            'pay2_id' => $api_id,
            'pay2_key'=> $api_key,
        );

        $req_json = json_encode($token_request_data, TRUE);

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $token_url);
        curl_setopt($ch,CURLOPT_POST, false);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $req_json);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT ,3);
        curl_setopt($ch,CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $tokenheaders);
        $RES_STR = curl_exec($ch);
        curl_close($ch);
        $RES_ARR = json_decode($RES_STR,TRUE);
        
        if($RES_ARR['RTN_CD'] == '0000'){
            $headers = array(); 
            array_push($headers, "content-type: application/json; charset=utf-8");
            array_push($headers, "TOKEN: ".$RES_ARR['TOKEN']);
            array_push($headers, "ApiKey: ".$api_key);

            $psysapi_url = PSYSAPI_CANCEL_URL;

            $request_data_array = array(
                'tid' => $tid,
                'reason' => '고객취소',
                'bank' => $bank,
                'account_no' => $accountno,
                'account_name' => $accountname
            );

            // 부분 취소일 경우 취소 금액 처리(취소금액이 없으면 전체 취소로 처리됨)
            // s: psysapi-plugin
            if ($amount > 0) {
                $request_data_array['amount'] = $amount;
            }
            // s: psysapi-plugin

            $psysapi_json = json_encode($request_data_array, TRUE);

            @psysapi_payment_log("결제 취소 요청 json", $psysapi_json, 3);

            $ch = curl_init();

            curl_setopt($ch,CURLOPT_URL, $psysapi_url);
            curl_setopt($ch,CURLOPT_POST, false);
            curl_setopt($ch,CURLOPT_POSTFIELDS, $psysapi_json);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch,CURLOPT_CONNECTTIMEOUT ,3);
            curl_setopt($ch,CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            curl_close($ch);

            $result_array = json_decode($response,TRUE);

            if ($result_array['cancel_code'] == '0000') {
                $data['result'] = $result_array['cancel_code'];
                @psysapi_payment_log("결제 취소 성공", $response, 3);

                $sql = " INSERT INTO ".PSYSAPI_PG_CANCEL." (orderno, cancel_tid, cancel_code, cancel_msg, cancel_date, cancel_amt) VALUES ('{$orderno}', '{$result_array['cancel_tid']}', '{$result_array['cancel_code']}', '{$result_array['cancel_msg']}', '{$result_array['cancel_date']}', '{$result_array['cancel_amt']}') ";
                
                $res = sql_query($sql, false);

                if ($res) {
                    @psysapi_payment_log("결제 취소 결과 저장 성공", $sql, 3);
                } else {
                    @psysapi_payment_log("결제 취소 결과 저장 실패", $sql, 3);
                }

                // s : psysapi-plugin
                if($pg_res_cd == '') {
                    $pg_cancel_log = ' PG 신용카드 승인취소 처리';
                    $sql = " update {$g5['g5_shop_order_table']}
                                set od_refund_price = od_refund_price + $amount
                                where od_id = '$od_id' ";
                    sql_query($sql);
                } 
                // e : psysapi-plugin
                                
            } else {
                $data['error'] = "[{$result_array['cancel_code']}] {$result_array['cancel_msg']}";
                @psysapi_payment_log("결제 취소 실패", $response, 3);
            }
        } else {
            $data['error'] = "결제 취소 토큰 발급 실패";
            @psysapi_payment_log("결제 취소 토큰 발급 실패", "", 3);
        }    

    }
}
