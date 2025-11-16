function showQR(url) {
    document.getElementById('qr-modal').style.display='block';
    document.getElementById('qr-code').innerHTML = '';
    new QRCode(document.getElementById('qr-code'), url);
}
function closeQR() {
    document.getElementById('qr-modal').style.display='none';
    document.getElementById('qr-code').innerHTML = '';
}