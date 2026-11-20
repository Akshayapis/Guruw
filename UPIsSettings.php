<?php
require_once('layouts/header.php');

$account = json_decode($userdata['settle_account']);
?>

      <!-- adminx-content-aside -->
      <div class="adminx-content">
        <!-- <div class="adminx-aside">

        </div> -->

        <div class="adminx-main-content">
          <div class="container-fluid">
            <!-- BreadCrumb -->
<?php if($userdata['upi_active']>0){ ?> 
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

<div class="row mt-1">
              <div class="col">
                <div class="card mb-grid card-nblue">
                <div class="row p-4">
                <div class="col-md-4 mb-4">
				<h4 class="text-white">UPI Settings</h4><hr class="bg-white">
<form action="" method="post">
    
    <div class="form-group">
      <label class="text-white">UPI Address</label>
      <input type="text" name="upi_id" value="<?php echo get_upi_id($userdata['id'],"upi_id");?>" id="upi_id" onchange="UPIValid(this.value);" class="form-control form-control-sm" placeholder="Enter Your UPI Address" required>
    </div>
    
    <div class="form-group">
      <label class="text-white">Bank Account Name</label>
      <input type="text" name="upi_name" value="<?php echo get_upi_id($userdata['id'],"upi_name");?>" class="form-control form-control-sm" placeholder="Enter Your Bank Account Name" required>
    </div>
	
    <div class="form-group">
      <label class="text-white">IFSC Code</label>
      <input type="text" name="ifsc" value="<?php echo get_upi_id($userdata['id'],"ifsc");?>" onkeyup="icsf_valid(this.value);" class="form-control form-control-sm" placeholder="Enter Your IFSC Code" minlength="11" maxlength="11" required>
      <span id="ifsc_bank" class="text-white"></span>
    </div>
	
    <button type="submit" name="upiupdate" class="btn bg-white text-dark btn-sm"><i class="fa fa-bolt"></i> Submit</button>
<script>
function icsf_valid(ifsc){
document.getElementById("ifsc_bank").innerHTML = '';
 if(ifsc.length==11){
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
      var myObj = JSON.parse(this.responseText);
      document.getElementById("ifsc_bank").innerHTML = myObj.BANK+', '+myObj.BRANCH;
    }
  };
  xhttp.open("GET", "https://ifsc.razorpay.com/"+ifsc, true);
  xhttp.send();
  
}

}

function UPIValid(upi_id) { 
  if(isUPI(upi_id)!=true){
  alert("UPI Adress is not valid");
  document.getElementById("upi_id").value = ""; 
  }
}


function isUPI(upi) {
    return upi.endsWith('@paytm') || upi.endsWith('@sbi');
}
</script>    
</form> 
				
			  
			   
                </div>	
				
                <div class="col-md-8 mb-4">
				<h4 class="text-white">Connected Device</h4><hr class="bg-white">
<?php
$cpDeviceModel = trim((string) get_device($userdata['id'], 16));
$cpDeviceBrand = trim((string) get_device($userdata['id'], 17));
$cpAndroidId = trim((string) get_device($userdata['id'], 15));
$cpAndroidVer = trim((string) get_device($userdata['id'], 1));
$cpLastSeen = trim((string) get_device($userdata['id'], 21));
$cpDeviceName = trim($cpDeviceBrand . ' ' . $cpDeviceModel);
?>
<div class="card card-custom gutter-b">
  <div class="card-body cp-device-card">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
      <div class="d-flex align-items-center font-weight-bold" style="font-size:15px;color:#0f172a">
        <i class="fas fa-mobile-alt fa-lg text-nblue mr-2"></i>
        <?php echo $cpDeviceName !== '' ? htmlspecialchars($cpDeviceName) : 'No device paired yet'; ?>
      </div>
      <?php if ($cpDeviceModel !== ''): ?><span class="cp-badge-ok">Connected</span><?php else: ?><span class="cp-badge-off">Disconnected</span><?php endif; ?>
    </div>
    <?php if ($cpDeviceModel !== ''): ?>
    <div class="cp-device-row"><i class="fas fa-lock"></i><span>Android ID: <?php echo htmlspecialchars($cpAndroidId !== '' ? $cpAndroidId : '—'); ?></span></div>
    <div class="cp-device-row"><i class="fas fa-tools"></i><span>Android version: <?php echo htmlspecialchars($cpAndroidVer !== '' ? $cpAndroidVer : '—'); ?></span></div>
    <div class="cp-device-row"><i class="fas fa-history"></i><span>Last seen: <?php echo $cpLastSeen !== '' ? htmlspecialchars(misec_datetime($cpLastSeen, 'Y-m-d h:i:s')) : '—'; ?></span></div>
    <?php else: ?>
    <p class="m-0" style="font-size:12.5px;color:#64748b">
      Pair the Mitra Gateway Android app with this account to confirm Paytm Business notifications automatically.
      Until then your UPI ID and QR code still accept payments from every UPI app.
    </p>
    <?php endif; ?>
  </div>
</div>
<h4 class="text-white mt-3"><b>Smart Gateway works with</b></h4>
<div class="font-size-sm text-white">Your customers can pay with any UPI app — the same UPI ID and QR works everywhere.</div>
<hr class="bg-white">
<div class="card card-custom">
  <div class="card-body">
    <div class="cp-apps">
  <div class="cp-app">
    <img src="assets/img/gpay.svg" alt="Google Pay">
    <a class="cp-app-link" href="https://play.google.com/store/apps/details?id=com.google.android.apps.nbu.paisa.user" target="_blank" rel="noopener">Get app</a>
  </div>
  <div class="cp-app">
    <img src="assets/img/phonepe.svg" alt="PhonePe">
    <a class="cp-app-link" href="https://play.google.com/store/apps/details?id=com.phonepe.app" target="_blank" rel="noopener">Get app</a>
  </div>
  <div class="cp-app">
    <img src="assets/img/paytm.svg" alt="Paytm">
    <a class="cp-app-link" href="https://play.google.com/store/apps/details?id=net.one97.paytm" target="_blank" rel="noopener">Get app</a>
  </div>
  <div class="cp-app">
    <img src="assets/img/sbi.svg" alt="SBI Pay">
    <a class="cp-app-link" href="https://play.google.com/store/apps/details?id=com.sbi.upi" target="_blank" rel="noopener">Get app</a>
  </div>
  <div class="cp-app">
    <img src="assets/img/bhim_logo.svg" alt="BHIM UPI">
    <a class="cp-app-link" href="https://play.google.com/store/apps/details?id=in.org.npci.upiapp" target="_blank" rel="noopener">Get app</a>
  </div>
  <div class="cp-app">
    <img src="assets/img/amazonpay.svg" alt="Amazon Pay">
    <a class="cp-app-link" href="https://play.google.com/store/apps/details?id=in.amazon.mShop.android.shopping" target="_blank" rel="noopener">Get app</a>
  </div>
</div>
    <p class="cp-apps-note">Your clients can pay with Google Pay, PhonePe, Paytm, Freecharge UPI, BHIM UPI, SBI UPI and every other BHIM-UPI app. App names and logos belong to their respective owners.</p>
    <div class="cp-apps-mitra">
      <img src="assets/img/smartgateway.svg" alt="Mitra Gateway">
      <div>
        <strong>Mitra Gateway app</strong>
        <p>The Android helper app for auto-confirming Paytm Business notifications. This file was not included in the source archive, so no download is offered here.</p>
      </div>
    </div>
  </div>
</div>
                </div>
                
                
              </div>
            </div>

            
            
          </div>
        </div>
<?php }else{ ?>        
<div class="col-md-12 col-lg-12 d-flex mt-4">
<div class="card mb-grid w-100 card-nblue text-center">
<h2 class="mt-3 text-white"><b>Accept Payments Directly to your Bank Account</b></h2>
<h3 class="text-white"><b>At 0% Transaction Fee</b></h3>
<button class="btn btn-success btn-sm text-white" onclick="window.open('https://wa.me/91<?=support('mobile')?>?text=<?=urlencode("I'm interested in your UPI Smart Gateway")?>');">Activate UPI Smart Gateway</button>
<?php if (trim(webdata("notification")) !== ""): ?><div class="card card-custom p-2"><?=webdata("notification")?></div><?php endif; ?>
</div>
</div>        
<?php } ?>        
      </div>
<?php
require_once('layouts/footer.php');
?>