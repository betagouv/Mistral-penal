const LZString = require('lz-string/libs/lz-string.min.js');
import { data } from "jquery";
import { getNote, saveNote, removeNote } from "./utils/db.js";

const CREATED = 'CREATED';
const INIT = 'init';
const RUNNING = 'running';
const DONE = 'done';

export class LocalStorageManager {
    /**
     * @var {string} filter Section de nommage indiquant les elements a traiter
     */
    constructor(filter) {
        this.affaireId = $('#note_audience_add_affaireId').val();
        this.filter = filter;
        this.memory = [];
        this.editor = null;
        this.state = CREATED;
        document.querySelector("#note_audience_add_note").addEventListener("autoSaveAddNote", this.handleAddEvent.bind(this));
    }

    setEditor(editor){
        this.editor = editor;
    };

    handleAddEvent(event){
        data = this.editor.getData();
        this.setData(this.affaireId, data);
    }

    initLoad(){
        this.getData(this.affaireId).then(result => {
            /** check if we have a local backup */
            if(typeof result !== 'undefined'){
                const content = JSON.parse(result.data);
                /** check if we have a server backup */
                if(noteAudienceUpdate != "null"){
                    const savedDate = content.saveTime.split('.')[0];
                    const incomingDate = noteAudienceUpdate.split('+')[0].replace(' ', 'T');
                    /** if server backup is late we load local record  */
                    if(savedDate > incomingDate){
                        this.editor.setData(content.data);
                    }
                }
            }

            this.state = INIT;
        });
    }

    setMemory(key, value) {
        this.memory[key] = value;
    }

    getMemory(key) {
        return this.memory[key];
    }

    async getData(key) {
        return new Promise((resolve) => {
            getNote(key).then(result => {
            resolve(result);
            })
        });
    }

    async setData(key, data) {
        await saveNote(key, JSON.stringify({ data: data, saveTime: new Date() }));
    }

    removeData(key) {
        removeNote(key);
    }

    /**
     * Génération du hash correspondant au texte
     *
     * @param {string} data
     * @return {Promise}
     */
    async getHash(data) {
        const encoder = new TextEncoder();
        const promise = new Promise((resolve, reject) => {
            const msg = encoder.encode(data);
            crypto.subtle.digest("SHA-256", msg).then((hashBuffer) => {
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            const hash = hashArray
            .map((b) => b.toString(16).padStart(2, "0"))
            .join("")
            ;
            resolve(hash);
            });
        });
        let result = await promise;
        return {'data':data, 'hash':result};
    }

    /**
     * Fonction de sauvegarde automatique
     *
     * @var {int} timeout Intervalle de temps entre deux appels en secondes
     */
    autosave(timeout, callback) {
        this.state = RUNNING;
        let whoami = this;
        this.getData(this.affaireId).then((response) => {
            if(!response){
                throw 'no data';
            }
            const note = JSON.parse(response.data).data;
            return whoami.getHash(note);
        }).then((result) => {
            const oldHash = whoami.getMemory(this.affaireId);
            if( result.hash === oldHash) {
                throw 'no update';
            }
            whoami.setMemory(this.affaireId, result.hash);
            return callback({data: result.data, key: this.affaireId});

        }).then(()=>{
            whoami.removeData(this.affaireId);
            whoami.restartAutosave(timeout, callback);
            this.state = DONE;
        }).catch(()=>{
            whoami.setMemory(this.affaireId, 'undefined');
            whoami.restartAutosave(timeout, callback);
            this.state = DONE;
        });

        this.state = DONE;
    }

    restartAutosave(timeout, callback) {
        if(this.state === RUNNING) return;
        setTimeout(() => this.autosave(timeout, callback), timeout*1000);
    }  
}
