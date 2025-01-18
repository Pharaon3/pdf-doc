<?php

require 'vendor/autoload.php';

\Cloudinary::config(array(
    "cloud_name" => 'dmwe1am9p',
    "api_key" => '981264142345426',
    "api_secret" => 'LxKlji-0pCRmxPu75JbIi1Lheik'
));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (!empty($_FILES['file']['tmp_name'])) {
        
        try {
            $result = \Cloudinary\Uploader::upload($_FILES['file']['tmp_name']);
            
            // Return the URL of the uploaded image
            echo json_encode($result['secure_url']); 
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        
    } else {
       echo json_encode(['error' => 'No file uploaded.']);
   }
}
?>
