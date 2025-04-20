self.addEventListener("install", function(event) {
    self.skip;
});

self.addEventListener("activate", function(event) {
});
/**
self.addEventListener('sync', (event) => {
  let obj = null;
  try { obj = JSON.parse(event.tag); }
  catch(e) { return; }
  console.log(obj);

  const tmp = obj.tag.split('-');
  if(tmp.length===2) {
    const message = {action:'sync-'+tmp[0],data: {id: tmp[1]}, methods: obj.methods};
    self.registration.active.postMessage(message);
  }
  return message;
});
*/
