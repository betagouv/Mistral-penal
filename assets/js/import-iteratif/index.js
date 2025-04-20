async function importAudiences(id, currentDay, currentMonth) {
    const url = Routing.generate('audience_import_from_cassiopee', {
        'id': id
    });

    const redirect_url = Routing.generate('homepage', {'month': currentMonth})

    try {
        const res = await fetch(url);
        const data = await res.json();

      await monitorImport(data.importJobId);

      window.location.assign(redirect_url);
    } catch(ex) {
        console.error(ex);
    }
}

async function monitorImport(importJobId) {
  const url = Routing.generate('_api_/cassiopee_import_jobs/{id}{._format}_get', {
    'id': importJobId
  })

  let status = null;

  do {
    const response = await fetch(url);
    const jobData = await response.json();

    update_bar(null, jobData.progress)

    await sleep(1000);

    status = jobData.status
  } while (status != 'DONE');
}

function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

$(document).ready(function() {
  let currentMonth = $('.current-month').val();
  let currentDay = $('.current-day').val();
  let id = $('.id').val();
  if(currentMonth && currentDay) {
    importAudiences(id, currentDay, currentMonth);
  }
});

function update_bar(msg,perc) {
    let bar = $(".percentage");
    bar.css("width", perc+'%');
    bar.attr("data-perc", Math.floor(perc)+'%');
    //$(".bar-label").text(msg);
    update_label(Math.floor(perc));
}
  
function update_label(percent) {
    let $subtitle = $(".bar-label");
    let defaultTitle = $subtitle.data("defaultLabel");
    defaultTitle=defaultTitle.replace("#percent#",percent);
    $subtitle.text(defaultTitle);
}
