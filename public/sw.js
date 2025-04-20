// update the version when there is a chnage in event.js or sw.js
const version = "0.2.1";

try{
    importScripts(
        // 'build/dexie/dexie.js',
        // 'build/serviceWorker/db.js',
        'build/serviceWorker/event.js'
    );
}catch(e){
    console.log('error in sw.js');
    console.log(e);
}

console.log('sw');


self.addEventListener('install', function (event) {
    self.skipWaiting();
})
