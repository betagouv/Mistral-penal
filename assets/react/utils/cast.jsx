export const transformDateToLocaleString = (date) => {
  const options = {
      timeZone: 'Europe/Paris', hour12: false, year: 'numeric',
      day: '2-digit', month: '2-digit', minimumIntegerDigits: 2,
      minute: "2-digit", hour: "2-digit"
  }
  return date.toLocaleString('fr-FR', options);
}

export const formatDateStringToInputValue = (str) => {
  return str.split("+")[0];
}

export const getIdFromUrl = (url) => {
  const regexp = /^(.*)\/(\d+)$/;
  return url.replace(regexp, "$2");
}
export const transformLocaleStringToDate = (str) => {
  let tmp = str.replace(",","");
  const [dataDate,dataTime] = tmp.split(' ');
  let tabDate = dataDate.split('/');
  const enDate =tabDate[2]+'-'+tabDate[1]+'-'+tabDate[0];
  return enDate+'T'+dataTime;
}
