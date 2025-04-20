import React, {useState, useEffect} from 'react';
import AffairePersonne from './AffairePersonne';

const isAcceptedStatut = (statut) => {
  const VALIDATED_STATUS = ['Prévenu',"Mis en cause","Accusé"];
  if(VALIDATED_STATUS.includes(statut.code))
    return true;
  return (statut.mnemo && (statut.mnemo.length > 0));
}

const AffairePersonnes = ({affaireId,setCheckedAffairePersonnes}) => {

  const [affairePersonnes,setAffairePersonnes]=useState([]);
  const [isLoading,setIsLoading]=useState(false);

  useEffect(() => {
    if(true === isLoading)
      return;

    const url = Routing.generate("_api_/affaires/{id}{._format}_get", {id:affaireId});
    fetch(url)
      .then((res) => res.json())
      .then((data) => {
        const affairePersonnes = data.affairePersonnes;
        let tmp=[];
        affairePersonnes.map((item) => {
          if(true === isAcceptedStatut(item.statut))
            tmp[tmp.length]=item;
        });

        setAffairePersonnes(tmp);
        setIsLoading(true);
      })
    ;
  },[]);

  return (
    <>
    {(isLoading === true) && <div>{affairePersonnes.map((item) => {
      return (
        <AffairePersonne affairePersonne={item} key={item.id} />
      )
    })}</div>}
    {(isLoading === false) && <div>Chargement en cours</div>}
    </>
  );
}

export default AffairePersonnes;
