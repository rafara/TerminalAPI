<html>
<form action="/POS" method="get">
  <input type='submit' value='Back' />
</form>
<?php
 
  include 'extras.php';

  header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
  header("Cache-Control: post-check=0, pre-check=0", false);
  header("Pragma: no-cache");

  $apiKey = $_POST["apiKey"];
  $cloudURL = $_POST["cloudURL"];
  $localIP = $_POST["localIP"];


  $TransactionID = $_POST["TransactionID"]; 
  $ServiceID = $_POST["ServiceID"];
  $SaleID = $_POST["SaleID"];

  $POIID = $_POST["POIID"];
  $TimeStamp = $_POST["TimeStamp"];


  $paymentAmount = $_POST["paymentAmount"];
  $currencyCode = $_POST["currencyCode"];
  
  $TransactionConditions = $_POST["TransactionConditions"];
  $SaleToAcquirerData = $_POST["SaleToAcquirerData"];

  $url = $cloudURL;
  $CloudAPI = $_POST["CloudAPI"];
  if (!empty($CloudAPI) && $CloudAPI == "n"){
      $url = $localIP;
      $key = '';
  }
  $encyption = $_POST["ENC"];
  
  $ch = curl_init();
  


  curl_setopt($ch, CURLOPT_URL, $url);
  
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_VERBOSE, 1);
  curl_setopt($ch, CURLOPT_HEADER, 1);

 // curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json")); 
    $key = 'x-api-key:'.$apiKey;
  curl_setopt($ch, CURLOPT_HTTPHEADER, array($key,"Content-Type:application/json")); 


 
  
    $Data = '{
      "SaleToPOIRequest": {
          "MessageHeader": {
            "ProtocolVersion":"3.0",
            "MessageClass":"Service",
            "MessageCategory":"Payment",
            "MessageType":"Request",
            "ServiceID":"'.$ServiceID.'",
            "SaleID":"'.$SaleID.'",
            "POIID":"'.$POIID.'"
          },
          "PaymentRequest": {
            "SaleData": {
              "SaleTransactionID": {
                "TransactionID": "'.$TransactionID.'",
                "TimeStamp": "'.$TimeStamp.'" 
              },
              "SaleReferenceID": "'.$TransactionID.'",
                "SaleToAcquirerData": "'.$SaleToAcquirerData.'"
            },
            "PaymentTransaction": {
              "AmountsReq": {
                "Currency": "'.$currencyCode.'",
                "RequestedAmount": '.$paymentAmount.'
              },
              "TransactionConditions": {'.$TransactionConditions.' }
            }
          }
        }
      }';


  echo '<b>Request:: </b><br/>'._format_json($Data,true);
  echo "<br/><br/><br/>";


  if (!empty($encyption) && $encyption == "y"){

    $keymaterial = NexoDeriveKeyMaterial("mysupersecretpassphrase");
    $outmessage = NexoSender($Data, 'mykey', 0, $keymaterial);
    echo '<b>Encrypted Request::</b> <br/>'._format_json($outmessage,true);
    echo "<br/><br/><br/>";
  }
  else{
     $outmessage = $Data;
  }


    curl_setopt($ch, CURLOPT_POSTFIELDS ,$outmessage);


   // $info = curl_getinfo($ch);

    $response = curl_exec($ch);

    //status
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); 

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);


    echo "=====================<br/>";
   // echo $header;
    echo "<br/><br/><br/>";
    echo '<b>Response:   </b> <br/>'._format_json($body,true);

    echo "<br/><br/><br/>";
    if (!empty($encyption) && $encyption == "y"){
      $message = NexoReceiver($body);
      echo '<b>Unencrypted Response:    </b><br/>' . _format_json($message,true);
    }

   
   // close cURL resource, and free up system resources
    curl_multi_close($ch);

?>