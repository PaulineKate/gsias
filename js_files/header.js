
(function () {
    "use strict";

    const DAYS   = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
    const MONTHS = ["January","February","March","April","May","June",
                    "July","August","September","October","November","December"];

    /**
     * Pad a number to 2 digits.
     * @param {number} n
     * @returns {string}
     */
    function pad(n) {
        return String(n).padStart(2, "0");
    }

    /**
     * Format the time as "hh:mm AM/PM"
     * @param {Date} now
     * @returns {string}
     */
    function formatTime(now) {
        let hours   = now.getHours();
        const mins  = pad(now.getMinutes());
        const secs  = pad(now.getSeconds());
        const ampm  = hours >= 12 ? "PM" : "AM";
        hours = hours % 12 || 12;
        return `${pad(hours)}:${mins}:${secs} ${ampm}`;
    }

    /**
     * Format the date as "DayName, Month DD, YYYY"
     * @param {Date} now
     * @returns {string}
     */
    function formatDate(now) {
        const dayName = DAYS[now.getDay()];
        const month   = MONTHS[now.getMonth()];
        const day     = now.getDate();
        const year    = now.getFullYear();
        return `${dayName}, ${month} ${day}, ${year}`;
    }

    function tick() {
        const now      = new Date();
        const timeEl   = document.getElementById("header-time");
        const dateEl   = document.getElementById("header-date");

        if (timeEl) timeEl.textContent = formatTime(now);
        if (dateEl) dateEl.textContent = formatDate(now);
    }

    tick();
    setInterval(tick, 1000);

})();