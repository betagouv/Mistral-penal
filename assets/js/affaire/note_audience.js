import 'ckeditor4';
import { getKey } from '../utils/crypto.js';
import { LocalStorageManager } from '../LocalStorageManager.js';

require('../../styles/affaire/note_audience.css');

var editor=null;
var memoryNavigator={width: 0};
const TARE_HEIGHT = 80;
const REDUCE_WINDOWS_HEIGHT=250;

window.initEditor = () => {
  const i = document.querySelector("#note_audience_add");
  memoryNavigator = {width: i.offsetWidth};
}

window.resizeEditor = () => {
  const scrolltop = $(window).scrollTop();
  let height = $(window).height()-REDUCE_WINDOWS_HEIGHT;
  const i = document.querySelector("#note_audience_add");
  if (scrolltop > height+TARE_HEIGHT) {
    $("#note_audience_add")
      .css('position', 'fixed')
      .css('width', memoryNavigator.width)
      .css('top',0)
      .css('z-index',1000)
    ;
  }
  else
    $("#note_audience_add").css('position', 'static');
}

/**
 * @author yanroussel
 *         Désactivation suite à retour UX
 *
$(window).scroll(function () {
  resizeEditor();
});
$(document).ready(() => {
  initEditor();
});
*/
const key = await getKey(localKey);
/**
 * @var {LocalStorageManager} manager Gestionnaire des sauvegardes à la volée de la note d'audience
 *
 * @param {string} filter Sous-chaîne permettant l'identification des éléments sauvegardés via le localStorage
 */
let manager = new LocalStorageManager("autosave_note_audience_", key);

/** @const {int} affaireId */
const affaireId = $('#note_audience_add_affaireId').val();
/** @const {string} url */
const url = Routing.generate('_api_/affaires/v1/{id}/mot_rapide_get', {id: affaireId});

/**
 * Fonction de sauvegarde de la note d'audience
 *
 * @param {array} params Tableau des paramètres nécessaire pour la sauvegarde
 */
const callbackCkEditor = function(params) {
    const note = params.data;
    /** @const {int} affaireId */
    const affaireId = params.key;
    const url = Routing.generate('affaire_note_audience_POST', {affaire_id: affaireId});

    return new Promise((successCallback, failureCallback) => {

        $.ajax({
            contentType: 'application/json',
            data: JSON.stringify({"note": note}),
            dataType: 'json',
            success: function(data){
              /** sauvegarde réussi */
              const id = '#synchro_server';
              $(id).text("Sauvegarde effectuée");
              setTimeout(function() { $(id).text(""); }, 2000);
              successCallback(data);
            },
            error: function(error){
                console.log("Un problème d'enregistrement est survenu !");
                failureCallback(error);
            },
            processData: false,
            type: 'POST',
            url: url
          });
    });

};

/** fonction pour configurer l'autocomplete de ckeditor
 *  il peut y avoir un problème/délai de chargement de l'éditeur ou des plugins
 */
function registerAutocomplete(editor) {
    var config = {};
    function textTestCallback( range ) {
        if(!range.collapsed) return null;
        return CKEDITOR.plugins.textMatch.match(range,matchCallback);
    }

    function matchCallback( text, offset ) {
    var left = text.slice( 0, offset ),
        match = left.match( /#\w*$/ );

    if ( !match ) { return null; }
        return { start: match.index, end: offset };
    }

    config.textTestCallback = textTestCallback;

    function dataCallback( matchInfo, callback ) {
        /** @const {string} word Termes de recherche */
        const word = matchInfo.query.substring( 1 );

        if(word.length > 2) {
            $.ajax({
            url: url,
            type: 'GET',
            data: {word: word},
            dataType: 'json',
            success: function(response) {
                callback(response);
            }
            });
        }
    }

    config.dataCallback = dataCallback;
    config.itemTemplate = '<li data-id="{id}" class="issue-{type}">{nom_complet}</li>';
    config.outputTemplate = '{id} ';

    try{
        manager.setEditor(editor);
        new CKEDITOR.plugins.autocomplete( editor, config );
        /**
         * Le manager est configuré pour effectuer des mise à jour toutes les 5 secondes
         */
        manager.initLoad();
        manager.autosave(5, callbackCkEditor);

        // document.querySelector("#note_audience_add_note").addEventListener("autoSaveNote", manager.handleEvent.bind(this));
    }catch(error){
        setTimeout(function(){
            registerAutocomplete(editor);
        }, 1000);
    }
}

$(document).ready(function() {
    editor = CKEDITOR.instances.note_audience_add_note;
    editor.config.height=window.innerHeight-parseInt(1.8*TARE_HEIGHT);
    const z = $("#note_audience_add").parent();
    z.height(window.innerHeight-TARE_HEIGHT);
    //editor.resize('100%', window.innerHeight-TARE_HEIGHT, true);
    registerAutocomplete(editor);
});
