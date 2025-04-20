import React from 'react';
import { useEffect,useState, useRef  } from 'react';
import RenvoiComponent from './RenvoiComponent';
import Renvoi from '../component/Renvoi';

const isAcceptedStatut = (statut) => {
  const VALIDATED_STATUS = ['Prévenu',"Mis en cause","Accusé"];
  if(VALIDATED_STATUS.includes(statut.code))
    return true;
  return (statut.mnemo && (statut.mnemo.length > 0));
}

const updateAffairePersonnes = async function(affaireId) {
  const url = Routing.generate("_api_/affaires/{id}{._format}_get", {id:affaireId});
  const result = await fetch(url);
  const data = await result.json();
  const affairePersonnes = data.affairePersonnes;

  affairePersonnes.map((item) => {
    if(true === isAcceptedStatut(item.statut)) {
      const $obj = $('#renvoi_affairePersonnes_'+item.id);
      if(0 === $obj.length) {
        const $area = $("#checkboxes-renvoi-small");
        const html = `
        <div class="fr-fieldset__element" id="fieldset_renvoi_affairePersonnes_#id#">
          <div class="fr-pl-2w">
            <input type="checkbox" id="renvoi_affairePersonnes_#id#" value="#id#">
            <label
              class="fr-pl-2w"
              for="renvoi_affairePersonnes_#id#"
            >
              <span
                id="renvoi_affairePersonnes_#id#_label"
              >
              #nomComplet#
              </span>
              <span
                class="fr-pl-4w renvoi-modal-statut"
                id="renvoi_affairePersonnes_#id#_statut"
              >
              #statut#
              </span>
            </label>
          </div>
        </div>`
          .replaceAll("#id#",item.id)
          .replaceAll("#nomComplet#",item.personne.smallNomComplet)
          .replaceAll("#statut#",item.statut.libelle)
        ;
        $area.append(html);
      }
      else {
        const labelId = "#renvoi_affairePersonnes_"+item.id+"_label";
        const statutId = "#renvoi_affairePersonnes_"+item.id+"_statut";
        $(labelId).text(item.personne.smallNomComplet);
        $(statutId).text(item.statut.libelle);
      }
    }
  });
}

function updateDetailsMesureSureteState() {
    const $mesureSurete = document.querySelector('#renvoi_mesureSurete')
    const $detailsMesureSurete = document.querySelector('#formRenvoiDetailsMesureSureteContainer')

    $detailsMesureSurete.style.display = ($mesureSurete.value !== '') ? 'flex' : 'none'
}

function Renvois({affaireId, mistralClient}) {
    const [hasBeenCalled, setHasBeenCalled] = useState(false);
    const [updates,setUpdates]=useState(0);
    const [renvoisData,setRenvoisData]=useState([]);
    const deleteUrl = '_api_/renvois/{id}{._format}_delete';
    const [isLoading, setIsLoading]=useState(false);
    const [renvois, setRenvois]=useState([]);
/**
    const handle = () => {
        setUpdates(updates + 1);
    }

    const handleEvent = useRef(handle);
    handleEvent.current = handle;
*/
    useEffect(()=>{
        if(true === isLoading)
          return;

        const url = Routing.generate("_api_/renvois{._format}_get_collection", {
          affaire: affaireId

        })
        ;
        fetch(url)
        .then((response) => response.json())
        .then((blob) => {
          let tmp=[];
          blob["hydra:member"].map((renvoi) => tmp[tmp.length]=renvoi);
          setIsLoading(true);
          setRenvois(tmp);
        });

//        if(hasBeenCalled) return;
//        document.querySelector("#renvois-container").addEventListener("update-renvoi", event => handleEvent.current());
//        setHasBeenCalled(true);
    }, [isLoading]);

    useEffect(() => {
        const $mesureSurete = document.querySelector('#renvoi_mesureSurete')
        $mesureSurete.addEventListener('change', updateDetailsMesureSureteState)

        updateDetailsMesureSureteState()

        return () => {
            $mesureSurete.removeEventListener('change', updateDetailsMesureSureteState)
        }
    }, [])

    const deleteItem = (id) => {
      showSpinner();
      mistralClient
        .deleteItem(id, deleteUrl)
        .then(()=> {
          hideSpinner();
          const newRenvois = renvois.filter((data) => data.id !== id);
          setRenvois(newRenvois);
        })
        .catch((error) => hideSpinner())
      ;
    }

    const openModalRenvoi = () => {
        $('#toggle-dialog-20100').attr('data-fr-opened', true);
        $('#checkbox-all').prop('checked', false);
        updateAffairePersonnes(affaireId).then(() => {

          var d = new Date();
          d.toLocaleString('fr-FR', {
            timeZone: 'Europe/Paris',
          });
          var curr_date = d.getDate();
          var curr_month = d.getMonth();
          curr_month++;
          var curr_year = d.getFullYear();
          var dateValue = curr_date + "/" + curr_month + "/" + curr_year + " 10:10";
          $('#renvoi_date').val(dateValue);
          $('#renvoi_id').removeAttr("value");
          $('#renvoi_mesureSurete').val('')
          $('#renvoi_detailsMesureSurete').val('')
          $('#renvoi_expertise').val('')

          $("input[id^=renvoi_affairePersonnes_]").each(function() {
            $('#renvoi_affairePersonnes_'+this.value).prop('checked', false);
          });

          updateDetailsMesureSureteState()
        })
    }


    return (
        <>
            <p className="affaire-section">Renvoi(s)</p>
            <div className="fr-grid-row">
                <div className="fr-col-12">
                    <div className="fr-grid-row" id="fiche-container">
                    {renvois.map((renvoi, index) =>
                      <Renvoi
                        key={renvoi.id}
                        renvoi={renvoi}
                        index={index+1}
                        deleteItem={deleteItem}
                      />
                    )}
                    </div>
                </div>
            </div>

            <div className="fr-col-12 fr-pt-1w"></div>
            <div className="fr-col-12">
                <button onClick={openModalRenvoi} className="fr-btn fr-btn--tertiary fr-btn-mistral" data-fr-opened="false" aria-controls="fr-modal-renvoi">
                    <i className="ri-add-line fr-ml-1w"></i>
                    Ajouter un renvoi
                </button>
            </div>
        </>
    );
}

export default Renvois;
