<?php
if (!defined("WHMCS"))
	die("This file cannot be accessed directly");
	
function keyko_getconfig(){
		$result = select_query( 'tbladdonmodules', '*', array( 'module' => 'keyko_admin' ) );
		$to_return=array();
		while ($data = mysql_fetch_array($result)) {
			if($data['setting']=='keykoapikey'){
				$to_return['apikey']=$data['value'];
			}elseif($data['setting']=='keykosecretekey'){
				$to_return['secretekey']=$data['value'];
			}elseif($data['setting']=='callbackurl'){
				$to_return['callbackurl']=$data['value'];
			}
		}
		return($to_return);
}

function keyko_api_call($apikey,$secretkey,$service,$method,$postfields='',$type='json',$curloptions=''){
	
	$tohash=  $service.$method;
    foreach($postfields as $key=>$value) { 
		$tohash.=$value;
	}
	$signature = hash_hmac('sha256', $tohash, $secretkey); //generate signature with your secrete key
	$url = 'https://www.ssltrust.com.au/api/v1/'.$apikey.'/'.$signature.'/'.$service .'/'.$method.'.'.$type;
	$response = curlCall($url,$postfields,$curloptions);	
	return $response;
}

?>