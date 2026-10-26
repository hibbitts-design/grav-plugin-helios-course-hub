// Frontend JS customizations

(function () {
  var theme = localStorage.getItem("helios-theme") || "system";
  var isDark =
    theme === "dark" ||
    (theme === "system" &&
      window.matchMedia("(prefers-color-scheme: dark)").matches);
  var cardTheme = isDark ? "dark" : "light";

  var embeds = document.querySelectorAll(".embedly-card");
  for (var i = 0; i < embeds.length; i++) {
    embeds[i].setAttribute("data-card-theme", cardTheme);
  }

  // Load Embedly platform.js if embedly cards are present
  if (embeds.length > 0) {
    var script = document.createElement("script");
    script.src = "https://cdn.embedly.com/widgets/platform.js";
    script.async = true;
    document.body.appendChild(script);

    // Reload on theme class change so Embedly cards re-render with the correct theme
    window.addEventListener("load", function () {
      var observer = new MutationObserver(function () {
        observer.disconnect();
        window.location.reload();
      });
      observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ["class"],
      });
    });
  }

  // --- BEGIN: HTMX Embedly fix --- (remove this block if it causes issues)
  window.addEventListener("helios:content-loaded", function (evt) {
    var container = evt.detail && evt.detail.container;
    if (!container) return;

    var newEmbeds = container.querySelectorAll(".embedly-card");
    if (newEmbeds.length === 0) return;

    for (var i = 0; i < newEmbeds.length; i++) {
      newEmbeds[i].setAttribute("data-card-theme", cardTheme);
    }

    // Remove existing Embedly script(s) then re-append to force re-execution in all browsers
    document.querySelectorAll('script[src*="cdn.embedly.com/widgets/platform.js"]').forEach(function(el) {
      el.parentNode.removeChild(el);
    });
    var s = document.createElement("script");
    s.src = "https://cdn.embedly.com/widgets/platform.js";
    s.async = true;
    document.body.appendChild(s);
  });
  // --- END: HTMX Embedly fix ---
})();

// Collapse/expand the sidebar: on wider screens, a button below the site icon collapses the sidebar to a slim strip, so
// readers can focus on the page, or expands it again. The choice is remembered in the reader's browser (localStorage) for
// the whole site, and applied before the page is drawn (see partials/base.html.twig). Phones use the menu button as
// before - hibbittsdesign.org
(function () {
  var button = document.querySelector(".hor-sidebar-toggle");
  var sidebar = document.getElementById("hor-sidebar");
  if (!button || !sidebar) {
    return;
  }
  var root = document.documentElement;
  var expandLabel = button.querySelector(".hor-sidebar-toggle-expand");
  var homeLink = sidebar.querySelector(".hor-sidebar-head a");
  var siteName = homeLink ? homeLink.textContent.trim() : "";

  // collapse or expand, and tell screen readers whether the sidebar is expanded (aria-expanded); collapsed, the button
  // shows only its arrows, so its name also shows on hover (title)
  function setCollapsed(collapsed) {
    root.classList.toggle("hor-sidebar-collapsed", collapsed);
    button.setAttribute("aria-expanded", collapsed ? "false" : "true");
    if (collapsed) {
      button.setAttribute("title", expandLabel.textContent.trim());
    } else {
      button.removeAttribute("title");
    }
    // collapsed, only the site icon shows, so the site name also shows on hover
    if (homeLink && siteName) {
      if (collapsed) {
        homeLink.setAttribute("title", siteName);
      } else {
        homeLink.removeAttribute("title");
      }
    }
  }

  setCollapsed(root.classList.contains("hor-sidebar-collapsed"));
  button.classList.add("is-ready");

  button.addEventListener("click", function () {
    var collapsed = !root.classList.contains("hor-sidebar-collapsed");
    setCollapsed(collapsed);
    try {
      localStorage.setItem("hor-sidebar-collapsed", collapsed ? "1" : "0");
    } catch (error) {
      // without storage the choice still works on this page, it just isn't remembered
    }
  });
})();
