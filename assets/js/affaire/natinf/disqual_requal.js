import AffaireNatinf from './affaireNatinf.class.js';
//import { SyncroManager } from '../../serviceWorker/SyncroManager.js';
import {syncElement} from '../../serviceWorker/';
//var syncManager = new SyncroManager();

const affaireNatinf = new AffaireNatinf(
  "#affaire_natinf_requal_disqual_",
  '#declencheur-requal-disqual-natinf',
  '#natinf_requal_disqual_personnes',
  'natinf_personne_requal_disqual'
);

/**
 * @author yanroussel
 * @description Récupération des informations de la nature d'infraction
 */
function manageNatinf() {
  if(affaireNatinf.newId) {
    return $.ajax({
      url: Routing.generate('remote_api_srj_natinf_details', {id: affaireNatinf.newId}),
      method: 'GET'
    });
  }
  else
    return null;
}

/**
 * @author yanroussel
 * @description Déclenchement d'une disqualification / requalification
 */
window.saveDisqualRequalNatinf = function() {
  showSpinner();

  /**
   * @author yanroussel
   * @date 2023-08-28
   * @description contrôle ici que l'on est en situation de disqual / requal
   */
  $.when(
    affaireNatinf.checkNatinf(affaireNatinf.newId),
    affaireNatinf.checkCommune(affaireNatinf.commune)
  ).then(
    function(newNatinf, commune) {
      const data = {
        "lieu": affaireNatinf.lieu,
        "natinf_id": (null != newNatinf) ? newNatinf[0].id : null,
        "commune_id": (null != commune) ? commune[0].id : null,
        "debut_operateur": affaireNatinf.debutOperateur,
        "debut_date": affaireNatinf.debutDate,
        "debut_heure": affaireNatinf.debutHour,
        "debut_minute": affaireNatinf.debutMinute,
        "fin_operateur": affaireNatinf.finOperateur,
        "fin_date": affaireNatinf.finDate,
        "fin_heure": affaireNatinf.finHour,
        "fin_minute": affaireNatinf.finMinute,
        "personnes": affaireNatinf.personnesDisqualRequal,
        "qualification_developpee": affaireNatinf.qualificationDeveloppee
      };

      showSpinner();

      const affaireId = parseInt($('#affaire_id').val());

      syncElement(
        affaireId,
        'natinf',
        affaireNatinf.id,
        JSON.stringify(data),
        true,
        null,
        {"panelId": affaireNatinf.panel}
      );

      // return $.ajax({
      //   data: JSON.stringify(data),
      //   contentType: "application/ld+json",
      //   dataType: "json",
      //   url: url,
      //   method: 'POST',
      //   success: function(data) {

      //     const updateNatinfsEvent = new Event('updateNatinfs', {
      //       bubbles: true,
      //       cancelable: true,
      //       composed: false
      //     })

      //     document.querySelector("#affaireNatinfs-codes").dispatchEvent(updateNatinfsEvent);

      //     affaireNatinf.updatePanel(data);
      //     affaireNatinf.hidePopup();
      //     hideSpinner();
      //   },
      //   error: function(err) {
      //     alert(err.responseJSON.errmsg);
      //     hideSpinner();
      //     return;
      //   }
      // });
  });
}

window.revertModalNatinf = function(panel) {
  var obj = document.getElementById(panel);
  const affaireNatinfId =obj.dataset.affaireNatinf;
  const targetPersonneId=obj.dataset.personne;
  const natinfPersonneId=obj.dataset.natinfPersonne;
  affaireNatinf.panel=panel;
  showSpinner();
  /** @const {string} url */

  syncElement(
    affaireNatinfId,
    'revertnatinf',
    targetPersonneId,
    JSON.stringify({}),
    false,
    null,
    {"panelId": affaireNatinf.panel}
  );

  // $.ajax({
  //   url: url,
  //   data: JSON.stringify({}),
  //   contentType: "application/ld+json",
  //   dataType: "json",
  //   method: 'POST',
  //   success: function(data) {

  //     const updateNatinfsEvent = new Event('updateNatinfs', {
  //       bubbles: true,
  //       cancelable: true,
  //       composed: false
  //     })

  //     document.querySelector("#affaireNatinfs-codes").dispatchEvent(updateNatinfsEvent);

  //     affaireNatinf.updatePanel(data);
  //     hideSpinner();
  //   }
  // });
}
/**
 * @author yanroussel
 * @description Chargement de la modale de disqual/requal
 * @param {string} panel Panneau appelant
 */
window.showModalNatinf = function(panel) {
  var obj = document.getElementById(panel);
  const affaireNatinfId =obj.dataset.affaireNatinf;
  const targetPersonneId=obj.dataset.personne;
  const natinfPersonneId=obj.dataset.natinfPersonne;
  affaireNatinf.panel=panel;

  const url = Routing.generate('_api_/affaire_natinfs/{id}{._format}_get', {id: affaireNatinfId});
  showSpinner();
  $.get(url)
    .done(function(data){

      const debut = data.debut;
      const fin = data.fin;
      const operateurDebut = debut ? debut.operateur : null;
      const operateurFin = fin ? fin.operateur : null;
      const dateApplication = debut.date.substr(0,10);
      /**
       * @author yanroussel
       * @date   2024-04-23
       *         Affichage uniquement des personnes dont le nom/prénom est
       *         constitué
       */
      let tmp = [];
      data.personnes.map((item,index) => {
        const p = item.personne;
        if(p.smallNomComplet)
          tmp[tmp.length]=item;
      });

      affaireNatinf.natinfPersonnes = tmp;
      affaireNatinf.id = data.id;
      affaireNatinf.qualificationDeveloppee = data.qualificationDeveloppee;
      affaireNatinf.newId = "";
      affaireNatinf.commune = "";
      affaireNatinf.debutOperateur = operateurDebut ? operateurDebut.id : null;
      affaireNatinf.debutId = debut ? debut.id : null;
      affaireNatinf.debutDate = (debut && debut.date) ? debut.date.substr(0,10) : null;
      affaireNatinf.debutHour = (debut && debut.heure) ? debut.heure.substr(11,5).substr(0,2) : null;
      affaireNatinf.debutMinute = (debut && debut.heure) ? debut.heure.substr(11,5).substr(3,2) : null;
      affaireNatinf.finOperateur = operateurFin ? operateurFin.id : null;
      affaireNatinf.finId = fin ? fin.id : null;
      affaireNatinf.finDate = (fin && fin.date) ? fin.date.substr(0,10) : null;
      affaireNatinf.finHour = (fin && fin.heure) ? fin.heure.substr(11,5).substr(0,2) : null;
      affaireNatinf.finMinute = (fin && fin.heure) ? fin.heure.substr(11,5).substr(3,2) : null;
      affaireNatinf.lieu = data.lieu;

      /**
       * @author yanroussel
       * @description Gestion du backup pour vérifier les mises à jour
       */
      affaireNatinf.memorize();
      affaireNatinf.displayPersonnes(targetPersonneId);

      let dept = (data.commune && data.commune.codePostal) ? '('+data.commune.codePostal.substr(0,2)+')' : '';
      $("#fake_affaire_natinf_requal_disqual_natinf").val("");
      if(data.natinf && data.natinf.code)
        $("#fake_affaire_natinf_requal_disqual_natinf").val(data.natinf.code+" - "+data.natinf.libelle);
      if(data.commune && data.commune.libelle)
        $("#fake_affaire_natinf_requal_disqual_commune").val(data.commune.libelle+' '+dept);
      affaireNatinf.showPopup();
      hideSpinner();
    })
  ;
}
