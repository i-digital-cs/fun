window.FunnityV3Charts = (() => {
  let eventsChart;
  let usersChart;
  let sparkChart;

  const brand = "#7138F5";
  const accent = "#FF762E";
  const ink = "#6B7390";
  const grid = "rgba(148,163,184,0.18)";

  function baseLineOpts() {
    const family = getComputedStyle(document.body).fontFamily;
    return {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: "index", intersect: false },
      plugins: {
        legend: {
          position: "bottom",
          labels: {
            boxWidth: 8,
            boxHeight: 8,
            usePointStyle: true,
            pointStyle: "circle",
            color: ink,
            padding: 14,
            font: { size: 12, family, weight: "600" },
          },
        },
        tooltip: {
          backgroundColor: "#19213D",
          titleColor: "#fff",
          bodyColor: "#E8EAF2",
          padding: 11,
          cornerRadius: 10,
          displayColors: true,
          titleFont: { family, size: 12, weight: "700" },
          bodyFont: { family, size: 12 },
        },
      },
      scales: {
        x: {
          ticks: { color: ink, maxRotation: 0, autoSkip: true, maxTicksLimit: 8, font: { size: 11, family } },
          grid: { display: false },
          border: { display: false },
        },
        y: {
          beginAtZero: true,
          ticks: { precision: 0, color: ink, font: { size: 11, family } },
          grid: { color: grid, drawTicks: false },
          border: { display: false },
        },
      },
    };
  }

  function destroy(chart) {
    if (chart) chart.destroy();
  }

  function renderSpark(canvas, values) {
    destroy(sparkChart);
    if (!canvas || typeof Chart === "undefined") return;
    sparkChart = new Chart(canvas, {
      type: "line",
      data: {
        labels: values.map((_, i) => i + 1),
        datasets: [{
          data: values,
          borderColor: "rgba(255,255,255,0.95)",
          backgroundColor: "rgba(255,255,255,0.18)",
          fill: true,
          tension: 0.4,
          borderWidth: 2,
          pointRadius: 0,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        scales: { x: { display: false }, y: { display: false } },
      },
    });
  }

  function renderEvents(canvas, period, labels) {
    destroy(eventsChart);
    if (!canvas || typeof Chart === "undefined") return;
    eventsChart = new Chart(canvas, {
      type: "line",
      data: {
        labels: period.days,
        datasets: [
          {
            label: labels.events,
            data: period.events_per_day,
            borderColor: brand,
            backgroundColor: "rgba(113,56,245,0.12)",
            fill: true,
            tension: 0.35,
            borderWidth: 2.5,
            pointRadius: 3,
            pointBackgroundColor: brand,
          },
          {
            label: labels.participants,
            data: period.participants_per_day,
            borderColor: accent,
            backgroundColor: "transparent",
            fill: false,
            tension: 0.35,
            borderWidth: 2.5,
            pointRadius: 3,
            pointBackgroundColor: accent,
          },
        ],
      },
      options: baseLineOpts(),
    });
  }

  function renderUsers(canvas, period, labels) {
    destroy(usersChart);
    if (!canvas || typeof Chart === "undefined") return;
    usersChart = new Chart(canvas, {
      type: "bar",
      data: {
        labels: period.days,
        datasets: [
          {
            label: labels.users,
            data: period.users_per_day,
            backgroundColor: brand,
            borderRadius: 8,
            maxBarThickness: 28,
          },
          {
            label: labels.events,
            data: period.events_per_day,
            backgroundColor: accent,
            borderRadius: 8,
            maxBarThickness: 28,
          },
        ],
      },
      options: baseLineOpts(),
    });
  }

  function applyReducedMotion() {
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches && typeof Chart !== "undefined") {
      Chart.defaults.animation = false;
    }
  }

  return { renderSpark, renderEvents, renderUsers, applyReducedMotion };
})();
