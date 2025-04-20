import AffaireNatinfPanel from './affaireNatinfPanel.class.js';
/**
 * @author yanroussel
 * @date 2023-08-28
 * @description Ajout d'une classe AffaireNatinf pour gérer les mise à jour
 */
export default class AffaireNatinf {
  getDomElement(data) { return $(this.PREFIX+data); }
  getPanel(objId, objPersonneIds) { return new AffaireNatinfPanel(this._panel, objId, objPersonneIds); }
  constructor(prefix,popupId, personnesAreaId, personnesImpliqueesClass) {
    this._panel = null;
    this.PREFIX = prefix;
    this.POPUP_ID = popupId;
    this.PERSONNES_AREA_ID = personnesAreaId;
    this.PERSONNES_IMPLIQUEES_CLASS = personnesImpliqueesClass;
    this.backup = {
      debutOperateur: null,
      debutDate: null,
      debutHour: null,
      debutMinute: null,
      lieu: null,
      finOperateur: null,
      finDate: null,
      finHour: null,
      finMinute: null,
      qualificationDeveloppee: null
    }
  }
  memorize() {
    this.backup.debutOperateur = this.debutOperateur;
    this.backup.debutDate = this.debutDate;
    this.backup.debutHour = this.debutHour;
    this.backup.debutMinute = this.debutMinute;
    this.backup.lieu = this.lieu;
    this.backup.finOperateur = this.finOperateur;
    this.backup.finDate = this.finDate;
    this.backup.finHour = this.finHour;
    this.backup.finMinute = this.finMinute;
    this.backup.qualificationDeveloppee = this.qualificationDeveloppee;
  }
  /**
   * @author yanroussel
   * @description L'action demandée est-elle une mise à jour
   */
  isModificationOnly() {
    return (
    (this.backup.debutOperateur == this.debutOperateur) &&
    (this.backup.debutDate == this.debutDate) &&
    (this.backup.debutHour == this.debutHour) &&
    (this.backup.debutMinute == this.debutMinute) &&
    (this.backup.lieu == this.lieu) &&
    (this.backup.finOperateur == this.finOperateur) &&
    (this.backup.finDate == this.finDate) &&
    (this.backup.finHour == this.finHour) &&
    (this.backup.finMinute == this.finMinute)
    );
  }

  updatePanel(data) {
    let panel = this.getPanel(data.id, data.personnes);
    panel.natinf=data.natinf.new;
    panel.natinfDisqual=data.natinf.old;
    if(data.natinf.change==false)
      panel.eraseNatinfDisqual();

    panel.date=data.date.new;
    panel.dateDisqual=data.date.old;
    if(data.date.change==false)
      panel.eraseDateDisqual();

    panel.qualificationDeveloppee= (data.qd.change==true) ? "QD modifiée" : "";
    panel.commune=data.commune.new;
    if(data.commune.change==false)
      panel.eraseCommuneHighlight();
    panel.lieu=data.lieu.new;
    if(data.lieu.change==false)
      panel.eraseLieuHighlight();
    if(data.nature!='modification')
      panel.updateAffaireNatinfForDisqual(data.id.new,data.nature);
    else
      panel.updateAffaireNatinf(data.id.new,data.nature);
  }

  get panel() { return this._panel; }
  set panel(panel) { this._panel=panel; }
  get newId() { return this.getDomElement("natinf").val(); }
  set newId(newId) { this.getDomElement("natinf").val(newId); }
  get id() { return this.getDomElement("id").val(); }
  set id(id) { this.getDomElement("id").val(id); }
  get qualificationDeveloppee() { return this.getDomElement("qualificationDeveloppee").val(); }
  set qualificationDeveloppee(qd) { this.getDomElement("qualificationDeveloppee").val(qd); }
  get natinfPersonnes() { return this.np; }
  set natinfPersonnes(natinfPersonnes) { this.np = natinfPersonnes; }
  get debutOperateur() { return this.getDomElement("debut_operateur").val(); }
  set debutOperateur(op) { this.getDomElement("debut_operateur").val(op); }
  get debutId() { return this.getDomElement("debut_id").val(); }
  set debutId(id) { this.getDomElement("debut_id").val(id); }
  get debutDate() {
    const date = this.getDomElement("debut_date");
    if(!date.val())
      return null;
    return date.val();
  }
  set debutDate(date) {
    if(!date)
      return;
    const yyyy = date.substr(0,4);
    const mm = date.substr(5,2);
    const dd = date.substr(8,2)
    this.getDomElement("debut_date").val(yyyy+"-"+mm+"-"+dd);
    /**
     * @author yanroussel
     * @description La NATINF doit être recherchée en fonction de la date d'application
     */
    this.reloadAutocompleteNatinf();
  }
  get debutHour() { return this.getDomElement("debut_heure_hour").val().substr(0,2); }
  set debutHour(hour) { this.getDomElement("debut_heure_hour").val(hour); }
  get debutMinute() { return this.getDomElement("debut_heure_minute").val(); }
  set debutMinute(minute) { this.getDomElement("debut_heure_minute").val(minute); }
  get lieu() { return this.getDomElement("lieu").val(); }
  set lieu(lieu) { this.getDomElement("lieu").val(lieu); }
  get commune() { return this.getDomElement("commune").val(); }
  set commune(commune) { this.getDomElement("commune").val(commune); }
  get finOperateur() { return this.getDomElement("fin_operateur").val(); }
  set finOperateur(op) { this.getDomElement("fin_operateur").val(op); }
  get finId() { return this.getDomElement("fin_id").val(); }
  set finId(id) { this.getDomElement("fin_id").val(id); }
  get finDate() {
    const date = this.getDomElement("fin_date");
    if(!date.val())
      return null;
    return date.val();
  }
  set finDate(date) {
    if(!date)
      return;
    const yyyy = date.substr(0,4);
    const mm = date.substr(5,2);
    const dd = date.substr(8,2)
    this.getDomElement("fin_date").val(yyyy+"-"+mm+"-"+dd);
  }
  get finHour() { return this.getDomElement("fin_heure_hour").val().substr(0,2); }
  set finHour(hour) { this.getDomElement("fin_heure_hour").val(hour); }
  get finMinute() { return this.getDomElement("fin_heure_minute").val(); }
  set finMinute(minute) { this.getDomElement("fin_heure_minute").val(minute); }
  get personnesDisqualRequal() {
    let personnesImpliquees = [];
    $("."+this.PERSONNES_IMPLIQUEES_CLASS).each(function() {
      if($(this).prop('checked'))
        personnesImpliquees[personnesImpliquees.length]=$(this).val();
    });
    return personnesImpliquees;
  }
  displayPersonnes(targetPersonneId) {
    let whoami = this;
    $(this.PERSONNES_AREA_ID).html("");
    this.natinfPersonnes.forEach(function(item) {
      const personneId = item.personne.id;
      if(false == item.isDisqualifie) {
        const checked = (personneId == targetPersonneId) ? 'checked' : '';
        let html = "<tr><td><input type='checkbox' class='"+whoami.PERSONNES_IMPLIQUEES_CLASS+"' value='"+item.id+"' "+checked+"> "+item.personne.smallNomComplet+"</td><td>"+item.statut+"</td></tr>";
        $(whoami.PERSONNES_AREA_ID).append(html);
      }
    });
  }
  reloadAutocompleteNatinf() {
    $(".autocomplete_natinf").parent().find('input[id^=fake_]').remove();
    $(".autocomplete_natinf").autocompleter({
      url_list: Routing.generate('remote_api_srj_natinf_collection')+'?date_application='+this.debutDate,
      url_get: Routing.generate('remote_api_srj_natinf_details')
    });
    $(".autocomplete_natinf").parent().find('input[id^=fake_]').prop("style","");
  }
  /**
   * @author yanroussel
   * @description Récupération des informations de la commune
   */
  checkCommune(id) {
    if(id)
      return $.ajax({
        url: Routing.generate('_api_/communes/{id}{._format}_get', {id: id}),
        method: 'GET'
      });
    else
      return null;
  }

  /**
   * @author yanroussel
   * @description Récupération des informations de la nature d'infraction
   */
  checkNatinf(id) {
    if(id)
      return $.ajax({
        url: Routing.generate('remote_api_srj_natinf_details', {id: id}),
        method: 'GET'
      });
    else
      return null;
  }
  showPopup() { $(this.POPUP_ID).attr('data-fr-opened', true); }
  hidePopup() { $(this.POPUP_ID).attr('data-fr-opened', false); }
}
