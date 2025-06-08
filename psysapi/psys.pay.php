<?php
include_once('../shop/_common.php');
require_once G5_PATH."/psysapi/psysapi.lib.php";
require_once G5_PATH."/psysapi/psysapi.migrate.php";

$ret = [
    'status' => false,
    'data' => ''
];

// $psysapi = $_POST;
$psysapi = array();
foreach ($_POST as $key => $value) {
    $psysapi[$key] = clean_xss_tags($value, 1, 1);
}

$mode = $psysapi['mode'];
unset($psysapi['mode']);

// 결제창 팝업시 결제결과 데이터 사전 생성
if ($mode == "try_pay") {
    @psysapi_payment_log("결제 시도", json_encode($psysapi), 3);
    
    $orderno = isset($psysapi['ORDERNO']) && !empty($psysapi['ORDERNO']) ? $psysapi['ORDERNO'] : '';
    
    $pgResultId = '';
    if (!empty($orderno)) {
        $pgResult = sql_fetch(" SELECT * FROM ".PSYSAPI_PG_RESULT." WHERE ORDERNO='{$orderno}' AND pay_status=0 ORDER BY `id` DESC LIMIT 1");
        $pgResultId = isset($pgResult['id']) && !empty($pgResult['id']) ? $pgResult['id'] : '';
    }
    
    if (!empty($orderno) && empty($pgResultId)) {
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
        $values['ORDERNO'] = "'{$orderno}'";
        $payType = isset($psysapi['PAY_TYPE']) && !empty($psysapi['PAY_TYPE']) ? $psysapi['PAY_TYPE'] : 3;
        $values['pay_type'] = "'{$payType}'";
        $values['pay_status'] = "0";
        $valueStr = implode(",", $values);

        $sql = " INSERT INTO ".PSYSAPI_PG_RESULT." ({$columnStr}) VALUES ({$valueStr}) ";
        $res = sql_query($sql, false);
        if ($res) {
            @psysapi_payment_log("결제 시도 저장 성공", $sql, 3);
            $ret['status'] = true;
        } else {
            @psysapi_payment_log("결제 시도 저장 실패", $sql, 3);
        }
    }

    echo json_encode($ret);
    exit;
}

// s: psysapi-plugin
if ($mode == "try_order")
{
    @psysapi_payment_log("주문정보 임시저장 시도", json_encode($psysapi), 3);
    
    $odId = isset($psysapi['od_id']) && !empty($psysapi['od_id']) ? $psysapi['od_id'] : '';
    $odResultId = '';
    if (!empty($od_id))
    {
        $odResult = sql_fetch(" SELECT * FROM ".PSYSAPI_SHOP_ORDER." WHERE od_id='{$od_id}' LIMIT 1");
        $odResultId = isset($odResult['od_id']) && !empty($odResult['od_id']) ? $odResult['od_id'] : '';
    }
    
    if (!empty($odId) && empty($odResultId))
    {
        foreach ($psysapi as $key => $val)
        {
            if ($key == 'od_zip')
            {
                $od_zip = preg_replace('/[^0-9]/', '', $val);
                $od_zip1 = substr($od_zip, 0, 3);
                $od_zip2 = substr($od_zip, 3);
                $keys['od_zip1'] = "od_zip1";
                $keys['od_zip2'] = "od_zip2";
                $vals['od_zip1'] = "'{$od_zip1}'";
                $vals['od_zip2'] = "'{$od_zip2}'";
            }
            else if ($key == 'od_b_zip')
            {
                $od_b_zip = preg_replace('/[^0-9]/', '', $val);
                $od_b_zip1  = substr($od_b_zip, 0, 3);
                $od_b_zip2  = substr($od_b_zip, 3);
                $keys['od_b_zip1'] = "od_b_zip1";
                $keys['od_b_zip2'] = "od_b_zip2";
                $vals['od_b_zip1'] = "'{$od_b_zip1}'";
                $vals['od_b_zip2'] = "'{$od_b_zip2}'";
            }
            else
            {
                $keys[$key] = "{$key}";
                $vals[$key] = "'{$val}'";
            }
        }
        
        $keyStr = implode(",", $keys);
        $valStr = implode(",", $vals);
        
        $sql = " INSERT INTO ".PSYSAPI_SHOP_ORDER." ({$keyStr}) VALUES ({$valStr}) ";
        $res = sql_query($sql, false);
        if ($res) {
            @psysapi_payment_log("주문정보 임시저장 시도 저장 성공", $sql, 3);
            $ret['status'] = true;
        } else {
            @psysapi_payment_log("주문정보 임시저장 시도 저장 실패", $sql, 3);
        }
    }
    
    echo json_encode($ret);
    exit;
}
// e: psysapi-plugin

// 수기결제
if ($mode == "keyin_pay") {
    $psysapiApiKeyin = psysapi_get_api_account_info($default, 1);

    $req_json = json_encode([
        'pay2_id' => $psysapiApiKeyin['api_id'],
        'pay2_key'=> $psysapiApiKeyin['api_key'],
    ], true);

    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL, PSYSAPI_TOKEN_URL);
    curl_setopt($ch,CURLOPT_POST, false);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $req_json);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT ,3);
    curl_setopt($ch,CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["content-type: application/json; charset=utf-8"]);
    $tokenResJson = curl_exec($ch);
    curl_close($ch);
    $tokenRes = json_decode($tokenResJson, true);
    
    if ($tokenRes['RTN_CD'] == '0000') {
        
        $pgResultId = '';
        if (!empty($psysapi['ORDERNO'])) {
            $pgResult = sql_fetch(" SELECT * FROM ".PSYSAPI_PG_RESULT." WHERE ORDERNO='{$psysapi['ORDERNO']}' AND pay_status=0 ORDER BY `id` DESC LIMIT 1");
            $pgResultId = isset($pgResult['id']) && !empty($pgResult['id']) ? $pgResult['id'] : '';
        }

        if (empty($pgResultId)) {
            // insert
            $columnStr = implode(",", $pgResultColumns);
            $values = [];
            foreach ($pgResultColumns as $val) {
                $values[$val] = "''";
            }
            foreach ($tokenRes as $key => $val) {
                if (array_key_exists($key, $values)) {
                    $values[$key] = "'{$val}'";
                }
            }
            $values['PGNAME'] = "'{$default['de_pg_service']}'";
            $values['ORDERNO'] = "'{$psysapi['ORDERNO']}'";
            $values['pay_type'] = "'1'";
            $values['pay_status'] = "0";
            $valueStr = implode(",", $values);

            $sql = " INSERT INTO ".PSYSAPI_PG_RESULT." ({$columnStr}) VALUES ({$valueStr}) ";
            $res = sql_query($sql, false);
            if ($res) {
                @psysapi_payment_log("수기결제 시도 저장 성공", $sql, 3);
            } else {
                @psysapi_payment_log("수기결제 시도 저장 실패", $sql, 3);
                echo '<script>location.href = "./pgresult.php?mode=after&RESULTCODE=&RESULTMSG=";</script>';
                exit;
            }
            // end insert
        }

        $headers = [
            "content-type: application/json; charset=utf-8",
            "ApiKey: {$psysapiApiKeyin['api_key']}",
            "TOKEN: {$tokenRes['TOKEN']}"
        ];

        $psysapiments_url = $default['de_card_test'] == '0' ? PSYSAPI_KEYIN_URL : PSYSAPI_TESTKEYIN_URL;

        $request_data_array = [
            'API_ID'        => $psysapiApiKeyin['api_id'],
            'ORDERNO'       => $psysapi['ORDERNO'],
            'PRODUCTNAME'   => $psysapi['PRODUCTNAME'],
            'AMOUNT'        => $psysapi['AMOUNT'],
            'BUYERNAME'     => $psysapi['BUYERNAME'],
            'BUYEREMAIL'    => $psysapi['BUYEREMAIL'],
            'CARDNO'        => $psysapi['CARDNO'],
            'EXPIREDT'      => $psysapi['EXPIREDT'],
            'PRODUCTCODE'   => $psysapi['PRODUCTCODE'],
            'BUYERID'       => $psysapi['BUYERID'],
            'BUYERADDRESS'  => $psysapi['BUYERADDRESS'],
            'BUYERPHONE'    => $psysapi['BUYERPHONE'],
            'QUOTA'         => $psysapi['QUOTA'],
            'CARDAUTH'      => $psysapi['CARDAUTH'],
            'CARDPWD'       => $psysapi['CARDPWD'],
            'HANACARD_USE'  => $psysapi['HANACARD_USE'],
            'ETC1'          => $psysapi['ETC1'],
            'ETC2'          => $psysapi['ETC2'],
            'ETC3'          => $psysapi['ETC3'],
            'ETC4'          => $psysapi['ETC4'],
            'ETC5'          => $psysapi['ETC5'],
        ];

        $psysapiments_json = json_encode($request_data_array, true);

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $psysapiments_url);
        curl_setopt($ch,CURLOPT_POST, false);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $psysapiments_json);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch,CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $keyinResJson = curl_exec($ch);
        curl_close($ch);
        $keyinRes = json_decode($keyinResJson, true);

        if ($keyinRes['RESULTCODE'] == '0000') {
            @psysapi_payment_log("수기결제 성공", $keyinResJson, 1);

            $payStatus = '';
            if (!empty($keyinRes['ORDERNO'])) {
                $pgResult = sql_fetch(" SELECT * FROM ".PSYSAPI_PG_RESULT." WHERE ORDERNO='{$keyinRes['ORDERNO']}' ORDER BY `id` DESC LIMIT 1");
                $payStatus = isset($pgResult['pay_status']) && $pgResult['pay_status']>=0 ? $pgResult['pay_status'] : '';
            }

            if ($payStatus == '') {
                // insert
                $columnStr = implode(",", $pgResultColumns);
                $values = [];
                foreach ($pgResultColumns as $val) {
                    $values[$val] = "''";
                }
                foreach ($keyinRes as $key => $val) {
                    if (array_key_exists($key, $values)) {
                        $values[$key] = "'{$val}'";
                    }
                }
                $values['PGNAME'] = "'{$default['de_pg_service']}'";
                $values['pay_type'] = "'1'";
                $values['pay_status'] = "1";
                $valueStr = implode(",", $values);

                $sql = " INSERT INTO ".PSYSAPI_PG_RESULT." ({$columnStr}) VALUES ({$valueStr}) ";
                $res = sql_query($sql, false);
                if ($res) {
                    @psysapi_payment_log("수기결제 결과 저장 성공1", $sql, 3);
                } else {
                    @psysapi_payment_log("수기결제 결과 저장 실패1", $sql, 3);
                }
                // end insert
            } else if ($payStatus != 1) {
                // update
                $set = [];
                foreach ($keyinRes as $key => $val) {
                    if (in_array($key, $pgResultColumns)) {
                        $set[$key] = "{$key}='{$val}'";
                    }
                }
                $set['PGNAME'] = "PGNAME='{$default['de_pg_service']}'";
                $set['pay_type'] = "pay_type='1'";
                $set['pay_status'] = "pay_status=1";
                $setStr = implode(",", $set);

                $sql = "UPDATE ".PSYSAPI_PG_RESULT." SET {$setStr} WHERE ORDERNO='{$keyinRes['ORDERNO']}'";

                $res = sql_query($sql, false);
                if ($res) {
                    @psysapi_payment_log("수기결제 결과 저장 성공2", $sql, 3);
                } else {
                    @psysapi_payment_log("수기결제 결과 저장 실패2", $sql, 3);
                }
                // end update
            }

            // 결제 검증
            $token_url = PSYSAPI_TOKEN_URL;

            $request_data = json_encode([
                'pay2_id' => $psysapiApiKeyin['api_id'],
                'pay2_key'=> $psysapiApiKeyin['api_key'],
            ], JSON_UNESCAPED_UNICODE);

            unset($tokenResJson);
            unset($tokenRes);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $token_url);
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json; charset=utf-8"]);
            $tokenResJson = curl_exec($ch);
            curl_close($ch);
            $tokenRes = json_decode($tokenResJson, true);

            if ($tokenRes['RTN_CD'] == '0000') {
                $headers = [
                    "content-type: application/json; charset=utf-8",
                    "TOKEN: {$tokenRes['TOKEN']}"
                ];
        
                $request_data = json_encode([
                    "tid" => $keyinRes['TID']
                ], JSON_UNESCAPED_UNICODE);
        
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, PSYSAPI_VERIFY_URL);
                curl_setopt($ch, CURLOPT_POST, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $verifyJson = curl_exec($ch);
                curl_close($ch);
                $verify = json_decode($verifyJson, true);

                // 결제 검증 결과 테이블에 저장
                $columnStr = implode(",", $pgVerifyColumns);
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
                if ($res) {
                    @psysapi_payment_log("수기결제 검증결과 저장 성공3", $sql, 3);
                } else {
                    @psysapi_payment_log("수기결제 검증결과 저장 실패3", $sql, 3);
                }

                if($verify['RESULTCODE'] == '0000') {
                    @psysapi_payment_log("수기결제 검증 성공4", $verifyJson, 3);
                } else {
                    @psysapi_payment_log("수기결제 검증 실패4", $verifyJson, 3);
                
                    $ret = psysapi_cancel_payment($psysapiApiKeyin['api_id'], $psysapiApiKeyin['api_key'], $keyinRes['TID']);
        
                    if ($ret['status'] === true) {
                        @psysapi_payment_log("수기결제 취소 성공5", $ret['data'], 3);
                        $payStatusRes = sql_query("UPDATE ".PSYSAPI_PG_RESULT." SET pay_status=2 WHERE ORDERNO='{$psysapi['ORDERNO']}'", false);
                    } else {
                        @psysapi_payment_log("수기결제 취소 실패5", $ret['data'], 3);
                    }
        
                    $cancelArr = json_decode($ret['data'], true);
        
                    $sql = " INSERT INTO ".PSYSAPI_PG_CANCEL." (orderno, cancel_tid, cancel_code, cancel_msg, cancel_date, cancel_amt) VALUES ('{$psysapi['ORDERNO']}', '{$cancelArr['cancel_tid']}', '{$cancelArr['cancel_code']}', '{$cancelArr['cancel_msg']}', '{$cancelArr['cancel_date']}', '{$cancelArr['cancel_amt']}') ";
                    $res = sql_query($sql, false);
                    if ($res) {
                        @psysapi_payment_log("수기결제 취소결과 저장 성공6", $sql, 3);
                    } else {
                        @psysapi_payment_log("수기결제 취소결과 저장 실패6", $sql, 3);
                    }
                }
            } else {
                @psysapi_payment_log("수기결제 검증 토큰 발행 실패7", $resultJson, 3);
                
                $ret = psysapi_cancel_payment($psysapiApiKeyin['api_id'], $psysapiApiKeyin['api_key'], $keyinRes['TID']);
        
                if ($ret['status'] === true) {
                    @psysapi_payment_log("수기결제 취소 성공8", $ret['data'], 3);
                    $payStatusRes = sql_query("UPDATE ".PSYSAPI_PG_RESULT." SET pay_status=2 WHERE ORDERNO='{$psysapi['ORDERNO']}'", false);
                } else {
                    @psysapi_payment_log("수기결제 취소 실패8", $ret['data'], 3);
                }
        
                $cancelArr = json_decode($ret['data'], true);
        
                $sql = " INSERT INTO ".PSYSAPI_PG_CANCEL." (orderno, cancel_tid, cancel_code, cancel_msg, cancel_date, cancel_amt) VALUES ('{$psysapi['ORDERNO']}', '{$cancelArr['cancel_tid']}', '{$cancelArr['cancel_code']}', '{$cancelArr['cancel_msg']}', '{$cancelArr['cancel_date']}', '{$cancelArr['cancel_amt']}') ";
                $res = sql_query($sql, false);
                if ($res) {
                    @psysapi_payment_log("수기결제 취소결과 저장 성공9", $sql, 3);
                } else {
                    @psysapi_payment_log("수기결제 취소결과 저장 실패9", $sql, 3);
                }
            }
        } else {
            @psysapi_payment_log("수기결제 실패", json_encode($keyinResJson), 3);
        }
    } else {
        @psysapi_payment_log("수기결제 토큰 발행 실패", $tokenResJson, 3);
    }
    
    // s: psysapi-plugin
    echo '<script>location.href = "./pgresult.php?mode=after&RESULTCODE='.$keyinRes['RESULTCODE'].'&RESULTMSG='.$keyinRes['RESULTMSG'].'&type=keyin";</script>';
    // e: psysapi-plugin
    
    exit;
} // end if ($mode == "keyin_pay") 
