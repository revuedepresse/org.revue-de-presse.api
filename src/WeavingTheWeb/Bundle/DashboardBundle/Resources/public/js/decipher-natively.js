
self.onmessage = function(e) {
    var crypto = CryptoJS;

    var hexToArrayBuffer = function (s) {
        var arrayBuffer, byteValue;

        if (s.length % 2 != 0) {
            s = '0' + s;
        }
        arrayBuffer = new Uint8Array(s.length / 2);

        for (var i = 0; i < s.length; i += 2) {
            byteValue = parseInt(s.substr(i, 2), 16);
            arrayBuffer[i / 2] = byteValue;
        }

        return arrayBuffer;
    };

    var base64ToArrayBuffer = function (base64) {
        var binary_string = self.atob(base64);
        var len = binary_string.length;
        var bytes = new Uint8Array(len);
        for (var i = 0; i < len; i++) {
            bytes[i] = binary_string.charCodeAt(i);
        }

        return bytes.buffer;
    };

    var data = e.data;

    var base64encodedKey = crypto.enc.Hex.parse(data.key).toString(crypto.enc.Base64);
    var decodedKey = base64ToArrayBuffer(base64encodedKey);

    var hexIv = crypto.enc.Hex.parse(data.iv);
    var decodedIv = base64ToArrayBuffer(hexIv.toString(crypto.enc.Base64));

    var hexMessage = crypto.enc.Base64.parse(data.result).toString(crypto.enc.Hex);
    var encryptedMessage = hexToArrayBuffer(hexMessage);

    self.crypto.subtle.importKey('raw', decodedKey, 'AES-CBC', false, ['decrypt'])
        .then(function (key) {
            self.crypto.subtle.decrypt({name: 'AES-CBC', iv: decodedIv}, key, encryptedMessage)
                .then(function (result) {
                    var decoder = new TextDecoder;
                    var decodedData = decoder.decode(new Uint8Array(result));
                    self.postMessage(decodedData);
                })
                .catch(function (e) {
                    self.postMessage({error: e});
                });
        }).catch(function (e) {
            self.postMessage({error: e});
        });
};
