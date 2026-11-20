<?php
session_start();
session_regenerate_id();
require_once('../system/function.php');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title><?php echo webdata('company_name');?> | Payments</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" type="text/css" href="../assets/dist/css/adminx.css" media="screen" />
    <link rel="stylesheet" href="../assets/vendor/fontawesome/css/all.min.css"/>
    <link rel="stylesheet" href="../assets/css/cashpe.css?v=20261120r3">
    <link rel="icon" href="<?php echo webdata('web_fav');?>" type="image/*" />
	<!--script src="../assets/dist/js/historry.js"></script-->
	<script src="../assets/dist/js/sweetalert.min.js"></script>
<style>
.navbar-brand-image {
    width: 3.875rem;
    height: 1.875rem;
}    
.brand-logo{
   background-image: url('../images/cashpe-logo.svg');
   background-size: contain; 
   background-repeat: no-repeat;
   background-position: 
   center;
   height: 40px; width: 120px 
    
}

.small-logo {
    /* height: 60px; */
    width: 150px;
}

.adminx-container .navbar {
    font-size: .875rem;
    /* background-color: #000; */
    height: 3.5rem;
    padding: 0;
    -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
    box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
    background-image: linear-gradient(90deg, #085bac, #11c4cb);
}
.adminx-sidebar {
    /* background: #fff; */
    position: fixed;
    width: 260px;
    top: 3.5rem;
    bottom: 0;
    left: 0;
    z-index: 1040;
    -webkit-box-shadow: 1px 1px 1px 0 rgba(0,0,0,.1);
    box-shadow: 1px 1px 1px 0 rgba(0,0,0,.1);
    background-image: linear-gradient(180deg, #095fae, #11c4cb);
}
.sidebar-nav-link {
    padding: .5rem 1.5rem;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-orient: horizontal;
    -webkit-box-direction: normal;
    -ms-flex-direction: row;
    flex-direction: row;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
    color: #ffffff;
}
.sidebar-nav-link.active {
    color: #ffffff;
}
.sidebar-sub-nav {
    list-style-type: none;
    margin: 0;
    padding: .5rem 0;
    font-size: .875rem;
    background-image: linear-gradient(180deg, #095fae, #11c4cb);
}
a:hover {
  color: white;
}
.card{
border-radius: 5px;	
}
.sp-background {
  background: #0857ab;
  background-position: center;
  background-repeat: no-repeat;
  background-size: cover;

}
.card-nblue {
 background: #011f3f;	
}
.table td, .table th {
    padding: .75rem;
    vertical-align: top;
    border-top: 1px solid #0e98be;
}

.bg-mix{
  background: linear-gradient(#0857ab,#11c9cc);	
}
.table-hover tbody tr:hover {
    background-color: #011f3f;
    color: white;
}
</style>

</head>
<body class="sp-background">
<div class="p-3">
<div class="container px-0">
<?php
if($_SERVER["REQUEST_METHOD"]=="POST"){
if(isset($_POST['token'])&& 
!empty($_POST['token'])&& 
!empty($_POST['orderId'])&& 
!empty($_POST['txnAmount'])&& 
!empty($_POST['txnNote'])&&
!empty($_POST['cust_Mobile']) &&
!empty($_POST['cust_Email']) &&
!empty($_POST['callback_url'])&& 
!empty($_POST['checksum']) &&
safe_str($_POST['txnAmount'])>0  &&
is_numeric($_POST['cust_Mobile']) &&
strlen($_POST['cust_Mobile'])==10 && 
filter_var($_POST['cust_Email'], FILTER_VALIDATE_EMAIL)
){
    

$token = strip_tags($_POST['token']);
$orderId = safe_str($_POST['orderId']);
$txnAmount = safe_str($_POST['txnAmount']);
$txnNote = safe_str($_POST['txnNote']);
$cust_Mobile = safe_str($_POST['cust_Mobile']);
$cust_Email= strip_tags($_POST['cust_Email']);
$callback_url = strip_tags($_POST['callback_url']);
$checksum = $_POST['checksum'];

$res = sql_query("SELECT * FROM `tb_partner` WHERE token='".$token."' AND status='active' ");
$result = sql_fetch_array($res);

$upiuid = strip_tags(get_paytm_business($result['id'],"upi_id"));

if(sql_num_rows($res)>0 && $result['token']==$token){
    // CHeck Plan Expired 
$exp_date = $result['expire_date'];
if(getExpiredays(date('d-m-Y H:i:s'),$exp_date) >0){
$txn_query = sql_query("SELECT * FROM `tb_virtualtxn` WHERE client_orderid='".$orderId."' ");
if(sql_num_rows($txn_query) == 0 && sql_num_rows($query)==0){
$paramList = array();
$paramList["upiuid"] = $upiuid;
$paramList["token"] = $token;
$paramList["orderId"] = $orderId;
$paramList["txnAmount"] = $txnAmount;
$paramList["txnNote"] = $txnNote;
$paramList["cust_Mobile"] = $cust_Mobile;
$paramList["cust_Email"] = $cust_Email;
$paramList["callback_url"] = $callback_url;	
require_once('../system/checksum.php');
$verifySignature = CxrChecksum::verifySignature($paramList, $result['secret'], $checksum);
if($verifySignature){
	
$upi_id = get_paytm_business($result['id'],"upi_id");
$upi_name = get_paytm_business($result['id'],"upi_name");

if(isset($upi_id) && !empty($upi_id) && isset($upi_name) && !empty($upi_name) && $upiuid==$upi_id){
$txn_ref_id = GenRandomString().time();	
$_SESSION['muid'] = $result['id'];
$_SESSION['auth_token'] = $result['token'];
$_SESSION['txn_ref_id'] = $txn_ref_id;
$_SESSION['upi_id'] = $upi_id;
$_SESSION['client_orderid'] = $orderId;	
$_SESSION['txnAmount'] = $txnAmount;	
$_SESSION["txnNote"] = $txnNote;
$_SESSION['cust_Mobile'] = $cust_Mobile;
$_SESSION['cust_Email'] = $cust_Email;
echo "<script>
setTimeout(function(){ 
document.getElementById(\"qrcode\").innerHTML = '';
GenerateQR('".$upi_id."', '".$upi_name."', '".$txnAmount."', '".$txn_ref_id."', '".$txnNote."'); 
var upilink = document.getElementById(\"qrcode\").title;
document.getElementById(\"upilink\").href = upilink;
}, 1500);
</script>";	
$html = '
<form id="formSubmit" action="'.$callback_url.'" method="post" style="display:none;">
<input type="hidden" id="status" name="status">
<input type="hidden" id="message" name="message">
<input type="hidden" name="cust_Mobile" value="'.$cust_Mobile.'">
<input type="hidden" name="cust_Email" value="'.$cust_Email.'">
<input type="hidden" id="hash" name="hash">
<input type="hidden" id="checksum" name="checksum">
</form>
<script type="text/javascript" src="../assets/dist/js/paytmTxnStatus.js"></script>
';
echo $html;
}else{
$html = '
<div class="alert alert-danger text-center" style="color:#991b1b">Please do not refresh this page...</div>
<form id="formSubmit" action="'.$callback_url.'" method="post" style="display:none;">
<input type="hidden" name="status" value="FAILED" >
<input type="hidden" name="message" value="upi id is invalid or not updated" >
<input type="hidden" name="cust_Mobile" value="'.$cust_Mobile.'">
<input type="hidden" name="cust_Email" value="'.$cust_Email.'">
<input type="hidden" name="hash" value="false">
<input type="hidden" name="checksum" value="false">
</form>
<script type="text/javascript">
setTimeout(function(){ 
document.getElementById("formSubmit").submit();
}, 1500);
</script>
';	
echo $html;
exit();	
}
	
	
}else{
$html = '
<div class="alert alert-danger text-center" style="color:#991b1b">Please do not refresh this page...</div>
<form id="formSubmit" action="'.$callback_url.'" method="post" style="display:none;">
<input type="hidden" name="status" value="FAILED" >
<input type="hidden" name="message" value="Checksum Mismatch" >
<input type="hidden" name="cust_Mobile" value="'.$cust_Mobile.'">
<input type="hidden" name="cust_Email" value="'.$cust_Email.'">
<input type="hidden" name="hash" value="false">
<input type="hidden" name="checksum" value="false">
</form>
<script type="text/javascript">
setTimeout(function(){ 
document.getElementById("formSubmit").submit();
}, 1000);
</script>
';	
echo $html;	
exit();	
}


}else{
$html = '
<div class="alert alert-danger text-center" style="color:#991b1b">Please do not refresh this page...</div>
<form id="formSubmit" action="'.$callback_url.'" method="post" style="display:none;">
<input type="hidden" name="status" value="FAILED" >
<input type="hidden" name="message" value="This Order Id is Already Taken" >
<input type="hidden" name="cust_Mobile" value="'.$cust_Mobile.'">
<input type="hidden" name="cust_Email" value="'.$cust_Email.'">
<input type="hidden" name="hash" value="false">
<input type="hidden" name="checksum" value="false">
</form>
<script type="text/javascript">
setTimeout(function(){ 
document.getElementById("formSubmit").submit();
}, 1000);
</script>
';	
echo $html;		
exit();	
}



}else{
$html = '
<div class="alert alert-danger text-center" style="color:#991b1b">Please do not refresh this page...</div>
<form id="formSubmit" action="'.$callback_url.'" method="post" style="display:none;">
<input type="hidden" name="status" value="FAILED" >
<input type="hidden" name="message" value="Marchant Plan Expired Recharge Immidiately" >
<input type="hidden" name="cust_Mobile" value="'.$cust_Mobile.'">
<input type="hidden" name="cust_Email" value="'.$cust_Email.'">
<input type="hidden" name="hash" value="false">
<input type="hidden" name="checksum" value="false">
</form>
<script type="text/javascript">
setTimeout(function(){ 
document.getElementById("formSubmit").submit();
}, 1000);
</script>
';	
echo $html;		
exit();	
}
}else{
$html = '
<div class="alert alert-danger text-center" style="color:#991b1b">Please do not refresh this page...</div>
<form id="formSubmit" action="'.$callback_url.'" method="post" style="display:none;">
<input type="hidden" name="status" value="FAILED" >
<input type="hidden" name="message" value="Unauthorized Access or Token Is Invalid" >
<input type="hidden" name="cust_Mobile" value="'.$cust_Mobile.'">
<input type="hidden" name="cust_Email" value="'.$cust_Email.'">
<input type="hidden" name="hash" value="false">
<input type="hidden" name="checksum" value="false">
</form>
<script type="text/javascript">
setTimeout(function(){ 
document.getElementById("formSubmit").submit();
}, 1000);
</script>
';	
echo $html;		
exit();	
}

	
}else{
echo '<div class="alert alert-danger text-center" style="color:#991b1b">Parameter Missing<br>Redirect...</div>';
redirect($_POST['callback_url'],'2000');		
exit();	
}

}else{
echo '<div class="alert alert-danger text-center" style="color:#991b1b">Unauthorized Access<br>Redirect...</div>';
redirect(webdata('socket').base_url(),'2000');		
exit();	
}
?>
<!-- Cashpe checkout -->
<div class="cp-pay-wrap">
  <div class="cp-pay-card">
    <div class="cp-pay-top">
      <div>
        <div style="font-size:11px;color:#64748b;font-weight:800;letter-spacing:.1em">SECURE CHECKOUT</div>
        <div style="font-size:17px;font-weight:800;color:#065f46"><?php echo htmlspecialchars($result['company_name']); ?></div>
      </div>
      <div style="text-align:right">
        <div style="font-size:12px;color:#64748b">Order ID</div>
        <div style="font-weight:800;color:#052e24"><?php echo htmlspecialchars($orderId); ?></div>
      </div>
    </div>
    <div class="cp-pay-body">
      <div style="color:#64748b;font-size:13px">Total amount payable</div>
      <div style="font-size:40px;font-weight:800;color:#052e24;margin:2px 0 8px">₹<?php echo htmlspecialchars($txnAmount); ?></div>
      <div class="cp-pill"><i class="fas fa-lock"></i> Scan with any UPI app to pay</div>

      <div class="cp-qr-box" id="qrcode">
        <img src="../assets/img/loading.svg" alt="Generating QR…" width="160">
      </div>

      <div class="cp-app-row">
        <img src="../assets/img/gpay.svg" alt="Google Pay">
        <img src="../assets/img/paytm.svg" alt="Paytm">
        <img src="../assets/img/phonepe.svg" alt="PhonePe">
        <img src="../assets/img/bhim_logo.svg" alt="BHIM">
        <img src="../assets/img/amazonpay.svg" alt="Amazon Pay">
      </div>

      <?php $paybtn = isMobile() ? 'display:inline-flex' : 'display:none'; ?>
      <div style="margin-top:18px">
        <a href="#" id="upilink" class="cp-btn" style="<?php echo $paybtn ?>"><i class="fas fa-mobile-alt"></i> Pay ₹<?php echo htmlspecialchars($txnAmount); ?> with UPI app</a>
      </div>
      <div class="cp-timer"><i class="far fa-clock"></i> QR expires in <span id="upitimer">05:00</span></div>
      <div style="margin-top:18px">
        <a href="<?php echo htmlspecialchars($callback_url); ?>?results=payment cancel" style="font-size:12px;color:#64748b">← Cancel and go back</a>
      </div>
      <div style="margin-top:22px;padding-top:16px;border-top:1px solid var(--cp-line);font-size:11px;color:#94a3b8">
        Powered by <strong style="color:#059669">Cashpe</strong> · Payments are verified automatically
      </div>
    </div>
  </div>
</div>
<script src="../js/jquery.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../assets/dist/js/vendor.js"></script>
<script src="../assets/dist/js/adminx.js"></script>
<script src="../assets/dist/js/custom-new.js?<?=time()?>"></script>
<script src="../assets/dist/js/qrcode.min.js"></script>
<script>upiCountdown("upitimer",005,00,'<?php echo $callback_url; ?>?results=time out');</script>
</body>
</html>
