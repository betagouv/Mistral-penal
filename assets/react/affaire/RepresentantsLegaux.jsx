import React, {useEffect,useState} from 'react';
import RepresentantLegalCard from '../representantLegal/Card/';
import { useFetch } from '../utils/';
import { ModalContext } from '../representantLegal/Modal/';

export const deleteRepresentantLegal = async (id) => {
  const url = Routing.generate("_api_/representant_legals/{id}{._format}_delete",{id: id})
  return await useFetch(url,"DELETE")
  ;
}

const delay = async (seconds) => {
  return new Promise((resolve) => setTimeout(resolve,seconds*1000));
}

const RepresentantsLegaux = ({id,affaireId}) => {

  const [data, setData]=useState([]);
  const [loading, setLoading]=useState(false);

  function updateAccordionTitle(blob) {
    $(".affaire-personne").each((index,item) => {
      const personneId = parseInt(item.dataset['personneId']);
      if(personneId===parseInt(id)) {
        let title = getNomAffairePersonne(item);
        let tab=[];
        blob.map((i) => {
          const personne = i['representant']['personne'];
          tab[tab.length]= personne.nomComplet;
        });
        item.dataset['rl']=tab.join(', ');
        item.textContent=getNomAffairePersonne(item)+getRepresentants(item);
      }
    })
  }

  useEffect(() => updateAccordionTitle(data),[data]);

  function reloadData(data=null) {
    const url=Routing.generate('api_representant_legal_findall_GET_collection',{id: id});
    useFetch(url)
      .then((response) => response.json())
      .then((items) => {
        setData(items);
        setLoading(true);
        if(null!=data && data.length) {
          (async () => {
            let open_modal=true;
            do {
              await delay(1);
              const $btn = $("#rl_open_modal_"+data[0]);
              if($btn.length) {
                $btn.click();
                open_modal=false;
              }
            }while(open_modal==true);
        })
       ();
        }
      })
      .catch((e) => console.error(e))
    ;
  }

  useEffect(() => {
    if(true === loading)
      return;
    reloadData();
  },[]);

  function handleAdd() {
    showSpinner();
    const url = Routing.generate('api_representant_legal_new_POST_collection');
    useFetch(url,'POST',JSON.stringify({affaire_id: affaireId,represente_id: id}))
      .then((response) => response.json())
      .then((data) => reloadData(data))
      .catch((e) => console.error(e))
    ;

    event.preventDefault();
    event.stopPropagation();
  }

  return (
    <>
    {loading && data.map((item) => <RepresentantLegalCard key={item['id']} item={item} id={id} /> )}
    {loading &&
      <>
        <div className="fr-col-12 fr-pt-1w"></div>
        <div className="fr-col-12">
          <button onClick={handleAdd} className="fr-btn fr-btn--tertiary fr-btn-mistral">
              <i className="ri-add-line fr-ml-1w"></i>
              Ajouter un représentant légal
          </button>
        </div>
      </>
    }
    {!loading && <h5>Chargement en cours ...</h5>}
    </>);
}

export default RepresentantsLegaux;
