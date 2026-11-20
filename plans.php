<?php
require_once('layouts/header.php');

$account = json_decode($userdata['settle_account']);
if(isset($_GET['paystatus'])){
if($_GET['paystatus']=='success'){
    echo '<script>swal("Success!", "Plan Activated Successfully!", "success");</script>';
}else{
    echo '<script>swal("Alert!", "Payment failed!", "error");</script>';
}
}
if(isset($_POST['trial']) && $_POST['trial'] =='free'){
    	$start_date = date('d-m-Y H:i:s');
		$exp_date = date('d-m-Y H:i:s', strtotime($start_date. ' + 3 days'));
		if($userdata['trial_done']=='0'){
		    $usql = sql_query("UPDATE `tb_partner` SET start_date='$start_date', expire_date='$exp_date', trial_done='1'  WHERE id='".$userdata['id']."'");
		    if($usql){
		         echo '<script>swal("Success!", "Trial Activated Successfully!", "success");</script>';
		    }else{
		        echo '<script>swal("Alert!", "Update Error!", "error");</script>';
		    }
		}else{
		    echo '<script>swal("Alert!", "Already Trial taken active genuine Plan!", "error");</script>';
		}
}
if(isset($_POST['plan']) && $_POST['plan'] =='basic'){
       // get payment data from db
        
        $activeKeyData = ['key'=>'d9fe71-bfddea-d2ss1-b76n57-a2e52e','secret'=>'rDffdze','upiuid'=>'paytma@paytm'];
		$keyId = $activeKeyData['key'];
		$keySecret = $activeKeyData['secret'];
		$upiuid = $activeKeyData['upiuid'];
		$request_id = rand(1111,9999).time();
		$callback_url = webdata('socket').base_url()."/activePlan.php";
		sql_query("UPDATE  `tb_partner` SET plan_txn_id='$request_id' WHERE id='".$userdata['id']."'");
		$amount = 199;

        $client_name = $userdata['name'];
		$client_email = $userdata['email'];
		$client_mobile = $userdata['mobile'];

        $paramList["upiuid"] = $upiuid;
        $paramList["token"] = $keyId;
        $paramList["orderId"] = $request_id;
        $paramList["txnAmount"] = $amount;
        $paramList["txnNote"] = "Payment received from ".$client_name;
        $paramList["cust_Mobile"] = $client_mobile;
        $paramList["cust_Email"] = $client_email;
        $paramList["callback_url"] = $callback_url;
        require_once('system/checksum.php');
        $checkSum = CxrChecksum::generateSignature($paramList,$keySecret);
        $paramList['checksum']=$checkSum;
        
        if(isset($paramList['checksum'])){
                echo '<html>
                <head>
                <title>Gateway Check Out Page</title>
                </head>
                <body>
                	<center><h1>Please do not refresh this page...</h1></center>
                		<form method="post" action="order/paytm" name="f1">
                		<table border="1">
                			<tbody>';
                			foreach($paramList as $name => $value) {
                				echo '<input type="hidden" name="' . $name .'" value="' . $value . '">';
                			}
                echo '</tbody>
                    </table>
                		<script type="text/javascript">
                			 document.f1.submit();
                		</script>
                	</form>
                </body>
                </html>';
           
        }else{
            echo '<script>swal("Alert!", "Service is Down!", "error");</script>';
            redirect('','1500');
        }
}
?>

      <!-- adminx-content-aside -->
      <div class="adminx-content">
        <!-- <div class="adminx-aside">

        </div> -->

        <div class="adminx-main-content">
          <div class="container-fluid">
            <!-- BreadCrumb -->
<?php
if(isset($_POST['upiupdate']) && 
!empty($_POST['upi_id']) && 
!empty($_POST['upi_name']) && 
!empty($_POST['ifsc']) ){
	
$upi_id = safe_str($_POST['upi_id']);
$upi_name = safe_str($_POST['upi_name']);
$ifsc = safe_str($_POST['ifsc']);
$allow = array("paytm", "sbi");

if(isUPI($upi_id,$allow)){
$upis = json_encode(array("upi_id"=>$upi_id, "upi_name"=>$upi_name, "ifsc"=>$ifsc));

if(sql_query("UPDATE `tb_partner` SET upis='".$upis."' WHERE id='".$userdata['id']."' ")){
echo '<script>swal("Successfully!", "UPI Details Updated!", "success");</script>';
redirect('','1500');		
}else{
echo '<script>swal("Alert!", "Service is Down!", "error");</script>';
redirect('','1500');
}

}else{
echo '<script>swal("Alert!", "UPI Address is not valid, currently support '.implode(", ",$allow).'", "error");</script>';
redirect('','1500');
}

}
?>
<div class="cp-plans-header">
  <span class="cp-auth-kicker">CASHPE PLANS</span>
  <h2>Choose your plan</h2>
  <p>Activate a plan or start the free trial to access the available features.</p>
</div>
<div class="cp-plans-grid">
  <section class="cp-plan-card">
    <span class="cp-plan-label">GATEWAY PLAN</span>
    <h3>₹199 <small>/ 30 days</small></h3>
    <p>Usage limit: Unlimited</p>
    <form action="" method="post"><input name="plan" type="hidden" value="basic"><button type="submit" class="cp-btn">Activate plan</button></form>
  </section>
  <section class="cp-plan-card">
    <span class="cp-plan-label">FREE TRIAL</span>
    <h3>Free <small>/ 3 days</small></h3>
    <p>Usage limit: Unlimited</p>
    <form action="" method="post"><input name="trial" type="hidden" value="free"><button type="submit" class="cp-btn">Activate trial</button></form>
  </section>
</div>
          </div><!-- container-fluid -->
        </div><!-- adminx-main-content -->
      </div><!-- adminx-content -->
<?php
require_once('layouts/footer.php');
?>