import React, {useState, useEffect, useContext} from "react";
import { Label } from '../component/Label/';
import { TextType, SelectType, DateType } from '../component/InputForm/';

const AvocatType = function({id=null}) {

  const [nom,setNom]=useState("");
  const [prenom,setPrenom]=useState("");
  const [adresse,setAdresse]=useState("");
  const [barreau,setBarreau]=useState("");

  useEffect(() => {
    clear();
    $("#avocat_nom").val(nom);
    $("#avocat_prenom").val(prenom);
    $("#avocat_adresse").val(adresse);
    $("#avocat_barreau").val(barreau);
  },[nom,prenom,adresse,barreau]);

  const clear = () => {
    const reset = $("#avocat_reset").val();
    if(reset==1) {
      if(nom.length===1) { setPrenom(""); setAdresse(""); setBarreau(""); }
      if(prenom.length===1) { setNom(""); setAdresse(""); setBarreau(""); }
      if(barreau.length===1) { setNom(""); setPrenom(""); setAdresse(""); }
      if(adresse.length===1) { setNom(""); setPrenom(""); setBarreau(""); }
      $("#avocat_reset").val(0);
    }
  }

  return (
    <div className="fr-grid-row fr-grid-row--gutters">
      <input type="hidden" id="avocat_reset" />
      <div className="fr-col-3">
        <Label name={"Nom"} required={true}/>
      </div>
      <div className="fr-col-9">
        <TextType id={"avocat_nom_main"} value={nom} setValue={setNom} />
      </div>
      <div className="fr-col-3">
        <Label name={"Prénom"} required={true}/>
      </div>
      <div className="fr-col-9">
        <TextType id={"avocat_prenom_main"} value={prenom} setValue={setPrenom} />
      </div>
      <div className="fr-col-3">
        <Label name={"Barreau"} required={true}/>
      </div>
      <div className="fr-col-9">
        <TextType id={"avocat_barreau_main"} value={barreau} setValue={setBarreau} />
      </div>
      <div className="fr-col-3">
        <Label name={"Adresse"} required={true}/>
      </div>
      <div className="fr-col-9">
        <textarea id={"avocat_adresse_main"} className="fr-input"></textarea>
      </div>
    </div>
  );
}

export default AvocatType;
