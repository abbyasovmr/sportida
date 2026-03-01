// Demo events data
const events = [
    {
        id: 1,
        title: "Кубок «Весенняя капель»",
        date: "15-16 марта 2026",
        location: "Москва, СК «Олимпийский»",
        type: "Соревнование",
        category: "Художественная гимнастика"
    },
    {
        id: 2,
        title: "Открытый турнир «Гимнастка»",
        date: "22-23 марта 2026",
        location: "Санкт-Петербург, ДС «Юбилейный»",
        type: "Соревнование",
        category: "Художественная гимнастика"
    },
    {
        id: 3,
        title: "Учебно-тренировочные сборы",
        date: "1-7 апреля 2026",
        location: "Сочи, ЦС «Юность»",
        type: "УТС",
        category: "Художественная гимнастика"
    },
    {
        id: 4,
        title: "Первенство Московской области",
        date: "12-13 апреля 2026",
        location: "Красногорск, СШОР №3",
        type: "Соревнование",
        category: "Художественная гимнастика"
    },
    {
        id: 5,
        title: "Мастер-класс с Олимпийской чемпионкой",
        date: "20 апреля 2026",
        location: "Москва, Академия гимнастики",
        type: "Мастер-класс",
        category: "Художественная гимнастика"
    },
    {
        id: 6,
        title: "Всероссийские соревнования «Надежды»",
        date: "5-8 мая 2026",
        location: "Казань, Ак Барс Арена",
        type: "Соревнование",
        category: "Художественная гимнастика"
    }
];

// Render events
function renderEvents() {
    const grid = document.getElementById('events-grid');
    if (!grid) return;

    grid.innerHTML = events.map(event => `
        <div class="event-card">
            <span class="event-date">${event.date}</span>
            <h3 class="event-title">${event.title}</h3>
            <p class="event-location">📍 ${event.location}</p>
            <div class="event-footer">
                <span class="event-type">${event.type}</span>
                <button class="btn btn-outline" onclick="showEventDetails(${event.id})">Подробнее</button>
            </div>
        </div>
    `).join('');
}

// Show event details (demo)
function showEventDetails(eventId) {
    const event = events.find(e => e.id === eventId);
    if (event) {
        alert(`Событие: ${event.title}\nДата: ${event.date}\nМесто: ${event.location}\n\nЗдесь будет карточка мероприятия с формой записи.`);
    }
}

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    renderEvents();
});
