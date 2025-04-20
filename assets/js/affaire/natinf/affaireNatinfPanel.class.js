/**
 * @author yanroussel
 * @date 2023-09-19
 * @description Ajout d'une classe AffaireNatinfPanel pour gérer les rafraichissements d'écran
 */
export default class AffaireNatinfPanel {

  constructor(panel, objAffaireNatinfId, objPersonneIds) {
    this.QD_PREFIX="Initiale : ";
    this.QD_POSTFIX="(Disqual/requal)";
    this._panel = panel;
    this._objAffaireNatinfId = objAffaireNatinfId;
    this._objPersonneIds = objPersonneIds;
    this.DEFAULT_CLASS_AFFAIRE_NATINF_PANEL="fr-natinf-card";
    this.DEFAULT_DATASET_PERSONNE="data-personne";
    this.DEFAULT_DATASET_AFFAIRE_NATINF="data-affaire-natinf";
    this.DEFAULT_BTN_ANNULATION='<a for="{{panel}}" href="javascript:revertModalNatinf(\'{{panel}}\');" class="fr-btn fr-btn--secondary btn-orange" title="Annulation"><i class="ri-arrow-go-back-line"></i></a>';
  }

  getLocalAffaireNatinfId() {
    var obj = document.getElementById(this._panel);
    return obj.dataset.affaireNatinf;
  }
  updateAffaireNatinfForDisqual(newId,nature) {
    let tab = [];
    let whoami = this;
    this._objPersonneIds.new.forEach(function(item) {
      tab[tab.length]="."+whoami.DEFAULT_CLASS_AFFAIRE_NATINF_PANEL+"["+whoami.DEFAULT_DATASET_PERSONNE+"="+item+"]["+whoami.DEFAULT_DATASET_AFFAIRE_NATINF+"="+whoami.getLocalAffaireNatinfId()+"]";
    });
    let classes = tab.join(",");
    $(classes).each(function() {
      const panelId=$(this).attr('id');
      const content = ('annulation' != nature) ? whoami.DEFAULT_BTN_ANNULATION.replaceAll('{{panel}}',panelId) : '';
      $("#"+panelId).find('span[for=annulation-btn]').html(content);
    });
    $(classes).attr(this.DEFAULT_DATASET_AFFAIRE_NATINF,newId);
  }
  updateAffaireNatinf(newId,nature) {
    let myclass = "."+this.DEFAULT_CLASS_AFFAIRE_NATINF_PANEL+"["+this.DEFAULT_DATASET_AFFAIRE_NATINF+"="+this.getLocalAffaireNatinfId()+"]";
    const panelId=$(myclass).attr('id');
    const content = ('annulation' != nature) ? this.DEFAULT_BTN_ANNULATION.replaceAll('{{panel}}',panelId) : '';
    $("#"+panelId).find('span[for=annulation-btn]').html(content);
    $(myclass).attr(this.DEFAULT_DATASET_AFFAIRE_NATINF,newId);
  }
  getDomElementsDisqualRequal(data) {
    let tab = [];
    let whoami = this;
    this._objPersonneIds.new.forEach(function(item) {
      tab[tab.length]="."+whoami.DEFAULT_CLASS_AFFAIRE_NATINF_PANEL+"["+whoami.DEFAULT_DATASET_PERSONNE+"="+item+"]["+whoami.DEFAULT_DATASET_AFFAIRE_NATINF+"="+whoami.getLocalAffaireNatinfId()+"]";
    });
    let classes = tab.join(",");
    return $(classes).find(data);
  }
  getDomElementDisqualRequal(data) {
    let objs=this.getDomElementsDisqualRequal(data);
    return (objs.length) ? $(objs[0]) : null;
  }
  getDomElements(data) {
    let myclass = "."+this.DEFAULT_CLASS_AFFAIRE_NATINF_PANEL+"["+this.DEFAULT_DATASET_AFFAIRE_NATINF+"="+this.getLocalAffaireNatinfId()+"]";
    return $(myclass).find(data);
  }
  getDomElement(data) {
    let objs=this.getDomElements(data);
    return (objs.length) ? $(objs[0]) : null;
  }
  get commune() { return this.getDomElement("[for=commune]").text().trim(); }
  set commune(commune) { this.getDomElements("[for=commune]").text(commune).addClass("highlight"); }
  get lieu() { return this.getDomElement("[for=lieu]").text().trim(); }
  set lieu(lieu) { this.getDomElements("[for=lieu]").text(" - "+lieu).addClass("highlight"); }
  eraseCommuneHighlight() { this.eraseObjHighlight("commune"); }
  eraseLieuHighlight() { this.eraseObjHighlight("lieu"); }
  eraseObjHighlight(element) {
    this.getDomElements("[for="+element+"]").removeClass("highlight");
  }
  get qualificationDeveloppee() { return this.getDomElement("[for=qualificationDeveloppee]").text().trim(); }
  set qualificationDeveloppee(qd) {
    let $items = this.getDomElements("[for=qualificationDeveloppee]");
    $items.text(qd)
    if(qd)
      $items.addClass("highlight").addClass("clearLeft");
    else
      $items.removeClass("highlight").removeClass("clearLeft");
  }
  get date() { return this.getDomElementDisqualRequal("[for=date]").text().trim(); }
  set date(dateLibelle) { this.getDomElementsDisqualRequal("[for=date]").text(dateLibelle); }
  get natinf() { return this.getDomElementDisqualRequal("[for=natinf]").text().trim(); }
  set natinf(natinfLibelle) { this.getDomElementsDisqualRequal("[for=natinf]").text(natinfLibelle); }
  get natinfDisqual() {
    return this
      .getDomElementDisqualRequal("[for=disqual-requal-natinf]")
      .text()
      .replace(this.QD_PREFIX,"")
      .replace(this.QD_POSTFIX, "")
      .trim()
    ;
  }
  set natinfDisqual(natinf) {
    this
      .getDomElementsDisqualRequal("[for=disqual-requal-natinf]")
      .text(this.QD_PREFIX+natinf+this.QD_POSTFIX)
      .addClass("highlight")
      .addClass("clearLeft")
    ;
  }
  get dateDisqual() {
    return this
      .getDomElementDisqualRequal("[for=disqual-requal-date]")
      .text()
      .replace(this.QD_PREFIX,"")
      .replace(this.QD_POSTFIX, "")
      .trim()
    ;
  }
  set dateDisqual(date) {
    this
      .getDomElementsDisqualRequal("[for=disqual-requal-date]")
      .text(this.QD_PREFIX+date+this.QD_POSTFIX)
      .addClass("highlight")
      .addClass("clearLeft")
    ;
  }
  eraseNatinfDisqual() {
    this
      .getDomElementsDisqualRequal("[for=disqual-requal-natinf]")
      .text("")
      .removeClass("highlight")
      .removeClass("clearLeft")
  }
  eraseDateDisqual() {
    this
      .getDomElementsDisqualRequal("[for=disqual-requal-date]")
      .text("")
      .removeClass("highlight")
      .removeClass("clearLeft")
  }
}
