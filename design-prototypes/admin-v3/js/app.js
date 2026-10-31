(() => {
  const state = {
    locale: "en",
    period: "7d",
    collapsed: false,
    fixtures: null,
  };

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

  function t(key) {
    return (window.FunnityV3I18n[state.locale] || {})[key] || key;
  }

  function fmt(n) {
    return new Intl.NumberFormat(state.locale === "ar" ? "ar-EG" : "en-US").format(n);
  }

  function deltaClass(v) {
    if (v > 0) return "";
    if (v < 0) return "is-down";
    return "is-flat";
  }

  function deltaText(v) {
    const sign = v > 0 ? "+" : "";
    return `${sign}${fmt(v)} ${t("vs_prev")}`;
  }

  function applyI18n() {
    $$("[data-i18n]").forEach((el) => {
      el.textContent = t(el.getAttribute("data-i18n"));
    });
    $$("[data-i18n-aria]").forEach((el) => {
      el.setAttribute("aria-label", t(el.getAttribute("data-i18n-aria")));
    });
    $$("[data-demo]").forEach((el) => el.setAttribute("data-demo", t("demo")));
  }

  function setLocale(locale) {
    state.locale = locale;
    document.documentElement.lang = locale;
    document.documentElement.dir = locale === "ar" ? "rtl" : "ltr";
    applyI18n();
    renderAll();
  }

  function setPeriod(period) {
    state.period = period;
    $$(".segmented button").forEach((btn) => {
      btn.setAttribute("aria-pressed", String(btn.dataset.period === period));
    });
    renderAll();
  }

  function toggleSidebar() {
    state.collapsed = !state.collapsed;
    $(".app")?.classList.toggle("is-collapsed", state.collapsed);
    const btn = $("#btn-collapse");
    if (btn) btn.setAttribute("aria-label", t(state.collapsed ? "expand" : "collapse"));
  }

  function openDrawer(open) {
    $(".sidebar")?.classList.toggle("is-open", open);
    $(".drawer-backdrop")?.classList.toggle("is-open", open);
  }

  function renderKpis(period) {
    const k = period.kpis;
    $("#kpi-featured-value").textContent = fmt(k.total_users);
    $("#kpi-new-users").textContent = fmt(k.new_users);
    $("#kpi-new-users-delta").textContent = deltaText(k.new_users_delta);
    $("#kpi-new-users-delta").className = `kpi-card__delta ${deltaClass(k.new_users_delta)}`;
    $("#kpi-published").textContent = fmt(k.published_events);
    $("#kpi-participants").textContent = fmt(k.participants);
    $("#kpi-participants-delta").textContent = deltaText(k.participants_delta);
    $("#kpi-participants-delta").className = `kpi-card__delta ${deltaClass(k.participants_delta)}`;
    $("#kpi-events-created").textContent = fmt(k.events_created);
    $("#kpi-events-created-delta").textContent = deltaText(k.events_created_delta);
    $("#kpi-events-created-delta").className = `kpi-card__delta ${deltaClass(k.events_created_delta)}`;
    $("#strip-session").textContent = fmt(k.session_active);
    $("#strip-active").textContent = fmt(k.active_accounts);
    $("#strip-reports").textContent = fmt(k.pending_reports);
    $("#strip-created").textContent = fmt(k.events_created);

    const range = state.locale === "ar" ? period.range_ar : period.range_en;
    $("#period-range").textContent = range;
  }

  function renderActions() {
    const root = $("#action-list");
    if (!root) return;
    const tierMap = {
      critical: { cls: "is-critical", badge: "badge--critical", label: t("critical") },
      warning: { cls: "is-warning", badge: "badge--warning", label: t("warning") },
      info: { cls: "", badge: "badge--info", label: t("info") },
      ok: { cls: "is-ok", badge: "badge--ok", label: t("clear") },
    };
    root.innerHTML = state.fixtures.actions.map((item) => {
      const tier = tierMap[item.tier] || tierMap.info;
      const label = state.locale === "ar" ? item.label_ar : item.label_en;
      const desc = state.locale === "ar" ? item.desc_ar : item.desc_en;
      return `<a class="action-row ${tier.cls}" href="#action" aria-label="${label}">
        <div class="action-row__count">${fmt(item.count)}</div>
        <div>
          <p class="action-row__title">${label}</p>
          <p class="action-row__desc">${desc}</p>
        </div>
        <span class="badge ${tier.badge}">${tier.label}</span>
      </a>`;
    }).join("");
  }

  function renderActivity() {
    const root = $("#activity-list");
    if (!root) return;
    root.innerHTML = state.fixtures.activity.map((row) => {
      const type = state.locale === "ar" ? row.type_ar : row.type_en;
      const title = state.locale === "ar" ? row.title_ar : row.title_en;
      return `<div class="activity-item">
        <div class="activity-item__icon" aria-hidden="true">${row.type}</div>
        <div class="min-w-0">
          <div class="activity-item__type">${type}</div>
          <div class="activity-item__title">${title}</div>
        </div>
        <div class="activity-item__meta">
          <div>${row.at}</div>
          <span class="badge badge--soft" style="margin-top:6px">${row.status}</span>
        </div>
      </div>`;
    }).join("");
  }

  function renderEventsTable() {
    const root = $("#events-tbody");
    if (!root) return;
    root.innerHTML = state.fixtures.recent_events.map((e) => {
      const title = state.locale === "ar" ? e.title_ar : e.title_en;
      const org = state.locale === "ar" ? e.organizer_ar : e.organizer_en;
      const city = state.locale === "ar" ? e.city_ar : e.city_en;
      return `<tr>
        <td class="event-name">${title}</td>
        <td>${org}</td>
        <td>${city}</td>
        <td>${e.starts}</td>
        <td><span class="badge badge--soft">${e.status}</span></td>
        <td>${fmt(e.members)}</td>
      </tr>`;
    }).join("");
  }

  function renderSocial() {
    const s = state.fixtures.social;
    const map = {
      "social-fr": s.friend_requests,
      "social-fa": s.friend_accepts,
      "social-em": s.event_messages,
      "social-dm": s.direct_messages,
      "social-af": s.accepted_friendships,
      "social-pf": s.pending_friendships,
      "social-creators": s.creators_period,
      "social-joiners": s.joiners_period,
      "social-active": s.by_status.active,
      "social-pending": s.by_status.pending,
      "social-deact": s.by_status.deactivated,
    };
    Object.entries(map).forEach(([id, val]) => {
      const el = document.getElementById(id);
      if (el) el.textContent = fmt(val);
    });
  }

  function renderGeo() {
    const root = $("#geo-list");
    if (!root) return;
    const rows = state.fixtures.geo.cities_by_events;
    const max = Math.max(...rows.map((r) => r.count), 1);
    root.innerHTML = rows.map((r, i) => {
      const label = state.locale === "ar" ? r.label_ar : r.label_en;
      const pct = Math.round((r.count / max) * 100);
      return `<div class="rank-row">
        <div class="rank-row__n">${i + 1}</div>
        <div>
          <div style="font-weight:750;font-size:13.5px;margin-bottom:6px">${label}</div>
          <div class="rank-row__bar"><div class="rank-row__fill" style="width:${pct}%"></div></div>
        </div>
        <div class="rank-row__count">${fmt(r.count)}</div>
      </div>`;
    }).join("");
  }

  function renderStatus() {
    const st = state.fixtures.event_status;
    $("#st-draft").textContent = fmt(st.draft);
    $("#st-published").textContent = fmt(st.published);
    $("#st-cancelled").textContent = fmt(st.cancelled);
    $("#st-completed").textContent = fmt(st.completed);
  }

  function renderHealth() {
    const h = state.fixtures.health;
    $("#health-failed").textContent = fmt(h.failed_jobs);
    $("#health-note").textContent = state.locale === "ar" ? h.monitoring_note_ar : h.monitoring_note_en;
  }

  function renderCharts(period) {
    window.FunnityV3Charts.applyReducedMotion();
    window.FunnityV3Charts.renderSpark($("#spark-chart"), period.users_per_day);
    window.FunnityV3Charts.renderEvents($("#events-chart"), period, {
      events: t("chart_events").split(" ")[0] || "Events",
      participants: t("participants"),
    });
    window.FunnityV3Charts.renderUsers($("#users-chart"), period, {
      users: t("new_users"),
      events: t("events_created"),
    });
  }

  function renderAll() {
    if (!state.fixtures) return;
    const period = state.fixtures.periods[state.period];
    renderKpis(period);
    renderActions();
    renderActivity();
    renderEventsTable();
    renderSocial();
    renderGeo();
    renderStatus();
    renderHealth();
    renderCharts(period);
  }

  async function boot() {
    const res = await fetch("./data/fixtures.json");
    state.fixtures = await res.json();
    applyI18n();

    $$(".segmented button").forEach((btn) => {
      btn.addEventListener("click", () => setPeriod(btn.dataset.period));
    });
    $("#btn-lang")?.addEventListener("click", () => setLocale(state.locale === "en" ? "ar" : "en"));
    $("#btn-collapse")?.addEventListener("click", toggleSidebar);
    $("#btn-menu")?.addEventListener("click", () => openDrawer(true));
    $(".drawer-backdrop")?.addEventListener("click", () => openDrawer(false));
    $("#btn-refresh")?.addEventListener("click", () => {
      const stamp = $("#refreshed-at");
      if (stamp) stamp.textContent = new Date().toLocaleString(state.locale === "ar" ? "ar-EG" : "en-GB");
      renderAll();
    });

    const now = new Date();
    $("#refreshed-at").textContent = now.toLocaleString("en-GB");
    setPeriod("7d");
  }

  document.addEventListener("DOMContentLoaded", boot);
})();
