import React from 'react';

export const Label = function(props) {

  const name=props.name ? props.name : 'non défini';
  const required=props.required && (true === props.required);

  return (
    <label className="form-label">{name} {required && '*'}</label>
  );
}
