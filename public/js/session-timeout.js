(function () {
  'use strict';

  var DEFAULT_TIMEOUT_SECONDS = 1800;
  var WARNING_BEFORE_MS = 120000;
  var ACTIVITY_DEBOUNCE_MS = 1000;
  var MAX_KEEPALIVE_INTERVAL_MS = 60000;

  var appBaseUrl = resolveAppBaseUrl();
  var LOGIN_URL = appBaseUrl + 'index.php?url=login&error=session_expired';
  var KEEPALIVE_URL = appBaseUrl + 'api/session/keepalive.php';

  var timeoutMs = readTimeoutSeconds() * 1000;
  var keepaliveMinIntervalMs = Math.min(
    MAX_KEEPALIVE_INTERVAL_MS,
    Math.max(1000, Math.floor(timeoutMs / 4))
  );
  var timeoutTimer = null;
  var warningTimer = null;
  var warningVisible = false;
  var overlay = null;
  var countdownInterval = null;
  var lastActivityReset = 0;
  var lastKeepalive = 0;
  var keepaliveInFlight = false;
  var redirecting = false;
  var nativeFetch = window.fetch;

  // We listen broadly because every protected Admin and Staff page shares this script.
  var ACTIVITY_EVENTS = [
    'click',
    'keydown',
    'input',
    'scroll',
    'mousemove',
    'mousedown',
    'touchstart',
    'wheel'
  ];

  function readTimeoutSeconds() {
    var configured = parseInt(window.INVENTRA_SESSION_TIMEOUT || DEFAULT_TIMEOUT_SECONDS, 10);
    return configured > 0 ? configured : DEFAULT_TIMEOUT_SECONDS;
  }

  function resolveAppBaseUrl() {
    var scripts = document.getElementsByTagName('script');

    for (var i = 0; i < scripts.length; i++) {
      var src = scripts[i].src || '';
      var idx = src.indexOf('public/js/session-timeout.js');

      if (idx !== -1) {
        return src.substring(0, idx);
      }
    }

    return './';
  }

  function onUserActivity(event) {
    if (event && event.type === 'visibilitychange' && document.hidden) {
      return;
    }

    registerActivity(false);
  }

  function registerActivity(forceKeepalive) {
    var now = Date.now();

    if (!forceKeepalive && now - lastActivityReset < ACTIVITY_DEBOUNCE_MS) {
      return;
    }

    lastActivityReset = now;
    resetTimers();
    refreshBackendSession(forceKeepalive);
  }

  function resetTimers() {
    if (warningVisible) {
      hideWarning();
    }

    clearTimeout(timeoutTimer);
    clearTimeout(warningTimer);

    if (timeoutMs > WARNING_BEFORE_MS) {
      warningTimer = setTimeout(showWarning, timeoutMs - WARNING_BEFORE_MS);
    }

    timeoutTimer = setTimeout(handleTimeout, timeoutMs);
  }

  function refreshBackendSession(forceKeepalive) {
    var now = Date.now();

    if (!nativeFetch || keepaliveInFlight) {
      return;
    }

    if (!forceKeepalive && now - lastKeepalive < keepaliveMinIntervalMs) {
      return;
    }

    // We refresh PHP's activity timestamp too; a browser-only reset would leave
    // the backend session free to expire while the user is still working.
    lastKeepalive = now;
    keepaliveInFlight = true;

    nativeFetch(KEEPALIVE_URL, {
      method: 'POST',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    }).then(function (response) {
      if (response.status === 401) {
        handleTimeout();
      }
    }).catch(function () {
      // Network hiccups should not log the user out; the next request still enforces timeout.
    }).then(function () {
      keepaliveInFlight = false;
    });
  }

  function handleTimeout() {
    if (redirecting) {
      return;
    }

    redirecting = true;
    clearTimeout(timeoutTimer);
    clearTimeout(warningTimer);
    clearInterval(countdownInterval);
    window.location.replace(LOGIN_URL);
  }

  function createOverlay() {
    if (overlay) {
      return;
    }

    overlay = document.createElement('div');
    overlay.id = 'session-timeout-overlay';
    overlay.innerHTML =
      '<div id="session-timeout-modal">' +
      '  <div class="stm-icon">\u23F1</div>' +
      '  <h2 class="stm-title">Session Expiring Soon</h2>' +
      '  <p class="stm-message">Your session will expire due to inactivity.<br>Click anywhere or press any key to stay logged in.</p>' +
      '  <div class="stm-countdown" id="stm-countdown"></div>' +
      '  <button type="button" id="stm-stay-btn" class="stm-btn">Stay Logged In</button>' +
      '</div>';

    document.body.appendChild(overlay);

    document.getElementById('stm-stay-btn').addEventListener('click', function () {
      registerActivity(true);
    });

    overlay.addEventListener('click', function (event) {
      if (event.target === overlay) {
        registerActivity(true);
      }
    });
  }

  function showWarning() {
    warningVisible = true;
    createOverlay();
    overlay.classList.add('is-visible');

    var secondsLeft = Math.floor(WARNING_BEFORE_MS / 1000);
    var countdownEl = document.getElementById('stm-countdown');

    function tick() {
      if (secondsLeft <= 0) {
        clearInterval(countdownInterval);
        return;
      }

      var minutes = Math.floor(secondsLeft / 60);
      var seconds = secondsLeft % 60;
      countdownEl.textContent = (minutes > 0 ? minutes + 'm ' : '') + seconds + 's remaining';
      secondsLeft--;
    }

    tick();
    clearInterval(countdownInterval);
    countdownInterval = setInterval(tick, 1000);
  }

  function hideWarning() {
    warningVisible = false;
    clearInterval(countdownInterval);

    if (overlay) {
      overlay.classList.remove('is-visible');
    }
  }

  function injectStyles() {
    var css =
      '#session-timeout-overlay{position:fixed;top:0;left:0;width:100%;height:100%;' +
      'background:rgba(0,0,0,.6);backdrop-filter:blur(4px);display:flex;align-items:center;' +
      'justify-content:center;z-index:99999;opacity:0;visibility:hidden;' +
      'transition:opacity .3s ease,visibility .3s ease}' +
      '#session-timeout-overlay.is-visible{opacity:1;visibility:visible}' +
      '#session-timeout-modal{background:#1e1e2e;color:#e0e0e0;border-radius:16px;' +
      'padding:40px 36px;max-width:420px;width:90%;text-align:center;' +
      'box-shadow:0 20px 60px rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.08);' +
      'transform:scale(.92);transition:transform .3s ease}' +
      '#session-timeout-overlay.is-visible #session-timeout-modal{transform:scale(1)}' +
      '.stm-icon{font-size:48px;margin-bottom:12px}' +
      '.stm-title{font-size:20px;font-weight:600;margin:0 0 8px;color:#fff;font-family:"DM Sans",sans-serif}' +
      '.stm-message{font-size:14px;line-height:1.6;margin:0 0 20px;color:#a0a0b0;font-family:"DM Sans",sans-serif}' +
      '.stm-countdown{font-size:28px;font-weight:700;margin-bottom:24px;color:#f59e0b;' +
      'font-family:"DM Sans",monospace;letter-spacing:1px}' +
      '.stm-btn{background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;' +
      'padding:12px 32px;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;' +
      'font-family:"DM Sans",sans-serif;transition:transform .15s ease,box-shadow .15s ease}' +
      '.stm-btn:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(99,102,241,.4)}' +
      '.stm-btn:active{transform:translateY(0)}';

    var styleEl = document.createElement('style');
    styleEl.textContent = css;
    document.head.appendChild(styleEl);
  }

  if (nativeFetch) {
    window.fetch = function () {
      return nativeFetch.apply(this, arguments).then(function (response) {
        // We redirect on protected-page 401s so expired fetch calls cannot leave stale UI active.
        if (response.status === 401) {
          response.clone().json().then(function (body) {
            if (!body || body.session_expired !== false) {
              handleTimeout();
            }
          }).catch(handleTimeout);
        }

        return response;
      });
    };
  }

  injectStyles();

  ACTIVITY_EVENTS.forEach(function (eventName) {
    document.addEventListener(eventName, onUserActivity, { passive: true });
  });

  document.addEventListener('visibilitychange', onUserActivity);

  resetTimers();
})();
