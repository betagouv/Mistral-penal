
function displayError(type, data) {
  let html='';
  let texte = data.field;

  if(texte.match(/[[]nom]/))
    texte = 'nom';
  if(texte.match(/[[]prenom1]/))
    texte = 'prénom 1';
  if(texte.match(/[[]dateNaissance]/))
    texte = 'date de naissance';
  if(texte.match(/[[]ligne1]/))
    texte = 'voie postale';
  if(texte.match(/[[]codePostal]/))
    texte = 'code postal';
  if(texte.match(/[[]localite]/))
    texte = 'localité';

  html+=type+" "+data.position+" : champs '"+texte+"' requis !"+"\r\n";
  return html;
}

function getPrevenus() {
  let prevenus=[];
  $("input").each(function() {
    if(this.id.match(/prevenus/)) {
      let prevenuId = getIdByTagId(this.id);
      if(-1 == prevenus.indexOf(prevenuId))
        prevenus.push(prevenuId);
    }
  });
  return prevenus;
}

/**
 * @author yanroussel
 * @date   04/09/23
 * @description Récupération de l'identifiant utilisateur à partir de son ID html
 * @return {?int}
 */
function getIdByTagId(tagId) {
  let id = tagId
    .replace(/^[a-zA-Z_]+/,'')
    .replace(/[a-zA-Z_]+/,'#')
    .replace(/#.+$/g,'')
    .replace(/#/,'')
  ;
  if(id.match(/^[0-9]+$/))
    return parseInt(id);
  else
    return null;
}

function getVictimes() {
  let victimes=[];
  /**
   * Récupération des prévenus et victimes
   */
  $("input").each(function() {
    if(this.id.match(/victimes/)) {
      let victimeId = getIdByTagId(this.id);
      if(-1 == victimes.indexOf(victimeId))
        victimes.push(victimeId);
    }
  });
  return victimes;
}

export default class fieldsInformation {
  constructor() {
    this.blankFields= {
      victimes: [],
      prevenus: []
    };
  }
  get victimes() {
    return getVictimes();
  }
  get prevenus() {
    return getPrevenus();
  }
  /**
   * @author yanroussel
   * @description Récupération de la position de l'individu
   * @return {?int}
   */
  getPositionById(id) {
    if(this.isVictime(id))
      return this.victimes.indexOf(id)+1;
    else if(this.isPrevenu(id))
      return this.prevenus.indexOf(id)+1;
    else
      return null;
  }

  /**
   * @author yanroussel
   * @description Renvoi du message d'erreur
   * @return {string}
   */
  renderErrors() {
    let html = '';
    let i, ln, curVictime, curPrevenu;

    ln = this.blankFields.prevenus.length;
    for(i=0;i<ln;i++) {
      curPrevenu = this.blankFields.prevenus[i];
      html+=displayError('Prévenu', curPrevenu);
    }

    ln = this.blankFields.victimes.length;
    for(i=0;i<ln;i++) {
      curVictime = this.blankFields.victimes[i];
      html+=displayError('Victime', curVictime);
    }

    return html;
  }
  /**
   * @author yanroussel
   * @description contrôle des zones vides
   * @return {int}
   */
  checkBlank() {
    this.blankFields= {
      victimes: [],
      prevenus: []
    };

    let approved = true;
    let whoami = this;
    $("input[required]").each(function() {
      let isEmpty = ($(this).val() == '');
      if(isEmpty) {
        let id = getIdByTagId(this.id);
        if(whoami.isVictime(id)) {
          approved=false;
          whoami.blankFields.victimes[whoami.blankFields.victimes.length] = {
            id: id,
            field: this.name,
            position: whoami.getPositionById(id)
          };
        }
        else if(whoami.isPrevenu(id)) {
          approved=false;
          whoami.blankFields.prevenus[whoami.blankFields.prevenus.length] = {
            id: id,
            field: this.name,
            position: whoami.getPositionById(id)
          };
        }
      }
    });
    return approved;
  }
  isPrevenu(id) {
    return (-1 != this.prevenus.indexOf(id));
  }
  isVictime(id) {
    return (-1 != this.victimes.indexOf(id));
  }
}
