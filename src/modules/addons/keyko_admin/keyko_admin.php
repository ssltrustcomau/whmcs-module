<?php
// for any support please email support@ssltrust.com.au


if (!defined("WHMCS"))
die("This file cannot be accessed directly");


require_once('common.php');


function keyko_admin_config() {
	
	$result = select_query( 'tblconfiguration', '*', array( 'setting' => 'SystemSSLURL' ) );
    $data = mysql_fetch_array( $result );
	if(empty($data['value'])){
		$result = select_query( 'tblconfiguration', '*', array( 'setting' => 'SystemURL' ) );
    	$data = mysql_fetch_array( $result );
	}
	
	$defaultcallbackurl=$data['value'].'modules/servers/keyko/callback.php';
	
    $configarray = array(
    "name" => "Keyko-Admin",
    "version" => "1.0",
    "author" => "<a href='https://www.keyko.com.au' target='_blank'>keyko.com.au</a>",
    "language" => "english",
    "fields" => array(
        "keykoapikey" => array ("FriendlyName" => "API Key", "Type" => "text", "Size" => "50",
                              "Description" => "Supplied by Keyko", "Default" => "", ),
        "keykosecretekey" => array ("FriendlyName" => "Secrete Key", "Type" => "text", "Size" => "50",
                              "Description" => "Supplied by Keyko", ),
		"callbackurl" => array ("FriendlyName" => "Callback URL", "Type" => "text", "Size" => "200",
                              "Description" => "", "Default" => $defaultcallbackurl, )
    ));
    return $configarray;
}


function keyko_admin_activate() {

   # Create Custom DB Table
    $query = getActiveScript();
	foreach($query as $script)
    {
        $nobreak = str_ireplace("\n",' ',$script);
        $result = mysql_query($nobreak);
        if (!$result)
        {
            die('Couldnt activate keyko_admin because: ' . mysql_error());
        }
    }
	
    return array('status'=>'success','description'=>'Successfully Activated Keyko Module');
}

function keyko_admin_upgrade($vars) {

    $version = $vars['version'];

  //nothing yet

}

function keyko_admin_deactivate() {
	//nothing yet
}

function getActiveScript()
{
    $scripts =
        array(
        "CREATE TABLE IF NOT EXISTS  `mod_keyko_orders` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT,`whmcs_order_id` int(10) unsigned NOT NULL,`whmcs_order_item_id` int(10) unsigned NOT NULL,`keyko_order_id` varchar(50) NOT NULL,`emailsent` int(10) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `Secondary` (`whmcs_order_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
    );
    return $scripts;
}

function keyko_admin_output($vars) {
	$modulelink = $vars['modulelink'];
	echo '<h2>Products Available</h2>';
		$api_data=keyko_api_call($vars['keykoapikey'],$vars['keykosecretekey'],'product','getlist');
		$api_data=json_decode($api_data);
		if($api_data->code=='200' && $api_data->data->getlist=='success'){
			echo '<table width="100%" border="0" cellspacing="2" cellpadding="2">
';

			$products=$api_data->data->products[0];
			foreach ($products as $product)
			{
				
				echo'  <tr>
    <td style="background-color:#2162A3"><h3 style="font-size: 20px;padding: 8px;margin: 0px;"><a href="'.$product->url.'" style="color:#FFF;text-decoration: none;" target="new">'.$product->name.'</a><span style="font-size:14px;color:#FFF"> Product ID: '.$product->id.'</span></h3></td>
  </tr>
  <tr>
    <td><table border="0" cellspacing="2" cellpadding="2">';
	$titles='';
	$price='';
	foreach ($product->months as $month)
			{
				$titles.='<td align="center" style="width:120px"><strong>'.$month->months.' MONTHS</strong></td>';
				if(empty($month->price_aud)){
					$price.='<td align="center" style="width:120px">USD $'.$month->price_usd.'</td>';
				}else{
					$price.='<td align="center" style="width:120px">AUD $'.$month->price_aud.'</td>';
				}
				
				
			}
			echo'
      <tr>'.$titles.'</tr>
      <tr>'.$price.'</tr>
</table></td>
  </tr>';
				
			}
			
			
			echo'</table>';
			
		}else{
				echo 'Error getting product list - check the keyko module settings';	
		}
	
}
?>