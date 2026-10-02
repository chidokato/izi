<?php
$envPath = '.env';
if (file_exists($envPath)) {
    $content = file_get_contents($envPath);
    $content = str_replace('IZI_SSO', 'HCC_SSO', $content);
    $content = str_replace('izi_session', 'hcc_session', $content);
    // DO NOT CHANGE DB_DATABASE=izi 
    file_put_contents($envPath, $content);
}
