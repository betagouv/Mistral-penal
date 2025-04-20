import React,{useState,useEffect} from 'react';

const AffairePersonne = ({affairePersonne}) => {

  const [checked, setChecked]=useState(true);
  const handleChange = () => {alert('ici'); setChecked(!checked)};


  return (
    <div className="fr-fieldset__element">
      <div className="fr-checkbox-group fr-checkbox-group--sm fr-pl-2w">
        <input
          checked={checked}
          type="checkbox"
        />
        <label className="fr-label fr-pl-2w">
          {affairePersonne.personne.smallNomComplet}
          <span className="fr-pl-4w renvoi-modal-statut">{affairePersonne.statut.libelle}</span>
        </label>
      </div>
    </div>
  );
}

export default AffairePersonne;
