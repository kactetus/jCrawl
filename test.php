<?php
 // with curl_multi, you only have to wait for the longest-running request
  
  // build the individual requests as above, but do not execute them
  $ch_1 = curl_init('http://webservice.one.com/');
  $ch_2 = curl_init('http://webservice.two.com/');
  curl_setopt($ch_1, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch_2, CURLOPT_RETURNTRANSFER, true);
  
  // build the multi-curl handle, adding both $ch
  $mh = curl_multi_init();
  curl_multi_add_handle($mh, $ch_1);
  curl_multi_add_handle($mh, $ch_2);
  
  // execute all queries simultaneously, and continue when all are complete
  $running = null;
  do {
    curl_multi_exec($mh, $running);
  } while ($running);
  
  // all of our requests are done, we can now access the results
  $response_1 = curl_multi_getcontent($ch_1);
  $response_2 = curl_multi_getcontent($ch_2);
  echo "$response_1 $response_2"; // same output as first example


