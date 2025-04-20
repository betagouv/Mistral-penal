import React from 'react';
import { useEffect,useState } from 'react';

const transformDateToLocaleString = (date) => {
  const options = {
      timeZone: 'Europe/Paris', hour12: false, year: 'numeric',
      day: '2-digit', month: '2-digit', minimumIntegerDigits: 2,
      minute: "2-digit", hour: "2-digit"
  }
  return date.toLocaleString('fr-FR', options);
}

const transformLocaleStringToDate = (str) => {
  let tmp = str.replace(",","");
  const [dataDate,dataTime] = tmp.split(' ');
  let tabDate = dataDate.split('/');
  const enDate =tabDate[2]+'-'+tabDate[1]+'-'+tabDate[0];
  return enDate+'T'+dataTime;
}

function RenvoiComponent({data, index, deleteItem, getAffairePersonnes}) {

    let date = new Date(data.date);
    let dateValue = transformDateToLocaleString(date);

    function handleClick(){
        deleteItem(data.id);
    }

    const updateRenvoi = () => {
        const myDate = transformLocaleStringToDate(dateValue);
        $('#renvoi_date').val(myDate);
        $('#renvoi_id').val(data.id);
        $("#renvoi_renvoiMotif").val(data.renvoiMotif.id);
        $('#renvoi_mesureSurete').val(data.mesureSurete)
        $('#renvoi_detailsMesureSurete').val(data.detailsMesureSurete)
        $('#renvoi_expertise').val(data.expertise)

        getAffairePersonnes(data.id);

        $("input[id^=renvoi_affairePersonnes_]").each(function() {
            data.affairePersonnes.map((affairePersonne) => {
                if(affairePersonne.id == this.value){
                    $('#renvoi_affairePersonnes_'+this.value).prop('checked', true);
                    $('#renvoi_affairePersonnes_'+this.value).attr("disabled", false);
                }
            });
        });
    }

    return (
        <div className="fr-col-12 renvoi-card fr-mb-1w" id={"renvoi_fiche_"+data.id} >
            <div className="fr-grid-row">
                <div className="fr-col-1 fr-pl-1w">
                    <div className="renvoi-number">{index}</div>
                </div>
                <div className="fr-col-9">
                    <strong>{dateValue} - {data.renvoiMotif.libelle}</strong> <br/>
                    {data.affairePersonnes.map((affairePersonne, index)=>{
                        let sep = " - ";
                        if(index == 0) sep = "";
                        return sep+affairePersonne.nomComplet
                    })}
                </div>
                <div id={"renvoi_personnes_"+data.id} data-personnes={data.affairePersonnesId}></div>
                <div className="fr-col-1 fr-pl-2w fr-pt-1v">
                    <button onClick={handleClick} className="fr-btn  fr-btn--icon fr-btn--secondary fr-icon-delete-line button-icon-delete" title="Supprimer renvoi">
                        Supprimer renvoi
                    </button>
                </div>
                <div className="fr-col-1 fr-pt-1v">
                    <button  onClick={updateRenvoi} className="fr-btn fr-icon-edit-line" data-fr-opened="false" aria-controls="fr-modal-renvoi"  title="Editer renvoi">
                        Editer renvoi
                    </button>
                </div>
            </div>
        </div>
    );
}

export default RenvoiComponent;
