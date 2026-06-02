<?php require_once __DIR__ . "/../includes/header.php"; ?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Boules Competities | Spelregels</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="../css/variables.css">
  <link rel="stylesheet" href="../css/index.css">
  <link rel="stylesheet" href="../css/spelregels.css">
</head>
<body>
  <main class="page-shell">
    <?php render_site_header("rules", false); ?>

    <div id="regels">
      <section class="hero">
        <div class="hero-copy">
          <p class="eyebrow" data-i18n="rules.kicker">Boules spelregels</p>
          <h1>
            <span data-i18n="rules.hero.line1">SPEEL.</span>
            <span data-i18n="rules.hero.line2">LEER.</span>
            <span class="accent" data-i18n="rules.hero.line3">BEHEERS.</span>
          </h1>
          <p class="hero-text" data-i18n="rules.hero.description">
            Een helder overzicht van de belangrijkste regels voor petanque:
            van de werpcirkel en het uitwerpen van het but tot de volgorde
            van spelen en de puntentelling.
          </p>

          <div class="hero-actions">
            <a class="button button-primary" href="#werpcirkel" data-i18n="rules.hero.ctaBase">Start bij de basis</a>
            <a class="button button-secondary" href="#punten" data-i18n="rules.hero.ctaScore">Bekijk puntentelling</a>
          </div>

          <div class="highlights">
            <article class="highlight-card">
              <h2 data-i18n="rules.highlight.circle.title">Werpcirkel</h2>
              <p data-i18n="rules.highlight.circle.description">35 tot 50 cm, of 50 cm bij voorgevormde cirkels.</p>
            </article>
            <article class="highlight-card">
              <h2 data-i18n="rules.highlight.jack.title">But uitwerpen</h2>
              <p data-i18n="rules.highlight.jack.description">Het but ligt tussen 6 en 10 meter van de werpcirkel.</p>
            </article>
            <article class="highlight-card">
              <h2 data-i18n="rules.highlight.win.title">Winnen</h2>
              <p data-i18n="rules.highlight.win.description">De wedstrijd stopt zodra een team 13 punten bereikt.</p>
            </article>
          </div>
        </div>

        <aside class="hero-panel">
            <div class="illustration-card">
            <img src="/images/spelregels_hero.png" alt="jeudeboules">
            <div class="legend">
              <div class="legend-item">
                <span class="legend-dot legend-circle"></span>
                <span data-i18n="rules.legend.circle">Werpcirkel</span>
              </div>
              <div class="legend-item">
                <span class="legend-dot legend-but"></span>
                <span data-i18n="rules.legend.jack">But</span>
              </div>
              <div class="legend-item">
                <span class="legend-dot legend-boule"></span>
                <span data-i18n="rules.legend.boules">Boules</span>
              </div>
            </div>
          </div>
        </aside>
      </section>

      <section class="rules-grid">
        <article class="rule-card" id="werpcirkel">
          <p class="card-tag">01</p>
          <h2 data-i18n="rules.circle.title">De werpcirkel</h2>
          <ul>
            <li data-i18n="rules.circle.item1">De cirkel om de voeten heet de werpcirkel en moet tussen de 35 en 50 cm zijn.</li>
            <li data-i18n="rules.circle.item2">Bij wedstrijden gebruikt men vaak voorgevormde werpcirkels met een vaste diameter van 50 cm.</li>
            <li data-i18n="rules.circle.item3">De werpcirkel mag niet zomaar overal worden getekend en moet minimaal 1 meter van de zijkant van het afgebakende terrein liggen.</li>
            <li data-i18n="rules.circle.item4">Tijdens het werpen mag een speler de werpcirkel niet verlaten; beide voeten moeten volledig binnen de cirkel blijven.</li>
            <li data-i18n="rules.circle.item5">Het team dat de toss wint en de werpcirkel tekent, mag daarna het but uitwerpen.</li>
          </ul>
        </article>

        <article class="rule-card accent-card">
          <p class="card-tag" data-i18n="rules.summary.tag">Belangrijk</p>
          <h2 data-i18n="rules.summary.title">Snelle samenvatting</h2>
          <p class="summary-line" data-i18n-html="rules.summary.jackDistance"><strong>Afstand but:</strong> 6 tot 10 meter vanaf de werpcirkel.</p>
          <p class="summary-line" data-i18n-html="rules.summary.edgeDistance"><strong>Afstand tot rand:</strong> minimaal 1 meter voor de werpcirkel en maximaal 1 meter voor het but.</p>
          <p class="summary-line" data-i18n-html="rules.summary.goal"><strong>Doel:</strong> gooi je boule dichter bij het but dan de tegenstander.</p>
          <p class="summary-line" data-i18n-html="rules.summary.end"><strong>Einde spel:</strong> zodra een team 13 punten haalt.</p>
        </article>

        <article class="rule-card" id="uitwerpen">
          <p class="card-tag">02</p>
          <h2 data-i18n="rules.jack.title">Uitwerpen van het but</h2>
          <ul>
            <li data-i18n="rules.jack.item1">Het uitwerpen van het but is aan vaste regels gebonden.</li>
            <li data-i18n="rules.jack.item2">De afstand tussen de werpcirkel en het but moet tussen de 6 en 10 meter liggen.</li>
            <li data-i18n="rules.jack.item3">Het but mag niet meer dan 1 meter van de buitenzijde van het terrein af liggen.</li>
            <li data-i18n="rules.jack.item4">Tijdens het spel kan het but verplaatsen; afhankelijk van de situatie wordt het teruggelegd, blijft het liggen of ongeldig verklaard.</li>
          </ul>
        </article>

        <article class="rule-card" id="werpen">
          <p class="card-tag">03</p>
          <h2 data-i18n="rules.throw.title">Werpen van de boules</h2>
          <ul>
            <li data-i18n="rules.throw.item1">Het doel is om je boule zo dicht mogelijk bij het but te gooien.</li>
            <li data-i18n="rules.throw.item2">Een boule moet onderhands worden gegooid of gerold.</li>
            <li data-i18n="rules.throw.item3">Een eenmaal geworpen boule mag niet opnieuw worden gegooid.</li>
            <li data-i18n="rules.throw.item4">Terwijl de speler aan de beurt is, moeten de overige aanwezigen stil zijn.</li>
            <li data-i18n="rules.throw.item5">Na de eerste boule van het team dat het but uitwierp, is de tegenstander aan zet.</li>
            <li data-i18n="rules.throw.item6">Wie de boule het dichtst bij het but legt, neemt de leiding.</li>
            <li data-i18n="rules.throw.item7">Het andere team moet daarna blijven gooien totdat het dichterbij ligt of geen boules meer over heeft.</li>
            <li data-i18n="rules.throw.item8">Heeft een team geen boules meer, dan mag de tegenstander zijn resterende boules spelen om extra punten te maken.</li>
          </ul>
        </article>

        <article class="rule-card" id="rondes">
          <p class="card-tag">04</p>
          <h2 data-i18n="rules.round.title">Rondeverloop</h2>
          <ul>
            <li data-i18n="rules.round.item1">Wanneer beide teams geen boules meer hebben, is de werpronde afgelopen.</li>
            <li data-i18n="rules.round.item2">Voor elke boule die dichter bij het but ligt dan de beste boule van de tegenstander, krijgt een team 1 punt.</li>
            <li data-i18n="rules.round.item3">Per werpronde kan slechts een team punten scoren.</li>
            <li data-i18n="rules.round.item4">Daarna begint een nieuwe werpronde.</li>
            <li data-i18n="rules.round.item5">Het team dat de vorige werpronde won, mag de volgende ronde beginnen.</li>
          </ul>
        </article>

        <article class="rule-card wide-card" id="punten">
          <p class="card-tag">05</p>
          <h2 data-i18n="rules.score.title">Puntentelling</h2>
          <div class="score-layout">
            <div>
              <p data-i18n="rules.score.description1">
                Het spel gaat door totdat een van de teams 13 punten heeft behaald.
                In sommige wedstrijden, bijvoorbeeld in voorrondes, wordt dit verkort
                tot 11 punten.
              </p>
              <p data-i18n="rules.score.description2">
                Het spel stopt direct zodra een team de vereiste score heeft bereikt,
                ook als de lopende werpronde nog niet volledig is afgerond.
              </p>
            </div>
            <div class="score-badge" data-target="13">
              <span class="score-label" data-i18n="rules.score.badgeLabel">Winnende score</span>
              <strong class="score-number">0</strong>
              <span class="score-unit" data-i18n="rules.score.unit">punten</span>
            </div>
          </div>
        </article>
      </section>
    </div>
  </main>

  <div class="auth-modal" data-auth-modal hidden>
    <div class="auth-modal-backdrop" data-auth-close></div>
    <div class="auth-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="auth-modal-title">
      <button type="button" class="auth-modal-close" data-auth-close aria-label="Sluit inlogvenster">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <div class="auth-panel">
        <div class="auth-panel-emblem" aria-hidden="true">
          <span class="auth-panel-leaf"></span>
          <span class="auth-panel-avatar"><i class="fa-regular fa-user"></i></span>
          <span class="auth-panel-leaf"></span>
        </div>

        <div class="auth-panel-tabs" role="tablist" aria-label="Authenticatie">
          <button type="button" class="auth-panel-tab is-active" data-auth-tab="login">Inloggen</button>
          <button type="button" class="auth-panel-tab" data-auth-tab="signup">Aanmelden</button>
        </div>

        <div class="auth-panel-copy">
          <p class="photo-kicker" data-auth-kicker>WELKOM TERUG</p>
          <h2 id="auth-modal-title" data-auth-title>Inloggen</h2>
          <p class="upload-description" data-auth-description>Log in om verder te gaan.</p>
        </div>

        <form class="auth-popup-form is-active" data-auth-form="login">
          <label class="auth-input">
            <i class="fa-regular fa-user"></i>
            <input type="text" data-auth-login-identity autocomplete="username">
          </label>

          <label class="auth-input auth-input-password">
            <i class="fa-solid fa-lock"></i>
            <input type="password" data-auth-login-password autocomplete="current-password">
            <button type="button" class="auth-password-toggle" data-auth-password-toggle aria-label="Toon wachtwoord">
              <i class="fa-regular fa-eye"></i>
            </button>
          </label>

          <a href="#regels" class="auth-inline-link" data-auth-forgot-password>Wachtwoord vergeten?</a>
          <p class="auth-popup-feedback" data-auth-feedback="login" aria-live="polite"></p>

          <button type="submit" class="button button-primary auth-popup-submit" data-auth-submit-login>Inloggen</button>

          <p class="auth-switch-row">
            <span data-auth-switch-copy-login>Nog geen account?</span>
            <button type="button" class="auth-switch-button" data-auth-switch="signup">Aanmelden</button>
          </p>
        </form>

        <form class="auth-popup-form" data-auth-form="signup" hidden>
          <label class="auth-input">
            <i class="fa-regular fa-user"></i>
            <input type="text" data-auth-signup-name autocomplete="name">
          </label>

          <label class="auth-input">
            <i class="fa-regular fa-envelope"></i>
            <input type="email" data-auth-signup-email autocomplete="email">
          </label>

          <label class="auth-input auth-input-password">
            <i class="fa-solid fa-lock"></i>
            <input type="password" data-auth-signup-password autocomplete="new-password">
            <button type="button" class="auth-password-toggle" data-auth-password-toggle aria-label="Toon wachtwoord">
              <i class="fa-regular fa-eye"></i>
            </button>
          </label>

          <label class="auth-input auth-input-password">
            <i class="fa-solid fa-shield-halved"></i>
            <input type="password" data-auth-signup-password-confirm autocomplete="new-password">
            <button type="button" class="auth-password-toggle" data-auth-password-toggle aria-label="Toon wachtwoord">
              <i class="fa-regular fa-eye"></i>
            </button>
          </label>

          <p class="auth-popup-feedback" data-auth-feedback="signup" aria-live="polite"></p>

          <button type="submit" class="button button-primary auth-popup-submit" data-auth-submit-signup>Account aanmaken</button>

          <p class="auth-switch-row">
            <span data-auth-switch-copy-signup>Heb je al een account?</span>
            <button type="button" class="auth-switch-button" data-auth-switch="login">Inloggen</button>
          </p>
        </form>
      </div>
    </div>
  </div>

  <script type="module" src="../scripts/spelregels.js"></script>
</body>
</html>
