import React,{useEffect,useState} from 'react';
import { deleteRepresentantLegal } from '../../affaire/RepresentantsLegaux';

const RepresentantLegalCard = ({item,id}) => {

  const [loading, setLoading]=useState(false);
  const [deleted, setDeleted]=useState(false);
  useEffect(() => {
    setLoading(true);
  },[])

  function handleClick() {
    const personneId = item.representant.personne.id;
    const representantLegalId = item.id;
    updateRepresentantLegal(personneId,representantLegalId);
    event.preventDefault();
    event.stopPropagation();
  }

  function handleDelete() {
    (async () => deleteRepresentantLegal(item.id)) ();
    setDeleted(true);
    $(".affaire-personne").each((index,zitem) => {
      const zid = zitem.dataset['personneId'];
      if(id==zid) {
        let rl = zitem.dataset['rl'];
        let np = item.representant.personne.nom+' '+item.representant.personne.prenom1;
        rl = rl.replace(","+np,"").replace(np,"");
        zitem.dataset['rl']=rl;
        $(zitem).text(getNomAffairePersonne(zitem)+getRepresentants(zitem));
      }
    });
    event.preventDefault();
    event.stopPropagation();
  }

  return (
    loading && !deleted && <div
      id={'panel-rl-'+id+'-'+item.representant.affaire_personne.id}
      className="fr-grid-row fr-natinf-card"
    >
      <div className="fr-col-10">
        <b>{item.lien_juridique.libelle}</b>
        <br/>
        {item.representant.personne.nom+' '+item.representant.personne.prenom1}
      </div>
      <div className="fr-col-1">
          <button onClick={handleDelete} className="fr-btn  fr-btn--icon fr-btn--secondary fr-icon-delete-line button-icon-delete" title="Supprimer renvoi">
              Supprimer représentant
          </button>
      </div>
      <div className="fr-col-1">
        <button  onClick={handleClick}
          className="fr-btn fr-icon-edit-line"
          title="Editer représentant"
          id={"rl_open_modal_"+item.id}>
        Editer représentant
        </button>
      </div>
    </div>
  );
}

export default RepresentantLegalCard;
