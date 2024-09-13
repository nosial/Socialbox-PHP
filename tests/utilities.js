export function crc32(str) {
    var crcTable = [];
    for (var i = 0; i < 256; i++) {
        var crc = i;
        for (var j = 8; j > 0; j--) {
            if (crc & 1) {
                crc = (crc >>> 1) ^ 0xEDB88320;
            } else {
                crc = crc >>> 1;
            }
        }
        crcTable[i] = crc;
    }

    var crc32val = 0xFFFFFFFF;
    for (var i = 0; i < str.length; i++) {
        var charCode = str.charCodeAt(i);
        crc32val = (crc32val >>> 8) ^ crcTable[(crc32val ^ charCode) & 0xFF];
    }

    return (crc32val ^ 0xFFFFFFFF) >>> 0;
}

export function randomCrc32String(length = 8) {
    var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var randomStr = '';
    for (var i = 0; i < length; i++) {
        randomStr += characters.charAt(Math.floor(Math.random() * characters.length));
    }
    return crc32(randomStr).toString(16); // Convert to hexadecimal string
}

console.log(randomCrc32String()); // Example usage
