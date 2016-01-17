<?php

require_once 'jsonRPCClient.php';
 
  $bitcoin = new jsonRPCClient('http://bitsman:passwordtwentybyte@192.168.1.4:8332/');
 
  echo "<pre>\n";
  print_r($bitcoin->getinfo());
  echo "</pre>";

?>
