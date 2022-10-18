<?php

error_reporting(1);
ini_set('max_execution_time', 0);
use \Magento\Framework\App\Bootstrap;

require __DIR__ . '/app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get('\Magento\Framework\App\State');
$state->setAreaCode('frontend');

$moduleDirReader = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');

$username = 'i-catchers@oort.nl';
$password = 'MJxu9Uqp';
$host = 'ftp.oort.nl';
$open = "ftp://$username:$password@$host";
//$open = "ftp://i-catchers@oort.nl:MJxu9Uqp@$ftp.oort.nl";

// $path = $open . "/shipping slips TEST/";
$path = "D:/vishalxampp/htdocs/icatchers/shipping_test/";
	
if (!is_dir($path)) {
    //mkdir($path, 0777, true);
}

$orderId = 2847;
$data = [];
$order = $objectManager->create('\Magento\Sales\Model\Order')->load($orderId);
$shipmentCollection = $order->getShipmentsCollection();
$shippingAddress = $order->getShippingAddress();


$shipping_method = $order->getData('shipping_method');
$sAddress = [
	'street' => $shippingAddress->getData('street'),
	'postcode' => $shippingAddress->getData('postcode'),
	'city' => $shippingAddress->getData('city'),
	'country_id' => $shippingAddress->getData('country_id'),
	'telephone' => $shippingAddress->getData('telephone')
];

if ($shipping_method == 'storepickup_storepickup') {
	$sAddress = [
		'street' => 'Homoetsestraat 72',
		'postcode' => '4024HJ',
		'city' => 'Eck en Wiel',
		'country_id' => 'NL',
		'telephone' => '0344-699000'
	];
}

$shipmentRepositoryInterface = $objectManager->get('Magento\Sales\Api\ShipmentItemRepositoryInterface');
foreach($shipmentCollection as $shipment){
	$shipmentItem = [];
	$shippedItems = $shipment->getItemsCollection();
	$data['Opdrachtreferentie'] = $shipment->getData('increment_id');
	$data['Debiteur'] = 13515;
	$data['DebiteurNaam'] = 'I-CATCHERS';
	$data['Activiteiten']['Activiteit'] = [
		'Activiteitreferentie' => $shipment->getData('increment_id'),
		'Activiteitsoort' => 'R',
		'Losnaam' => $shippingAddress->getData('firstname'),
		'Losadres' => $sAddress['street'],
		'Lospostcode' => $sAddress['postcode'],
		'Loswoonplaats' => $sAddress['city'],
		'Lostelefoon' => $sAddress['telephone'],
		'Losland' => $sAddress['country_id'],
		'Losreferentie' => $order->getData('increment_id'),
		'Commissie' => $order->getPayment()->getData('po_number') . '/' . $order->getData('increment_id'),
		'Losemail' => $shippingAddress->getData('email'),
		'Verzendinstructie' => ($shipment->getData('customer_note')) ? $shipment->getCreatedAt() . '/' . $shipment->getData('customer_note') : $shipment->getCreatedAt()
	]; 
	
	foreach ($shippedItems as $item) {
		$qty = $item->getData('qty');
		$product = $objectManager->create('Magento\Catalog\Model\Product')->load($item->getData('product_id'));
		$gr_wt = $product->getData('gr_wt');
		$shipmentItem[] = [
			'Aantal' => $qty,
			'Modelnaam' => $item->getData('sku') . '/' . trim($item->getData('name')),
			'Colli' => $qty * trim($product->getData('number_of_boxes')),
			'AantalZe' => '',
			'Kleur' => trim($product->getData('wood_color')),
			'Gewicht' => $qty * trim(str_replace(["Kgs","KGS","kgs"],"",$gr_wt)),
			'Stof' => trim($product->getData('wood_name')),
			'Volume' => str_replace(".",",",$qty * trim(str_replace(",",".",$product->getData('cbm'))))
		];        
    }
    $data['Partijen'] = $shipmentItem;  
}

$xmlData['Opdracht'] = $data;  
// echo "<pre>"; print_r($xmlData); exit;
$domxml = new DOMDocument('1.0');
$domxml->preserveWhiteSpace = false;
$domxml->formatOutput = true;

$xml_data = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Opdrachten></Opdrachten>');
array_to_xml($xmlData,$xml_data);

$fileName = $data['Opdrachtreferentie'];
$domxml->loadXML($xml_data->asXML());
$domxml->save($path . 'shipment_' . $fileName . '.xml');

function array_to_xml( $data, &$xml_data ) {
    foreach( $data as $key => $value ) {
        if( is_array($value) ) {
            if( is_numeric($key) ){
                $key = 'Partij';
            }
            $subnode = $xml_data->addChild($key);
            array_to_xml($value, $subnode);
        } else {
            $xml_data->addChild("$key",htmlspecialchars("$value"));
        }
     }
}


/*$directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');

$sftp = $objectManager->create('Magento\Framework\Filesystem\Io\Sftp');
$open =  $sftp->open(
            array(
                'host' => 'localhost',
                'username' => 'root',
                'password' => '',
                'port' => 22,
                'passive' => true
            )
        );

$content = file_get_contents($mediaPath . 'shipment/shipment_' . $fileName . '.xml');
$sftp->write('/Import/'. 'shipment_' . $fileName . '.xml', $content);
$sftp->close();
echo "File uploaded..!";*/

echo "Done"; exit;