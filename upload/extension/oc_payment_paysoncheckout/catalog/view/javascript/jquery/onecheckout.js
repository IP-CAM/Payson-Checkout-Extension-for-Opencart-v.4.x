/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Other/javascript.js to edit this template
 */

/*
 if ( document.addEventListener ) {
 
 //parent.addEventListener( "unload", unloadHandler, false );
 // Triggered when customer adress has been set or changed
 document.addEventListener("PaysonEmbeddedAddressChanged", function(evt) {
 var address = evt.detail;
 
 console.log(address.City);
 console.log(address.CountryCode);
 console.log(address.FirstName);
 console.log(address.LastName);
 console.log(address.PostalCode);
 console.log(address.Street);
 console.log(address.Email);
 
 sendLockDown();
 
 });
 }
 
 // Reloads the iframe object (for example after updating of an order amount)
 function sendUpdate() {
 document.getElementById('paysonIframe').contentWindow.postMessage('updatePage', '*');
 }
 
 // Lock iframe object from user interaction until checkout is updated
 function sendLockDown() {
 document.getElementById('paysonIframe').contentWindow.postMessage('lock', '*');
 }
 
 // Release an locked iframe object
 function sendRelease() {
 document.getElementById('paysonIframe').contentWindow.postMessage('release', '*');
 }
 
 // Triggered when customer has clicked "Complete payment" and started the payment process
 document.addEventListener("PaysonEmbeddedPaymentInitiated", function(evt) {
 
 });
 
 */