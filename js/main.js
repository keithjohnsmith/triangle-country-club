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

  // Background videos (hero + full-width bands) — start after load (poster first;
  // keeps first paint fast). Skipped entirely under reduced-motion.
  if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    const vids = document.querySelectorAll("#heroVideo, #golfVideo");
    const playAll = () => vids.forEach((v) => { v.preload = "auto"; v.play().catch(() => {}); });
    if (vids.length) {
      if (document.readyState === "complete") playAll();
      else window.addEventListener("load", playAll);
    }
  }
}

loadPartials().then(init);
