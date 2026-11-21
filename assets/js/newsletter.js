// The newsletter forms (includes/subscribe.php, and the docs theme's): Cloudflare Turnstile is loaded once
// someone starts filling in a form, and newsletter.daniil.it/subscribe sends errors back to /newsletter as
// ?error=<code>. In the docs, a dismissable box invites readers to subscribe (see showPopup).
(function () {
  var TURNSTILE = "https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=psalmNewsletterTurnstile";
  var STORAGE_KEY = "psalm-newsletter";
  // After a dismissal, the docs box comes back after this long
  var DISMISSED_FOR = 60 * 24 * 60 * 60 * 1000;

  var messages = {
    email: "Please enter a valid email address.",
    consent: "Please accept the privacy policy to subscribe.",
    captcha: "The anti-spam check didn't pass: please try again.",
    form: "The form couldn't be read: please try again.",
    unavailable: "The subscription couldn't be saved right now: please try again later.",
  };

  function storage(value) {
    try {
      if (value === undefined)
        return JSON.parse(localStorage.getItem(STORAGE_KEY) || "null");
      localStorage.setItem(STORAGE_KEY, JSON.stringify(value));
    } catch (e) {
      // Private windows and blocked storage: the box just shows again
    }
    return null;
  }

  var turnstileState = "idle";
  var waiting = [];

  window.psalmNewsletterTurnstile = function () {
    turnstileState = "ready";
    waiting.splice(0).forEach(render);
  };

  // Renders the form's widget, loading Turnstile first if needed
  function render(form) {
    if (form.newsletterWidget !== undefined)
      return;
    if (turnstileState !== "ready") {
      if (waiting.indexOf(form) === -1)
        waiting.push(form);
      if (turnstileState === "idle") {
        turnstileState = "loading";
        var script = document.createElement("script");
        script.src = TURNSTILE;
        script.async = true;
        script.onerror = function () {
          turnstileState = "idle";
        };
        document.head.appendChild(script);
      }
      return;
    }
    var box = form.querySelector(".newsletter__turnstile");
    form.newsletterWidget = window.turnstile.render(box, {
      sitekey: box.getAttribute("data-sitekey"),
      size: "flexible",
      callback: function () {
        if (form.newsletterPending) {
          form.newsletterPending = false;
          form.submit();
        }
      },
    });
  }

  function setUp(form) {
    if (form.newsletterReady)
      return;
    form.newsletterReady = true;
    form.addEventListener("focusin", function () {
      render(form);
    });
    // Until Turnstile has a token, wait for it and submit then
    form.addEventListener("submit", function (event) {
      var token = form.querySelector("[name=cf-turnstile-response]");
      if (!token || !token.value) {
        event.preventDefault();
        form.newsletterPending = true;
        render(form);
        return;
      }
      storage({ subscribed: Date.now() });
    });
  }

  function showError() {
    var code = new URLSearchParams(location.search).get("error");
    var form = document.querySelector("form.newsletter--page");
    if (!code || !form || !Object.prototype.hasOwnProperty.call(messages, code))
      return;
    var p = document.createElement("p");
    p.className = "newsletter__error";
    p.setAttribute("role", "alert");
    p.textContent = messages[code];
    form.parentNode.insertBefore(p, form);
  }

  // The docs box: shown once the reader has spent some time on the docs, unless they subscribed or
  // dismissed it recently
  function showPopup() {
    var template = document.getElementById("newsletter-popup");
    if (!template)
      return;
    var state = storage();
    if (state && (state.subscribed || (state.dismissed && Date.now() - state.dismissed < DISMISSED_FOR)))
      return;

    var shown = false;
    function show() {
      if (shown)
        return;
      shown = true;
      window.removeEventListener("scroll", onScroll);
      var popup = template.content.firstElementChild.cloneNode(true);
      popup.querySelector(".newsletter-popup__close").addEventListener("click", function () {
        storage({ dismissed: Date.now() });
        popup.remove();
      });
      document.body.appendChild(popup);
      setUp(popup.querySelector("form.newsletter"));
    }
    function onScroll() {
      var scrolled = window.scrollY + window.innerHeight;
      if (scrolled >= document.documentElement.scrollHeight * 0.6)
        show();
    }
    window.addEventListener("scroll", onScroll, { passive: true });
    setTimeout(show, 45000);
  }

  function init() {
    Array.prototype.forEach.call(document.querySelectorAll("form.newsletter"), setUp);
    showError();
    showPopup();
  }

  if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", init);
  else
    init();
})();
