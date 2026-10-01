<!DOCTYPE html>
<html lang="es-CO">
    <head>
        <meta charset="UTF-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1"/>
        <link rel="profile" href="https://gmpg.org/xfn/11"/>
        <link rel="pingback" href="https://founders.hdoinnovations.com/xmlrpc.php"/>
        <title>Kynap &#8211;Tu asistente nutricional</title>
        <meta name='robots' content='max-image-preview:large'/>
        <style>
            img:is([sizes="auto" i], [sizes^="auto," i]) {
                contain-intrinsic-size: 3000px 1500px
            }
        </style>
        <link rel="alternate" type="application/rss+xml" title="Kynap &raquo; Feed" href="https://founders.hdoinnovations.com/feed/"/>
        <link rel="alternate" type="application/rss+xml" title="Kynap &raquo; RSS de los comentarios" href="https://founders.hdoinnovations.com/comments/feed/"/>
        <script>
            window._wpemojiSettings = {
                "baseUrl": "https:\/\/s.w.org\/images\/core\/emoji\/15.1.0\/72x72\/",
                "ext": ".png",
                "svgUrl": "https:\/\/s.w.org\/images\/core\/emoji\/15.1.0\/svg\/",
                "svgExt": ".svg",
                "source": {
                    "concatemoji": "https:\/\/founders.hdoinnovations.com\/wp-includes\/js\/wp-emoji-release.min.js?ver=6.8.1"
                }
            };
            /*! This file is auto-generated */
            !function(i, n) {
                var o, s, e;
                function c(e) {
                    try {
                        var t = {
                            supportTests: e,
                            timestamp: (new Date).valueOf()
                        };
                        sessionStorage.setItem(o, JSON.stringify(t))
                    } catch (e) {}
                }
                function p(e, t, n) {
                    e.clearRect(0, 0, e.canvas.width, e.canvas.height),
                    e.fillText(t, 0, 0);
                    var t = new Uint32Array(e.getImageData(0, 0, e.canvas.width, e.canvas.height).data)
                      , r = (e.clearRect(0, 0, e.canvas.width, e.canvas.height),
                    e.fillText(n, 0, 0),
                    new Uint32Array(e.getImageData(0, 0, e.canvas.width, e.canvas.height).data));
                    return t.every(function(e, t) {
                        return e === r[t]
                    })
                }
                function u(e, t, n) {
                    switch (t) {
                    case "flag":
                        return n(e, "\ud83c\udff3\ufe0f\u200d\u26a7\ufe0f", "\ud83c\udff3\ufe0f\u200b\u26a7\ufe0f") ? !1 : !n(e, "\ud83c\uddfa\ud83c\uddf3", "\ud83c\uddfa\u200b\ud83c\uddf3") && !n(e, "\ud83c\udff4\udb40\udc67\udb40\udc62\udb40\udc65\udb40\udc6e\udb40\udc67\udb40\udc7f", "\ud83c\udff4\u200b\udb40\udc67\u200b\udb40\udc62\u200b\udb40\udc65\u200b\udb40\udc6e\u200b\udb40\udc67\u200b\udb40\udc7f");
                    case "emoji":
                        return !n(e, "\ud83d\udc26\u200d\ud83d\udd25", "\ud83d\udc26\u200b\ud83d\udd25")
                    }
                    return !1
                }
                function f(e, t, n) {
                    var r = "undefined" != typeof WorkerGlobalScope && self instanceof WorkerGlobalScope ? new OffscreenCanvas(300,150) : i.createElement("canvas")
                      , a = r.getContext("2d", {
                        willReadFrequently: !0
                    })
                      , o = (a.textBaseline = "top",
                    a.font = "600 32px Arial",
                    {});
                    return e.forEach(function(e) {
                        o[e] = t(a, e, n)
                    }),
                    o
                }
                function t(e) {
                    var t = i.createElement("script");
                    t.src = e,
                    t.defer = !0,
                    i.head.appendChild(t)
                }
                "undefined" != typeof Promise && (o = "wpEmojiSettingsSupports",
                s = ["flag", "emoji"],
                n.supports = {
                    everything: !0,
                    everythingExceptFlag: !0
                },
                e = new Promise(function(e) {
                    i.addEventListener("DOMContentLoaded", e, {
                        once: !0
                    })
                }
                ),
                new Promise(function(t) {
                    var n = function() {
                        try {
                            var e = JSON.parse(sessionStorage.getItem(o));
                            if ("object" == typeof e && "number" == typeof e.timestamp && (new Date).valueOf() < e.timestamp + 604800 && "object" == typeof e.supportTests)
                                return e.supportTests
                        } catch (e) {}
                        return null
                    }();
                    if (!n) {
                        if ("undefined" != typeof Worker && "undefined" != typeof OffscreenCanvas && "undefined" != typeof URL && URL.createObjectURL && "undefined" != typeof Blob)
                            try {
                                var e = "postMessage(" + f.toString() + "(" + [JSON.stringify(s), u.toString(), p.toString()].join(",") + "));"
                                  , r = new Blob([e],{
                                    type: "text/javascript"
                                })
                                  , a = new Worker(URL.createObjectURL(r),{
                                    name: "wpTestEmojiSupports"
                                });
                                return void (a.onmessage = function(e) {
                                    c(n = e.data),
                                    a.terminate(),
                                    t(n)
                                }
                                )
                            } catch (e) {}
                        c(n = f(s, u, p))
                    }
                    t(n)
                }
                ).then(function(e) {
                    for (var t in e)
                        n.supports[t] = e[t],
                        n.supports.everything = n.supports.everything && n.supports[t],
                        "flag" !== t && (n.supports.everythingExceptFlag = n.supports.everythingExceptFlag && n.supports[t]);
                    n.supports.everythingExceptFlag = n.supports.everythingExceptFlag && !n.supports.flag,
                    n.DOMReady = !1,
                    n.readyCallback = function() {
                        n.DOMReady = !0
                    }
                }).then(function() {
                    return e
                }).then(function() {
                    var e;
                    n.supports.everything || (n.readyCallback(),
                    (e = n.source || {}).concatemoji ? t(e.concatemoji) : e.wpemoji && e.twemoji && (t(e.twemoji),
                    t(e.wpemoji)))
                }))
            }((window,
            document), window._wpemojiSettings);
        </script>
        <link rel='stylesheet' id='hello-elementor-theme-style-css' href='https://founders.hdoinnovations.com/wp-content/themes/hello-elementor/theme.min.css?ver=3.1.1' media='all'/>
        <link rel='stylesheet' id='elementor-frontend-css' href='https://founders.hdoinnovations.com/wp-content/plugins/elementor/assets/css/frontend.min.css?ver=3.24.2' media='all'/>
        <link rel='stylesheet' id='eael-general-css' href='https://founders.hdoinnovations.com/wp-content/plugins/essential-addons-for-elementor-lite/assets/front-end/css/view/general.min.css?ver=6.0.4' media='all'/>
        <link rel='stylesheet' id='eael-671-css' href='https://founders.hdoinnovations.com/wp-content/uploads/essential-addons-elementor/eael-671.css?ver=1747314840' media='all'/>
        <link rel='stylesheet' id='elementor-post-671-css' href='https://founders.hdoinnovations.com/wp-content/uploads/elementor/css/post-671.css?ver=1747251929' media='all'/>
        <link rel='stylesheet' id='elementor-post-674-css' href='https://founders.hdoinnovations.com/wp-content/uploads/elementor/css/post-674.css?ver=1747251923' media='all'/>
        <link rel='stylesheet' id='elementor-post-668-css' href='https://founders.hdoinnovations.com/wp-content/uploads/elementor/css/post-668.css?ver=1747251929' media='all'/>
        <style id='wp-emoji-styles-inline-css'>
            img.wp-smiley, img.emoji {
                display: inline !important;
                border: none !important;
                box-shadow: none !important;
                height: 1em !important;
                width: 1em !important;
                margin: 0 0.07em !important;
                vertical-align: -0.1em !important;
                background: none !important;
                padding: 0 !important;
            }
        </style>
        <style id='classic-theme-styles-inline-css'>
            /*! This file is auto-generated */
            .wp-block-button__link {
                color: #fff;
                background-color: #32373c;
                border-radius: 9999px;
                box-shadow: none;
                text-decoration: none;
                padding: calc(.667em + 2px) calc(1.333em + 2px);
                font-size: 1.125em
            }

            .wp-block-file__button {
                background: #32373c;
                color: #fff;
                text-decoration: none
            }
        </style>
        <style id='global-styles-inline-css'>
            :root {
                --wp--preset--aspect-ratio--square: 1;
                --wp--preset--aspect-ratio--4-3: 4/3;
                --wp--preset--aspect-ratio--3-4: 3/4;
                --wp--preset--aspect-ratio--3-2: 3/2;
                --wp--preset--aspect-ratio--2-3: 2/3;
                --wp--preset--aspect-ratio--16-9: 16/9;
                --wp--preset--aspect-ratio--9-16: 9/16;
                --wp--preset--color--black: #000000;
                --wp--preset--color--cyan-bluish-gray: #abb8c3;
                --wp--preset--color--white: #ffffff;
                --wp--preset--color--pale-pink: #f78da7;
                --wp--preset--color--vivid-red: #cf2e2e;
                --wp--preset--color--luminous-vivid-orange: #ff6900;
                --wp--preset--color--luminous-vivid-amber: #fcb900;
                --wp--preset--color--light-green-cyan: #7bdcb5;
                --wp--preset--color--vivid-green-cyan: #00d084;
                --wp--preset--color--pale-cyan-blue: #8ed1fc;
                --wp--preset--color--vivid-cyan-blue: #0693e3;
                --wp--preset--color--vivid-purple: #9b51e0;
                --wp--preset--gradient--vivid-cyan-blue-to-vivid-purple: linear-gradient(135deg,rgba(6,147,227,1) 0%,rgb(155,81,224) 100%);
                --wp--preset--gradient--light-green-cyan-to-vivid-green-cyan: linear-gradient(135deg,rgb(122,220,180) 0%,rgb(0,208,130) 100%);
                --wp--preset--gradient--luminous-vivid-amber-to-luminous-vivid-orange: linear-gradient(135deg,rgba(252,185,0,1) 0%,rgba(255,105,0,1) 100%);
                --wp--preset--gradient--luminous-vivid-orange-to-vivid-red: linear-gradient(135deg,rgba(255,105,0,1) 0%,rgb(207,46,46) 100%);
                --wp--preset--gradient--very-light-gray-to-cyan-bluish-gray: linear-gradient(135deg,rgb(238,238,238) 0%,rgb(169,184,195) 100%);
                --wp--preset--gradient--cool-to-warm-spectrum: linear-gradient(135deg,rgb(74,234,220) 0%,rgb(151,120,209) 20%,rgb(207,42,186) 40%,rgb(238,44,130) 60%,rgb(251,105,98) 80%,rgb(254,248,76) 100%);
                --wp--preset--gradient--blush-light-purple: linear-gradient(135deg,rgb(255,206,236) 0%,rgb(152,150,240) 100%);
                --wp--preset--gradient--blush-bordeaux: linear-gradient(135deg,rgb(254,205,165) 0%,rgb(254,45,45) 50%,rgb(107,0,62) 100%);
                --wp--preset--gradient--luminous-dusk: linear-gradient(135deg,rgb(255,203,112) 0%,rgb(199,81,192) 50%,rgb(65,88,208) 100%);
                --wp--preset--gradient--pale-ocean: linear-gradient(135deg,rgb(255,245,203) 0%,rgb(182,227,212) 50%,rgb(51,167,181) 100%);
                --wp--preset--gradient--electric-grass: linear-gradient(135deg,rgb(202,248,128) 0%,rgb(113,206,126) 100%);
                --wp--preset--gradient--midnight: linear-gradient(135deg,rgb(2,3,129) 0%,rgb(40,116,252) 100%);
                --wp--preset--font-size--small: 13px;
                --wp--preset--font-size--medium: 20px;
                --wp--preset--font-size--large: 36px;
                --wp--preset--font-size--x-large: 42px;
                --wp--preset--spacing--20: 0.44rem;
                --wp--preset--spacing--30: 0.67rem;
                --wp--preset--spacing--40: 1rem;
                --wp--preset--spacing--50: 1.5rem;
                --wp--preset--spacing--60: 2.25rem;
                --wp--preset--spacing--70: 3.38rem;
                --wp--preset--spacing--80: 5.06rem;
                --wp--preset--shadow--natural: 6px 6px 9px rgba(0, 0, 0, 0.2);
                --wp--preset--shadow--deep: 12px 12px 50px rgba(0, 0, 0, 0.4);
                --wp--preset--shadow--sharp: 6px 6px 0px rgba(0, 0, 0, 0.2);
                --wp--preset--shadow--outlined: 6px 6px 0px -3px rgba(255, 255, 255, 1), 6px 6px rgba(0, 0, 0, 1);
                --wp--preset--shadow--crisp: 6px 6px 0px rgba(0, 0, 0, 1);
            }

            :where(.is-layout-flex) {
                gap: 0.5em;
            }

            :where(.is-layout-grid) {
                gap: 0.5em;
            }

            body .is-layout-flex {
                display: flex;
            }

            .is-layout-flex {
                flex-wrap: wrap;
                align-items: center;
            }

            .is-layout-flex > :is(*, div) {
                margin: 0;
            }

            body .is-layout-grid {
                display: grid;
            }

            .is-layout-grid > :is(*, div) {
                margin: 0;
            }

            :where(.wp-block-columns.is-layout-flex) {
                gap: 2em;
            }

            :where(.wp-block-columns.is-layout-grid) {
                gap: 2em;
            }

            :where(.wp-block-post-template.is-layout-flex) {
                gap: 1.25em;
            }

            :where(.wp-block-post-template.is-layout-grid) {
                gap: 1.25em;
            }

            .has-black-color {
                color: var(--wp--preset--color--black) !important;
            }

            .has-cyan-bluish-gray-color {
                color: var(--wp--preset--color--cyan-bluish-gray) !important;
            }

            .has-white-color {
                color: var(--wp--preset--color--white) !important;
            }

            .has-pale-pink-color {
                color: var(--wp--preset--color--pale-pink) !important;
            }

            .has-vivid-red-color {
                color: var(--wp--preset--color--vivid-red) !important;
            }

            .has-luminous-vivid-orange-color {
                color: var(--wp--preset--color--luminous-vivid-orange) !important;
            }

            .has-luminous-vivid-amber-color {
                color: var(--wp--preset--color--luminous-vivid-amber) !important;
            }

            .has-light-green-cyan-color {
                color: var(--wp--preset--color--light-green-cyan) !important;
            }

            .has-vivid-green-cyan-color {
                color: var(--wp--preset--color--vivid-green-cyan) !important;
            }

            .has-pale-cyan-blue-color {
                color: var(--wp--preset--color--pale-cyan-blue) !important;
            }

            .has-vivid-cyan-blue-color {
                color: var(--wp--preset--color--vivid-cyan-blue) !important;
            }

            .has-vivid-purple-color {
                color: var(--wp--preset--color--vivid-purple) !important;
            }

            .has-black-background-color {
                background-color: var(--wp--preset--color--black) !important;
            }

            .has-cyan-bluish-gray-background-color {
                background-color: var(--wp--preset--color--cyan-bluish-gray) !important;
            }

            .has-white-background-color {
                background-color: var(--wp--preset--color--white) !important;
            }

            .has-pale-pink-background-color {
                background-color: var(--wp--preset--color--pale-pink) !important;
            }

            .has-vivid-red-background-color {
                background-color: var(--wp--preset--color--vivid-red) !important;
            }

            .has-luminous-vivid-orange-background-color {
                background-color: var(--wp--preset--color--luminous-vivid-orange) !important;
            }

            .has-luminous-vivid-amber-background-color {
                background-color: var(--wp--preset--color--luminous-vivid-amber) !important;
            }

            .has-light-green-cyan-background-color {
                background-color: var(--wp--preset--color--light-green-cyan) !important;
            }

            .has-vivid-green-cyan-background-color {
                background-color: var(--wp--preset--color--vivid-green-cyan) !important;
            }

            .has-pale-cyan-blue-background-color {
                background-color: var(--wp--preset--color--pale-cyan-blue) !important;
            }

            .has-vivid-cyan-blue-background-color {
                background-color: var(--wp--preset--color--vivid-cyan-blue) !important;
            }

            .has-vivid-purple-background-color {
                background-color: var(--wp--preset--color--vivid-purple) !important;
            }

            .has-black-border-color {
                border-color: var(--wp--preset--color--black) !important;
            }

            .has-cyan-bluish-gray-border-color {
                border-color: var(--wp--preset--color--cyan-bluish-gray) !important;
            }

            .has-white-border-color {
                border-color: var(--wp--preset--color--white) !important;
            }

            .has-pale-pink-border-color {
                border-color: var(--wp--preset--color--pale-pink) !important;
            }

            .has-vivid-red-border-color {
                border-color: var(--wp--preset--color--vivid-red) !important;
            }

            .has-luminous-vivid-orange-border-color {
                border-color: var(--wp--preset--color--luminous-vivid-orange) !important;
            }

            .has-luminous-vivid-amber-border-color {
                border-color: var(--wp--preset--color--luminous-vivid-amber) !important;
            }

            .has-light-green-cyan-border-color {
                border-color: var(--wp--preset--color--light-green-cyan) !important;
            }

            .has-vivid-green-cyan-border-color {
                border-color: var(--wp--preset--color--vivid-green-cyan) !important;
            }

            .has-pale-cyan-blue-border-color {
                border-color: var(--wp--preset--color--pale-cyan-blue) !important;
            }

            .has-vivid-cyan-blue-border-color {
                border-color: var(--wp--preset--color--vivid-cyan-blue) !important;
            }

            .has-vivid-purple-border-color {
                border-color: var(--wp--preset--color--vivid-purple) !important;
            }

            .has-vivid-cyan-blue-to-vivid-purple-gradient-background {
                background: var(--wp--preset--gradient--vivid-cyan-blue-to-vivid-purple) !important;
            }

            .has-light-green-cyan-to-vivid-green-cyan-gradient-background {
                background: var(--wp--preset--gradient--light-green-cyan-to-vivid-green-cyan) !important;
            }

            .has-luminous-vivid-amber-to-luminous-vivid-orange-gradient-background {
                background: var(--wp--preset--gradient--luminous-vivid-amber-to-luminous-vivid-orange) !important;
            }

            .has-luminous-vivid-orange-to-vivid-red-gradient-background {
                background: var(--wp--preset--gradient--luminous-vivid-orange-to-vivid-red) !important;
            }

            .has-very-light-gray-to-cyan-bluish-gray-gradient-background {
                background: var(--wp--preset--gradient--very-light-gray-to-cyan-bluish-gray) !important;
            }

            .has-cool-to-warm-spectrum-gradient-background {
                background: var(--wp--preset--gradient--cool-to-warm-spectrum) !important;
            }

            .has-blush-light-purple-gradient-background {
                background: var(--wp--preset--gradient--blush-light-purple) !important;
            }

            .has-blush-bordeaux-gradient-background {
                background: var(--wp--preset--gradient--blush-bordeaux) !important;
            }

            .has-luminous-dusk-gradient-background {
                background: var(--wp--preset--gradient--luminous-dusk) !important;
            }

            .has-pale-ocean-gradient-background {
                background: var(--wp--preset--gradient--pale-ocean) !important;
            }

            .has-electric-grass-gradient-background {
                background: var(--wp--preset--gradient--electric-grass) !important;
            }

            .has-midnight-gradient-background {
                background: var(--wp--preset--gradient--midnight) !important;
            }

            .has-small-font-size {
                font-size: var(--wp--preset--font-size--small) !important;
            }

            .has-medium-font-size {
                font-size: var(--wp--preset--font-size--medium) !important;
            }

            .has-large-font-size {
                font-size: var(--wp--preset--font-size--large) !important;
            }

            .has-x-large-font-size {
                font-size: var(--wp--preset--font-size--x-large) !important;
            }

            :where(.wp-block-post-template.is-layout-flex) {
                gap: 1.25em;
            }

            :where(.wp-block-post-template.is-layout-grid) {
                gap: 1.25em;
            }

            :where(.wp-block-columns.is-layout-flex) {
                gap: 2em;
            }

            :where(.wp-block-columns.is-layout-grid) {
                gap: 2em;
            }

            :root :where(.wp-block-pullquote) {
                font-size: 1.5em;
                line-height: 1.6;
            }
        </style>
        <link rel='stylesheet' id='hello-elementor-css' href='https://founders.hdoinnovations.com/wp-content/themes/hello-elementor/style.min.css?ver=3.1.1' media='all'/>
        <link rel='stylesheet' id='eael-682-css' href='https://founders.hdoinnovations.com/wp-content/uploads/essential-addons-elementor/eael-682.css?ver=1747314840' media='all'/>
        <link rel='stylesheet' id='swiper-css' href='https://founders.hdoinnovations.com/wp-content/plugins/elementor/assets/lib/swiper/v8/css/swiper.min.css?ver=8.4.5' media='all'/>
        <link rel='stylesheet' id='e-swiper-css' href='https://founders.hdoinnovations.com/wp-content/plugins/elementor/assets/css/conditionals/e-swiper.min.css?ver=3.24.2' media='all'/>
        <link rel='stylesheet' id='elementor-post-676-css' href='https://founders.hdoinnovations.com/wp-content/uploads/elementor/css/post-676.css?ver=1747251923' media='all'/>
        <link rel='stylesheet' id='elementor-global-css' href='https://founders.hdoinnovations.com/wp-content/uploads/elementor/css/global.css?ver=1747251929' media='all'/>
        <link rel='stylesheet' id='e-animation-fadeIn-css' href='https://founders.hdoinnovations.com/wp-content/plugins/elementor/assets/lib/animations/styles/fadeIn.min.css?ver=3.24.2' media='all'/>
        <link rel='stylesheet' id='widget-heading-css' href='https://founders.hdoinnovations.com/wp-content/plugins/elementor/assets/css/widget-heading.min.css?ver=3.24.2' media='all'/>
        <link rel='stylesheet' id='e-animation-pulse-css' href='https://founders.hdoinnovations.com/wp-content/plugins/elementor/assets/lib/animations/styles/e-animation-pulse.min.css?ver=3.24.2' media='all'/>
        <link rel='stylesheet' id='widget-image-css' href='https://founders.hdoinnovations.com/wp-content/plugins/elementor/assets/css/widget-image.min.css?ver=3.24.2' media='all'/>
        <link rel='stylesheet' id='e-animation-bounceInUp-css' href='https://founders.hdoinnovations.com/wp-content/plugins/elementor/assets/lib/animations/styles/bounceInUp.min.css?ver=3.24.2' media='all'/>
        <link rel='stylesheet' id='e-animation-fadeInRight-css' href='https://founders.hdoinnovations.com/wp-content/plugins/elementor/assets/lib/animations/styles/fadeInRight.min.css?ver=3.24.2' media='all'/>
        <link rel='stylesheet' id='e-animation-rotateInUpLeft-css' href='https://founders.hdoinnovations.com/wp-content/plugins/elementor/assets/lib/animations/styles/rotateInUpLeft.min.css?ver=3.24.2' media='all'/>
        <link rel='stylesheet' id='elementor-post-682-css' href='https://founders.hdoinnovations.com/wp-content/uploads/elementor/css/post-682.css?ver=1747332843' media='all'/>
        <link rel='stylesheet' id='font-awesome-5-all-css' href='https://founders.hdoinnovations.com/wp-content/plugins/elementor/assets/lib/font-awesome/css/all.min.css?ver=6.0.4' media='all'/>
        <link rel='stylesheet' id='font-awesome-4-shim-css' href='https://founders.hdoinnovations.com/wp-content/plugins/elementor/assets/lib/font-awesome/css/v4-shims.min.css?ver=6.0.4' media='all'/>
        <link rel='stylesheet' id='google-fonts-1-css' href='https://fonts.googleapis.com/css?family=Poppins%3A100%2C100italic%2C200%2C200italic%2C300%2C300italic%2C400%2C400italic%2C500%2C500italic%2C600%2C600italic%2C700%2C700italic%2C800%2C800italic%2C900%2C900italic%7CRoboto%3A100%2C100italic%2C200%2C200italic%2C300%2C300italic%2C400%2C400italic%2C500%2C500italic%2C600%2C600italic%2C700%2C700italic%2C800%2C800italic%2C900%2C900italic&#038;display=swap&#038;ver=6.8.1' media='all'/>
        <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
        <script src="https://founders.hdoinnovations.com/wp-includes/js/jquery/jquery.min.js?ver=3.7.1" id="jquery-core-js"></script>
        <script src="https://founders.hdoinnovations.com/wp-includes/js/jquery/jquery-migrate.min.js?ver=3.4.1" id="jquery-migrate-js"></script>
        <script src="https://founders.hdoinnovations.com/wp-content/plugins/elementor/assets/lib/font-awesome/js/v4-shims.min.js?ver=6.0.4" id="font-awesome-4-shim-js"></script>
        <link rel="https://api.w.org/" href="https://founders.hdoinnovations.com/wp-json/"/>
        <link rel="alternate" title="JSON" type="application/json" href="https://founders.hdoinnovations.com/wp-json/wp/v2/pages/682"/>
        <link rel="EditURI" type="application/rsd+xml" title="RSD" href="https://founders.hdoinnovations.com/xmlrpc.php?rsd"/>
        <meta name="generator" content="WordPress 6.8.1"/>
        <link rel="canonical" href="https://founders.hdoinnovations.com/"/>
        <link rel='shortlink' href='https://founders.hdoinnovations.com/'/>
        <link rel="alternate" title="oEmbed (JSON)" type="application/json+oembed" href="https://founders.hdoinnovations.com/wp-json/oembed/1.0/embed?url=https%3A%2F%2Ffounders.hdoinnovations.com%2F"/>
        <link rel="alternate" title="oEmbed (XML)" type="text/xml+oembed" href="https://founders.hdoinnovations.com/wp-json/oembed/1.0/embed?url=https%3A%2F%2Ffounders.hdoinnovations.com%2F&#038;format=xml"/>
        <meta name="generator" content="Elementor 3.24.2; features: e_font_icon_svg, additional_custom_breakpoints, e_optimized_control_loading, e_element_cache; settings: css_print_method-external, google_font-enabled, font_display-swap">
        <style>
            .e-con.e-parent:nth-of-type(n+4):not(.e-lazyloaded):not(.e-no-lazyload), .e-con.e-parent:nth-of-type(n+4):not(.e-lazyloaded):not(.e-no-lazyload) * {
                background-image: none !important;
            }

            @media screen and (max-height: 1024px) {
                .e-con.e-parent:nth-of-type(n+3):not(.e-lazyloaded):not(.e-no-lazyload), .e-con.e-parent:nth-of-type(n+3):not(.e-lazyloaded):not(.e-no-lazyload) * {
                    background-image: none !important;
                }
            }

            @media screen and (max-height: 640px) {
                .e-con.e-parent:nth-of-type(n+2):not(.e-lazyloaded):not(.e-no-lazyload), .e-con.e-parent:nth-of-type(n+2):not(.e-lazyloaded):not(.e-no-lazyload) * {
                    background-image: none !important;
                }
            }
        </style>
    </head>
    <body class="home wp-singular page-template page-template-elementor_header_footer page page-id-682 wp-custom-logo wp-theme-hello-elementor elementor-default elementor-template-full-width elementor-kit-676 elementor-page elementor-page-682">
        <div data-elementor-type="templately-header" data-elementor-id="671" class="elementor elementor-671 post-682 page type-page status-publish hentry" data-elementor-post-type="templately_library" data-elementor-title="Header">
            <section class="elementor-section elementor-top-section elementor-element elementor-element-ca74514 elementor-section-content-middle elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-id="ca74514" data-element_type="section" id="header_section">
                <div class="elementor-container elementor-column-gap-no">
                    <div class="elementor-column elementor-col-50 elementor-top-column elementor-element elementor-element-5409fb8" data-id="5409fb8" data-element_type="column">
                        <div class="elementor-widget-wrap elementor-element-populated">
                            <div class="elementor-element elementor-element-197e2bb elementor-widget elementor-widget-tl-site-logo" data-id="197e2bb" data-element_type="widget" data-widget_type="tl-site-logo.default">
                                <div class="elementor-widget-container">
                                    <a href="">
                                        <img src="https://founders.hdoinnovations.com/wp-content/uploads/2025/05/cropped-Kynap_Logo2-scaled-1.png" title="Kynap_Logo2" alt="" class="attachment-full size-full wp-image-893" loading="lazy"/>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="elementor-column elementor-col-50 elementor-top-column elementor-element elementor-element-c854977" data-id="c854977" data-element_type="column">
                        <div class="elementor-widget-wrap elementor-element-populated">
                            <div class="elementor-element elementor-element-760d8d3 elementor-widget__width-auto eael_simple_menu_hamburger_disable_selected_menu_hide eael-simple-menu-hamburger-align-right eael-hamburger--tablet elementor-widget elementor-widget-eael-simple-menu" data-id="760d8d3" data-element_type="widget" data-widget_type="eael-simple-menu.default">
                                <div class="elementor-widget-container">
                                    <style>
                                        @media screen and (max-width: 1024px) {
                                            .eael-hamburger--tablet {
                                                .eael-simple-menu-horizontal, .eael-simple-menu-vertical {
                                                    display: none;
                                                }
                                            }

                                            .eael-hamburger--tablet {
                                                .eael-simple-menu-container .eael-simple-menu-toggle {
                                                    display: block;
                                                }
                                            }
                                        }
                                    </style>
                                    <div data-hamburger-icon="&lt;svg aria-hidden=&quot;true&quot; class=&quot;e-font-icon-svg e-fas-bars&quot; viewBox=&quot;0 0 448 512&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;&gt;&lt;path d=&quot;M16 132h416c8.837 0 16-7.163 16-16V76c0-8.837-7.163-16-16-16H16C7.163 60 0 67.163 0 76v40c0 8.837 7.163 16 16 16zm0 160h416c8.837 0 16-7.163 16-16v-40c0-8.837-7.163-16-16-16H16c-8.837 0-16 7.163-16 16v40c0 8.837 7.163 16 16 16zm0 160h416c8.837 0 16-7.163 16-16v-40c0-8.837-7.163-16-16-16H16c-8.837 0-16 7.163-16 16v40c0 8.837 7.163 16 16 16z&quot;&gt;&lt;/path&gt;&lt;/svg&gt;" data-indicator-icon="&lt;svg aria-hidden=&quot;true&quot; class=&quot;e-font-icon-svg e-fas-angle-down&quot; viewBox=&quot;0 0 320 512&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;&gt;&lt;path d=&quot;M143 352.3L7 216.3c-9.4-9.4-9.4-24.6 0-33.9l22.6-22.6c9.4-9.4 24.6-9.4 33.9 0l96.4 96.4 96.4-96.4c9.4-9.4 24.6-9.4 33.9 0l22.6 22.6c9.4 9.4 9.4 24.6 0 33.9l-136 136c-9.2 9.4-24.4 9.4-33.8 0z&quot;&gt;&lt;/path&gt;&lt;/svg&gt;" data-dropdown-indicator-icon="&lt;svg class=&quot;e-font-icon-svg e-fas-angle-down&quot; viewBox=&quot;0 0 320 512&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;&gt;&lt;path d=&quot;M143 352.3L7 216.3c-9.4-9.4-9.4-24.6 0-33.9l22.6-22.6c9.4-9.4 24.6-9.4 33.9 0l96.4 96.4 96.4-96.4c9.4-9.4 24.6-9.4 33.9 0l22.6 22.6c9.4 9.4 9.4 24.6 0 33.9l-136 136c-9.2 9.4-24.4 9.4-33.8 0z&quot;&gt;&lt;/path&gt;&lt;/svg&gt;" class="eael-simple-menu-container eael-simple-menu-align-right eael-simple-menu--stretch eael-simple-menu-dropdown-align-left preset-1" data-hamburger-breakpoints="{&quot;mobile&quot;:&quot;Mobile Portrait (&gt; 767px)&quot;,&quot;tablet&quot;:&quot;Tablet Portrait (&gt; 1024px)&quot;,&quot;desktop&quot;:&quot;Desktop (&gt; 2400px)&quot;,&quot;none&quot;:&quot;Ninguno&quot;}" data-hamburger-device="tablet">
                                        <ul id="menu-saasland-menu" class="eael-simple-menu eael-simple-menu-dropdown-animate-to-top eael-simple-menu-indicator eael-simple-menu-horizontal">
                                            <li id="menu-item-697" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-home current-menu-item page_item page-item-682 current_page_item menu-item-697">
                                                <a href="index.php" aria-current="page">inicio</a>
                                            </li>
                                            <li id="menu-item-872" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-872">
                                                <a href="">Sobre Nosotros</a>
                                            </li>
                                            <li id="menu-item-928" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-928">
                                                <a href="">Contáctanos</a>
                                            </li>
                                            <li id="menu-item-696" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-696">
                                                <a href="">FAQ</a>
                                            </li>
                                            <li id="menu-item-695" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-695">
                                                <a href="tyc.php">términos y condiciones</a>
                                            </li>
                                        </ul>
                                        <button class="eael-simple-menu-toggle">
                                            <span class="sr-only ">Humberger Toggle Menu</span>
                                            <svg aria-hidden="true" class="e-font-icon-svg e-fas-bars" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M16 132h416c8.837 0 16-7.163 16-16V76c0-8.837-7.163-16-16-16H16C7.163 60 0 67.163 0 76v40c0 8.837 7.163 16 16 16zm0 160h416c8.837 0 16-7.163 16-16v-40c0-8.837-7.163-16-16-16H16c-8.837 0-16 7.163-16 16v40c0 8.837 7.163 16 16 16zm0 160h416c8.837 0 16-7.163 16-16v-40c0-8.837-7.163-16-16-16H16c-8.837 0-16 7.163-16 16v40c0 8.837 7.163 16 16 16z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="elementor-element elementor-element-abb6e3f animated-slow elementor-widget__width-auto elementor-invisible elementor-widget elementor-widget-button" data-id="abb6e3f" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;,&quot;_animation_delay&quot;:900}" data-widget_type="button.default">
                                <div class="elementor-widget-container">
                                    <div class="elementor-button-wrapper">
                                        <a class="elementor-button elementor-button-link elementor-size-lg elementor-animation-pulse" href="http://localhost/kynap/bienvenida.php">
                                            <span class="elementor-button-content-wrapper">
                                                <span class="elementor-button-text">comenzar</span>
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <div data-elementor-type="wp-page" data-elementor-id="682" class="elementor elementor-682">
            <section class="elementor-section elementor-top-section elementor-element elementor-element-2468506 elementor-section-content-middle elementor-reverse-mobile elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-id="2468506" data-element_type="section" id="hero_section">
                <div class="elementor-container elementor-column-gap-no">
                    <div class="elementor-column elementor-col-50 elementor-top-column elementor-element elementor-element-8e55cb4" data-id="8e55cb4" data-element_type="column">
                        <div class="elementor-widget-wrap elementor-element-populated">
                            <div class="elementor-element elementor-element-d1ed589 animated-slow elementor-invisible elementor-widget elementor-widget-heading" data-id="d1ed589" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;}" data-widget_type="heading.default">
                                <div class="elementor-widget-container">
                                    <h1 class="elementor-heading-title elementor-size-default">El primer paso hacía una vida saludable</h1>
                                </div>
                            </div>
                            <div class="elementor-element elementor-element-a765aaf animated-slow elementor-invisible elementor-widget elementor-widget-heading" data-id="a765aaf" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;,&quot;_animation_delay&quot;:600}" data-widget_type="heading.default">
                                <div class="elementor-widget-container">
                                    <p class="elementor-heading-title elementor-size-default">Cambia la calidad de tu vida a través de nuestro servicios con IA, ofrecemos recomendaciones personalizadas para tí</p>
                                </div>
                            </div>
                            <div class="elementor-element elementor-element-4415af0 animated-slow elementor-widget__width-auto elementor-invisible elementor-widget elementor-widget-button" data-id="4415af0" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;,&quot;_animation_delay&quot;:900}" data-widget_type="button.default">
                                <div class="elementor-widget-container">
                                    <div class="elementor-button-wrapper">
                                        <a class="elementor-button elementor-button-link elementor-size-lg elementor-animation-pulse" href="http://localhost/kynap/bienvenida.php">
                                            <span class="elementor-button-content-wrapper">
                                                <span class="elementor-button-text">probar ahora</span>
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="elementor-column elementor-col-50 elementor-top-column elementor-element elementor-element-adc571d" data-id="adc571d" data-element_type="column">
                        <div class="elementor-widget-wrap elementor-element-populated">
                            <div class="elementor-element elementor-element-d9f9112 animated-slow elementor-invisible elementor-widget elementor-widget-image" data-id="d9f9112" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;}" data-widget_type="image.default">
                                <div class="elementor-widget-container">
                                    <img fetchpriority="high" decoding="async" width="902" height="1024" src="https://founders.hdoinnovations.com/wp-content/uploads/2025/05/kynap_Logo1.png" class="attachment-full size-full wp-image-888" alt="" srcset="https://founders.hdoinnovations.com/wp-content/uploads/2025/05/kynap_Logo1.png 902w, https://founders.hdoinnovations.com/wp-content/uploads/2025/05/kynap_Logo1-264x300.png 264w, https://founders.hdoinnovations.com/wp-content/uploads/2025/05/kynap_Logo1-768x872.png 768w" sizes="(max-width: 902px) 100vw, 902px"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <section class="elementor-section elementor-top-section elementor-element elementor-element-e9e2de4 elementor-section-full_width elementor-section-content-middle elementor-reverse-mobile elementor-section-height-default elementor-section-height-default" data-id="e9e2de4" data-element_type="section" id="features_section">
                <div class="elementor-container elementor-column-gap-no">
                    <div class="elementor-column elementor-col-100 elementor-top-column elementor-element elementor-element-3f383e1" data-id="3f383e1" data-element_type="column">
                        <div class="elementor-widget-wrap elementor-element-populated">
                            <div class="elementor-element elementor-element-f9e5b36 elementor-widget__width-initial elementor-absolute elementor-widget elementor-widget-image" data-id="f9e5b36" data-element_type="widget" data-settings="{&quot;_position&quot;:&quot;absolute&quot;}" data-widget_type="image.default">
                                <div class="elementor-widget-container">
                                    <img decoding="async" width="108" height="108" src="https://founders.hdoinnovations.com/wp-content/uploads/2025/05/ec662a31-sl-key-features-layer-1.png" class="attachment-full size-full wp-image-705" alt="" srcset="https://founders.hdoinnovations.com/wp-content/uploads/2025/05/ec662a31-sl-key-features-layer-1.png 108w, https://founders.hdoinnovations.com/wp-content/uploads/2025/05/ec662a31-sl-key-features-layer-1-100x100.png 100w" sizes="(max-width: 108px) 100vw, 108px"/>
                                </div>
                            </div>
                            <div class="elementor-element elementor-element-dc6bc17 animated-slow elementor-invisible elementor-widget elementor-widget-heading" data-id="dc6bc17" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;,&quot;_animation_delay&quot;:100}" data-widget_type="heading.default">
                                <div class="elementor-widget-container">
                                    <h2 class="elementor-heading-title elementor-size-default">Beneficios de Usarnos</h2>
                                </div>
                            </div>
                            <section class="elementor-section elementor-inner-section elementor-element elementor-element-cffe5a3 elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-id="cffe5a3" data-element_type="section">
                                <div class="elementor-container elementor-column-gap-default">
                                    <div class="elementor-column elementor-col-25 elementor-inner-column elementor-element elementor-element-6a85513" data-id="6a85513" data-element_type="column">
                                        <div class="elementor-widget-wrap elementor-element-populated">
                                            <div class="elementor-element elementor-element-99ff300 animated-slow eael-infobox-content-align--center eael-infobox-icon-bg-shape-none eael-infobox-icon-hover-bg-shape-none elementor-invisible elementor-widget elementor-widget-eael-info-box" data-id="99ff300" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;bounceInUp&quot;,&quot;_animation_delay&quot;:200}" data-widget_type="eael-info-box.default">
                                                <div class="elementor-widget-container">
                                                    <div class="eael-infobox">
                                                        <div class="infobox-icon eael-icon-only">
                                                            <div class="infobox-icon-wrap">
                                                                <svg aria-hidden="true" class="e-font-icon-svg e-far-heart" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M458.4 64.3C400.6 15.7 311.3 23 256 79.3 200.7 23 111.4 15.6 53.6 64.3-21.6 127.6-10.6 230.8 43 285.5l175.4 178.7c10 10.2 23.4 15.9 37.6 15.9 14.3 0 27.6-5.6 37.6-15.8L469 285.6c53.5-54.7 64.7-157.9-10.6-221.3zm-23.6 187.5L259.4 430.5c-2.4 2.4-4.4 2.4-6.8 0L77.2 251.8c-36.5-37.2-43.9-107.6 7.3-150.7 38.9-32.7 98.9-27.8 136.5 10.5l35 35.7 35-35.7c37.8-38.5 97.8-43.2 136.5-10.6 51.1 43.1 43.5 113.9 7.3 150.8z"></path>
                                                                </svg>
                                                            </div>
                                                        </div>
                                                        <div class="infobox-content eael-icon-only">
                                                            <h4 class="title">generación de hábitos saludables</h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="elementor-column elementor-col-25 elementor-inner-column elementor-element elementor-element-c7a1edb" data-id="c7a1edb" data-element_type="column">
                                        <div class="elementor-widget-wrap elementor-element-populated">
                                            <div class="elementor-element elementor-element-9c08e57 animated-slow eael-infobox-content-align--center eael-infobox-icon-bg-shape-none eael-infobox-icon-hover-bg-shape-none elementor-invisible elementor-widget elementor-widget-eael-info-box" data-id="9c08e57" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;bounceInUp&quot;,&quot;_animation_delay&quot;:500}" data-widget_type="eael-info-box.default">
                                                <div class="elementor-widget-container">
                                                    <div class="eael-infobox">
                                                        <div class="infobox-icon eael-icon-only">
                                                            <div class="infobox-icon-wrap">
                                                                <svg aria-hidden="true" class="e-font-icon-svg e-fas-person-booth" viewBox="0 0 576 512" xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M192 496c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16V320h-64v176zm32-272h-50.9l-45.2-45.3C115.8 166.6 99.7 160 82.7 160H64c-17.1 0-33.2 6.7-45.3 18.8C6.7 190.9 0 207 0 224.1L.2 320 0 480c0 17.7 14.3 32 31.9 32 17.6 0 32-14.3 32-32l.1-100.7c.9.5 1.6 1.3 2.5 1.7l29.1 43v56c0 17.7 14.3 32 32 32s32-14.3 32-32v-56.5c0-9.9-2.3-19.8-6.7-28.6l-41.2-61.3V253l20.9 20.9c9.1 9.1 21.1 14.1 33.9 14.1H224c17.7 0 32-14.3 32-32s-14.3-32-32-32zM64 128c26.5 0 48-21.5 48-48S90.5 32 64 32 16 53.5 16 80s21.5 48 48 48zm224-96l31.5 223.1-30.9 154.6c-4.3 21.6 13 38.3 31.4 38.3 15.2 0 28-9.1 32.3-30.4.9 16.9 14.6 30.4 31.7 30.4 17.7 0 32-14.3 32-32 0 17.7 14.3 32 32 32s32-14.3 32-32V0H288v32zm-96 0v160h64V0h-32c-17.7 0-32 14.3-32 32zM544 0h-32v496c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16V32c0-17.7-14.3-32-32-32z"></path>
                                                                </svg>
                                                            </div>
                                                        </div>
                                                        <div class="infobox-content eael-icon-only">
                                                            <h4 class="title">Asesoramiento personalizado mediante IA</h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="elementor-column elementor-col-25 elementor-inner-column elementor-element elementor-element-e621b3c" data-id="e621b3c" data-element_type="column">
                                        <div class="elementor-widget-wrap elementor-element-populated">
                                            <div class="elementor-element elementor-element-f8a6a97 animated-slow eael-infobox-content-align--center eael-infobox-icon-bg-shape-none eael-infobox-icon-hover-bg-shape-none elementor-invisible elementor-widget elementor-widget-eael-info-box" data-id="f8a6a97" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;bounceInUp&quot;,&quot;_animation_delay&quot;:800}" data-widget_type="eael-info-box.default">
                                                <div class="elementor-widget-container">
                                                    <div class="eael-infobox">
                                                        <div class="infobox-icon eael-icon-only">
                                                            <div class="infobox-icon-wrap">
                                                                <svg aria-hidden="true" class="e-font-icon-svg e-fas-child" viewBox="0 0 384 512" xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M120 72c0-39.765 32.235-72 72-72s72 32.235 72 72c0 39.764-32.235 72-72 72s-72-32.236-72-72zm254.627 1.373c-12.496-12.497-32.758-12.497-45.254 0L242.745 160H141.254L54.627 73.373c-12.496-12.497-32.758-12.497-45.254 0-12.497 12.497-12.497 32.758 0 45.255L104 213.254V480c0 17.673 14.327 32 32 32h16c17.673 0 32-14.327 32-32V368h16v112c0 17.673 14.327 32 32 32h16c17.673 0 32-14.327 32-32V213.254l94.627-94.627c12.497-12.497 12.497-32.757 0-45.254z"></path>
                                                                </svg>
                                                            </div>
                                                        </div>
                                                        <div class="infobox-content eael-icon-only">
                                                            <h4 class="title">Educación nutricional gratuita</h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="elementor-column elementor-col-25 elementor-inner-column elementor-element elementor-element-752a781" data-id="752a781" data-element_type="column">
                                        <div class="elementor-widget-wrap elementor-element-populated">
                                            <div class="elementor-element elementor-element-a7eed85 animated-slow eael-infobox-content-align--center eael-infobox-icon-bg-shape-none eael-infobox-icon-hover-bg-shape-none elementor-invisible elementor-widget elementor-widget-eael-info-box" data-id="a7eed85" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;bounceInUp&quot;,&quot;_animation_delay&quot;:1100}" data-widget_type="eael-info-box.default">
                                                <div class="elementor-widget-container">
                                                    <div class="eael-infobox">
                                                        <div class="infobox-icon eael-icon-only">
                                                            <div class="infobox-icon-wrap">
                                                                <svg aria-hidden="true" class="e-font-icon-svg e-fas-calendar-check" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M436 160H12c-6.627 0-12-5.373-12-12v-36c0-26.51 21.49-48 48-48h48V12c0-6.627 5.373-12 12-12h40c6.627 0 12 5.373 12 12v52h128V12c0-6.627 5.373-12 12-12h40c6.627 0 12 5.373 12 12v52h48c26.51 0 48 21.49 48 48v36c0 6.627-5.373 12-12 12zM12 192h424c6.627 0 12 5.373 12 12v260c0 26.51-21.49 48-48 48H48c-26.51 0-48-21.49-48-48V204c0-6.627 5.373-12 12-12zm333.296 95.947l-28.169-28.398c-4.667-4.705-12.265-4.736-16.97-.068L194.12 364.665l-45.98-46.352c-4.667-4.705-12.266-4.736-16.971-.068l-28.397 28.17c-4.705 4.667-4.736 12.265-.068 16.97l82.601 83.269c4.667 4.705 12.265 4.736 16.97.068l142.953-141.805c4.705-4.667 4.736-12.265.068-16.97z"></path>
                                                                </svg>
                                                            </div>
                                                        </div>
                                                        <div class="infobox-content eael-icon-only">
                                                            <h4 class="title">Accesibilidad y disponibilidad continua</h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </section>
            <section class="elementor-section elementor-top-section elementor-element elementor-element-46c957b elementor-section-full_width elementor-section-content-middle elementor-reverse-mobile elementor-section-height-default elementor-section-height-default" data-id="46c957b" data-element_type="section" id="services_section">
                <div class="elementor-container elementor-column-gap-no">
                    <div class="elementor-column elementor-col-100 elementor-top-column elementor-element elementor-element-7e4326f" data-id="7e4326f" data-element_type="column">
                        <div class="elementor-widget-wrap elementor-element-populated">
                            <div class="elementor-element elementor-element-291c3df animated-slow elementor-widget__width-initial elementor-absolute elementor-invisible elementor-widget elementor-widget-image" data-id="291c3df" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeInRight&quot;,&quot;_animation_delay&quot;:700,&quot;_position&quot;:&quot;absolute&quot;}" data-widget_type="image.default">
                                <div class="elementor-widget-container">
                                    <img decoding="async" width="312" height="385" src="https://founders.hdoinnovations.com/wp-content/uploads/2025/05/bf26e860-sl-shape-layer-1.png" class="attachment-full size-full wp-image-710" alt="" srcset="https://founders.hdoinnovations.com/wp-content/uploads/2025/05/bf26e860-sl-shape-layer-1.png 312w, https://founders.hdoinnovations.com/wp-content/uploads/2025/05/bf26e860-sl-shape-layer-1-243x300.png 243w" sizes="(max-width: 312px) 100vw, 312px"/>
                                </div>
                            </div>
                            <section class="elementor-section elementor-inner-section elementor-element elementor-element-005a80d elementor-section-content-middle elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-id="005a80d" data-element_type="section">
                                <div class="elementor-container elementor-column-gap-no">
                                    <div class="elementor-column elementor-col-50 elementor-inner-column elementor-element elementor-element-b88dc38" data-id="b88dc38" data-element_type="column">
                                        <div class="elementor-widget-wrap elementor-element-populated">
                                            <div class="elementor-element elementor-element-fd84ee2 animated-slow elementor-widget__width-initial elementor-absolute elementor-invisible elementor-widget elementor-widget-image" data-id="fd84ee2" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;rotateInUpLeft&quot;,&quot;_animation_delay&quot;:700,&quot;_position&quot;:&quot;absolute&quot;}" data-widget_type="image.default">
                                                <div class="elementor-widget-container">
                                                    <img loading="lazy" decoding="async" width="260" height="235" src="https://founders.hdoinnovations.com/wp-content/uploads/2025/05/f921bff6-sl-shape-layer-2-1.png" class="attachment-full size-full wp-image-711" alt=""/>
                                                </div>
                                            </div>
                                            <div class="elementor-element elementor-element-cc4dcba animated-slow elementor-invisible elementor-widget elementor-widget-image" data-id="cc4dcba" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;,&quot;_animation_delay&quot;:200}" data-widget_type="image.default">
                                                <div class="elementor-widget-container">
                                                    <img loading="lazy" decoding="async" width="819" height="580" src="https://founders.hdoinnovations.com/wp-content/uploads/2025/05/0e29c998-sl-our-team-call-image-1.png" class="attachment-full size-full wp-image-713" alt="" srcset="https://founders.hdoinnovations.com/wp-content/uploads/2025/05/0e29c998-sl-our-team-call-image-1.png 819w, https://founders.hdoinnovations.com/wp-content/uploads/2025/05/0e29c998-sl-our-team-call-image-1-300x212.png 300w, https://founders.hdoinnovations.com/wp-content/uploads/2025/05/0e29c998-sl-our-team-call-image-1-768x544.png 768w" sizes="(max-width: 819px) 100vw, 819px"/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="elementor-column elementor-col-50 elementor-inner-column elementor-element elementor-element-fb8ff08" data-id="fb8ff08" data-element_type="column">
                                        <div class="elementor-widget-wrap elementor-element-populated">
                                            <div class="elementor-element elementor-element-80ffb98 animated-slow elementor-invisible elementor-widget elementor-widget-heading" data-id="80ffb98" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;,&quot;_animation_delay&quot;:200}" data-widget_type="heading.default">
                                                <div class="elementor-widget-container">
                                                    <h2 class="elementor-heading-title elementor-size-default">Sobre Nosotros</h2>
                                                </div>
                                            </div>
                                            <div class="elementor-element elementor-element-289e90f animated-slow elementor-invisible elementor-widget elementor-widget-heading" data-id="289e90f" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;,&quot;_animation_delay&quot;:500}" data-widget_type="heading.default">
                                                <div class="elementor-widget-container">
                                                    <p class="elementor-heading-title elementor-size-default">
                                                        <span style="white-space-collapse: collapse;">En Kynap, creemos que el bienestar integral es la base de una vida plena. Por eso, nos dedicamos a ofrecer asistencia personalizada en hábitos saludables haciendo uso de tecnología de Inteligencia Artificial, con la finalidad de brindarle una buena calidad de vida, para ello se combina el uso de tecnología, ciencia y acompañamiento humano.</span>
                                                        <br>
                                                        <br>
                                                        <span style="white-space-collapse: collapse;">Nuestra misión es inspirar y guiar a las personas hacia una vida más saludable, a través de información confiable, seguimiento personalizado y herramientas intuitivas.</span>
                                                        <br>
                                                        <br>
                                                        <span style="white-space-collapse: collapse;">Contamos con un equipo comprometido y apasionado por brindar soluciones efectivas, accesibles e innovadoras a nuestros usuarios.</span>
                                                        <br style="white-space-collapse: collapse;">
                                                        <span style="white-space-collapse: collapse;">Creemos en los valores de responsabilidad, innovación, cercanía al usuario, y queremos ser parte activa de un cambio positivo en la vida de las personas.</span>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="elementor-element elementor-element-7aea53b animated-slow elementor-widget__width-auto elementor-invisible elementor-widget elementor-widget-button" data-id="7aea53b" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;,&quot;_animation_delay&quot;:700}" data-widget_type="button.default">
                                                <div class="elementor-widget-container">
                                                    <div class="elementor-button-wrapper">
                                                        <a class="elementor-button elementor-size-lg elementor-animation-pulse" role="button">
                                                            <span class="elementor-button-content-wrapper">
                                                                <span class="elementor-button-text">Contáctanos</span>
                                                            </span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </section>
            <section class="elementor-section elementor-top-section elementor-element elementor-element-3fa5ac2 elementor-section-full_width elementor-section-content-middle elementor-reverse-mobile elementor-section-height-default elementor-section-height-default" data-id="3fa5ac2" data-element_type="section">
                <div class="elementor-container elementor-column-gap-no">
                    <div class="elementor-column elementor-col-100 elementor-top-column elementor-element elementor-element-4e339cc" data-id="4e339cc" data-element_type="column">
                        <div class="elementor-widget-wrap elementor-element-populated">
                            <section class="elementor-section elementor-inner-section elementor-element elementor-element-586ae26 elementor-section-content-middle elementor-reverse-tablet elementor-reverse-mobile elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-id="586ae26" data-element_type="section">
                                <div class="elementor-container elementor-column-gap-no">
                                    <div class="elementor-column elementor-col-50 elementor-inner-column elementor-element elementor-element-3326098" data-id="3326098" data-element_type="column">
                                        <div class="elementor-widget-wrap elementor-element-populated">
                                            <div class="elementor-element elementor-element-f386513 animated-slow elementor-invisible elementor-widget elementor-widget-heading" data-id="f386513" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;,&quot;_animation_delay&quot;:200}" data-widget_type="heading.default">
                                                <div class="elementor-widget-container">
                                                    <h2 class="elementor-heading-title elementor-size-default">Interfaz intuitiva
</h2>
                                                </div>
                                            </div>
                                            <div class="elementor-element elementor-element-3fd4fff animated-slow elementor-invisible elementor-widget elementor-widget-heading" data-id="3fd4fff" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;,&quot;_animation_delay&quot;:500}" data-widget_type="heading.default">
                                                <div class="elementor-widget-container">
                                                    <p class="elementor-heading-title elementor-size-default">El diseño está enfocado en ser intuitivo y fácil de utilizar para personas de todas las edades, incluyendo botones claros, formularios simples y respuestas automatizadas.</p>
                                                </div>
                                            </div>
                                            <div class="elementor-element elementor-element-55864d4 animated-slow elementor-widget__width-auto elementor-widget-mobile__width-initial elementor-invisible elementor-widget elementor-widget-button" data-id="55864d4" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;,&quot;_animation_delay&quot;:900}" data-widget_type="button.default">
                                                <div class="elementor-widget-container">
                                                    <div class="elementor-button-wrapper">
                                                        <a class="elementor-button elementor-button-link elementor-size-lg elementor-animation-pulse" href="http://localhost/kynap/bienvenida.php">
                                                            <span class="elementor-button-content-wrapper">
                                                                <span class="elementor-button-text">comenzar</span>
                                                            </span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="elementor-column elementor-col-50 elementor-inner-column elementor-element elementor-element-a8d2a74" data-id="a8d2a74" data-element_type="column">
                                        <div class="elementor-widget-wrap elementor-element-populated">
                                            <div class="elementor-element elementor-element-e1c471e animated-slow elementor-invisible elementor-widget elementor-widget-image" data-id="e1c471e" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;,&quot;_animation_delay&quot;:200}" data-widget_type="image.default">
                                                <div class="elementor-widget-container">
                                                    <img loading="lazy" decoding="async" width="819" height="580" src="https://founders.hdoinnovations.com/wp-content/uploads/2025/05/793a015d-sl-our-best-integration-image-1-1.png" class="attachment-full size-full wp-image-714" alt="" srcset="https://founders.hdoinnovations.com/wp-content/uploads/2025/05/793a015d-sl-our-best-integration-image-1-1.png 819w, https://founders.hdoinnovations.com/wp-content/uploads/2025/05/793a015d-sl-our-best-integration-image-1-1-300x212.png 300w, https://founders.hdoinnovations.com/wp-content/uploads/2025/05/793a015d-sl-our-best-integration-image-1-1-768x544.png 768w" sizes="(max-width: 819px) 100vw, 819px"/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </section>
            <section class="elementor-section elementor-top-section elementor-element elementor-element-9420b5d elementor-section-full_width elementor-section-content-middle elementor-reverse-mobile elementor-section-height-default elementor-section-height-default" data-id="9420b5d" data-element_type="section">
                <div class="elementor-container elementor-column-gap-no">
                    <div class="elementor-column elementor-col-100 elementor-top-column elementor-element elementor-element-96c6ea8" data-id="96c6ea8" data-element_type="column">
                        <div class="elementor-widget-wrap elementor-element-populated">
                            <div class="elementor-element elementor-element-cc60299 animated-slow elementor-widget__width-initial elementor-absolute elementor-invisible elementor-widget elementor-widget-image" data-id="cc60299" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeInRight&quot;,&quot;_animation_delay&quot;:700,&quot;_position&quot;:&quot;absolute&quot;}" data-widget_type="image.default">
                                <div class="elementor-widget-container">
                                    <img decoding="async" width="312" height="385" src="https://founders.hdoinnovations.com/wp-content/uploads/2025/05/bf26e860-sl-shape-layer-1.png" class="attachment-full size-full wp-image-710" alt="" srcset="https://founders.hdoinnovations.com/wp-content/uploads/2025/05/bf26e860-sl-shape-layer-1.png 312w, https://founders.hdoinnovations.com/wp-content/uploads/2025/05/bf26e860-sl-shape-layer-1-243x300.png 243w" sizes="(max-width: 312px) 100vw, 312px"/>
                                </div>
                            </div>
                            <section class="elementor-section elementor-inner-section elementor-element elementor-element-6a407e6 elementor-section-content-middle elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-id="6a407e6" data-element_type="section">
                                <div class="elementor-container elementor-column-gap-no">
                                    <div class="elementor-column elementor-col-50 elementor-inner-column elementor-element elementor-element-31c7112" data-id="31c7112" data-element_type="column">
                                        <div class="elementor-widget-wrap elementor-element-populated">
                                            <div class="elementor-element elementor-element-cd301f3 animated-slow elementor-invisible elementor-widget elementor-widget-image" data-id="cd301f3" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;,&quot;_animation_delay&quot;:200}" data-widget_type="image.default">
                                                <div class="elementor-widget-container">
                                                    <img loading="lazy" decoding="async" width="819" height="580" src="https://founders.hdoinnovations.com/wp-content/uploads/2025/05/315e6a41-sl-the-free-developer-image-1.png" class="attachment-full size-full wp-image-716" alt="" srcset="https://founders.hdoinnovations.com/wp-content/uploads/2025/05/315e6a41-sl-the-free-developer-image-1.png 819w, https://founders.hdoinnovations.com/wp-content/uploads/2025/05/315e6a41-sl-the-free-developer-image-1-300x212.png 300w, https://founders.hdoinnovations.com/wp-content/uploads/2025/05/315e6a41-sl-the-free-developer-image-1-768x544.png 768w" sizes="(max-width: 819px) 100vw, 819px"/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="elementor-column elementor-col-50 elementor-inner-column elementor-element elementor-element-ce48090" data-id="ce48090" data-element_type="column">
                                        <div class="elementor-widget-wrap elementor-element-populated">
                                            <div class="elementor-element elementor-element-675bf8c animated-slow elementor-invisible elementor-widget elementor-widget-heading" data-id="675bf8c" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;,&quot;_animation_delay&quot;:200}" data-widget_type="heading.default">
                                                <div class="elementor-widget-container">
                                                    <h2 class="elementor-heading-title elementor-size-default">Recomendaciones profesionales</h2>
                                                </div>
                                            </div>
                                            <div class="elementor-element elementor-element-c83a96c animated-slow elementor-invisible elementor-widget elementor-widget-heading" data-id="c83a96c" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;,&quot;_animation_delay&quot;:500}" data-widget_type="heading.default">
                                                <div class="elementor-widget-container">
                                                    <p class="elementor-heading-title elementor-size-default">Las recomendaciones  brindadas por el chatbot de Kynap Ai, están basadas en evidencia científica, esto quiere decir que solo usará fuentes confiables e información certificada.</p>
                                                </div>
                                            </div>
                                            <div class="elementor-element elementor-element-3ed1b34 animated-slow elementor-widget__width-auto elementor-widget-mobile__width-initial elementor-invisible elementor-widget elementor-widget-button" data-id="3ed1b34" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;,&quot;_animation_delay&quot;:900}" data-widget_type="button.default">
                                                <div class="elementor-widget-container">
                                                    <div class="elementor-button-wrapper">
                                                        <a class="elementor-button elementor-button-link elementor-size-lg elementor-animation-pulse" href="http://localhost/kynap/bienvenida.php">
                                                            <span class="elementor-button-content-wrapper">
                                                                <span class="elementor-button-text">comenzar</span>
                                                            </span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </section>
            <section class="elementor-section elementor-top-section elementor-element elementor-element-16973e3 elementor-section-content-middle elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-id="16973e3" data-element_type="section" id="faq_section">
                <div class="elementor-container elementor-column-gap-no">
                    <section class="elementor-column elementor-col-100 elementor-top-column elementor-element elementor-element-ecedcf7" data-id="ecedcf7" data-element_type="column">
                        <div class="elementor-widget-wrap elementor-element-populated">
                            <div class="elementor-element elementor-element-d10b29c animated-slow elementor-invisible elementor-widget elementor-widget-heading" data-id="d10b29c" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;}" data-widget_type="heading.default">
                                <div class="elementor-widget-container">
                                    <h2 class="elementor-heading-title elementor-size-default">preguntas frecuentes y ayuda</h2>
                                </div>
                            </div>
                            <div class="elementor-element elementor-element-b5597d1 animated-slow elementor-invisible elementor-widget elementor-widget-eael-adv-accordion" data-id="b5597d1" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;,&quot;_animation_delay&quot;:300}" data-widget_type="eael-adv-accordion.default">
                                <div class="elementor-widget-container">
                                    <div class="eael-adv-accordion" id="eael-adv-accordion-b5597d1" data-scroll-on-click="no" data-scroll-speed="300" data-accordion-id="b5597d1" data-accordion-type="accordion" data-toogle-speed="300">
                                        <div class="eael-accordion-list">
                                            <div id="qu-tipo-de-recomendaciones-ofrece-esta-pgina" class="elementor-tab-title eael-accordion-header" tabindex="0" data-tab="1" aria-controls="elementor-tab-content-1901">
                                                <span class="eael-accordion-tab-title">¿Qué tipo de recomendaciones ofrece esta página?</span>
                                                <svg aria-hidden="true" class="fa-toggle e-font-icon-svg e-fas-angle-right" viewBox="0 0 256 512" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34z"></path>
                                                </svg>
                                            </div>
                                            <div id="elementor-tab-content-1901" class="eael-accordion-content clearfix" data-tab="1" aria-labelledby="qu-tipo-de-recomendaciones-ofrece-esta-pgina">
                                                <p>La página brinda sugerencias personalizadas sobre alimentación nutritiva, hidratación, actividad física y hábitos saludables, según las características físicas y el estilo de vida del usuario.</p>
                                            </div>
                                        </div>
                                        <div class="eael-accordion-list">
                                            <div id="cmo-funciona-el-sistema-de-recomendaciones" class="elementor-tab-title eael-accordion-header" tabindex="0" data-tab="2" aria-controls="elementor-tab-content-1902">
                                                <span class="eael-accordion-tab-title">¿Cómo funciona el sistema de recomendaciones?</span>
                                                <svg aria-hidden="true" class="fa-toggle e-font-icon-svg e-fas-angle-right" viewBox="0 0 256 512" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34z"></path>
                                                </svg>
                                            </div>
                                            <div id="elementor-tab-content-1902" class="eael-accordion-content clearfix" data-tab="2" aria-labelledby="cmo-funciona-el-sistema-de-recomendaciones">
                                                <p>Nuestra IA analiza tus datos y haciendo uso de los mismo genera recomendaciones con base científica adecuadas para ti.</p>
                                            </div>
                                        </div>
                                        <div class="eael-accordion-list">
                                            <div id="cmo-puedo-registrarme" class="elementor-tab-title eael-accordion-header" tabindex="0" data-tab="3" aria-controls="elementor-tab-content-1903">
                                                <span class="eael-accordion-tab-title">¿Cómo puedo registrarme?</span>
                                                <svg aria-hidden="true" class="fa-toggle e-font-icon-svg e-fas-angle-right" viewBox="0 0 256 512" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34z"></path>
                                                </svg>
                                            </div>
                                            <div id="elementor-tab-content-1903" class="eael-accordion-content clearfix" data-tab="3" aria-labelledby="cmo-puedo-registrarme">
                                                <p>Solo necesitas registrar tu correo electrónico y una contraseña segura, además de proporcionar información personal sobre tu salud para que nuestro sistema pueda hacer recomendaciones personalizadas. ¡Y listo! Podrás hacer uso de nuestro servicio.</p>
                                            </div>
                                        </div>
                                        <div class="eael-accordion-list">
                                            <div id="dnde-puedo-cambiar-mi-informacin" class="elementor-tab-title eael-accordion-header" tabindex="0" data-tab="4" aria-controls="elementor-tab-content-1904">
                                                <span class="eael-accordion-tab-title">¿Dónde puedo cambiar mi información?</span>
                                                <svg aria-hidden="true" class="fa-toggle e-font-icon-svg e-fas-angle-right" viewBox="0 0 256 512" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34z"></path>
                                                </svg>
                                            </div>
                                            <div id="elementor-tab-content-1904" class="eael-accordion-content clearfix" data-tab="4" aria-labelledby="dnde-puedo-cambiar-mi-informacin">
                                                <p>En el menú, selecciona “Configuración”, luego “información”  y encontrarás una opción de editar. De esta manera podrás realizar cambios en tu información. Estos cambios solo pueden hacerse cada 15 días. Nuestra página está diseñada para pedir cambios automáticamente cada 30 días (se puede omitir).</p>
                                            </div>
                                        </div>
                                        <div class="eael-accordion-list">
                                            <div id="la-asesora-que-brinda-el-chatbot-reemplaza-a-un-nutricionista" class="elementor-tab-title eael-accordion-header" tabindex="0" data-tab="5" aria-controls="elementor-tab-content-1905">
                                                <span class="eael-accordion-tab-title">¿La asesoría que brinda el chatbot reemplaza a un nutricionista?</span>
                                                <svg aria-hidden="true" class="fa-toggle e-font-icon-svg e-fas-angle-right" viewBox="0 0 256 512" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34z"></path>
                                                </svg>
                                            </div>
                                            <div id="elementor-tab-content-1905" class="eael-accordion-content clearfix" data-tab="5" aria-labelledby="la-asesora-que-brinda-el-chatbot-reemplaza-a-un-nutricionista">
                                                <p>No. El chatbot ofrece orientación general basada en inteligencia artificial, pero no sustituye la evaluación médica o nutricional profesional. Se recomienda consultar a un especialista para diagnósticos específicos.</p>
                                            </div>
                                        </div>
                                        <div class="eael-accordion-list">
                                            <div id="debo-pagar-para-usar-esta-pgina-web" class="elementor-tab-title eael-accordion-header" tabindex="0" data-tab="6" aria-controls="elementor-tab-content-1906">
                                                <span class="eael-accordion-tab-title">¿Debo pagar para usar esta página web?</span>
                                                <svg aria-hidden="true" class="fa-toggle e-font-icon-svg e-fas-angle-right" viewBox="0 0 256 512" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34z"></path>
                                                </svg>
                                            </div>
                                            <div id="elementor-tab-content-1906" class="eael-accordion-content clearfix" data-tab="6" aria-labelledby="debo-pagar-para-usar-esta-pgina-web">
                                                <p>
                                                    <span style="font-weight: 400;">No, nuestra inteligencia artificial genera recomendaciones con un limite de intentos al día. No obstante, está completamente abierta la opción de comprar una suscripción especial para obtener un servicio completo.</span>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="eael-accordion-list">
                                            <div id="necesitas-ms-ayuda" class="elementor-tab-title eael-accordion-header" tabindex="0" data-tab="7" aria-controls="elementor-tab-content-1907">
                                                <span class="eael-accordion-tab-title">¿Necesitas más ayuda?</span>
                                                <svg aria-hidden="true" class="fa-toggle e-font-icon-svg e-fas-angle-right" viewBox="0 0 256 512" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34z"></path>
                                                </svg>
                                            </div>
                                            <div id="elementor-tab-content-1907" class="eael-accordion-content clearfix" data-tab="7" aria-labelledby="necesitas-ms-ayuda">
                                                <p>Escríbenos a &#8220;kynapservice@gmail.com &#8221;.</p>
                                                <p>Te responderemos rápidamente. 😉</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </section>
        </div>
        <div data-elementor-type="templately-footer" data-elementor-id="674" class="elementor elementor-674 post-682 page type-page status-publish hentry" data-elementor-post-type="templately_library" data-elementor-title="Footer">
            <section class="elementor-section elementor-top-section elementor-element elementor-element-dbc712b elementor-section-content-middle elementor-reverse-mobile elementor-section-boxed elementor-section-height-default elementor-section-height-default" data-id="dbc712b" data-element_type="section" id="contact">
                <div class="elementor-container elementor-column-gap-no">
                    <div class="elementor-column elementor-col-100 elementor-top-column elementor-element elementor-element-b0afc3b" data-id="b0afc3b" data-element_type="column">
                        <div class="elementor-widget-wrap elementor-element-populated">
                            <div class="elementor-element elementor-element-53c9dd1 animated-slow elementor-invisible elementor-widget elementor-widget-heading" data-id="53c9dd1" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;}" data-widget_type="heading.default">
                                <div class="elementor-widget-container">
                                    <h2 class="elementor-heading-title elementor-size-default">¿Qué esperas?</h2>
                                </div>
                            </div>
                            <div class="elementor-element elementor-element-74f2cd8 animated-slow elementor-invisible elementor-widget elementor-widget-heading" data-id="74f2cd8" data-element_type="widget" data-settings="{&quot;_animation&quot;:&quot;fadeIn&quot;,&quot;_animation_delay&quot;:200}" data-widget_type="heading.default">
                                <div class="elementor-widget-container">
                                    <p class="elementor-heading-title elementor-size-default">Mejora tu salud y vida antes de que sea demasiado tarde</p>
                                </div>
                            </div>
                            <section class="elementor-section elementor-inner-section elementor-element elementor-element-085ca1d elementor-section-full_width elementor-section-content-middle elementor-section-height-default elementor-section-height-default" data-id="085ca1d" data-element_type="section">
                                <div class="elementor-container elementor-column-gap-no">
                                    <div class="elementor-column elementor-col-33 elementor-inner-column elementor-element elementor-element-837f067" data-id="837f067" data-element_type="column">
                                        <div class="elementor-widget-wrap"></div>
                                    </div>
                                    <div class="elementor-column elementor-col-33 elementor-inner-column elementor-element elementor-element-e8536ea" data-id="e8536ea" data-element_type="column">
                                        <div class="elementor-widget-wrap elementor-element-populated">
                                            <div class="elementor-element elementor-element-5a9f60b elementor-shape-rounded elementor-grid-0 e-grid-align-center elementor-widget elementor-widget-social-icons" data-id="5a9f60b" data-element_type="widget" data-widget_type="social-icons.default">
                                                <div class="elementor-widget-container">
                                                    <div class="elementor-social-icons-wrapper elementor-grid">
                                                        <span class="elementor-grid-item">
                                                            <a class="elementor-icon elementor-social-icon elementor-social-icon-facebook-square elementor-animation-grow elementor-repeater-item-6b56f98" target="_blank">
                                                                <span class="elementor-screen-only">Facebook-square</span>
                                                                <svg class="e-font-icon-svg e-fab-facebook-square" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M400 32H48A48 48 0 0 0 0 80v352a48 48 0 0 0 48 48h137.25V327.69h-63V256h63v-54.64c0-62.15 37-96.48 93.67-96.48 27.14 0 55.52 4.84 55.52 4.84v61h-31.27c-30.81 0-40.42 19.12-40.42 38.73V256h68.78l-11 71.69h-57.78V480H400a48 48 0 0 0 48-48V80a48 48 0 0 0-48-48z"></path>
                                                                </svg>
                                                            </a>
                                                        </span>
                                                        <span class="elementor-grid-item">
                                                            <a class="elementor-icon elementor-social-icon elementor-social-icon-x-twitter elementor-animation-grow elementor-repeater-item-b03aad0" target="_blank">
                                                                <span class="elementor-screen-only">X-twitter</span>
                                                                <svg class="e-font-icon-svg e-fab-x-twitter" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"></path>
                                                                </svg>
                                                            </a>
                                                        </span>
                                                        <span class="elementor-grid-item">
                                                            <a class="elementor-icon elementor-social-icon elementor-social-icon-instagram elementor-animation-grow elementor-repeater-item-cf2da25" target="_blank">
                                                                <span class="elementor-screen-only">Instagram</span>
                                                                <svg class="e-font-icon-svg e-fab-instagram" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z"></path>
                                                                </svg>
                                                            </a>
                                                        </span>
                                                        <span class="elementor-grid-item">
                                                            <a class="elementor-icon elementor-social-icon elementor-social-icon-envelope elementor-animation-grow elementor-repeater-item-427ad40" target="_blank">
                                                                <span class="elementor-screen-only">Envelope</span>
                                                                <svg class="e-font-icon-svg e-fas-envelope" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M502.3 190.8c3.9-3.1 9.7-.2 9.7 4.7V400c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V195.6c0-5 5.7-7.8 9.7-4.7 22.4 17.4 52.1 39.5 154.1 113.6 21.1 15.4 56.7 47.8 92.2 47.6 35.7.3 72-32.8 92.3-47.6 102-74.1 131.6-96.3 154-113.7zM256 320c23.2.4 56.6-29.2 73.4-41.4 132.7-96.3 142.8-104.7 173.4-128.7 5.8-4.5 9.2-11.5 9.2-18.9v-19c0-26.5-21.5-48-48-48H48C21.5 64 0 85.5 0 112v19c0 7.4 3.4 14.3 9.2 18.9 30.6 23.9 40.7 32.4 173.4 128.7 16.8 12.2 50.2 41.8 73.4 41.4z"></path>
                                                                </svg>
                                                            </a>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="elementor-column elementor-col-33 elementor-inner-column elementor-element elementor-element-7d84149" data-id="7d84149" data-element_type="column">
                                        <div class="elementor-widget-wrap"></div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <script type="speculationrules">
            {
                "prefetch": [
                    {
                        "source": "document",
                        "where": {
                            "and": [
                                {
                                    "href_matches": "\/*"
                                },
                                {
                                    "not": {
                                        "href_matches": [
                                            "\/wp-*.php",
                                            "\/wp-admin\/*",
                                            "\/wp-content\/uploads\/*",
                                            "\/wp-content\/*",
                                            "\/wp-content\/plugins\/*",
                                            "\/wp-content\/themes\/hello-elementor\/*",
                                            "\/*\\?(.+)"
                                        ]
                                    }
                                },
                                {
                                    "not": {
                                        "selector_matches": "a[rel~=\"nofollow\"]"
                                    }
                                },
                                {
                                    "not": {
                                        "selector_matches": ".no-prefetch, .no-prefetch a"
                                    }
                                }
                            ]
                        },
                        "eagerness": "conservative"
                    }]
                }</script>
            <script type='text/javascript'>
                const lazyloadRunObserver = () => {
                    const lazyloadBackgrounds = document.querySelectorAll(`.e-con.e-parent:not(.e-lazyloaded)`);
                    const lazyloadBackgroundObserver = new IntersectionObserver( (entries) => {
                        entries.forEach( (entry) => {
                            if (entry.isIntersecting) {
                                let lazyloadBackground = entry.target;
                                if (lazyloadBackground) {
                                    lazyloadBackground.classList.add('e-lazyloaded');
                                }
                                lazyloadBackgroundObserver.unobserve(entry.target);
                            }
                        }
                        );
                    }
                    ,{
                        rootMargin: '200px 0px 200px 0px'
                    });
                    lazyloadBackgrounds.forEach( (lazyloadBackground) => {
                        lazyloadBackgroundObserver.observe(lazyloadBackground);
                    }
                    );
                }
                ;
                const events = ['DOMContentLoaded', 'elementor/lazyload/observe', ];
                events.forEach( (event) => {
                    document.addEventListener(event, lazyloadRunObserver);
                }
                );
            </script>
            <link rel='stylesheet' id='elementor-icons-css' href='https://founders.hdoinnovations.com/wp-content/plugins/elementor/assets/lib/eicons/css/elementor-icons.min.css?ver=5.31.0' media='all'/>
            <link rel='stylesheet' id='fluentform-elementor-widget-css' href='https://founders.hdoinnovations.com/wp-content/plugins/fluentform/assets/css/fluent-forms-elementor-widget.css?ver=6.0.3' media='all'/>
            <link rel='stylesheet' id='e-animation-grow-css' href='https://founders.hdoinnovations.com/wp-content/plugins/elementor/assets/lib/animations/styles/e-animation-grow.min.css?ver=3.24.2' media='all'/>
            <link rel='stylesheet' id='widget-social-icons-css' href='https://founders.hdoinnovations.com/wp-content/plugins/elementor/assets/css/widget-social-icons.min.css?ver=3.24.2' media='all'/>
            <link rel='stylesheet' id='e-apple-webkit-css' href='https://founders.hdoinnovations.com/wp-content/plugins/elementor/assets/css/conditionals/apple-webkit.min.css?ver=3.24.2' media='all'/>
            <script id="eael-general-js-extra">
                var localize = {
                    "ajaxurl": "https:\/\/founders.hdoinnovations.com\/wp-admin\/admin-ajax.php",
                    "nonce": "2b4621ff8f",
                    "i18n": {
                        "added": "Added ",
                        "compare": "Comparar",
                        "loading": "Cargando..."
                    },
                    "eael_translate_text": {
                        "required_text": "is a required field",
                        "invalid_text": "Invalid",
                        "billing_text": "Billing",
                        "shipping_text": "Env\u00edo",
                        "fg_mfp_counter_text": "of"
                    },
                    "page_permalink": "https:\/\/founders.hdoinnovations.com\/",
                    "cart_redirectition": "no",
                    "cart_page_url": "",
                    "el_breakpoints": {
                        "mobile": {
                            "label": "Mobile Portrait",
                            "value": 767,
                            "default_value": 767,
                            "direction": "max",
                            "is_enabled": true
                        },
                        "mobile_extra": {
                            "label": "Mobile Landscape",
                            "value": 880,
                            "default_value": 880,
                            "direction": "max",
                            "is_enabled": false
                        },
                        "tablet": {
                            "label": "Tablet Portrait",
                            "value": 1024,
                            "default_value": 1024,
                            "direction": "max",
                            "is_enabled": true
                        },
                        "tablet_extra": {
                            "label": "Tablet Landscape",
                            "value": 1200,
                            "default_value": 1200,
                            "direction": "max",
                            "is_enabled": false
                        },
                        "laptop": {
                            "label": "Port\u00e1til",
                            "value": 1366,
                            "default_value": 1366,
                            "direction": "max",
                            "is_enabled": false
                        },
                        "widescreen": {
                            "label": "Pantalla grande",
                            "value": 2400,
                            "default_value": 2400,
                            "direction": "min",
                            "is_enabled": false
                        }
                    }
                };
            </script>
            <script src="https://founders.hdoinnovations.com/wp-content/plugins/essential-addons-for-elementor-lite/assets/front-end/js/view/general.min.js?ver=6.0.4" id="eael-general-js"></script>
            <script src="https://founders.hdoinnovations.com/wp-content/uploads/essential-addons-elementor/eael-671.js?ver=1747314840" id="eael-671-js"></script>
            <script src="https://founders.hdoinnovations.com/wp-content/uploads/essential-addons-elementor/eael-682.js?ver=1747314840" id="eael-682-js"></script>
            <script src="https://founders.hdoinnovations.com/wp-content/plugins/elementor/assets/js/webpack.runtime.min.js?ver=3.24.2" id="elementor-webpack-runtime-js"></script>
            <script src="https://founders.hdoinnovations.com/wp-content/plugins/elementor/assets/js/frontend-modules.min.js?ver=3.24.2" id="elementor-frontend-modules-js"></script>
            <script src="https://founders.hdoinnovations.com/wp-includes/js/jquery/ui/core.min.js?ver=1.13.3" id="jquery-ui-core-js"></script>
            <script id="elementor-frontend-js-before">
                var elementorFrontendConfig = {
                    "environmentMode": {
                        "edit": false,
                        "wpPreview": false,
                        "isScriptDebug": false
                    },
                    "i18n": {
                        "shareOnFacebook": "Compartir en Facebook",
                        "shareOnTwitter": "Compartir en Twitter",
                        "pinIt": "Pinear",
                        "download": "Descargar",
                        "downloadImage": "Descargar imagen",
                        "fullscreen": "Pantalla completa",
                        "zoom": "Zoom",
                        "share": "Compartir",
                        "playVideo": "Reproducir v\u00eddeo",
                        "previous": "Anterior",
                        "next": "Siguiente",
                        "close": "Cerrar",
                        "a11yCarouselWrapperAriaLabel": "Carousel | Horizontal scrolling: Arrow Left & Right",
                        "a11yCarouselPrevSlideMessage": "Previous slide",
                        "a11yCarouselNextSlideMessage": "Next slide",
                        "a11yCarouselFirstSlideMessage": "This is the first slide",
                        "a11yCarouselLastSlideMessage": "This is the last slide",
                        "a11yCarouselPaginationBulletMessage": "Go to slide"
                    },
                    "is_rtl": false,
                    "breakpoints": {
                        "xs": 0,
                        "sm": 480,
                        "md": 768,
                        "lg": 1025,
                        "xl": 1440,
                        "xxl": 1600
                    },
                    "responsive": {
                        "breakpoints": {
                            "mobile": {
                                "label": "Mobile Portrait",
                                "value": 767,
                                "default_value": 767,
                                "direction": "max",
                                "is_enabled": true
                            },
                            "mobile_extra": {
                                "label": "Mobile Landscape",
                                "value": 880,
                                "default_value": 880,
                                "direction": "max",
                                "is_enabled": false
                            },
                            "tablet": {
                                "label": "Tablet Portrait",
                                "value": 1024,
                                "default_value": 1024,
                                "direction": "max",
                                "is_enabled": true
                            },
                            "tablet_extra": {
                                "label": "Tablet Landscape",
                                "value": 1200,
                                "default_value": 1200,
                                "direction": "max",
                                "is_enabled": false
                            },
                            "laptop": {
                                "label": "Port\u00e1til",
                                "value": 1366,
                                "default_value": 1366,
                                "direction": "max",
                                "is_enabled": false
                            },
                            "widescreen": {
                                "label": "Pantalla grande",
                                "value": 2400,
                                "default_value": 2400,
                                "direction": "min",
                                "is_enabled": false
                            }
                        }
                    },
                    "version": "3.24.2",
                    "is_static": false,
                    "experimentalFeatures": {
                        "e_font_icon_svg": true,
                        "additional_custom_breakpoints": true,
                        "container": true,
                        "container_grid": true,
                        "e_swiper_latest": true,
                        "e_nested_atomic_repeaters": true,
                        "e_optimized_control_loading": true,
                        "e_onboarding": true,
                        "hello-theme-header-footer": true,
                        "home_screen": true,
                        "ai-layout": true,
                        "nested-elements": true,
                        "editor_v2": true,
                        "e_element_cache": true,
                        "link-in-bio": true,
                        "floating-buttons": true
                    },
                    "urls": {
                        "assets": "https:\/\/founders.hdoinnovations.com\/wp-content\/plugins\/elementor\/assets\/",
                        "ajaxurl": "https:\/\/founders.hdoinnovations.com\/wp-admin\/admin-ajax.php"
                    },
                    "nonces": {
                        "floatingButtonsClickTracking": "097c447c50"
                    },
                    "swiperClass": "swiper",
                    "settings": {
                        "page": [],
                        "editorPreferences": []
                    },
                    "kit": {
                        "body_background_background": "classic",
                        "active_breakpoints": ["viewport_mobile", "viewport_tablet"],
                        "global_image_lightbox": "yes",
                        "lightbox_enable_counter": "yes",
                        "lightbox_enable_fullscreen": "yes",
                        "lightbox_enable_zoom": "yes",
                        "lightbox_enable_share": "yes",
                        "lightbox_title_src": "title",
                        "lightbox_description_src": "description"
                    },
                    "post": {
                        "id": 682,
                        "title": "Kynap%20%E2%80%93%20Tu%20asistente%20nutricional",
                        "excerpt": "",
                        "featuredImage": false
                    }
                };
            </script>
            <script src="https://founders.hdoinnovations.com/wp-content/plugins/elementor/assets/js/frontend.min.js?ver=3.24.2" id="elementor-frontend-js"></script>
        </body>
    </html>
