import './natinf/disqual_requal.js';
import FieldsInformation from './formulaire.js';
require('../../styles/affaire/renvoi-decision.css');
require('../../styles/audience/chrono.css');
import Chrono from "../../react/audience/Chrono";
import { Modal } from "../../react/representantLegal/Modal/";
import { AvocatModal } from "../../react/avocat/Modal/";
import { Board } from "../../react/affaire/Board";
import { createRoot } from 'react-dom/client';
import React, {useState} from "react";
import  MistralClient from '../utils/mistral-client';
import { generate_rl_container_from_scratch_by_id } from './representant_legal';
//import { SwManager } from '../serviceWorker/SwManager';
// import { encryptSymmetric, getKey, decryptSymmetric } from '../utils/crypto.js';
import {db} from '../utils/db.js';
import { clearOutdated } from '../utils/clearOutdated.js';
//import { SyncroManager } from '../serviceWorker/SyncroManager.js';
import { registerServiceWorker, syncElement, postMessage } from '../serviceWorker/';


// Service Worker Initialisation //
//let swManager = new SwManager();
// Syncro manager Initialisation //
//var syncManager = new SyncroManager();
//let swManager = null;
const reload = true;
registerServiceWorker(reload);

// swManager.postMessage('save-affaire', affaireId, result);
// decryptSymmetric(result.ciphertext, result.iv, key).then((result) => {
//   console.log(result);
// });
// encryptSymmetric(data, key).then((result) => {
//   console.log('encrypt data >>>>>>');
//   console.log(result.ciphertext);
//   console.log(result.iv);
// });
// const affaireId = parseInt($('#affaire_id').val());
// swManager.postMessage('get-affaire', affaireId, null);

// getAffaire(8).then(result => {
//   console.log(result);
// });

window.backupAffaire=function() {

  event.preventDefault();
  const fieldsInformation = new FieldsInformation();
  if(false == fieldsInformation.checkBlank()) {
    alert(fieldsInformation.renderErrors());
  }

  const form = $('form[name="affaire"]');
  let data = form.serialize();
  const affaireId = parseInt($('#affaire_id').val());
  syncElement(affaireId, 'affaire', affaireId, data, false, null);
}

/**
 * Ouverture de la modale de l'avocat
 *
 */
const av_container = document.getElementById('modale-avocat-form');
const av_root = createRoot(av_container);
const init_av_modal = () => showSpinner();
const open_av_modal = (id) => {
 $("#avocat_nom_main").val("");
 $("#avocat_prenom_main").val("");
 $("#avocat_adresse_main").val("");
 $("#avocat_barreau_main").val("");
 $("#avocat_reset").val(1);
 let $modal = $('#declencheur-avocat');
 $modal.attr('data-fr-opened', true);
 setTimeout(() => hideSpinner(),1000);
}
const close_av_modal = () => {
 let $modal = $('#declencheur-avocat');
 $modal.attr('data-fr-opened', false);
}
av_root.render(
  <AvocatModal
    title={"Ajouter un avocat"}
    closing={close_av_modal}
    submitLabel={"Ajouter"}
    handleSubmit={(event) => {
      /** condition **/
      event.preventDefault();
      const url = Routing.generate('_api_/avocats{._format}_post');

      if(!$("#avocat_nom").val()) { alert('Le nom d\'avocat est requis'); return; }
      if(!$("#avocat_prenom").val()) { alert('Le prénom d\'avocat est requis'); return; }
      if(!$("#avocat_barreau").val()) { alert('Le barreau d\'avocat est requis'); return; }

      fetch(url, {
        method: "POST",
        body: JSON.stringify({
          nom: $("#avocat_nom").val(),
          prenom: $("#avocat_prenom").val(),
          adresse: $("#avocat_adresse").val(),
          barreau: $("#avocat_barreau").val(),
          etat: "ACTIF",
          uid: "MIS_"+Date.now(),
        }),
        headers: { "Content-type": "application/json; charset=UTF-8", }
      });

      const targetId = $("#avocat_modal_id").val();
      $("#"+targetId).val($("#avocat_prenom").val()+" "+$("#avocat_nom").val());
      close_av_modal();
      autosaveForm();
    }}
  />
);
/**
 * Ouverture de la popup de gestion d'un représentant légal
 *
 */
const rl_container = document.getElementById('modale-representant-legal-form');
const rl_root = createRoot(rl_container);
const init_rl_modal = () => showSpinner();
const open_rl_modal = () => {
  let $modal = $('#declencheur-representant-legal');
  $modal.attr('data-fr-opened', true);
  setTimeout(() => hideSpinner(),1000);
}
const close_rl_modal = () => {
  let $modal = $('#declencheur-representant-legal');
  $modal.attr('data-fr-opened', false);
}

/**
 * Gestion du panneau des affaires
 *
 */
const aff_container = document.getElementById('affaire_list');
const aff_root = createRoot(aff_container);

aff_root.render(
  <Board
    audience={JSON.parse(aff_container.dataset.audience)}
    affaireId={JSON.parse(aff_container.dataset.affaireId)}
  />
);

window.updateRepresentantLegal=function(personneId,representantLegalId) {
  let rand = Math.random()*1000;
  rl_root.render(<Modal
    rand={rand}
    personneId={personneId}
    representantLegalId={representantLegalId}
    start={init_rl_modal}
    ending={open_rl_modal}
    closing={close_rl_modal}
    title="Représentant légal"
    closeLabel="Fermer"
    submitLabel="Sauvegarder"
  />);
}

function managePersonneType(whoami) {

  if(!whoami.id)
    return;

  const id = whoami.id.replace('_isPersonneMorale_localCheckbox','');
  const classSectionPersonneMorale = '.section-personne-morale-'+id;
  const classSectionPersonnePhysique = '.section-sans-domicile-'+id+',.section-filiation-'+id+',.section-personne-physique-'+id;
  const isPersonneMorale = $(whoami).is(':checked');
  $(classSectionPersonnePhysique).show();
  $(classSectionPersonneMorale).hide();
  if(true == isPersonneMorale) {
    $(classSectionPersonnePhysique).hide();
    $(classSectionPersonneMorale).show();
  }
}

window.getRepresentants = (item) => {
  const representants = item.dataset['rl'];
  if(representants)
    return ' - représenté.e par '+representants;
  return '';
}

window.getNomAffairePersonne = (item,withType=true) => {
  const id = item.dataset['personneId'];
  const type = item.dataset['type'];
  const statut = item.dataset['statut'];
  /** @var {bool} isPersonneMorale */
  const isPersonneMorale = $("#affaire_"+type+"s_"+id+"_personne_isPersonneMorale_localCheckbox").prop('checked');
  let newData = null;
  if(true === isPersonneMorale)
    newData = $("#affaire_"+type+"s_"+id+"_personne_raisonSociale").val();
  else {
    const nom = $("#affaire_"+type+"s_"+id+"_personne_nom").val();
    const prenom1 = $("#affaire_"+type+"s_"+id+"_personne_prenom1").val();
    newData = nom+" "+prenom1;
  }
  if(type=="victime" && withType)
    newData+=" ("+(statut?statut:"Victime")+")";
  return newData;
}

window.getNomAffairePersonnes = (affaireId, type) => {
  let content = "";
  $(".affaire-personne").each((index,item) => {
    //only display first 4 users
    if (index >= 4) return ;
    const stype = item.dataset['type'];
    if(type===stype)
      content+="; "+getNomAffairePersonne(item,false);
  });
  return content.substring(2);
}
window.buildHeaderPersonnes = (targetId=null,newStatut=null) => {
  $(".affaire-personne").each((index,item) => {
    const affaireId = item.dataset['affaireId'];
    const type = item.dataset['type'];
    const id = item.dataset['personneId'];
    /**
     * 1. Mise à jour du visuel de l'entête personne
     */
    if((targetId==id) && newStatut)
      item.dataset['statut']=newStatut;
    if(id)
      $(item).text(getNomAffairePersonne(item)+getRepresentants(item));
    /**
     * 2. Mise à jour du visuel de l'affaire (panneau gauche)
     */
    $(".dossier-content").each((sindex,sitem) => {
      const taffaireId = sitem.dataset['affaireId'];
      const ttype = sitem.dataset['type'];
      if(taffaireId === affaireId && ttype === type) {
        $(sitem).text(getNomAffairePersonnes(affaireId,type));
      }

    });
  });
}

window.openModalAvocat = function(whoami) {
  const id = $(whoami).attr('id');
  $("#avocat_modal_id").val(id);
  open_av_modal(id);
  setTimeout(() => {
    $("#"+id).val("test");
  },500);
}

window.autosaveForm = () => {
  const form = $('form[name="affaire"]');
  let data = form.serialize();
  const affaireId = parseInt($('#affaire_id').val());
  syncElement(affaireId, 'affaire', affaireId, data, false, null,{spinner: false});
}

$(document).ready(function() {
    var timeout = null;
    /**
     * @author yanroussel
     *         Script temporaire pour afficher l'alimentation des avocats
     */
    $( ".autocomplete-avocat" ).autocomplete({
      source: Routing.generate('avocat_collection_search'),
      minLength: 3,
      select: function( event, ui ) {
      },
      create: function( event, ui) {
        $(this).data('ui-autocomplete')._renderItem = function( ul, item ) {
          return $( "<li class='ui-menu-item'>" )
            .append( "<div>" + item.desc + "</div>" )
            .appendTo( ul );
        };
      },
      response: function(event, ui) {
        const id = this.id;
        if (!ui.content.length) {
            var noResult = { value:"",label:"Aucun résultat",desc:"Aucun résultat<br><button class='fr-btn' onClick='javascript:openModalAvocat("+id+")'>+ Créer</button>" };
            ui.content.push(noResult);
        }
      }
    })
    ;

    $(document).on('input',".fr-fieldset-autosave",() => {
      if (timeout !== null)
        clearTimeout(timeout);
      timeout = setTimeout(() => {
        const form = $('form[name="affaire"]');
        let data = form.serialize();
        const affaireId = parseInt($('#affaire_id').val());
        syncElement(affaireId, 'affaire', affaireId, data, false, null,{spinner: false});
      }, 1000);
    });

    // Clear outdated data (experimentation...)
    clearOutdated(outdated, db);

    $('.auto-record-status').hide();

    $(".fr-accordion__btn").click(function(e) {
        e.preventDefault();
        return false;
    });

    $(document).on("change", '.personne_field_is_personne_morale', (ev) => {
      managePersonneType(ev.target);buildHeaderPersonnes()
    });

    $(document).on("change", '.affaire_personne_field_statut', (ev) => {
      const id=ev.target.id.replace(/[^0-9]+/g, "");
      const isChecked = $(ev.target).prop('checked');
      $("#affaire_victimes_"+id+"_statut").prop('disabled',!isChecked);
    });

    /**
     *  Mise à jour du statut victime/PC
     *
     */
    $(document).on("change", '.update_statut', (ev) => {
      const id    = ev.target.id.replace(/[^0-9]+/g, "");
      const data  = {
        statut: Routing.generate('_api_/statut_personnes/{id}{._format}_get', {id: ev.target.value})
      };
      const url   = Routing.generate('_api_/affaire_personnes/{id}{._format}_put', {id: id});
      const label = $(ev.target).find('option:selected').text();
      $.ajax({
        url: url,
        type: 'PUT',
        data: JSON.stringify(data),
        headers: { "Content-type": "application/json; charset=UTF-8" },
        success: function(response) { },
        fail: function(response){
          alert("La prise en compte du changement d'état a échouée. Veuillez rafraichire votre navigateur et effectuer de nouveau la mise à jour");
        }
      });

      $("#affaire_victimes_"+id+"_statut").prop('disabled',true);
      $("#affaire_victimes_"+id+"_changeEtat_localCheckbox").prop("checked", false);

      buildHeaderPersonnes(id,label);
    });

    $("input[id$=_isPersonneMorale_localCheckbox]")
      .each(function() { managePersonneType(this);buildHeaderPersonnes() })
    ;

    $(document).on("keyup",'.fr-header-title', () => buildHeaderPersonnes());

    // action sur les checkbox toggle (enable or disable la toggle-target)
    $.each( $("input[id$=_localCheckbox]"), function () {
        if($(this).attr('toggle-target') != undefined){
            $(this).click(function() {
                let dateInputId = "#"+$(this).attr('toggle-target')
                if(this.checked){
                    $(dateInputId).prop( "disabled", false );
                }else{
                    $(dateInputId).prop( "disabled", true );
                };
            });
        }
    });

    var mistralClient = new MistralClient();

    /* CHRONO */
    const container = document.getElementById('chrono-container');
    const root = createRoot(container);
    root.render(
        <Chrono mistralClient={mistralClient} audienceId={audienceId} />
    );
});

window.addVictime = function(whoami) {
  const affaireId = parseInt($('#affaire_id').val());
  let url = Routing.generate('_api_/affaires/v1/{id}/ajout-victime_get_collection', {id: affaireId});
  // @var {jquery} $container
  let $container  = $(whoami);
  // @var {string} random
  showSpinner();
  $.get(url)
    .done(function(data){
      /** @var {int} id */
      const id = data.id;
      /** @var {int} random */
      const random    = Math.floor(Math.random() * 100000);
      /** @var {string} prototype */
      let prototype   = $container.attr('data-prototype');
      prototype       = prototype
      .replace(/__name__/g, id)
      .replace(/__rand__/g, random)
      ;
      $('#victime-group').append(prototype);
      /**
       * @author yanroussel
       * @date 31/08/23
       * @description activation des autocomplétions PUGX
       */
      bindCommune("#affaire_victimes_"+id+"_personne_communeNaissance");
      /**
       * @author yanroussel
       * @date 22/04/24
       * @description activation des zones liées à la personne morale
       */
      $("#affaire_victimes_"+id+"_personne_isPersonneMorale_localCheckbox")
        .each((index,item) => managePersonneType(item))
      ;
      $.each($("button[class='fr-accordion__btn affaire-personne']"), function () {
          $(this).click(function(event) {
            event.preventDefault();
          });
      });
      /**
       * @author yanroussel
       *         Activation des autocomplétions
       */
      generate_rl_container_from_scratch_by_id(id);
      hideSpinner();
    }).fail(function(err) {
      alert('Un problème est survenu ! Veuillez vous déconnecter de MISTRAL puis retenter après reconnexion');
      hideSpinner();
    })
  ;
}

window.deleteVictime = function(id, accordeonId) {

  if(confirm('Confirmez-vous la suppression de cette victime ?')) {
    const affaireId = $('#affaire_id').val();
    let url = Routing.generate('api_affaire_victime_DELETE', {'affaire_id': affaireId, 'victime_id':id});
    showSpinner();
    $.ajax({
      url: url,
      type: 'DELETE',
      success: function(response) {
        $("#accordion-"+accordeonId).parent().remove();
        hideSpinner();
      },
      fail: function(response){
        hideSpinner();
      }
    });
    ;
  }
}
