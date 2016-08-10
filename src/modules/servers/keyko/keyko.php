<?php
// for any support please contact support@ssltrust.com.au


global $CONFIG;
require_once('common.php');

function keyko_ConfigOptions() {


//Email Template
    $result = select_query( 'tblemailtemplates', 'COUNT(*)', array( 'name' => 'Your SSL Certificate - Additional Steps Required' ) );
    $data = mysql_fetch_array( $result );
    if (!$data[0]) {
        full_query( 'INSERT INTO `tblemailtemplates` (`type` ,`name` ,`subject` ,`message` ,`fromname` ,`fromemail` ,`disabled` ,`custom` ,`language` ,`copyto` ,`plaintext` )VALUES (\'product\', \'Your SSL Certificate - Additional Steps Required\', \'Your SSL Certificate - Additional Steps Required\', \'<p>Dear {$client_name},</p><p>You’ve successfully completed the purchasing process for an SSL Certificate! Your SSL still requires a few more steps which can be easily done at the following URL:</p><p>{$ssl_configuration_link}</p><p>If you experience any problems or have any questions throughout the process, please feel free to open a support ticket, we know all the ins and outs of SSL and can quickly help you with any issues. Thank you for trusting us with your web security needs.</p><p>{$signature}</p>\', \'\', \'\', \'\', \'\', \'\', \'\', \'0\')' );
    }


	$keyko_config=keyko_getconfig();
	if(empty($keyko_config)){
		return;	
	}
	$api_data=keyko_api_call($keyko_config['apikey'],$keyko_config['secretekey'],'product','getlist');
	$api_data=json_decode($api_data);
	if($api_data->code=='200' && $api_data->data->getlist=='success'){
		$products=$api_data->data->products[0];
		foreach ($products as $product)
        {
			 $certcode = $product->name . " #" . $product->id;
             $certtypelist .= $certcode . ',';
		}
	}else{
			$certtypelist = 'Error getting product list';	
	}
     $configarray = array('Certificate Type' => array( 'Type' => 'dropdown', 'Options' => $certtypelist ), 'Months' => array( 'Type' => 'dropdown', 'Options' => 'default,1,3,6,12,24,36' ),'Sandbox' => array( 'Type' => 'yesno' ));
    return $configarray;

	
}

function keyko_CreateAccount($params) {
	$keyko_config=keyko_getconfig();
	if(empty($keyko_config)){
		return 'Error: Please check keyko module settings';	
	}
	
	
	$result = select_query( 'tblsslorders', 'COUNT(*)', array( 'serviceid' => $params['serviceid'] ) );
    $data = mysql_fetch_array( $result );

    if ($data[0]) {
        return 'Sorry an order already exists with this Service ID';
    }
	
	$certtype=$params['configoption1'];
	$certmonths = ($params['configoptions']['Months'] ? $params['configoptions']['Months'] : $params['configoption2']);
	$sandbox = $params['configoption3'];
	//
	
	$certx = explode('#',$certtype);
    $certcode = $certx[1];
    $certname = $certx[0];
	if($sandbox=='on'){
		$postfields=array(
			'prod'=>$certcode,
			'sandbox'=>'yes',
			'months'=>$certmonths,
			'callback'=>urlencode($keyko_config['callbackurl'])
		);
	}else{
		$postfields=array(
			'prod'=>$certcode,
			'months'=>$certmonths,
			'callback'=>urlencode($keyko_config['callbackurl'])
		);
	}
	$api_data=keyko_api_call($keyko_config['apikey'],$keyko_config['secretekey'],'order','newssl',$postfields);
	$api_data=json_decode($api_data);
	
	if($api_data->code=='200' && $api_data->data->newssl=='success'){
			//inset into WHMCS table
			$sslorderid = insert_query( 'tblsslorders', array( 'userid' => $params['clientsdetails']['userid'], 'serviceid' => $params['serviceid'], 'remoteid' => $api_data->data->orderid, 'module' => 'keyko', 'certtype' => $certtype, 'status' => 'Pending', 'configdata'=>'') );
			//insert into keyko tabke
			$keykoorderid = insert_query( 'mod_keyko_orders', array( 'whmcs_order_id' => $params['serviceid'], 'whmcs_order_item_id' => $params['serviceid'], 'keyko_order_id' => $api_data->data->orderid));
			
			if(!empty($api_data->data->config)){ //all payed for and good to go
				//send it
				$messagename = 'Your SSL Certificate - Additional Steps Required';
				$relid = $params['serviceid'];
				$extravars = array( 'ssl_configuration_link' => $api_data->data->config);
				sendMessage($messagename,$relid,$extravars);
				
				//update database			
				$table = "mod_keyko_orders";
				$update = array("emailsent"=>"1");
				$where = array("keyko_order_id"=>$api_data->data->orderid,"emailsent"=>"0");
				update_query($table,$update,$where);
				
			}
			
			
		return 'success';
	}else{
		 return 'Error creating product with supplier.';
	}
	
	
	
}


function keyko_ClientArea($params) {
	$keyko_config=keyko_getconfig();
	if(empty($keyko_config)){
		return 'Error: Please check keyko module settings';	
	}
	
	$result = select_query( 'tblsslorders', '', array( 'serviceid' => $params['serviceid'] ) );
    $data = mysql_fetch_array( $result );
	
	$id = $data['id'];
    $orderid = $data['orderid'];
    $serviceid = $data['serviceid'];
    $remoteid = $data['remoteid'];
    $module = $data['module'];
    $certtype = $data['certtype'];
    $domain = $data['domain'];
    $provisiondate = $data['provisiondate'];
    $completiondate = $data['completiondate'];
    $status = $data['status'];
	
	
	//get fresh status
	$postfields=array(
			'id'=>$remoteid 
		);
	$api_data=keyko_api_call($keyko_config['apikey'],$keyko_config['secretekey'],'order','status',$postfields);
	$api_data=json_decode($api_data);
	
	
	
	if($api_data->code=='200' && $api_data->data->status=='success'){
		if($api_data->data->statusis=='Initial'){
		
		$status = '<span class="label active">'.$api_data->data->minorstatus.'</span><br /><br /><a target="_blank" href="' . $api_data->data->item->configlink . '" class="btn btn-primary">Configure Now</a>';
                $output = '<div align="left">
				<h4 style="text-align:center;padding:10px;width:100%;">Product Details</h4>
                <table width="100%" border="0" cellpadding="10" cellspacing="2" class="sslstatustable">
                <tr><td class="fieldarea">Status:</td><td>' . $status . '</td></tr>
				<tr><td width="150" class="fieldarea">Vendor ID:</td><td>' . $api_data->data->item->vendorid . '</td></tr>
                </table>
                </div>';


					//	$output.='<h4 style="text-align:center;padding:10px;width:100%;">AutoInstall SSL</h4>
					//	<table width="100%" border="0" cellpadding="10" cellspacing="2" class="sslstatustable">
					//	<tr><td width="150" class="fieldarea">Token Code:</td><td>' . $api_data->data->item->token_code . '</td></tr>
					//	<tr><td width="150" class="fieldarea">Token ID:</td><td>' . $api_data->data->item->token_id . '</td></tr>
					//	</table>';


                return $output;
		}elseif($api_data->data->statusis=='Pending'){
			
			 $output = '<div align="left">
			<h4 style="text-align:center;padding:10px;width:100%;">Product Details</h4>
            <table width="100%" border="0" cellpadding="10" cellspacing="2" class="sslstatustable">
            <tr><td class="fieldarea">Status:</td><td><span class="label active">Processing</span></td></tr>
			<tr><td width="150" class="fieldarea">Vendor ID:</td><td>' . $api_data->data->item->vendorid . '</td></tr>
            <tr><td width="150" class="fieldarea">Provisioning Date:</td><td>' . $api_data->data->item->provision_date . '</td></tr>
            <tr><td width="150" class="fieldarea">Expiry Date:</td><td>' . $api_data->data->item->expiry_date . '</td></tr>
            <tr><td width="150" class="fieldarea">Minor Status:</td><td>' .$api_data->data->minorstatus. '</td></tr>
            <tr><td width="150" class="fieldarea">Domans:</td><td>' . $api_data->data->item->common_name . '<br/>'.$api_data->data->item->dns_names . '</td></tr>
			<tr><td width="150" class="fieldarea">Site Seal:</td><td><a target="_blank" href='.$api_data->data->item->site_seal.'>'.$api_data->data->item->site_seal.'</a></td></tr>
            </table>
            </div>';
			
					//	$output.='<h4 style="text-align:center;padding:10px;width:100%;">AutoInstall SSL</h4>
					//	<table width="100%" border="0" cellpadding="10" cellspacing="2" class="sslstatustable">
					//	<tr><td width="150" class="fieldarea">Token Code:</td><td>' . $api_data->data->item->token_code . '</td></tr>
					//	<tr><td width="150" class="fieldarea">Token ID:</td><td>' . $api_data->data->item->token_id . '</td></tr>
					//	</table>';

            $code = '
                <form method="post" style="display: inline-block;margin-top: 10px;">
                <input type="hidden" name="id" value="' . $serviceid . '" />
                <input type="hidden" name="modop" value="custom" />
                <input type="hidden" name="a" value="resendemail" />
                <input type="submit" class="btn btn-primary" value="Resend Approver Email" />
                </form>
				<form method="post" style="display: inline-block;margin-top: 10px;">
                <input type="submit" class="btn btn-primary" name="listemail" value="Change Approval Email" />
                </form>';

                if($_POST['listemail']!=''){
					
                    $approvedemaillist = keyko_listapproveremail($params);
					if(!empty($approvedemaillist)){
						

						$array = $approvedemaillist;
						$code1 = createRadio('email',$approvedemaillist->emails,$approvedemaillist->approveremail);
						$code2= '<div style="text-align:left;padding: 0px 0px 0px 180px;"><form method="post" style="display: inline-block;margin-top: 10px;">'.$code1.'<input type="hidden" name="id" value="' . $serviceid . '" />
					<input type="hidden" name="modop" value="custom" />
					<input type="hidden" name="a" value="changeapproveremail" />
					<input type="submit" class="btn btn-primary" value="Save" onclick="" /></form></div>';
					}
                }

                $total = $output . $code . $code2;
                return $total;
			
			
			
			
			///
			
		}elseif($api_data->data->statusis=='Active'){
		$output = '<div align="left">
			<h4 style="text-align:center;padding:10px;width:100%;">Product Details</h4>
            <table width="100%" border="0" cellpadding="10" cellspacing="2" class="sslstatustable">
            <tr><td class="fieldarea">Status:</td><td><span class="label active">Active</span></td></tr>
			<tr><td width="150" class="fieldarea">Vendor ID:</td><td>' . $api_data->data->item->vendorid . '</td></tr>
            <tr><td width="150" class="fieldarea">Provisioning Date:</td><td>' . $api_data->data->item->provision_date . '</td></tr>
            <tr><td width="150" class="fieldarea">Expiry Date:</td><td>' . $api_data->data->item->expiry_date . '</td></tr>
            <tr><td width="150" class="fieldarea">Minor Status:</td><td>' .$api_data->data->minorstatus. '</td></tr>
            <tr><td width="150" class="fieldarea">Domans:</td><td>' . $api_data->data->item->common_name . '<br/>'.$api_data->data->item->dns_names . '</td></tr>
			<tr><td width="150" class="fieldarea">Site Seal:</td><td><a target="_blank" href='.$api_data->data->item->site_seal.'>'.$api_data->data->item->site_seal.'</a></td></tr>
            </table>
            </div>';
			
			
					//	$output.='<h4 style="text-align:center;padding:10px;width:100%;">AutoInstall SSL</h4>
					//	<table width="100%" border="0" cellpadding="10" cellspacing="2" class="sslstatustable">
					//	<tr><td width="150" class="fieldarea">Token Code:</td><td>' . $api_data->data->item->token_code . '</td></tr>
					//	<tr><td width="150" class="fieldarea">Token ID:</td><td>' . $api_data->data->item->token_id . '</td></tr>
					//	</table>';
			

            $code = '
                <form method="post" style="display: inline-block;margin-top: 10px;">
                <input type="hidden" name="id" value="' . $serviceid . '" />
                <input type="hidden" name="modop" value="custom" />
                <input type="hidden" name="a" value="downloadcert" />
                <input type="submit" class="btn btn-primary" value="Download Certificare" />
                </form>
                <form method="post" style="display: inline-block;margin-top: 10px;">
                <input type="hidden" name="id" value="' . $serviceid . '" />
                <input type="hidden" name="modop" value="custom" />
                <input type="hidden" name="a" value="reissuecert" />
                <input type="submit" class="btn btn-primary" value="Reissue Certificate" />
                </form>';

                $total = $output . $code;
                return $total;	
			
		}elseif($api_data->data->statusis=='Cancelled'){
			$output = '<div align="left">
            <table width="100%" border="0" cellpadding="10" cellspacing="2" class="sslstatustable">
            <tr><td class="fieldarea">Status:</td><td><span class="label">Cancelled</span></td></tr>
			<tr><td width="150" class="fieldarea">Vendor ID:</td><td>' . $api_data->data->item->vendorid . '</td></tr>
            </table>
            </div>';
            return $output;
			
		}elseif($api_data->data->statusis=='Pending Vendor Assignment' || $api_data->data->statusis=='UnPaid - Awaiting Payment'){
			$output = '<div align="left">
				<h4 style="text-align:center;padding:10px;width:100%;">Product Details</h4>
                <table width="100%" border="0" cellpadding="10" cellspacing="2" class="sslstatustable">
                <tr><td class="fieldarea">Status:</td><td>Pending</td></tr>
				<tr><td width="150" class="fieldarea">Vendor ID:</td><td>Not yet assigned</td></tr>
                </table>
                </div>';
                return $output;
			
		}
	}else{
		return 'Error getting Product status, please try again later.';	
	}
	
}



function keyko_listapproveremail($params) {
	$keyko_config=keyko_getconfig();
	$serviceid = $params['serviceid'];
    $result = select_query( 'tblsslorders', '', array( 'serviceid' => $params['serviceid'] ) );
    $data = mysql_fetch_array( $result );
    $remoteid = $data['remoteid'];
	//get fresh status
	$postfields=array(
			'id'=>$remoteid 
		);
	$api_data=keyko_api_call($keyko_config['apikey'],$keyko_config['secretekey'],'order','listemails',$postfields);
	$api_data=json_decode($api_data);
	if($api_data->code=='200' && $api_data->data->status=='success'){
		return $api_data->data;
	}
}

function keyko_ClientAreaCustomButtonArray()
	{
    return $buttonarray = array('Resend Approval Email' => 'resendemail','Change Approval Email' => 'listapproveremail','Re-issue Certificate' => 'reissuecert','Save' => 'changeapproveremail', 'Download Certificate'=>'downloadcert' );
	}


function keyko_changeapproveremail($params) {
	if(empty($_POST['email'])){
		return 'error: please select an email to change to';
	}
    $serviceid = $params['serviceid'];
    $result = select_query( 'tblsslorders', '', array( 'serviceid' => $params['serviceid'] ) );
    $data = mysql_fetch_array( $result );
    $remoteid = $data['remoteid'];
	$newemail=$_POST['email'];
	$keyko_config=keyko_getconfig();
	$postfields=array(
			'id'=>$remoteid ,
			'email'=>$_POST['email']
		);
	$api_data=keyko_api_call($keyko_config['apikey'],$keyko_config['secretekey'],'order','changeemail',$postfields);
	$api_data=json_decode($api_data);
	if($api_data->code=='200' && $api_data->data->status=='success'){
		return 'success';
	}else{
		return 'error: unable to change email';
	}
	
    
}

function keyko_resendemail($params) {
	$serviceid = $params['serviceid'];
    $result = select_query( 'tblsslorders', '', array( 'serviceid' => $params['serviceid'] ) );
    $data = mysql_fetch_array( $result );
    $remoteid = $data['remoteid'];
	$keyko_config=keyko_getconfig();
	$postfields=array(
			'id'=>$remoteid ,
		);
	$api_data=keyko_api_call($keyko_config['apikey'],$keyko_config['secretekey'],'order','resendemail',$postfields);
	$api_data=json_decode($api_data);
	if($api_data->code=='200' && $api_data->data->status=='success'){
		return 'success';
	}else{
		return 'error: unable to resend email';
	}
	
}

function keyko_reissuecert($params) {
	
	$serviceid = $params['serviceid'];
    $result = select_query( 'tblsslorders', '', array( 'serviceid' => $params['serviceid'] ) );
    $data = mysql_fetch_array( $result );
    $remoteid = $data['remoteid'];
	$keyko_config=keyko_getconfig();
	$postfields=array(
			'id'=>$remoteid ,
		);
	$api_data=keyko_api_call($keyko_config['apikey'],$keyko_config['secretekey'],'order','reissue',$postfields);
	$api_data=json_decode($api_data);
	if($api_data->code=='200' && $api_data->data->status=='success'){
        header('Location:'.$api_data->data->url.''); die();
	}else{
	return 'error getting new reissue link';	
	}
	
	
}


function keyko_downloadcert($params)
{
	$serviceid = $params['serviceid'];
    $result = select_query( 'tblsslorders', '', array( 'serviceid' => $params['serviceid'] ) );
    $data = mysql_fetch_array( $result );
    $remoteid = $data['remoteid'];
	$keyko_config=keyko_getconfig();
	$postfields=array(
			'id'=>$remoteid ,
		);
	$api_data=keyko_api_call($keyko_config['apikey'],$keyko_config['secretekey'],'order','download',$postfields);
	$api_data=json_decode($api_data);
	if($api_data->code=='200' && $api_data->data->status=='success'){
		
		$certdecoded = base64_decode($api_data->data->data);
        $filename = $api_data->data->filename;
        header('Content-type:application/octet-stream');
        header('Content-Disposition:attachment; filename=' . $filename);
        echo $certdecoded;
        return 'Success';
		
	}
}

function createRadio($name,$options,$default){
    $name = htmlentities($name);
    $html = '';

    foreach($options as $value=>$label){
        $value = htmlentities($value);
        $html .= '<input type="radio" ';
        if($label == $default){
            $html .= ' checked="checked" ';
        };
        $html .= ' name="'.$name.'" value="'.$label.'" />'.$label.'<br />'."\n";
    };
    return $html;
}
?>