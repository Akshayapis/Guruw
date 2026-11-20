<?php
session_start();
session_regenerate_id();
require_once('system/function.php');
$uid = isset($_SESSION['uid']) ? $_SESSION['uid'] : '';
$usql = sql_query("SELECT * FROM `tb_partner` WHERE id='".$uid."' ");
$userdata = sql_fetch_array($usql);

$plan_slab = sql_fetch_array(sql_query("SELECT * FROM `tb_slab` WHERE id='".$userdata['slab']."' "));

if(empty($_SESSION['uid']) || !$userdata || $userdata['status']!='active'){
redirect('Logout','0');	
exit();
}
// Check expired or not
$filename = basename($_SERVER['SCRIPT_NAME']);
if(in_array(strtolower($filename), array('plans.php', 'plans', 'dashboard.php', 'dashboard'), true)){
}else{
    $exp_date = $userdata['expire_date'];
    if(getExpiredays(date('d-m-Y H:i:s'),$exp_date) < 0){
        redirect('plans.php',0);
    }
}
$cpInitials = strtoupper(substr((string)(isset($userdata['company_name']) ? $userdata['company_name'] : 'C'), 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="theme-color" content="#059669">
  <title><?php echo htmlspecialchars(webdata('company_name')); ?> | Partner Dashboard</title>
  <link rel="icon" href="images/cashpe-icon.svg" type="image/svg+xml">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="assets/dist/css/adminx.css" media="screen">
  <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/cashpe.css?v=20261120r3">
  <script src="assets/dist/js/historry.js"></script>
  <script src="assets/dist/js/sweetalert.min.js"></script>
</head>
<body>
<div class="adminx-container">
  <nav class="navbar navbar-expand justify-content-between fixed-top">
    <div class="navbar-brand mb-0 ml-2 d-flex align-items-center">
      <a class="brand" href="Dashboard"><img src="images/cashpe-logo-inverse.svg" alt="Cashpe"></a>
    </div>
    <ul class="navbar-nav flex-row align-items-center mr-3">
      <li class="nav-item d-none d-sm-block">
        <span class="nav-user mr-4"><span class="cp-pill"><i class="fas fa-circle" style="font-size:7px"></i> Account Active</span></span>
      </li>
      <li class="nav-item">
        <span class="nav-user mr-3" title="<?php echo htmlspecialchars($userdata['company_name']); ?>">
          <span style="width:32px;height:32px;border-radius:50%;background:#a7f3d0;color:#065f46;display:grid;place-items:center;font-size:11px;font-weight:800"><?php echo htmlspecialchars($cpInitials); ?></span>
          <span class="d-none d-md-inline"><?php echo htmlspecialchars($userdata['company_name']); ?></span>
        </span>
      </li>
      <li class="nav-item"><a href="Logout" class="text-white" title="Log out"><i class="fas fa-sign-out-alt"></i></a></li>
      <li class="nav-item"><button class="cp-sidebar-toggle" type="button" aria-label="Open navigation" aria-controls="cp-sidebar" aria-expanded="false">☰ <span>Menu</span></button></li>
    </ul>
  </nav>

  <div class="adminx-sidebar expand-hover push" id="cp-sidebar">
    <ul class="sidebar-nav">
      <div class="sidebar-group-label">Overview</div>
      <li class="sidebar-nav-item"><a href="Dashboard" class="sidebar-nav-link"><span class="sidebar-nav-icon"><i class="fas fa-th-large"></i></span><span class="sidebar-nav-name">Dashboard</span></a></li>
      <li class="sidebar-nav-item"><a href="plans" class="sidebar-nav-link"><span class="sidebar-nav-icon"><i class="fas fa-gem"></i></span><span class="sidebar-nav-name">Plans &amp; Billing</span></a></li>

      <div class="sidebar-group-label">Collections</div>
      <li class="sidebar-nav-item"><a href="PaytmBusiness" class="sidebar-nav-link"><span class="sidebar-nav-icon"><i class="fas fa-robot"></i></span><span class="sidebar-nav-name">Paytm Business <span class="badge">Robotics</span></span></a></li>
      <li class="sidebar-nav-item"><a href="PaytmReports" class="sidebar-nav-link"><span class="sidebar-nav-icon"><i class="fas fa-wallet"></i></span><span class="sidebar-nav-name">Paytm Reports</span></a></li>
      <li class="sidebar-nav-item"><a href="UPITransactions" class="sidebar-nav-link"><span class="sidebar-nav-icon"><i class="fas fa-exchange-alt"></i></span><span class="sidebar-nav-name">UPI Reports</span></a></li>
      <li class="sidebar-nav-item"><a href="Transactions" class="sidebar-nav-link"><span class="sidebar-nav-icon"><i class="fas fa-list-alt"></i></span><span class="sidebar-nav-name">Transactions</span></a></li>
      <li class="sidebar-nav-item"><a href="Settlement" class="sidebar-nav-link"><span class="sidebar-nav-icon"><i class="fas fa-university"></i></span><span class="sidebar-nav-name">Settlements</span></a></li>

      <div class="sidebar-group-label">UPI &amp; QR</div>
      <li class="sidebar-nav-item"><a href="UPIsSettings" class="sidebar-nav-link"><span class="sidebar-nav-icon"><i class="fas fa-cog"></i></span><span class="sidebar-nav-name">UPI Settings</span></a></li>
      <li class="sidebar-nav-item"><a href="MyQRCode" class="sidebar-nav-link"><span class="sidebar-nav-icon"><i class="fas fa-qrcode"></i></span><span class="sidebar-nav-name">My QR Code</span></a></li>
      <li class="sidebar-nav-item"><a href="UPIsAccounts" class="sidebar-nav-link"><span class="sidebar-nav-icon"><i class="fas fa-at"></i></span><span class="sidebar-nav-name">UPI Accounts</span></a></li>

      <div class="sidebar-group-label">Account</div>
      <li class="sidebar-nav-item"><a href="Developer" class="sidebar-nav-link"><span class="sidebar-nav-icon"><i class="fas fa-code"></i></span><span class="sidebar-nav-name">Developer API</span></a></li>
      <li class="sidebar-nav-item"><a href="Settings" class="sidebar-nav-link"><span class="sidebar-nav-icon"><i class="fas fa-cogs"></i></span><span class="sidebar-nav-name">Settings</span></a></li>
    </ul>
  </div>
