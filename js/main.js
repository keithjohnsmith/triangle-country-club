// Triangle Country Club — Concept B (multi-page) interactions

// Inject shared nav + footer, then wire everything up.
async function loadPartials() {
  const grab = (f) => fetch(f, { cache: "no-cache" }).then((r) => r.text()).catch(() => "");
  const [nav, footer] = await Promise.all([grab("partials/nav.html"), grab("partials/footer.html")]);
  const navRoot = document.getElementById("site-nav");
  const footRoot = document.getElementById("site-footer");
  if (navRoot) navRoot.innerHTML = nav;
  if (footRoot) footRoot.innerHTML = footer;
}

function init() {
  // Year
  const y = document.getElementById("year");
  if (y) y.textContent = new Date().getFullYear();

  // Active nav
  const page = (location.pathname.split("/").pop() || "index.html").toLowerCase() || "index.html";
  const map = {
    "golf.html": "activities",
    "cricket.html": "activities",
    "tennis.html": "activities",
    "squash.html": "activities",
    "lawn-bowls.html": "activities",
    "activities.html": "activities",
    "stay.html": "stay",
    "membership.html": "membership",
    "about.html": "about",
    "contact.html": "contact",
  };
  const key = map[page];
  if (key) {
    const el = document.querySelector(`[data-nav="${key}"]`);
    if (el) {
      const link = el.classList.contains("nav-link") ? el : el.querySelector(".nav-link");
      if (link) link.classList.add("active");
    }
  }

  // Nav: transparent over the dark hero/header band, solid once scrolled
  const nav = document.getElementById("nav");
  if (nav) {
    const setNav = () => nav.classList.toggle("at-top", window.scrollY < 40);
    setNav();
    window.addEventListener("scroll", setNav, { passive: true });
  }

  // Mobile menu
  const toggle = document.getElementById("navToggle");
  const menu = document.getElementById("mobileMenu");
  const mSportToggle = document.getElementById("mSportToggle");
  const mSportSub = document.getElementById("mSportSub");
  if (toggle && menu) {
    const closeMenu = () => {
      menu.classList.remove("open");
      toggle.setAttribute("aria-expanded", "false");
      if (mSportSub) mSportSub.classList.remove("open");
      if (mSportToggle) mSportToggle.setAttribute("aria-expanded", "false");
      document.body.style.overflow = "";
    };
    toggle.addEventListener("click", () => {
      const open = menu.classList.toggle("open");
      toggle.setAttribute("aria-expanded", String(open));
      document.body.style.overflow = open ? "hidden" : "";
    });
    menu.querySelectorAll("a").forEach((a) => a.addEventListener("click", closeMenu));
    document.addEventListener("keydown", (e) => e.key === "Escape" && closeMenu());

    if (mSportToggle && mSportSub) {
      mSportToggle.addEventListener("click", () => {
        const open = mSportSub.classList.toggle("open");
        mSportToggle.setAttribute("aria-expanded", String(open));
      });
    }
  }

  // Scroll reveal
  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("in");
          io.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.14, rootMargin: "0px 0px -8% 0px" }
  );
  document.querySelectorAll(".reveal").forEach((el) => io.observe(el));

  // Background videos — start after load (poster first; keeps first paint fast).
  // Skipped entirely under reduced-motion.
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  // Hero video: adaptive quality + loading spinner. On slow / data-saver
  // connections it loads the lighter 480p up front; if the 720p hasn't started
  // playing within ~8s it auto-downgrades to 480p rather than stalling on the
  // static poster. The spinner fades out once playback starts, and we give up
  // gracefully to the poster if the video truly can't load.
  const hero = document.getElementById("heroVideo");
  if (hero && !reduceMotion) {
    const spin = document.getElementById("heroLoading");
    const hideSpin = () => spin && spin.classList.add("hidden");
    const hd = hero.dataset.srcHd || (hero.querySelector("source") || {}).src;
    const sd = hero.dataset.srcSd;
    const conn = navigator.connection || navigator.webkitConnection || navigator.mozConnection;
    const slow = conn && (conn.saveData || /(^|-)2g$|^3g$/.test(conn.effectiveType || ""));

    let onSD = false;
    const load = (src) => { hero.src = src; hero.preload = "auto"; hero.load(); hero.play().catch(hideSpin); };
    const toSD = () => { if (!onSD && sd && hero.src.indexOf(sd) === -1) { onSD = true; load(sd); } };

    hero.addEventListener("playing", hideSpin);
    hero.addEventListener("error", hideSpin);

    const start = () => {
      load(slow && sd ? (onSD = true, sd) : hd);
      if (!onSD) setTimeout(() => { if (hero.readyState < 3) toSD(); }, 8000); // HAVE_FUTURE_DATA
      setTimeout(hideSpin, 20000); // last resort: don't spin forever
    };
    if (document.readyState === "complete") start();
    else window.addEventListener("load", start);
  }

  // Any other background videos (e.g. #golfVideo) — simple play after load.
  if (!reduceMotion) {
    const vids = document.querySelectorAll("#golfVideo");
    const playAll = () => vids.forEach((v) => { v.preload = "auto"; v.play().catch(() => {}); });
    if (vids.length) {
      if (document.readyState === "complete") playAll();
      else window.addEventListener("load", playAll);
    }
  }
}

loadPartials().then(init);
