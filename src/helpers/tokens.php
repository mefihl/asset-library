<?php
// Token format: <base64-encoded json-encoded data>&<base64-encoded id (composed of raw random bytes)>|<base64-encoded time>&<base64-encoded hmac>

class Tokens
{
  var $c;
  public function __construct($c)
  {
    $this->c = $c;
  }

  function sign_token($token) {
    return hash_hmac('sha256', $token, $this->c->settings['auth']['secret'], true);
  }

  public function generate($data)
  {
    $token_data = json_encode($data);

    $token_id = openssl_random_pseudo_bytes(8);
    $token_time = time();

    $token_payload = base64_encode($token_data) . '&' . base64_encode($token_id) . '|' . base64_encode($token_time);
    $token = $token_payload . '&' . base64_encode($this->sign_token($token_payload));

    return $token;
  }

  public function validate($token)
  {
    $token_parts = explode('&', $token);
    if(count($token_parts) != 3) {
      return false;
    }

    $token_data = json_decode(base64_decode($token_parts[0]));
    $token_time = base64_decode(explode('|', $token_parts[1])[1]);
    $token_signature = base64_decode($token_parts[2]);

    $token_payload = $token_parts[0] . '&' . $token_parts[1];

    if($token_signature !== $this->sign_token($token_payload) || time() > $token_time + $this->c->settings['auth']['tokenExpirationTime']) {
      return false;
    }

    return $token_data;
  }
}

