import React, {useState, useEffect, useContext} from "react";
import { Label } from '../component/Label/';
import { AutocompleteType, TextType, SelectType, DateType } from '../component/InputForm/';
import { ModalContext } from '../representantLegal/Modal/';

const RepresentantLegalType = function({id,rand}) {

  const {representantLegal, setRepresentantLegal}=useContext(ModalContext);

  const [lienSocial,setLienSocial]=useState("");
  const [lienJuridique, setLienJuridique]=useState("");
  const [randMemory, setRandMemory] = useState("");
  const [representantLegalUrl, setRepresentantLegalUrl]= useState("");

  useEffect((props) => {

    if(rand === randMemory)
      return;

    const url = Routing.generate('_api_/representant_legals/{id}{._format}_get', {id: id});
    setRepresentantLegalUrl(url);
    fetch(url)
      .then((response) => response.json())
      .then((data) => {
        setLienSocial(data.lienSocial?data.lienSocial["@id"]:"");
        setLienJuridique(data.lienJuridique?data.lienJuridique["@id"]:"");
        setRandMemory(rand);
      })
    ;
  },[rand]);

  useEffect(() => {
    setRepresentantLegal({
      "@id": representantLegalUrl,
      id: id,
      lienSocial: lienSocial,
      lienJuridique: lienJuridique
    })
  },[lienSocial, lienJuridique]);

  return (
    <div className="fr-grid-row">
      <div className="fr-col-3">
        <Label name={"Lien social"} required={false}/>
      </div>
      <div className="fr-col-9">
        <SelectType
          value={lienSocial}
          setValue={setLienSocial}
          url={Routing.generate('_api_/lien_socials{._format}_get_collection')}
        />
      </div>
      <div className="fr-col-12 fr-mt-1w"></div>
      <div className="fr-col-3">
        <Label name={"Lien juridique"} required={false}/>
      </div>
      <div className="fr-col-9">
        <SelectType
          value={lienJuridique}
          setValue={setLienJuridique}
          url={Routing.generate('_api_/lien_juridiques{._format}_get_collection')}
        />
      </div>
      <div className="fr-col-12 fr-mt-1w"></div>
    </div>
  );
}

export default RepresentantLegalType;
