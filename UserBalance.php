<?php
session_start();
session_regenerate_id();
require_once('system/function.php');
$uid = isset($_SESSION['uid']) ? $_SESSION['uid'] : '';
$usql = sql_query("SELECT * FROM `tb_partner` WHERE id='".$uid."' ");
$userdata = sql_fetch_array($usql);

if(empty($_SESSION['uid']) || !$userdata || $userdata['status']!='active'){
redirect('Logout','0');	
}
$arr = array("status"=>'SUCCESS', "balance"=>moneyformat($userdata['balance']));

echo json_encode($arr);
?>