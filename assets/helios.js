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

// Keep My Place, from Grav Helios Open Reader's "Continue reading" (the same markup, names and styles), but just the
// page, as module pages are scanned rather than read from top to bottom: a page in a course's module list is remembered
// in the reader's browser (localStorage), and the course's Modules page shows a "Continue where you left off" strip
// linking back to it; its close button forgets the place. Each Modules page has its own place. Runs on each page load
// and again after each HTMX page swap - hibbittsdesign.org
(function () {
  // Per-Modules-page key: namespaced by origin + the Modules page's path (as Open Reader's publication path), so each
  // course's place is stored independently
  function horPageKey(publicationPath) {
    return 'hor-last-page|' + window.location.origin + '|' + (publicationPath || '');
  }

  function horInitSavePlace(root) {
    // Save the current page when on a page in a module list
    var savePlaceEl = root.querySelector('[data-hor-save-page]');
    if (savePlaceEl) {
      try {
        localStorage.setItem(horPageKey(savePlaceEl.dataset.horPublicationPath), JSON.stringify({
          url: savePlaceEl.dataset.horUrl,
          title: savePlaceEl.dataset.horTitle
        }));
      } catch (e) {}
    }

    // Populate and show the resume strip when on a Modules page
    var resumeStrip = root.querySelector('#hor-resume-reading');
    if (resumeStrip) {
      var stripPubPath = resumeStrip.dataset.horPublicationPath || '';
      try {
        var saved = localStorage.getItem(horPageKey(stripPubPath));
        if (saved) {
          var pageData = JSON.parse(saved);
          root.querySelector('#hor-resume-btn').href = pageData.url;
          root.querySelector('#hor-resume-title').textContent = pageData.title;
          resumeStrip.removeAttribute('hidden');

          var dismissBtn = resumeStrip.querySelector('.hor-resume-dismiss');
          if (dismissBtn) {
            dismissBtn.addEventListener('click', function () {
              try { localStorage.removeItem(horPageKey(stripPubPath)); } catch (e) {}
              resumeStrip.setAttribute('hidden', '');
              // keyboard focus moves to the page's heading, as the button it was on is now hidden
              var heading = document.querySelector('#main-content h1');
              if (heading) {
                heading.setAttribute('tabindex', '-1');
                heading.focus();
              }
            });
          }
        }
      } catch (e) {}
    }
  }

  horInitSavePlace(document);
  window.addEventListener('helios:content-loaded', function (evt) {
    var container = evt.detail && evt.detail.container;
    if (container) {
      horInitSavePlace(container);
    }
  });
})();

// Arrow keys: the left and right arrow keys go to the previous and next page (the page's Previous and Next links), as
// in Grav Helios Open Reader - handy when presenting a course by screen sharing. The links are clicked rather than
// opened, so pages load just as they would for a reader clicking them - hibbittsdesign.org
(function () {
  // true when an element is a box that scrolls sideways (such as a wide table or code block) and is wider than its space,
  // where the arrow keys should scroll it instead
  function scrollsSideways(element) {
    var overflowX = window.getComputedStyle(element).overflowX;
    var canScroll = overflowX === "auto" || overflowX === "scroll";
    return canScroll && element.scrollWidth > element.clientWidth;
  }

  document.addEventListener("keydown", function (event) {
    // leave the keys alone with a modifier key held, so browser shortcuts still work
    if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) {
      return;
    }
    // and while typing (including in the search dialog), or in a box that scrolls sideways
    var target = event.target;
    var typingTags = ["INPUT", "TEXTAREA", "SELECT"];
    if (target.isContentEditable || typingTags.indexOf(target.tagName) !== -1) {
      return;
    }
    if (target !== document.body && scrollsSideways(target)) {
      return;
    }
    // and while an image is shown larger (tap to see it larger), where the reader is looking at the image
    if (document.querySelector("dialog[open]")) {
      return;
    }

    var link = null;
    if (event.key === "ArrowLeft") {
      link = document.querySelector(".prev-next-nav .nav-button.prev");
    }
    if (event.key === "ArrowRight") {
      link = document.querySelector(".prev-next-nav .nav-button.next");
    }
    if (link) {
      link.click();
    }
  });
})();
