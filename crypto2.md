# Nexo security example PHP code

## Introduction
For communication between a sales system and a terminal the Nexo protocol uses a security mechanism that
ensure both authenticity and confidentiality of the messages exchanged.

Before being able to communicate, the two parties involved will need to both know a shared key, called a passphrase.
This passphrase is variable length and is used as input to generate the fixed length key material that is used by the various algorithms.

	$keymaterial = NexoDeriveKeymaterial(passphrase);

A sender encrypts the message into a a base64 encoded string called a NexoBlob. The sender also adds other security related 
data to let the receiver know which passphrase (but not the passphrase itself!) is being used and a HMAC (authentication code) 
to prove authenticity of the message.
Lastly, an unencrypted version of the original message header is added to the new message. This header is used to route the message.
 
In general, an unsecured Nexo message looks like this:

	{
		RequestOrResponse {
			"MessageHeader": { ... },
			PayloadType: { ... }
		}
	}

After being processed by the sender code, the secured message would look like:

	{
		RequestOrResponse: {  
      		"MessageHeader": { copy of header of original message }
      		"NexoBlob": "base64 encoded encrypted original message",
      		"SecurityTrailer": { ... }
		}
	}
 
 Where 
 
	"SecurityTrailer":{  
		"KeyVersion":0,
        "KeyIdentifier":"mykey",
        "Hmac":"h6ehPJOASK4NXGESERmXo5mP9YFxpox7VoAFGIb9s8Y=",
        "Nonce":"BoBZRF2QmDlNnmeo1QYeZQ==",
        "AdyenCryptoVersion":1
      }
 
 KeyIdentifier and KeyVersion together identify the passphrase used.
 HMac is a base 64 encoded authentication code computed by the crypto code that proves the authenticiy
 of the message.
 Nonce is a random base64 encoded string, which is used for initializing the encryption function. This string is generated
 by the sender for each message to be sent.
 AdyenCryptoVersion is fixed at 1, and specifies the version of the security protocol used.
 
 A sender calls 
 
	$encapsulatedMessage = NexoSender($message, $keyid, $keyversion, $keymaterial);

To get the secured message to be sent out.

A receiver received an encapsulated message and calls

	$message = NexoReceiver($receivedmessage, $keymaterial);
	
to decrypt and validate a received message. Note that the receiver uses the same key material as the sender, since
the protocol uses a *shared key*.
 
## Key Derivation
Before being able to communicate, both the sender and the receiver need to derive the key material. Key derivation is
a mechanism to translate a variable length string into a fixed number of bytes in a secure way. The crypto protocol needs 80 bytes of key material, divided into three parts: a hmac key used by the HMAC authentication) algorithm, a cipher key used for encryption and decryption and an initialization vector to initialize the encryption and decryption algorithms.
The derived key material only changes if the passphrase changes, it does not need to be re-computed for each message. But it *is*
secret data, so care should be taking when storing it on disk.


	function NexoDeriveKeymaterial($passphrase) {
        $outlen = 80;
        $salt =  "AdyenNexoV1Salt";
        $rounds = 4000;
        $bytes = openssl_pbkdf2($passphrase, $salt, $outlen, $rounds, "sha1");

        $hmac_key = substr($bytes, 0, 32);
        $ciher_key = substr($bytes, 32, 32);
        $iv = substr($bytes, 64, 16);

        return array('hmac_key' => $hmac_key, 'cipher_key' => $cipher_key, 'iv' => $iv);
	}
	 
 ## Encryption and Authentication
See php file: NexoSender function
 
 ## Decryption and Authentication Validation
 See php file: NexoReceiver function
 
 
 
