
self.onmessage = function(e) {
   var data = e.data;
   var key = CryptoJS.enc.Hex.parse(data.key);
   var iv = CryptoJS.enc.Hex.parse(data.iv);
   var encryptedContent = {
       ciphertext: CryptoJS.enc.Base64.parse(data.result)
   };
   var decryptionConfig = {iv: iv, padding: CryptoJS.pad.NoPadding};
   var decryptedResponse = CryptoJS.AES.decrypt(encryptedContent, key, decryptionConfig)
       .toString(CryptoJS.enc.Latin1);
   self.postMessage(decryptedResponse);
};
