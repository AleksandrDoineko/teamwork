/* SVĒTKU IELĀDE NO API */

async function loadHolidays(force = false) {
    const list = document.getElementById("svetki-list");

    const now = new Date();
    const currentMonth = now.getMonth(); // Pašreizējais mēnesis (0–11)

    const cached = localStorage.getItem("holidays_cache");
    const cachedMonth = localStorage.getItem("holidays_month");

    // Ja kešā ir dati un tie ir no šī mēneša — izmantojam tos
    if (!force && cached && cachedMonth == currentMonth) {
        renderHolidays(JSON.parse(cached));
        return;
    }

    list.innerHTML = "<li>Notiek ielāde...</li>";

    try {
        const year = now.getFullYear();

        // API pieprasījums
        const response = await fetch(`https://date.nager.at/api/v3/PublicHolidays/${year}/LV`);
        const data = await response.json();

        // Saglabājam kešā
        localStorage.setItem("holidays_cache", JSON.stringify(data));
        localStorage.setItem("holidays_month", currentMonth);

        renderHolidays(data);

    } catch (e) {
        list.innerHTML = "<li>Kļūda ielādējot datus.</li>";
    }
}

/*
  Funkcija attēlo svētkus HTML sarakstā.
  - filtrē tikai nākotnes datumus
  - sakārto pēc tuvuma
  - parāda tikai 5 tuvākos
*/
function renderHolidays(data) {
    const list = document.getElementById("svetki-list");
    const today = new Date();

    const upcoming = data
        .filter(h => new Date(h.date) >= today) // tikai nākotnes svētki
        .sort((a, b) => new Date(a.date) - new Date(b.date)) // sakārto pēc datuma
        .slice(0, 5); // tikai 5 tuvākie

    list.innerHTML = "";

    upcoming.forEach(h => {
        const li = document.createElement("li");
        li.innerHTML = `
            <span class="svetki-name">${h.localName}</span>
            <span class="svetki-date">${formatDate(h.date)}</span>
        `;
        list.appendChild(li);
    });

    if (upcoming.length === 0) {
        list.innerHTML = "<li>Šogad vairs nav svētku.</li>";
    }
}

/*
  Funkcija formatē datumu latviešu valodā:
  piemēram: "11. novembris"
*/
function formatDate(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString("lv-LV", {
        day: "numeric",
        month: "long"
    });
}

/* Automātiski ielādē svētkus, kad lapa atveras */
loadHolidays();