import Dexie from 'dexie';
import { applyEncryptionMiddleware, NON_INDEXED_FIELDS } from 'dexie-encrypted';

export let db = null;


function initDB(db_name,retry) {
  if(retry == 0)
    return;

  db =  new Dexie(db_name);
  const symmetricKey = new TextEncoder().encode(localKey);
  applyEncryptionMiddleware(db, symmetricKey, {
      affaire: NON_INDEXED_FIELDS,
      note: NON_INDEXED_FIELDS,
      renvoi: NON_INDEXED_FIELDS,
      decision: NON_INDEXED_FIELDS,
      natinf: NON_INDEXED_FIELDS,
      revertnatinf: NON_INDEXED_FIELDS,
  });

  db.version(13).stores({
      affaire: `++id, affaireId, date, url, options`,
      note: `++id, date`,
      renvoi: `++id, affaireId, date, url, options`,
      decision: `++id, affaireId, date, url, options`,
      natinf: `++id, affaireId, date, url, options`,
      revertnatinf: `++id, affaireId, date, url, options`,
  });

  // Open the database
  db
    .open()
    .then(function() {
      console.log("Open of "+db_name+" successed");
    })
    .catch(function (e) {
      console.log("Open of "+db_name+" failed: " + e);
      Dexie.delete(db_name);
      initDB(db_name,retry-1);
      return false;
  });
  return true;
}

initDB('Mistral',2);

export async function getAffaire(id) {
    return await db.table('affaire').get({id:id});
}

export async function saveAffaire(id, data) {
    let date = new Date();
    date = JSON.stringify(date);
    return db.table('affaire').put({id:id, data:data, date:date})
}


export async function saveNote(id, data) {
    return await db.table('note').put({id:parseInt(id), data:data})
}

export function getNote(id) {
    return new Promise((resolve) => {
        db.table('note').get({id:parseInt(id)}).then((result) => {
            resolve(result);
        });
    });
}

export function removeNote(id) {
    db.table('note').delete(parseInt(id));
}

export async function getElementById(table, id) {
    return await db.table(table).get({id:id});;
}

export async function saveElement(element, affaireId, data, url, options=null) {

    let date = new Date();
    date = JSON.stringify(date);
    let request = {
        'data':data,
        'date':date,
        'url':url,
        'options':options
    }
    // check if element already exists
    let regexObj = new RegExp("^\/mon-affaire\/" + affaireId + "\/"+element+"s\/(?<elementId>\\d+)$");
    let matches = url.match(regexObj);
    if (matches && matches.groups.elementId) {
        const result = await db.table(element).get({url:url});
        if(result){
            request['id'] = result.id;
        }
    }
    return db.table(element).put(request)
}


export function removeElement(element, id) {
    db.table(element).delete(parseInt(id));
}
