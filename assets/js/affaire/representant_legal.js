import React, { useEffect,useState, useRef  } from 'react';
import RepresentantLegaux from '../../react/affaire/RepresentantsLegaux';
import  MistralClient from '../utils/mistral-client';
import {syncElement} from '../serviceWorker/';
import { createRoot } from 'react-dom/client';

var rl_containers=[];
var affaireId=null;
/**
 * @author yanroussel
 *         Page dédiée à la gestion des représentants légaux
 *
 */
$(document).ready(function() {
  affaireId = parseInt($('#affaire_id').val());
  generate_rl_containers();
});

export function refresh_rl_container_by_id(id) {
  generate_rl_container_by_id(id);
}

function generate_rl_containers() {
  $(".representant-legal-container").each((index,item) => {
    const id = (item.dataset && item.dataset['id']) ? item.dataset['id'] : null;
    if(id) {
      const rootRL = createRoot(item);
      rootRL.render(
        <RepresentantLegaux affaireId={affaireId} id={id} key={'rl-'+id}/>
      );
      rl_containers[parseInt(id)]=rootRL;
    }
  });
}

export const generate_rl_container_from_scratch_by_id=(id) => {
  let rootRL = null;
  $(".representant-legal-container").each((index,item) => {
    const lid = (item.dataset && item.dataset['id']) ? parseInt(item.dataset['id']) : null;
    if(id===lid) {
      rootRL = createRoot(item);
      rootRL.render(
        <RepresentantLegaux affaireId={affaireId} id={id} key={'rl-'+id}/>
      );
      rl_containers[id]=rootRL;
    }
  });
}

function generate_rl_container_by_id(id) {
  let rootRL = rl_containers[id];
  if(rootRL) {
    rootRL.unmount();
    $(".representant-legal-container").each((index,item) => {
      const lid = (item.dataset && item.dataset['id']) ? parseInt(item.dataset['id']) : null;
      if(id===lid) {
        rootRL = createRoot(item);
        rootRL.render(
          <RepresentantLegaux affaireId={affaireId} id={id} key={'rl-'+id}/>
        );
        rl_containers[id]=rootRL;
      }
    });
  }
}
