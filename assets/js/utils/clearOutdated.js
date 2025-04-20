
export const clearOutdated = (outdated, db) => {
    console.log('clearOutdated');
    db.table('affaire').toArray().then( result => {
        for (let d = 0; d < result.length; d++) {
            const affaire = result[d];
            let date = affaire.date.replace('"', '').substring(0,19).replace('T', ' ');
            if(date < outdated) {
                db.table('affaire').delete(affaire.id)
            }
        }
    })

}