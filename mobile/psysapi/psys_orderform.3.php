<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// 주문정보 처리 및 결제창 팝업

require_once G5_PATH."/psysapi/psysapi.lib.php";

// s: psysapi-plugin
// 회원정보
$member = get_member($_SESSION['ss_mb_id']);

if (get_session("ss_direct")) {
    $tmp_cart_id = get_session('ss_cart_direct');
}
else {
    $tmp_cart_id = get_session('ss_cart_id');
}

if ($_SESSION['ss_direct']) {
    $tmp_cart_id = $_SESSION['ss_cart_direct'];
}
else {
    $tmp_cart_id = $_SESSION['ss_cart_id'];
}

$sql = " select SUM(IF(io_type = 1, (io_price * ct_qty), ((ct_price + io_price) * ct_qty))) as od_price,
              COUNT(distinct it_id) as cart_count
            from {$g5['g5_shop_cart_table']} where od_id = '$tmp_cart_id' and ct_select = '1' ";
$row = sql_fetch($sql);
$tot_ct_price = $row['od_price'];
$cart_count = $row['cart_count'];
// e: psysapi-plugin
?>

<form name="sm_form" method="POST" action="" accept-charset="euc-kr">
<input type="hidden" name="good_mny" value="<?php echo $tot_price; ?>" >
</form>

<!-- // s: psysapi-plugin -->
<script src="<?php echo G5_JS_URL; ?>/jquery-1.12.4.min.js"></script>

<?php
// 상품정보를 가저온다.
$sql = "
  select op.* 
  from g5_shop_order where od_id = '$od_id' 
  order by od_id desc limit 1 ";
$result = sql_query($sql);
$od = sql_fetch_array($result);

# de_psys_kw_id_web
# de_psys_kw_key_web

$default_pg_str = explode("_", $default['de_pg_service']);
$default_pg = strtolower($default_pg_str[1]);

$de_card_test = $default['de_card_test'] == "0" ? "Y" : "N"; // 테스트 결제인지(N) : 실결제인지 (Y)

## 피시스 API > Secure Key 통신
$headers = array(); 
array_push($headers, "content-type: application/json; charset=utf-8");
array_push($headers, "WebKey: ".$default["de_psys_{$default_pg}_key_web"]);

$psys_api_url = $psysApiUrl."/EdiAuth/edi_encrypt";

$edi_date = date('YmdHis');
$total_price = $tot_sell_price + $send_cost;

$request_data_array = array(
    'WEB_ID' => $default["de_psys_{$default_pg}_id_web"],
    'AMOUNT' => "{$total_price}",
    'EDI_DATE'=> $edi_date,
);

$psys_api_json = json_encode($request_data_array, TRUE);

$ch = curl_init(); // curl 초기화

curl_setopt($ch,CURLOPT_URL, $psys_api_url);
curl_setopt($ch,CURLOPT_POST, false);
curl_setopt($ch,CURLOPT_POSTFIELDS, $psys_api_json);
curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch,CURLOPT_CONNECTTIMEOUT ,3);
curl_setopt($ch,CURLOPT_TIMEOUT, 20);
curl_setopt($ch,CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
curl_close($ch);

$result_array = json_decode($response, true);

if($result_array['RESULTCODE'] == "9999") { 
    echo "result_code=E009\r\nresult_msg=웹연동 결제 설정이 되어있지 않습니다.";
    exit;
}

?>
<!-- Psys  결제 요청 데이터 시작 ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->
<form name='psysForm' id="psysForm" method='POST'>
<input type="hidden" name="Psys_shopid" value="<? echo $default["de_psys_{$default_pg}_id_web"]; ?>"> <!-- PSYS 가맹점ID  [필수] -->
<input type="hidden" name="Psys_securekey" value="<?=$result_array['encryptData']?>"> <!-- PSYS Psys_securekey -->

<!--개인 정보 암호화 시작 ##################################################################-->
<input type="hidden" name="Psys_buyername" value="<?php echo isset($member['mb_name']) ? get_text($member['mb_name']) : ''; ?>"> <!-- 구매자명 -->
<input type="hidden" name="Psys_handphone" value="<?php echo str_replace("-", "", get_text($member['mb_hp'])); ?>"> <!-- 구매자 인증용 핸드폰 번 -->
<input type="hidden" name="Psys_email" value="<?php echo isset($member['mb_email']) ? get_text($member['mb_email']) : ''; ?>"> <!-- 이메일주소 -->
<input type="hidden" name="Psys_recp_nm" id="Psys_recp_nm" value=""> <!-- 수신자 -->
<input type="hidden" name="Psys_recp_addr" value="<?=$od['addr1'].$od['addr2'].$od['addr3']?>"> <!-- 수신 주소 -->
<input type="hidden" name="Psys_rcvr_name" value="<?=$od['b_name']?>"> <!-- 수신자명 -->
<input type="hidden" name="Psys_rcvr_add" value=""> <!-- 수신 주소 -->
<input type="hidden" name="Psys_pmember_id" value="<?php echo isset($member['mb_id']) ? get_text($member['mb_id']) : ''; ?>"> <!-- 구매자 ID -->
<!--개인 정보 암호화 끝 ###################################################################-->

<input type="hidden" name="Psys_totalamt" value="<?php echo ($tot_sell_price + $send_cost); ?>">  <!--  결제금액 [필수] -->
<input type="hidden" name="ReturnURL" value="<?php echo PSYS_RETURN_URL; ?>?od_id=<?php echo $od_id; ?>"> <!-- //리턴 주소[필수] -->

<input type="hidden" name="Psys_product_ea" value="1">    <!-- // 상품수 기본=1 -->
<input type="hidden" name="Psys_goods_code" value="">    <!-- // 상점상품코드 -->
<input type="hidden" name="Psys_title" value="<?php echo mb_substr(str_replace("&", "", $goods), 0, 40); ?>">  <!-- // 상품명 (한글시 인코딩처리) [필수] -->

<input type="hidden" name="Psys_shopingmall_order_no" value="<?php echo $od_id; ?>"> <!-- // 상품 오더번호 -->
<input type="hidden" name="Psys_card_type" value="<?php echo $default["de_psys_{$default_pg}_card_type"]; ?>"> <!-- // 연구비카드 구분 : 미입력시 카드 구분 전체 가능<br>1: 연구비카드(신한,BC,삼성), 3: 일반카드, 4: 키인(현장)결제, 5.키인결제(전화승인) ,6:장기무이자,7:장기무이자(인증),9: 연구비카드(신한,BC,삼성)+일반카드 -->
<input type="hidden" name="Psys_test_yn" value="<?php echo $de_card_test; ?>"> <!-- // 테스트(Y/N) -->

<input type="hidden" name="Psys_etc_data1" value=""> <!-- 추가데이타1 -->
<input type="hidden" name="Psys_etc_data2" value=""> <!-- 추가데이타2 -->
<input type="hidden" name="Psys_etc_data3" value=""> <!-- 추가데이타3 -->
<input type="hidden" name="Psys_etc_data4" value=""> <!-- 추가데이타4 -->
<input type="hidden" name="Psys_etc_data5" value="<?php echo $tmp_cart_id; ?>"> <!-- 추가데이타5 -->
<input type="hidden" name="Psys_etc_data6" value=""> <!-- 추가데이타6 -->
<input type="hidden" name="Psys_etc_data7" value=""> <!-- 추가데이타7 -->

<input type="hidden" name="Psys_enc_yn" value="N"> <!-- 암호화 여부(Y/N) 디폴트 N //  Y 일때만 AES256 적용-->
<input type="hidden" name="Psys_enc_data" value=""> <!--결제요청 응답 데이터-->
<input type="hidden" name="edi_date" id="edi_date" value="<?=$edi_date?>">

<!-- Psys  결제 요청 끝 ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->
</form>

<script language='javascript'>
function pay_psysapi(payType) {

    <?php
    $psysApi = psys_get_api_account_info($default);

    ?>
    var payMethod = '';
    switch (payType) {
        case '간편결제':
        case '카카오페이':
            // 키움만 가능. 키움이 아니라면 CARD로 설정
            if ("<?php echo $default['de_pg_service']; ?>" == "PSYSAPI_KW") {
                payMethod = 'KAKAOPAY';
            } else {
                payMethod = 'CARD';
            }
            break;
        case '계좌이체':
            payMethod = 'BANK';
            break;
        case '가상계좌':
            payMethod = 'VACCT';
            break;
        case '휴대폰':
            payMethod = 'MOBILE';
            break;
        default:
            payMethod = 'CARD';
    }
    
    var isTest = "<?php echo $default['de_card_test']; ?>";
    if (isTest == '0') {
        var url = "<?php echo PSYS_API_URL; ?>"; // 실결제
    } else {
        var url = "<?php echo PSYS_API_TEST_URL; ?>"; // 테스트결제
    }
    
    if (document.getElementById("BUYERPHONE").value == '') {
        if ($("input[name=od_b_hp]").val()) {
            var buyerphone = $("input[name=od_b_hp]").val();
        }
        else {
            var buyerphone = $("input[name=od_b_tel]").val();
        }
    }
    else {
        var buyerphone = document.getElementById("BUYERPHONE").value;
    }
    
    var params = {
        API_ID: "<?php echo $psysApi['api_id']; ?>",
        ORDERNO: document.getElementById("ORDERNO").value, //주문번호 (필수)
        PRODUCTNAME: document.getElementById("PRODUCTNAME").value, //상품명 (필수)
        AMOUNT: document.getElementById("AMOUNT").value, //결제 금액 (필수)
        BUYERNAME: document.getElementById("BUYERNAME").value, //고객명 (필수)
        BUYEREMAIL: document.getElementById("BUYEREMAIL").value, //고객 e-mail (필수)
        PAYMETHOD: payMethod, //결제 수단 (선택)
        PRODUCTCODE: document.getElementById("PRODUCTCODE").value, //상품 코드 (선택)
        BUYERID: document.getElementById("BUYERID").value, //고객 아이디 (선택)
        BUYERADDRESS: document.getElementById("BUYERADDRESS").value, //고객 주소 (선택)
        BUYERPHONE : buyerphone, //고객 휴대폰번호 (선택, 웰컴페이는 필수)
        RETURNURL: document.getElementById("RETURNURL").value, //결제 완료 후 리다이렉트 url (필수)
        CANCELURL : document.getElementById("CANCELURL").value,
        PAY_TYPE : document.getElementById("PAY_TYPE").value,
        ENG_FLAG : document.getElementById("ENG_FLAG").value,
        ETC1 : document.getElementById("ETC1").value, //사용자 추가필드1 (선택)
        ETC2 : document.getElementById("ETC2").value, //사용자 추가필드2 (선택)
        ETC3 : document.getElementById("ETC3").value, //사용자 추가필드3 (선택)
        ETC4 : document.getElementById("ETC4").value, //사용자 추가필드4 (선택)
        //ETC5 : document.getElementById("ETC5").value, //사용자 추가필드5 (선택)
        ETC5 : "<?=$tmp_cart_id?>", // u: psysapi-plugin > 장바구니 업데이트 > v1.2 > 240321
        ORDER_NO_CHECK : "N",
    };
    var tryPayParams = params;
    tryPayParams['mode'] = "try_pay";
    
    var tryOrderParams = {
        mode : 'try_order',
        od_id : $("input[name=ORDERNO]").val(),
        mb_id : '<?=$member['mb_id']?>',
        od_name : $("input[name=od_name]").val(),
        od_email : $("input[name=od_email]").val(),
        od_tel : $("input[name=od_tel]").val(),
        od_hp : $("input[name=od_hp]").val(),
        od_zip : $("input[name=od_zip]").val(),
        od_addr1 : $("input[name=od_addr1]").val(),
        od_addr2 : $("input[name=od_addr2]").val(),
        od_addr3 : $("input[name=od_addr3]").val(),
        od_addr_jibeon : $("input[name=od_addr_jibeon]").val(),
        od_deposit_name : $("input[name=od_deposit_name]").val(),
        od_b_name : $("input[name=od_b_name]").val(),
        od_b_tel : $("input[name=od_b_tel]").val(),
        od_b_hp : $("input[name=od_b_hp]").val(),
        od_b_zip : $("input[name=od_b_zip]").val(),
        od_b_addr1 : $("input[name=od_b_addr1]").val(),
        od_b_addr2 : $("input[name=od_b_addr2]").val(),
        od_b_addr3 : $("input[name=od_b_addr3]").val(),
        od_b_addr_jibeon : $("input[name=od_b_addr_jibeon]").val(),
        od_memo : $("input[name=od_memo]").val(),
        od_cart_count : '<?=$cart_count?>',
        od_cart_price : '<?=$tot_ct_price?>',
        od_cart_coupon : '0',
        od_send_cost : $("input[name=od_send_cost]").val(),
        od_send_cost2 : $("input[name=od_send_cost2]").val(),
        od_send_coupon : $("input[name=od_send_coupon]").val(),
        od_receipt_price : '0',
        od_cancel_price : '0',
        od_receipt_point : '0',
        od_refund_price : '0',
        od_bank_account : '',
        od_receipt_time : '0000-00-00 00:00:00',
        od_coupon : $("input[name=od_coupon]").val(),
        od_misu : '0',
        od_shop_memo : '',
        od_mod_history : '',
        od_status : '',
        od_hope_date : $("input[name=od_hope_date]").val(),
        od_settle_case : $("input[name=od_settle_case]:checked").val(),
        od_other_pay_type : '',
        od_test : '<?=$default['de_card_test']?>',
        od_mobile : '0',
        od_pg : '',
        od_tno : '',
        od_app_no : '',
        od_escrow : '0',
        od_tax_flag : '<?=$default['de_tax_flag_use']?>',
        od_tax_mny : '0',
        od_vat_mny : '0',
        od_free_mny : '0',
        od_delivery_company : '0',
        od_invoice : '',
        od_invoice_time : '0000-00-00 00:00:00',
        od_cash : '',
        od_cash_no : '',
        od_cash_info : '',
        od_time : '<?=G5_TIME_YMDHIS?>',
        od_pwd : $("input[name=od_pwd]").val(),
        od_ip : '<?=$REMOTE_ADDR?>',
        od_price : $("input[name=od_price]").val(),
        org_od_price : $("input[name=org_od_price]").val(),
        od_goods_name : $("input[name=od_goods_name]").val(),
        od_temp_point : $("input[name=od_temp_point]").val(),
        ad_default : $("input[name=ad_default]").val(),
        it_id : $("input[name=it_id]").val(),
        cp_id : $("input[name=cp_id]").val(),
        od_cp_id : $("input[name=od_cp_id]").val(),
        sc_cp_id : $("input[name=sc_cp_id]").val(),
        comm_tax_mny : $("input[name=comm_tax_mny]").val(),
        comm_vat_mny : $("input[name=comm_vat_mny]").val(),
        comm_free_mny : $("input[name=comm_free_mny]").val(),
        ad_subject : $("input[name=ad_subject]").val(),
        item_coupon : $("input[name=item_coupon]").val(),
    };
    
    $("#Psys_recp_nm").val($("input[name=od_b_name]").val());

    $.ajax({
        type: "POST",
        url: "<?php echo PSYSAPI_URL ?>/psys.pay.php",
        data : tryPayParams,
        cache: false,
        contentType : "application/x-www-form-urlencoded",
        success: function(tryPayRes) {
            $.ajax({
                type: "POST",
                url: "<?php echo PSYSAPI_URL ?>/psys.pay.php",
                data : tryOrderParams,
                cache: false,
                contentType : "application/x-www-form-urlencoded",
                success: function(tryOrderRes) {

                    // POST 새창 팝업 띄우기(S)
                    var option = "left=500, top=100, width=800, height=700";
                    var pgWin1 = window.open(url, "psysForm", option);
                    
                    var myForm = document.psysForm; // 결제요청 인자값들이 있는 폼
                    myForm.action = url;
                    myForm.method = "post";
                    myForm.target = "psysForm";
                    myForm.submit();
                    // POST 새창 팝업 띄우기(E)

                    if(!pgWin1 || pgWin1.closed || typeof pgWin1.closed=='undefined') { 
                        alert("팝업이 차단되어 있습니다.\n팝업 차단 해제 후 다시 시도해 주세요.");
                    }

                    /*
                    alert('피시스 API > 팝업 띄움 > POST 팝업');

                    return;

                    var popupPos = "left=500, top=180, width=650, height=550";
                    var pt = document.querySelector("#PAY_TYPE").value;

                    alert('url :'+url);
                    //var pgWin1 = window.open(`<?php echo PSYSAPI_URL; ?>/`+url+`?pm=${payType}&pt=${pt}&it_id=<?=$row2['it_id']?>`, "pgWin1", popupPos);
                    
                    if(!pgWin1 || pgWin1.closed || typeof pgWin1.closed=='undefined') { 
                        alert("팝업이 차단되어 있습니다.\n팝업 차단 해제 후 다시 시도해 주세요.");
                    }
                    */

                },
            });
        },
    });
}
</script>
