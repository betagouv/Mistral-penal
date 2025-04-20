
export const encryptSymmetric = async (plaintext, key) => {
    // create a random 96-bit initialization vector (IV)
    const iv = new TextEncoder().encode(crypto.getRandomValues(new Uint8Array(12)));

    // encode the text you want to encrypt
    const encodedPlaintext = new TextEncoder().encode(plaintext);

    // prepare the secret key for encryption
    const secretKey = await crypto.subtle.importKey(
        'raw', 
        key,
        {
            name: 'AES-GCM',
            length: 256
        }, 
        true, 
        ['encrypt', 'decrypt']
    );

    // encrypt the text with the secret key
    const ciphertext = await crypto.subtle.encrypt({
        name: 'AES-GCM',
        iv
    }, secretKey, encodedPlaintext);

    // return the encrypted text "ciphertext" and the IV
    // encoded in base64
    return ({
        ciphertext: ciphertext,
        iv: iv
    });
}

export const decryptSymmetric = async (ciphertext, iv, key) => {
    // prepare the secret key
    const secretKey = await crypto.subtle.importKey(
        'raw',
        key, 
        {
        name: 'AES-GCM',
        length: 256
    }, true, ['encrypt', 'decrypt']);

    console.log(ciphertext);

    // decrypt the encrypted text "ciphertext" with the secret key and IV
    const cleartext = await crypto.subtle.decrypt({
        name: 'AES-GCM',
        iv: iv,
    }, secretKey, ciphertext);

    // decode the text and return it
    return new TextDecoder().decode(cleartext);
}

export async function getKey(value) {
    return await crypto.subtle.digest(
        'SHA-256',
        new TextEncoder().encode(value)
    );
}