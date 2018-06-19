<?php


function NexoDeriveKeymaterial($passphrase) {
  $outlen = 80;
  $salt =  "AdyenNexoV1Salt";
  $rounds = 4000;
  $bytes = openssl_pbkdf2($passphrase, $salt, $outlen, $rounds, "sha1");

  $hmac_key = substr($bytes, 0, 32);
  $cipher_key = substr($bytes, 32, 32);
  $iv = substr($bytes, 64, 16);

  return array('hmac_key' => $hmac_key, 'cipher_key' => $cipher_key, 'iv' => $iv);
}
function NexoSender($message, $keyid, $keyversion, $keymaterial) {
  $jsonin = json_decode($message, true);
  $isrequest = isset($jsonin['SaleToPOIRequest']);
  $bodykey = $isrequest ? 'SaleToPOIRequest' : 'SaleToPOIResponse';
  $body = $jsonin[$bodykey];

  $header = $body['MessageHeader'];
  
  // Encrypt the original message and compute its hmac
  $nonce = openssl_random_pseudo_bytes(16);
  $nexoblob = NexoEncrypt($message, $keymaterial, $nonce);
  $hmac = NexoHMAC($message, $keymaterial);

  $trailer = NexoTrailer($keyid, $keyversion, $nonce, $hmac);

  // result has three parts: header, blob and trailer
  $result = array('MessageHeader' => $header, 'NexoBlob' => base64_encode($nexoblob), 'SecurityTrailer' => $trailer);

  return json_encode(array($bodykey => $result), JSON_PRETTY_PRINT);
}

function NexoEncrypt($message, $keymaterial, $nonce) {
  $realiv = XorBytes($keymaterial['iv'], $nonce);
  return openssl_encrypt($message, "AES-256-CBC", $keymaterial['cipher_key'], OPENSSL_RAW_DATA, $realiv);
}

function NexoHMAC($message, $keymaterial) {
  return hash_hmac("sha256", $message, $keymaterial['hmac_key'], true);
}

function NexoTrailer($keyid, $keyversion, $nonce, $hmac) {
  return array('KeyVersion' => $keyversion,
    'KeyIdentifier' => $keyid,
    'Hmac' => base64_encode($hmac),
    'Nonce' => base64_encode($nonce),
    'AdyenCryptoVersion' => 1);
}

function XorBytes($a, $b) {
  $r = $a;
  for ($i = 0; $i < 16; $i++) {
    $r[$i] = $r[$i] ^ $b[$i];
  }
  return $r;
}
function NexoReceiver($message) {
  // Warning: almost all validation is missing!
  // Parse the incoming message and decompose it
  $jsonin = json_decode($message, true);
  $isrequest = isset($jsonin['SaleToPOIRequest']);
  $bodykey = $isrequest ? 'SaleToPOIRequest' : 'SaleToPOIResponse';
  $body = $jsonin[$bodykey];
  $blob = $body['NexoBlob'];
  $header = $body['MessageHeader'];
  $trailer = $body['SecurityTrailer'];

  // Get the information from the SecurityTrailer
  if ($trailer['AdyenCryptoVersion'] != 1) {
    return null;
  }
  $keymaterial = NexoLookupKeybyIdAndVersion($trailer['KeyIdentifier'], $trailer['KeyVersion']);
  $nonce = base64_decode($trailer['Nonce']);
  $hmac = base64_decode($trailer['Hmac']);

  // Decrypt the blob
  $nexoblob = base64_decode($body['NexoBlob']);
  $decrypted = NexoDecrypt($nexoblob, $keymaterial, $nonce);

  // Validate the received hmac against the computed hmac
  $computed_hmac = NexoHMac($decrypted, $keymaterial);
  if ($computed_hmac != $hmac) {
    return null;
  }

  // Make sure the plaintext header and the header in the decrypted message match
  $decrypted_json = json_decode($decrypted, true);
  if ($decrypted_json[$bodykey]['MessageHeader'] !== $header) {
    return null;
  }
  return $decrypted;
}
function NexoDecrypt($message, $keymaterial, $nonce) {
  $realiv = XorBytes($keymaterial['iv'], $nonce);
  return openssl_decrypt($message, "AES-256-CBC", $keymaterial['cipher_key'], OPENSSL_RAW_DATA, $realiv);
}

function NexoLookupKeybyIdAndVersion($keyid, $keyversion) {
  // Actually, this function should do a lookup based on key id and version.
  // But for demonstration purposes we just return the derived keymaterial for
  // the given test passphrase.
  return NexoDeriveKeyMaterial("mysupersecretpassphrase");
}

function _format_json($json, $html = false) {
    
    $tabcount = 0; 
    $result = ''; 
    $inquote = false; 
    $ignorenext = false; 

    if ($html) { 
        $tab = "&nbsp;&nbsp;&nbsp;"; 
        $newline = "<br/>"; 
    } else { 
        $tab = "\t"; 
        $newline = "\n"; 
    } 

    for($i = 0; $i < strlen($json); $i++) { 
    $char = $json[$i]; 

        if ($ignorenext) { 
            $result .= $char; 
            $ignorenext = false; 
        } else { 
            switch($char) { 
                case '{': 
                    $tabcount++; 
                    $result .= $char . $newline . str_repeat($tab, $tabcount); 
                    break; 
                case '}': 
                    $tabcount--; 
                    $result = trim($result) . $newline . str_repeat($tab, $tabcount) . $char; 
                    break; 
                case ',': 
                    $result .= $char . $newline . str_repeat($tab, $tabcount); 
                    break; 
                case '"': 
                    $inquote = !$inquote; 
                    $result .= $char; 
                    break; 
                case '\\': 
                    if ($inquote) $ignorenext = true; 
                    $result .= $char; 
                    break; 
                default: 
                    $result .= $char; 
            } 
        } 
    } 

    return $result; 
}
?>
