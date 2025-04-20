import React, {useState, useEffect, Fragment} from 'react';
import {bindCommune} from '../../utils/';
export const DateType = function({value,setValue}) {

  const format = (value) => { return (value.split("T"))[0]; }
  const [formattedValue,setFormattedValue]=useState(format(value));
  function handleChange(props) {
    setValue(props.target.value);
    setFormattedValue(format(props.target.value));
  }

  useEffect(() => setFormattedValue(format(value)),[value]);

  return (
    <input
      type="date"
      placeholder="jj / mm / aaaa"
      className="fr-input"
      value={formattedValue}
      onChange={handleChange}
    />
  );
}

export const AutocompleteType = function({value,setValue,routeGet,routeRemoteGet,routeList,fakerBuild}) {

  const [filteredSuggestions, setFilteredSuggestions]=useState([]);
  const [showSuggestions, setShowSuggestions]=useState(false);
  const [userInput, setUserInput]=useState("");
  const [fakeValue, setFakeValue]=useState("");
  const [isLoading, setIsLoading]=useState(false);
  const [atimeout, setATimeout]=useState(null);
  function handleClick(props) {
    const remoteUrl = Routing.generate(routeRemoteGet,{id: props.target.value});
    fetch(remoteUrl)
      .then((response) => response.json())
      .then((data) => {
        const url = Routing.generate(routeGet,{id: data.id});
        setValue(url);
        setFakeValue(fakerBuild(data));
        setShowSuggestions(false);
      })
      .catch((err) => console.error(err))
    ;
  }

  function handleKeyUp(props) {
    const term = props.target.value;
    const url = Routing.generate(routeList,{term: term, limit: 10});
    //setUserInput(term);
    setShowSuggestions(false);
    if(term.length < 2)
      return;
    if (atimeout !== null)
      clearTimeout(atimeout);
    setATimeout(
      setTimeout(() => {
        fetch(url)
          .then((response) => response.json())
          .then((data) => {
            setFilteredSuggestions(data);
            setShowSuggestions(true);
          })
          .catch((err) => console.error(err))
        ;
      },1000)
    );
  }

  function handleRemove(props) {
    setFakeValue("");
    setValue("");
  }

  function handleChange(props) {
    const value = props.target.value;
    setFakeValue(value);
  }

  useEffect(() => {
    if(isLoading===true)
      return;
    if(value && !fakeValue) {
      (async () => {
        const response = await fetch(value);
        const data = await response.json();
        setFakeValue(data.plaintext);
      })();
      setIsLoading(true);
    }
  },[value]);

  return (
    <div className="fr-autocomplete-area">
      <Fragment>
        <div style={{float:'left', width:'100%'}}>
            <input
              type="text"
              className="fr-input fr-autocomplete ui-autocomplete-input"
              onKeyUp={handleKeyUp}
              onChange={handleChange}
              value={fakeValue}
            />
          <div className="load load-autocomplete hide"></div>
          <div className="remove-autocomplete" onClick={handleRemove}>
            <i className="ri-close-circle-fill"></i>
          </div>
        </div>
        <ul className="load-autocomplete" style={{width: '100%',position: 'absolute', top: '30px'}}>
        {showSuggestions && filteredSuggestions.length && filteredSuggestions.map(
          (suggestion) => {
          return (
            <li key={suggestion.id} id={suggestion.id} style={{
              padding:'10px',
              cursor:'pointer',
              backgroundColor:'#fff',
              borderBottom: '1px solid #d4d4d4',
              listStyleType: 'none'
            }} value={suggestion.value} onClick={handleClick}>{suggestion.label}</li>
          );
        })}
        </ul>
      </Fragment>
    </div>
  );
}

export const SelectType = function({value,setValue,url}) {

    const [content, setContent]=useState([]);
    const [isLoading, setIsLoading]=useState(false);
    useEffect(() => {

      if(true===isLoading)
        return;

      fetch(url)
        .then((response) => response.json())
        .then((data) => {
          const lContent = data["hydra:member"];
          const lCount = data["hydra:totalItems"];
          setContent(lContent);
          setIsLoading(true);
        })
        .catch((err) => console.log(err))
      ;
    },[url]);

    function handleChange(props) {
      content.map((item) => {
        if(props.target.value===item["@id"])
          setValue(item["@id"]);
      });
    }

    return (
      <select
        className="fr-select"
        onChange={handleChange}
        value={value}
      >
        {
          content.map((item,index) => <option key={item.id+'-'+index} id={item.id} value={item["@id"]}>{item.libelle}</option>)
        }
      </select>
    )
}

export const TextType = function({value,setValue,id=null}) {

  function handleChange(props) {
    setValue(props.target.value);
  }

  return (
    <input
      type="text"
      id={id}
      className="fr-input"
      value={value}
      onChange={handleChange}
    />
  );
}
