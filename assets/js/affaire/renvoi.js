import Renvois from "../../react/affaire/Renvois.jsx";
import { createRoot } from 'react-dom/client';
import React from "react";
import  MistralClient from '../utils/mistral-client';
require('../../styles/affaire/renvoi-decision.css');
//import { SyncroManager } from '../serviceWorker/SyncroManager.js';
import {syncElement} from '../serviceWorker/';
//var syncManager = new SyncroManager();

var affaireId           = document.getElementById('affaire_id').value;
var rootRenvois         = null;
var mistralClient       = new MistralClient();
const renvoisContainer  = document.getElementById('renvois-container');

/**
 * @author yanroussel
 *         Ajout d'un nouveau renvoi à partir de la modale
 */
function insertOrUpdateRenvoi() {
  const input = document.getElementById('renvoi_date');
  const validityState = input.validity;
  if(validityState.valueMissing == true) {
      input.reportValidity();
      return;
  }

  let affairePersonnes = [];
   $("input[id^=renvoi_affairePersonnes_]").each(function() {
     if(true === $(this).prop('checked'))
      affairePersonnes[affairePersonnes.length]=Routing.generate('_api_/affaire_personnes/{id}{._format}_get',{id:this.value});
   });
  const renvoiId = $('#renvoi_id').val();


  const url = (renvoiId) ? Routing.generate('_api_/renvois/{id}{._format}_patch',{id:renvoiId}) : Routing.generate('_api_/renvois{._format}_post');
  const method = (renvoiId) ? 'PATCH' : 'POST';
  const body = {
    renvoiMotif: Routing.generate('_api_/renvoi_motifs/{id}{._format}_get', {id: $('#renvoi_renvoiMotif').val()}),
    date: $("#renvoi_date").val(),
    affaire: Routing.generate('_api_/affaires/{id}{._format}_get', {id: $('#renvoi_affaire').val()}),
    affairePersonnes: affairePersonnes,
    mesureSurete: document.querySelector('#renvoi_mesureSurete').value,
    detailsMesureSurete: document.querySelector('#renvoi_detailsMesureSurete').value,
    expertise: document.querySelector('#renvoi_expertise').value,
  };
  showSpinner();

  fetch(url,{
    method: (renvoiId) ? 'PATCH' : 'POST',
    headers: { 'Content-Type': (renvoiId) ? 'application/merge-patch+json' : 'application/ld+json' },
    body: JSON.stringify(body)
  })
    .then((response) => response.json())
    .then(() => {
      rootRenvois.unmount();
      rootRenvois = createRoot(renvoisContainer);
      rootRenvois.render(
          <Renvois affaireId={affaireId} mistralClient={mistralClient} />
      );
      closeModale('renvoi');
      hideSpinner();
    })
  ;
}

$(document).ready(function() {

    $('#checkbox-all').click(function(){
        let checked = $(this).prop('checked');
        $("input[id^=renvoi_affairePersonnes_]").each(function() {
            if($(this).prop('disabled') == false){
                $('#renvoi_affairePersonnes_'+this.value).prop('checked', checked);
            }
        });
    })

    // Pour sélectionner/déselectionner #checkbox-all
    $("input[id^=renvoi_affairePersonnes_]").each(function(){
        $(this).click(function() {
            $('#checkbox-all').prop('checked', false);
        });
    })

    /**
     * Evénement dédiée à la création d'un nouveau renvoi
     */
    $('#renvoi_submit').click(function (e) {
        e.preventDefault();
        e.stopPropagation();
        insertOrUpdateRenvoi();
    });

    /* Renvois */
    rootRenvois = createRoot(renvoisContainer);
    rootRenvois.render(
        <Renvois affaireId={affaireId} mistralClient={mistralClient} />
    );
});
