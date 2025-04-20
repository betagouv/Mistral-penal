import {writeNotification, writeSuccessNotification} from './notification';
import {updateNatinf} from './specific';
import { saveElement, removeElement, getElementById } from "../utils/db";
import MistralClient from "../utils/mistral-client";
import {useFetch} from '../../react/utils/';

const DEFAULT_REGISTER_NAMESPACE="/sw.js";
const DISPATCH_ELEMENTS=['renvoi','decision'];
const mistralClient = new MistralClient();

const getRegistration = async () => {
  if ("serviceWorker" in navigator)
    return navigator.serviceWorker.register(DEFAULT_REGISTER_NAMESPACE, { });
  else
    return null;
}

export const postMessage = async (message) => {
  const registration = await getRegistration();
  registration.active.postMessage(message);
}

const parseBlob = (blob) => {
  const action = blob['action']?blob['action']:null;
  const data = blob['data']?blob['data']:{};
  const tmp = action.split('-').length==2 ? action.split('-') : [null,null];
  const isSync = (tmp[0]==='sync');
  const element = tmp[1];
  const id = data['id'] ? parseInt(data['id']) : null;
  return {isSync: isSync, element: element,id: id, action: action, data: data};
}

export const registerServiceWorker = async (reload=false) => {
  if ("serviceWorker" in navigator) {
    try {
      const registration = await getRegistration();
      if (registration.installing) {
        console.log("Service worker installing");
      } else if (registration.waiting) {
        console.log("Service worker installed");
      } else if (registration.active) {
        console.log("Service worker active");
      }

      navigator.serviceWorker.ready.then((registration) => {
        if(true===reload)
          registration.update();
      });
    } catch (error) {
      console.error(`Registration failed with ${error}`);
    }
  }
};

const dispatchResult = (element, response, url) => {
    const updateEvent = new CustomEvent('update-'+element, {
        detail: { response:response, url:url }
    })
    document.querySelector("#"+element+"s-container").dispatchEvent(updateEvent);
}

export const syncElement = async (
  affaireId,
  element,
  elementId,
  data,
  isCloseModale = false,
  callBackSuccess = null,
  options = null
) => {
  writeNotification('ENREGISTREMENT EN COURS');
  const hasSpinner =  (null !== options && options.hasOwnProperty('spinner')) ? options.spinner : true;
  const {url,method} = generateUrl(affaireId, element, elementId);
  const storageId = await saveElement(element, affaireId, data, url, options);
  const registration = await getRegistration();
  (async () => {
    const result = await getElementById(element, storageId);
    if(result['url']&&result['data']) {
      if(true === hasSpinner) { showSpinner(); }
      useFetch(url,method,result.data)
        .then((response) => response.json())
        .then((data) => {
          writeSuccessNotification('ENREGISTRÉ');
          removeElement(element, storageId);
          if(DISPATCH_ELEMENTS.includes(element))
            dispatchResult(element,data,url);
          if(true === hasSpinner) { hideSpinner(); }
          if(isCloseModale) closeModale(element);
          if(callBackSuccess) callBackSuccess();
          /**
           * @author yanroussel
           *         Spécifique pour le natinf
           */
          if(['natinf','revertnatinf'].includes(element) && result['options'] && result.options['panelId'])
            updateNatinf(data, result.options.panelId);
        })
        .catch((e) => {console.log(e)})
    }
  })();
}

const generateUrl = (affaireId, element, elementId = null) => {

  const routing = [
    {
      name: 'renvoi',
      url_create: { route: 'affaire_renvois_post_item', params: {affaire:affaireId}, method: 'POST' },
      url_update: { route: 'affaire_renvois_update_item', params: {affaire:affaireId, renvoi:elementId}, method: 'POST' }
    },
    {
      name: 'revertnatinf',
      url_update: { route: 'api_affaire_natinfs_revert_disqual_POST', params: {id:affaireId, personnes:elementId}, method: 'POST' }
    },
    {
      name: 'natinf',
      url_update: { route: 'api_affaire_natinfs_disqual_requal_POST', params: {id:elementId}, method: 'POST' }
    },
    {
      name: 'affaire',
      url_create: { route: 'affaire_edit_general', params: {affaireId:affaireId,FORCE_OUTPUT_JSON:true}, method: 'POST' }
    },
    {
      name: 'decision',
      url_create: { route: 'affaire_decisions_post_item', params: {affaireId:affaireId}, method: 'POST' },
      url_update: { route: 'affaire_decisions_update_item', params: {affaireId:affaireId, decisionId:elementId}, method: 'POST' }
    },
  ];

  let output = null;
  routing.map(function(data) {
    if(data.name===element) {
      if(elementId && data['url_update']) {
        output={
          url: Routing.generate(data.url_update.route,data.url_update.params),
          method: data.url_update.method
        };
      }
      else if(data['url_create'])
        output={
          url: Routing.generate(data.url_create.route,data.url_create.params),
          method: data.url_create.method
        };
    }
  });
  return output;
}
