<?php
//Simple slack webhook for ratekey explode by vmavromatis
$apiKey = "YOUR_APIKEY_HERE";
$Secret = "YOUR_SECRET_HERE";
$signature = hash("sha256", $apiKey.$Secret.time());
$endpoint = "https://api.test.hotelbeds.com/hotel-api/1.0/hotels";

define('SLACK_WEBHOOK', 'YOUR_SLACK_WEBHOOKHERE_HERE');
$text = $_POST['text'];
//$text = "20181116|20181121|W|1|229318|JSU.C3|CG-BAR-BB|BB||1~2~1|8|N@275D91AF788C4C118BD06FFD1CE2F4D42109";
$text = str_replace('"', '', $text);

$msg = array(
	"text" => "test",
 	"mrkdwn" => true
);
$c = curl_init(SLACK_WEBHOOK);
curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($c, CURLOPT_POST, true);
curl_setopt($c, CURLOPT_POSTFIELDS, array(json_encode($msg)));
curl_exec($c);
curl_close($c);
$text = explode("|", $text);

$checkin=$text[0];
$checkin = substr_replace($checkin, "-", 4, 0);
$checkin = substr_replace($checkin, "-", 7, 0);
$checkout=$text[1];
$checkout = substr_replace($checkout, "-", 4, 0);
$checkout = substr_replace($checkout, "-", 7, 0);
$hotel=$text[4];
$occupancy=$text[9];
$numberOfRooms=$occupancy[0];
$numberOfAdults=$occupancy[2];
$numberOfChildren=$occupancy[4];
$childrenAges=$text[10];
$childrenAges = explode("~", $childrenAges);

echo '*Availability Request* 
```
';
$xml = '<availabilityRQ xmlns="http://www.hotelbeds.com/schemas/messages" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
	<debug><showProviderDetails>Y</showProviderDetails></debug>
	<stay checkIn="'.$checkin.'" checkOut="'.$checkout.'" />
	<occupancies>
		<occupancy rooms="'.$numberOfRooms.'" adults="'.$numberOfAdults.'" children="'.$numberOfChildren.'">
			<paxes>';
if ($numberOfChildren>0){
	for ($i=0;$i<$numberOfChildren;$i++){
	$xml .='
				<pax type="CH" age="'.$childrenAges[$i].'"/>';
	}
}
$xml .='
			</paxes>
		</occupancy>
	</occupancies>
	<hotels>
		<hotel>'.$hotel.'</hotel>
	</hotels>
</availabilityRQ>';
echo $xml;

echo '
```
*Availability Response* 
```';

try
{	
	// Get cURL resource
	$curl = curl_init();
	// Set some options 
	curl_setopt_array($curl, array(
	CURLOPT_RETURNTRANSFER => 1,
	CURLOPT_URL => $endpoint,
	CURLOPT_POST => 1,
	CURLOPT_HTTPHEADER => ['Content-Type: application/xml', 'Accept:application/xml' , 'Api-key:'.$apiKey.'', 'X-Signature:'.$signature.''],
    CURLOPT_POSTFIELDS => $xml,
	));
	// Send the request & save response to $resp
	$resp = curl_exec($curl);

	// Check HTTP status code
	if (!curl_errno($curl)) {
		switch ($http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
			case 200:  # OK
				echo $resp;
				echo "```";
				break;
			default:
				//echo 'Unexpected HTTP code: ', $http_code, "\n";
				echo $resp;
				echo "```";
		}
	}
	
	// Close request to clear up some resources
	curl_close($curl);


} catch (Exception $ex) {

	printf("Error while sending request, reason: %s\n",$ex->getMessage());

}

?>
