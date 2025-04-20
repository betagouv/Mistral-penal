const DEFAULT_TIMEOUT=60000*5;

function execRefreshSelenium() {
  const route = Routing.generate('refresh_ksp');
  $.ajax(route);
}

function waitForRefresh(timeout=60000) {
  setTimeout(() => refreshSelenium(timeout),timeout);
}

function refreshSelenium(timeout=60000) {
  execRefreshSelenium();
  setTimeout(() => refreshSelenium(timeout),timeout);
}

$(document).ready(() => waitForRefresh(DEFAULT_TIMEOUT));
