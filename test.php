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


$curl_handles = array();
foreach ($urls as $url) {
    $curl_handles[$url] = curl_init();
    curl_setopt($curl_handles[$url], CURLOPT_URL, $url);
    // set other curl options here
}

// -- start going through the cURL handles and running them
$curl_multi_handle = curl_multi_init();

$i = 0; // count where we are in the list so we can break up the runs into smaller blocks
$block = array(); // to accumulate the curl_handles for each group we'll run simultaneously

foreach ($curl_handles as $a_curl_handle) {
    $i++; // increment the position-counter

    // add the handle to the curl_multi_handle and to our tracking "block"
    curl_multi_add_handle($curl_multi_handle, $a_curl_handle);
    $block[] = $a_curl_handle;

    // -- check to see if we've got a "full block" to run or if we're at the end of out list of handles
    if (($i % BLOCK_SIZE == 0) or ($i == count($curl_handles))) {
        // -- run the block

        $running = NULL;
        do {
            // track the previous loop's number of handles still running so we can tell if it changes
            $running_before = $running;

            // run the block or check on the running block and get the number of sites still running in $running
            curl_multi_exec($curl_multi_handle, $running);

            // if the number of sites still running changed, print out a message with the number of sites that are still running.
            if ($running != $running_before) {
                echo("Waiting for $running sites to finish...\n");
            }
        } while ($running > 0);

        // -- once the number still running is 0, curl_multi_ is done, so check the results
        foreach ($block as $handle) {
            // HTTP response code
            $code = curl_getinfo($handle,  CURLINFO_HTTP_CODE);

            // cURL error number
            $curl_errno = curl_errno($handle);

            // cURL error message
            $curl_error = curl_error($handle);

            // output if there was an error
            if ($curl_error) {
                echo("    *** cURL error: ($curl_errno) $curl_error\n");
            }

            // remove the (used) handle from the curl_multi_handle
            curl_multi_remove_handle($curl_multi_handle, $handle);
        }

        // reset the block to empty, since we've run its curl_handles
        $block = array();
    }
}

// close the curl_multi_handle once we're done
curl_multi_close($curl_multi_handle);
