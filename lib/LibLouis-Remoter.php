<?php
/*
  LibLouis-Remoter is an open source project developed by the University of Maryland Institute for Technology in the Humanities. 

  PHP-LibLouis, LibLouis-Remoter, and Remote-LibLouis was written by Cory Bohon (@coryb).

  View README.md for information on how to use this library. 

*/


function returnBrailleForString($textToTranslate, $url)
{
  //convert the passed content into an array, and then JSON encode it 
  $content = json_encode(array("content" => $textToTranslate));
  
  if(function_exists('wp_remote_post')) {
    $response = wp_remote_post( $url, array(
      'method' => 'POST',
      'timeout' => 45,
      'redirection' => 5,
      'httpversion' => '1.0',
      'blocking' => true,
      'headers' => array("Content-type: application/json"),
      'body' => $content,
      'cookies' => array()
      )
    );

    if( is_wp_error( $response ) ) {
      $error_message = $response->get_error_message();
      die("Error: call to URL $url failed with $error_message.");
    } else {
       $json_response = $response["body"];
    }
  }
  else {
    //cURL implementation to handle posting the content and getting a response back
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER,
            array("Content-type: application/json"));
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
    
    //get the response after executing cURL
    $json_response = curl_exec($curl);
    
    //get the status from cURL
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    //anything other than a status code of 200 is an error
    if ( $status != 200 ) {
        die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
    }
    
    //close the connection 
    curl_close($curl);
  }

  //decode the response
  $response = json_decode($json_response, true);
  
  //return the 'content' string of the response
  return $response['content'];
}

function returnBRFFileForString($textToTranslate, $url)
{
  //convert the passed content into an array, and then JSON encode it 
  $content = json_encode(array("content" => $textToTranslate));

  //cURL implementation to handle posting the content and getting a response back
  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_HEADER, false);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_HTTPHEADER,
          array("Content-type: application/json"));
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
  
  //get the response after executing cURL
  $json_response = curl_exec($curl);
  
  //get the status from cURL
  $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  
  //anything other than a status code of 200 is an error
  if ( $status != 200 ) {
      die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
  }
  
  //close the connection 
  curl_close($curl);
  
  //decode the response
  $response = json_decode($json_response, true);
  
  //create temp file
  $_translatedText = tempnam("/tmp", "pll_");
  
  //check for errors
  if($_translatedText == FALSE)
  {
      return "";
  }
  else
  {
      //Write the contents of the passed text to the temp file
      $_handle = fopen($response['content'], "w");
      fwrite($_handle, $text);
      fclose($_handle);
  }

  //return the file
  return $_translatedText;

}


?>