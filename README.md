# 피시스 API 플러그인 (피시스 API 영카트 결제 연동 플러그인)

- 영카트 5.6.8 > 2025.01.14 기준 최신버전 지원

- 결제 로그 기록  
    - DOCUMENT ROOT의 상위 디렉토리에 logs 디렉토리 생성  
    - logs 디렉토리에 707 권한 부여  

- 피시스 API 플러그인 관련 테이블  
    - 자동으로 생성되며 "psysapi_"와 같은 Prefix로 시작합니다.  

- https 설정 안내  
      - /psysapi/psysapi.constants.php  
      - 위 파일에서 아래 코드를 찾아 변경 (https 사용시: true, 미사용시: false)  
      ```  
      define('PSYSAPI_USE_HTTPS', true);
      ```  
  
- 결제 통지 설정 안내 
    - 아래의 URL을 피시스 API 담당자에게 전달해 [PG 통지 URL] 설정을 요청해 주세요.    
      ```  
      https://[플러그인설치도메인]/psysapi/result.noti.php  
      ```  

- 영카트를 커스터마이징 하지 않은 경우  
    - 아래 디렉토리와 파일을 영카트 설치 디렉토리에 Copy & Paste(덮어쓰기) 
    - (psysapi-plugin)  
        - /adm  
        - /psysapi  
        - /shop
        - /mobile  
        - /theme  
        - /lib

- 영카트를 커스터마이징한 경우  
        - Copy & Paste(붙여넣기)

        - /psysapi 
        - /adm/shop_admin/psysapi.ajax.php
        - /adm/shop_admin/psysapi.cancel.php 
        - /adm/shop_admin/psysapi.configformupdate.php
        - /adm/shop_admin/psysapi.pgconfig.php
        - /adm/shop_admin/psysapi.pgresult.php
        - /adm/shop_admin/orderform.php
        - /adm/shop_admin/orderformcartupdate.php
        - /shop/settle_PSYS_AL.inc.php
        - /shop/settle_PSYS_KW.inc.php
        - /shop/PSYS_AL
        - /shop/PSYS_KW
        - /mobile/shop/settle_PSYS_AL.inc.php
        - /mobile/shop/settle_PSYS_KW.inc.php
        - /mobile/shop/PSYS_AL
        - /mobile/shop/PSYS_KW
            
        - 코드 추가  
        - /adm/admin.menu400.shop_1of2.php
        - /adm/shop_admin/configform.php
        - /adm/shop_admin/configformupdate.php
        - /adm/shop_admin/orderform.php
        - /adm/shop_admin/orderformcartupdate.php
        - /adm/shop_admin/orderlist.php
        - /shop/orderform.sub.php
        - /shop/orderformupdate.php
        - /shop/orderinquiryview.php
        - /shop/cartupdate.php
        - /mobile/shop/orderform.sub.php
        - /mobile/shop/orderformupdate.php
        - /mobile/shop/orderinquiryview.php
        - /theme/basic/shop/orderinquiryview.php
        - /theme/basic/shop/mypage.php
        - /lib/shop.lib.php
        ```  
        ❗ 코드 추가는 영카트 원본파일이 수정된 경우이므로 아래와 같은 주석 구문을 검색해  
         해당 코드블록을 복사해 붙여넣어 주시기 바랍니다.

        // s: psysapi-plugin -> "s:" 코드블록 시작을 의미
        // e: psysapi-plugin -> "e:" 코드블록 종료를 의미

        (주의: 코드블록은 여러 개가 존재할 수 있습니다)
        ```  


<br><br>

감사합니다.<br>

