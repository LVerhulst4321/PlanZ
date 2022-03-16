<?php
// Copyright (c) 2021 BC Holmes. All rights reserved. See copyright document for more details.
// These functions provide support for handling JWTs

define('__ROOT__', dirname(dirname(__FILE__))); 
require_once(__ROOT__.'/vendor/autoload.php');

use Emarref\Jwt\Claim;

function jwt_from_header() {
    if (array_key_exists('HTTP_AUTHORIZATION', $_SERVER)) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
        if (strpos($auth, 'Bearer ') === 0) {
            $auth = substr($auth, 7);
        }
        return $auth;
    } else {
        return null;
    }
}

// We expect to use two types of JWT tokens. The "basic" tokens are
// signed and valid for a long period of time, but have no scopes
// and basically represent a valid mobile app sending requests (as
// opposed to a hacker in Russia). An authenticated user (i.e. someone
// who has logged in) has a token that includes a participant scope and
// the token's subject ("sub") references the user's badgeid.
function jwt_validate_token($token, $as_participant_scope = false) {
    
    $jwt = new Emarref\Jwt\Jwt();

    $algorithm = new Emarref\Jwt\Algorithm\Hs512(JWT_TOKEN_SIGNING_KEY);
    $encryption = Emarref\Jwt\Encryption\Factory::create($algorithm);
	$context = new Emarref\Jwt\Verification\Context($encryption);

	try {
		$deserialized = $jwt->deserialize($token);

		// annoyingly, the $jwt->verify function wants to verify a specific subject.
		// so let's just call the individual verifiers.
		$verifiers = [
            new Emarref\Jwt\Verification\EncryptionVerifier($context->getEncryption(),  new Emarref\Jwt\Encoding\Base64()),
            new Emarref\Jwt\Verification\ExpirationVerifier(),
            new Emarref\Jwt\Verification\NotBeforeVerifier(),
        ];

		foreach ($verifiers as $verifier) {
            $verifier->verify($deserialized);
        }

        if ($as_participant_scope) {
            $scope = $deserialized->getPayload()->findClaimByName("scope");
            if ($scope === null || !in_array("participant", $scope->getValue())) {
                return false;
            }
        }   

		return true;
	} catch (InvalidArgumentException $e) {
		return false;
	} catch (Emarref\Jwt\Exception\VerificationException $e) {
		return false;
	}
}

function jwt_extract_badgeid($token) {

	$jwt = new Emarref\Jwt\Jwt();

	$deserialized = $jwt->deserialize($token);
	$subject = $deserialized->getPayload()->findClaimByName("sub");
	return $subject != null ? $subject->getValue() : null;
}

function jwt_create_token($badgeid, $name, $registered = false) {
    $token = new Emarref\Jwt\Token();

    // Standard claims are supported
    $token->addClaim(new Claim\Expiration(new DateTime('1 year')));
    $token->addClaim(new Claim\IssuedAt(new DateTime('now')));
    $token->addClaim(new Claim\Issuer(CON_NAME));
    $token->addClaim(new Claim\NotBefore(new DateTime('now')));
    $token->addClaim(new Claim\Subject($badgeid));
    $token->addClaim(new Claim\PublicClaim('name', $name));
    if ($registered) {
        $token->addClaim(new Claim\PublicClaim('scope', array( "participant", "registered" )));
    } else {
        $token->addClaim(new Claim\PublicClaim('scope', array( "participant" )));
    }

    $jwt = new Emarref\Jwt\Jwt();

    $algorithm = new Emarref\Jwt\Algorithm\Hs512(JWT_TOKEN_SIGNING_KEY);
    $encryption = Emarref\Jwt\Encryption\Factory::create($algorithm);
    $serializedToken = $jwt->serialize($token, $encryption);


    return $serializedToken;
}

?>