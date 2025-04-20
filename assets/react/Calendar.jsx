import React from 'react';
import {Component} from 'react';
import moment from 'moment';
import Audience from './Audiences';

class Calendar extends Component {
    constructor( props ) {
        super();
        this.year = props.year;
        this.month = props.month;

        // config moment en fr et premier jour de la semaine lundi
        moment.updateLocale("fr", {});
        // noms des jours de la semaine
        this.weekdayshort = moment.weekdays(true);
        this.dateObject = moment(this.year+"-"+this.month+"-01");

        this.audiences = []
        props.audiences.forEach(a => {
            a.date = moment(a.date).startOf('day')
            const key = a.date.format('MMDD')
            if (!Array.isArray(this.audiences[key])) {
                this.audiences[key] = []
            }

            this.audiences[key].push(a)
        })
    }

    // jours de la semaine
    getWeekDays(day){
        return (
            <th key={day} scope="col" width="14%" className="week-day">
                { day.charAt(0).toUpperCase() + day.slice(1) }
            </th>
        );
    }

    render() {
        let currentDay = moment(this.dateObject).startOf("week")
        let lastDay = moment(this.dateObject).endOf("month").endOf('week').startOf("day")

        const rows = []
        let daysCount = 0;

        do {
            const d = currentDay.date()
            const key = currentDay.format('MMDD')
            const audiences = this.audiences[key] || []
            const isCurrentMonth = currentDay.month() == this.dateObject.month()

            const cell = (
                <td key={key} className="td-calendar-day fr-p-2v">
                    <div className="fr-pl-1v"><strong>{isCurrentMonth && d}</strong></div>

                    {audiences.map(a => (
                        <Audience
                            key={a.id}
                            id={a.id}
                            finAudience={a.finAudience}
                            idKsp={a.idKsp}
                            debut={a.debut}
                            serviceLabel={a.serviceLabel}
                            quantiteJugee={0}
                            quantitePrevue={a.numberOfFolders}
                            dateDernierImport={a.dateDernierImport}
                            dateMiseAJour={a.dateMiseAJour}
                            audienceType={a.type}
                            url={Routing.generate('audience_show', {id:a.id})}
                            jour={d}
                            mois={this.month}
                            annee={this.year}
                        />
                    ))}

                </td>)

            const rowId = Math.floor(daysCount / 7)

            if (!Array.isArray(rows[rowId])) {
                rows[rowId] = []
            }

            rows[rowId].push(cell)

            daysCount++
            currentDay.add(1, 'days')

        } while (lastDay.diff(currentDay) !== 0);

        return (
            <div className="fr-grid-row">
                <div className="fr-col-12 fr-table fr-table--bordered fr-table--layout-fixed">
                    <table>
                        <thead>
                            <tr key="tr 10">
                                { this.weekdayshort.map(day => this.getWeekDays(day)) }
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((r, i) => (
                                <tr key={i}>
                                    {r}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        );
    }
  }

  export default Calendar;
