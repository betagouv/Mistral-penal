import React from 'react';

const isJsonString = (str) => {
    try { JSON.parse(str); }
    catch (e) { return false; }
    return true;
}

export const useFetch = async (url, method='GET', data=null) => {
  const isJson = isJsonString(data);
  //const body = isJson ? JSON.stringify(data) : data;
  const body = data;
  let defaultOptions = {
    method: method,
    headers: { 'Content-Type': (isJson ? 'application/ld+json' : 'application/x-www-form-urlencoded; charset=UTF-8') },
    body: body
  };
  return await fetch(url, defaultOptions)
  ;
}
