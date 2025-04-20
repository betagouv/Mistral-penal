import React,{useState,useEffect} from 'react';
import { transformDateToLocaleString, formatDateStringToInputValue, getIdFromUrl } from '../utils/cast';

const isAcceptedStatut = (statut) => {
  const VALIDATED_STATUS = ['Prévenu',"Mis en cause","Accusé"];
  if(VALIDATED_STATUS.includes(statut.code))
    return true;
  return (statut.mnemo && (statut.mnemo.length > 0));
}

const resetAffairePersonneModal = () => {
  $("input[id^=renvoi_affairePersonnes_]").each(() => $(this).remove());
  $("#checkbox-all").prop("checked",false);
}

const appendAffairePersonneToModal = (affairePersonne, renvoi) => {
  const $obj = $('#renvoi_affairePersonnes_'+affairePersonne.id);
  const isChecked = renvoi && renvoi.affairePersonnes.includes(affairePersonne["@id"]);
  if(0 === $obj.length) {
    const $area = $("#checkboxes-renvoi-small");
    const html = `
    <div class="fr-fieldset__element" id="fieldset_renvoi_affairePersonnes_#id#">
      <div class="fr-pl-2w">
        <input type="checkbox" id="renvoi_affairePersonnes_#id#" value="#id#" #isChecked#>
        <label class="fr-pl-2w" for="renvoi_affairePersonnes_#id#">#nomComplet#<span class="fr-pl-4w renvoi-modal-statut">#statut#</span></label>
      </div>
    </div>`
      .replaceAll("#id#",affairePersonne.id)
      .replaceAll("#nomComplet#",affairePersonne.personne.smallNomComplet)
      .replaceAll("#statut#",affairePersonne.statut.libelle)
      .replaceAll("#isChecked#",isChecked?"checked":"")
    ;
    $area.append(html);
  }
  else
    $obj.prop("checked",isChecked);
}

function updateDetailsMesureSureteState() {
    const $mesureSurete = document.querySelector('#renvoi_mesureSurete')
    const $detailsMesureSurete = document.querySelector('#formRenvoiDetailsMesureSureteContainer')

    $detailsMesureSurete.style.display = ($mesureSurete.value !== '') ? 'flex' : 'none'
}

const Renvoi = ({renvoi,index,deleteItem}) => {
  const PROCESS_WAITING = 'En cours de chargement ...';
  const [isLoading, setIsLoading]=useState(false);
  const [renvoiMotif, setRenvoiMotif]=useState(PROCESS_WAITING);
  const [affairePersonnes, setAffairePersonnes]=useState("");
  const [_renvoi,setRenvoi]=useState(renvoi);

  function handleUpdate() {
    $('#renvoi_date').val(formatDateStringToInputValue(renvoi.date));
    $('#renvoi_id').val(renvoi.id);
    $("#renvoi_renvoiMotif").val(getIdFromUrl(renvoi.renvoiMotif));
    $('#renvoi_mesureSurete').val(renvoi.mesureSurete ?? '')
    $('#renvoi_detailsMesureSurete').val(renvoi.detailsMesureSurete ?? '')
    $('#renvoi_expertise').val(renvoi.expertise ?? '')

    updateDetailsMesureSureteState()

    resetAffairePersonneModal();
    fetch(renvoi.affaire)
      .then((response) => response.json())
      .then((data) => {
        data.affairePersonnes.map((item) => {
          if(true === isAcceptedStatut(item.statut)) {
            appendAffairePersonneToModal(item, _renvoi);
          }
        });
      })
  }

  const handleDelete = () => deleteItem(renvoi.id);

  useEffect(() => {
    if(true === isLoading)
      return;
    fetch(renvoi.renvoiMotif)
      .then((response) => response.json())
      .then((data) => setRenvoiMotif(data.libelle))
    ;
    let tmp="";
    let cpt = 0;
    renvoi.affairePersonnes.map((urlAP) => {
      fetch(urlAP)
        .then((response) => response.json())
        .then((data) => {
          tmp+= ((tmp)?", ":"")+data.personne.smallNomComplet;
          setAffairePersonnes(tmp);
        })
      ;
    });
    setIsLoading(true);
  },[isLoading])

  return (
    <>
    {isLoading &&
    <div className="fr-col-12 renvoi-card fr-mb-1w" id={"renvoi_fiche_"+renvoi.id} >
      <div className="fr-grid-row">
        <div className="fr-col-1 fr-pl-1w">
          <div className="renvoi-number">{index}</div>
        </div>
        <div className="fr-col-9">
          <strong>{transformDateToLocaleString(new Date(renvoi.date))} - {renvoiMotif}</strong>
          <br/>
          {affairePersonnes}
        </div>
        <div className="fr-col-1 fr-pl-2w fr-pt-1v">
            <button onClick={handleDelete} className="fr-btn  fr-btn--icon fr-btn--secondary fr-icon-delete-line button-icon-delete" title="Supprimer renvoi">
                Supprimer renvoi
            </button>
        </div>
        <div className="fr-col-1 fr-pt-1v">
            <button  onClick={handleUpdate} className="fr-btn fr-icon-edit-line" data-fr-opened="false" aria-controls="fr-modal-renvoi"  title="Editer renvoi">
                Editer renvoi
            </button>
        </div>
      </div>
    </div>
    }
    {!isLoading &&
      <div className="fr-col-12 renvoi-card fr-mb-1w">
        <div className="fr-grid-row">
          <h5>{PROCESS_WAITING}</h5>
        </div>
      </div>
    }
    </>
  )
}

export default Renvoi;
