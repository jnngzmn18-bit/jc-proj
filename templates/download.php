<?php
require_once __DIR__.'/common/header.php';
$file = $_GET['file'] ?? null;
$path = __DIR__.'/../../public/'.basename($file);
if(file_exists($path)){
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.basename($path).'"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
} else {
echo '<div class="box">File not found</div>';
}
?>