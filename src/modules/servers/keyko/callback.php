<?php

include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/modulefunctions.php");
include("common.php");

$orderid=$_POST['orderid'];
if(!empty($orderid)){
	$table = "mod_keyko_orders";
	$fields = "whmcs_order_id";
	$where = array("keyko_order_id"=>$orderid,'emailsent'=>'0');
	$result = select_query($table,$fields,$where);
	$data = mysql_fetch_array($result);
	if ( empty($data) || empty($data['whmcs_order_id'])) { 
	echo 'WHMCS Order ID not found';
	return;
	}
	$whmcs_order_id = $data['whmcs_order_id'];
	$keyko_config=keyko_getconfig();
	//get the status
	
	$postfields=array(
			'id'=>$orderid 
		);
	$api_data=keyko_api_call($keyko_config['apikey'],$keyko_config['secretekey'],'order','status',$postfields);
	$api_data=json_decode($api_data);
	if($api_data->code=='200' && $api_data->data->status=='success'){
		if($api_data->data->statusis=='Initial'){
			$configlink=$api_data->data->item->configlink;
			
			//send it
			$messagename = 'Your SSL Certificate - Additional Steps Required';
			$relid = $whmcs_order_id;
			$extravars = array( 'ssl_configuration_link' => $configlink);
			sendMessage($messagename,$relid,$extravars);
			echo 'Message Sent';
			//update database			
			$table = "mod_keyko_orders";
			$update = array("emailsent"=>"1");
			$where = array("keyko_order_id"=>$orderid,"emailsent"=>"0");
			update_query($table,$update,$where);
		}elseif($api_data->data->statusis=='Pending' || $api_data->data->statusis=='Active'){
			pdate_query( 'tblsslorders', array( 'status' => $api_data->data->statusis), array( 'remoteid' => $orderid ) );
		}else{
			echo 'Unkown Status';
		}
	}else{
		echo 'Status not obtained';
	}
	
	
}

?>