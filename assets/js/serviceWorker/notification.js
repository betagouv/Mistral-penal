const className = '.auto-record-status';

export function writeSuccessNotification(message) {
  cleanupNotification();
  $(className)
    .children('p')
    .addClass('auto-record-badge-succes')
    .addClass('fr-badge--success')
    .html(message)
  ;
  showNotification();
  setTimeout(() => hideNotification(),5000);
}

export function writeErrorNotification(message) {
  cleanupNotification();
  $(className)
    .children('p')
    .addClass('auto-record-badge-succes')
    .addClass('fr-badge--error')
    .html(message)
  ;
  showNotification();
  setTimeout(() => hideNotification(),5000);
}

export function writeNotification(message) {
  cleanupNotification();
  $(className)
    .children('p')
    .addClass('auto-record-badge-new')
    .addClass('fr-badge--new')
    .html(message)
  ;
  showNotification();
}

function cleanupNotification() {
  $(className)
    .children('p')
    .removeClass('auto-record-badge-succes')
    .removeClass('fr-badge--success')
    .removeClass('auto-record-badge-new')
    .removeClass('fr-badge--new')
  ;
}

export function hideNotification() {
  $(className).hide();
}

function showNotification() {
  $(className).show();
}
