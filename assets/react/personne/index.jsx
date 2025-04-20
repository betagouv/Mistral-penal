import React, {useState, useEffect, useContext} from "react";
import { Label } from '../component/Label/';
import { AutocompleteType, TextType, SelectType, DateType } from '../component/InputForm/';
import { ModalContext } from '../representantLegal/Modal/';

function PersonneType({id,start,ending,rand}) {

  const {personne, adresse, setAdresse, setPersonne}=useContext(ModalContext);

  const [adresseUrl, setAdresseUrl] = useState("");
  const [personneUrl, setPersonneUrl] = useState("");
  const [adresseCodePostal, setAdresseCodePostal] = useState("");
  const [adresseLocalite, setAdresseLocalite] = useState("");
  const [civilite, setCivilite] = useState("");
  const [communeNaissance, setCommuneNaissance] = useState("");
  const [dateNaissance, setDateNaissance] = useState("");
  const [nationalite, setNationalite] = useState("");
  const [nom, setNom] = useState("");
  const [adresseId, setAdresseId] = useState("");
  const [adresseLigne1, setAdresseLigne1] = useState("");
  const [adresseLigne2, setAdresseLigne2] = useState("");
  const [adresseLigne3, setAdresseLigne3] = useState("");
  const [adressePays, setAdressePays] = useState("");
  const [prenom1, setPrenom1] = useState("");
  const [randMemory, setRandMemory] = useState("");

  const fakerCommuneBuild = (data) => (data.codePostal)?(data.libelle+' ('+data.codePostal.substr(-5,2)+')'):data.libelle;

  useEffect((props) => {

    if(rand === randMemory)
      return;

    start();
    const url = Routing.generate('_api_/personnes/{id}{._format}_get', {id: id});
    setPersonneUrl(url);
    fetch(url)
      .then((response) => response.json())
      .then((data) => {
        if(data['adresse'])
          setAdresseUrl(data.adresse["@id"]);
        setAdresseId(data.adresse.id);
        setAdresseCodePostal(data.adresse.codePostal||'');
        setAdresseLigne1(data.adresse.ligne1||'');
        setAdresseLigne2(data.adresse.ligne2||'');
        setAdresseLigne3(data.adresse.ligne3||'');
        setAdresseLocalite(data.adresse.localite||'');
        if(data['adresse'] && data.adresse['pays'])
          setAdressePays(data.adresse.pays?data.adresse.pays["@id"]:"");
        if(data['civilite'])
          setCivilite(data.civilite?data.civilite["@id"]:"");
        if(data['communeNaissance'])
          setCommuneNaissance(data.communeNaissance?data.communeNaissance["@id"]:"");
        setDateNaissance(data.dateNaissance||'');
        setNom(data.nom||'');
        if(data['nationalite'])
          setNationalite(data.nationalite?data.nationalite["@id"]:"");
        setPrenom1(data.prenom1||'');
        setRandMemory(rand);

        ending();
      })
      .catch((err) => console.log(err))
    ;
  },[rand]);

  useEffect(() => {
    setAdresse({
      "@id": adresseUrl,
      id: adresseId,
      codePostal: adresseCodePostal,
      localite: adresseLocalite,
      ligne1: adresseLigne1,
      ligne2: adresseLigne2,
      ligne3: adresseLigne3,
      pays: adressePays
    });
    setPersonne({
      "@id": personneUrl,
      id: id,
      civilite: civilite,
      communeNaissance: communeNaissance,
      dateNaissance: (dateNaissance.split('T'))[0],
      nationalite: nationalite,
      nom: nom,
      prenom1: prenom1
    });
  },[
    adresseUrl,
    adresseCodePostal,
    adresseLocalite,
    civilite,
    communeNaissance,
    dateNaissance,
    nationalite,
    nom,
    adresseLigne1,
    adresseLigne2,
    adresseLigne3,
    adressePays,
    prenom1
  ]);

  return (
    <div className="fr-grid-row">
      <div className="fr-col-3">
        <Label name={"Civilité"} required={false}/>
      </div>
      <div className="fr-col-3">
        <SelectType
          value={civilite}
          setValue={setCivilite}
          url={Routing.generate('_api_/civilites{._format}_get_collection')}
        />
      </div>
      <div className="fr-col-12 fr-mt-1w"></div>
      <div className="fr-col-3">
        <Label name={"Nom"} required={true}/>
      </div>
      <div className="fr-col-9">
        <TextType value={nom} setValue={setNom} />
      </div>
      <div className="fr-col-12 fr-mt-1w"></div>
      <div className="fr-col-3">
        <Label name={"Prénom"} required={true}/>
      </div>
      <div className="fr-col-9">
        <TextType value={prenom1} setValue={setPrenom1} />
      </div>
      <div className="fr-col-12 fr-mt-1w"></div>
      <div className="fr-col-3">
        <Label name={"Né.e le"} required={true}/>
      </div>
      <div className="fr-col-3">
        <DateType value={dateNaissance} setValue={setDateNaissance} />
      </div>
      <div className="fr-col-1">
        <Label name={"à"} required={true}/>
      </div>
      <div className="fr-col-5">
        <AutocompleteType
          value={communeNaissance}
          setValue={setCommuneNaissance}
          routeGet={'_api_/communes/{id}{._format}_get'}
          routeList={'_api_communes/api-srj/v1_get_collection'}
          routeRemoteGet={'_api_communes/api-srj/v1/{id}_get'}
          fakerBuild={fakerCommuneBuild}
        />
      </div>
      <div className="fr-col-12 fr-mt-1w"></div>
      <div className="fr-col-3">
        <Label name={"Nationalité"} required={false}/>
      </div>
      <div className="fr-col-9">
        <SelectType
          value={nationalite}
          setValue={setNationalite}
          url={Routing.generate('_api_/nationalites{._format}_get_collection')}
        />
      </div>
      <div className="fr-col-12 fr-mt-1w"></div>
      <div className="fr-col-3">
        <Label name={"Adresse"} required={false}/>
      </div>
      <div className="fr-col-9">
        <TextType value={adresseLigne1} setValue={setAdresseLigne1} />
      </div>
      <div className="fr-col-12 fr-mt-1w"></div>
      <div className="fr-col-3"></div>
      <div className="fr-col-9">
        <TextType value={adresseLigne2} setValue={setAdresseLigne2} />
      </div>
      <div className="fr-col-12 fr-mt-1w"></div>
      <div className="fr-col-3"></div>
      <div className="fr-col-9">
        <TextType value={adresseLigne3} setValue={setAdresseLigne3} />
      </div>
      <div className="fr-col-12 fr-mt-1w"></div>
      <div className="fr-col-3">{"Code postal"}</div>
      <div className="fr-col-3">
        <TextType value={adresseCodePostal} setValue={setAdresseCodePostal} />
      </div>
      <div className="fr-col-12 fr-mt-1w"></div>
      <div className="fr-col-3">{"Ville"}</div>
      <div className="fr-col-9">
        <TextType value={adresseLocalite} setValue={setAdresseLocalite} />
      </div>
      <div className="fr-col-12 fr-mt-1w"></div>
      <div className="fr-col-3">{"Pays"}</div>
      <div className="fr-col-9">
        <SelectType
          value={adressePays}
          setValue={setAdressePays}
          url={Routing.generate('_api_/pays{._format}_get_collection')}
        />
      </div>
    </div>
  );
}

export default PersonneType;
