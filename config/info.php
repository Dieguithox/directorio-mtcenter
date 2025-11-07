<?php
$info = password_get_info(password_hash('test', PASSWORD_DEFAULT));
var_dump($info); 
/* array(3) { ["algo"]=> string(2) "2y" 
["algoName"]=> string(6) "bcrypt" 
["options"]=> array(1) { 
["cost"]=> int(10) } } */

var_dump(defined('PASSWORD_ARGON2ID'));        // true = disponible
if (defined('PASSWORD_ARGON2ID')) {
  $h = password_hash('test', PASSWORD_ARGON2ID, ['memory_cost'=>1<<16,'time_cost'=>3,'threads'=>2]);
  var_dump(password_get_info($h));             // verás ["algoName"] => "argon2id"
}
phpinfo();
