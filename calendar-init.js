document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridWeek',
        locale: 'es',
        height: '100%',
        contentHeight: 'auto',
        aspectRatio: 1, 
        headerToolbar: {
            left: '',
            center: 'title',
            right: ''
        },
        footerToolbar: {
            left: '',
            center: '',
            right: 'prev,next today'            
        },
        buttonText: {
            today: 'Hoy'
        },
        dayHeaderFormat: { weekday: 'short' },
        dayCellContent: function (arg) {
            return { html: `<span>${arg.date.getDate()}</span>` }; 
        }
    });

    calendar.render();
});
