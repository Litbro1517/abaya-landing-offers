/* =====================================================================
 * Page /merci/ — Pop-up d'évaluation « 1-clic » perfectionné
 * (mandat 4P LP « Thank You Page Redesign » ; étoiles v3.15 — système
 * interactif fourni : tracés SVG arrondis + dégradé satiné)
 * ---------------------------------------------------------------------
 * UX imposée par le mandat :
 *   1. Remplissage progressif : au survol ou au clic sur la N-ième étoile,
 *      TOUTES les étoiles de 1 à N s'allument simultanément (sens RTL).
 *   2. Persistance : un clic enregistre DIRECTEMENT la note en base de
 *      données (route REST landing/v1/submit-review, coordonnées de la
 *      commande attachées) puis le pop-up NE SE FERME PAS instantanément —
 *      il affiche un message de remerciement chaleureux avec une zone de
 *      texte OPTIONNELLE.
 *   3. Fermeture : via la croix (X), le bouton valider (envoi du texte
 *      optionnel), ou après un délai prolongé (réinitialisé à chaque
 *      interaction dans la modale).
 *   4. Règle du maximum : si l'utilisatrice modifie son choix, la valeur
 *      conservée en base est max(note_existante, nouvelle_note) — le
 *      serveur renvoie la note finale, l'affichage s'y aligne.
 *   5. Modération intelligente (côté serveur) : étoiles seules = auto-
 *      approbation immédiate (comment_approved = 1, score sous le H1
 *      actualisé) ; avis avec texte = modération (comment_approved = 0).
 *
 * Mode simulation (mock-api.js) : si le serveur WordPress réel est
 * injoignable (404/50x réseau), les données passent en localStorage —
 * prévisualisation locale des redirections, du pop-up, de la règle du
 * maximum et du nouveau design du récapitulatif. Une réponse HTTP réelle
 * désactive le mock correspondant ; les erreurs métier 4xx restent des
 * erreurs (jamais simulées).
 * ===================================================================== */
import { getMockOrders, postWithLocalFallback, saveMockReview } from "./assets/js/mock-api.js";

const config = window.ABAYA_MOCK || {};
const params = new URLSearchParams(location.search);
const orderId = Number(params.get("order"));
const orderKey = params.get("key");
const RATING_DONE_KEY = "abaya_rating_done";
const AUTO_CLOSE_DELAY = 30000; /* délai prolongé après le message de remerciement */
const SENT_CLOSE_DELAY = 2600;  /* fermeture après envoi du texte optionnel */

let overlay = document.getElementById("abaya-rating-overlay");
let closeTimer = null;

function escapeHtml(value) {
  const replacements = { "&": "&amp;", "<": "&lt;", ">": "&gt;", "\"": "&quot;", "'": "&#39;" };
  return String(value ?? "").replace(/[&<>"']/g, (char) => replacements[char]);
}

/* ---------- Pop-up : ouverture / fermeture ---------- */
function clearCloseTimer() {
  if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
}
function scheduleClose(delay) {
  clearCloseTimer();
  closeTimer = setTimeout(() => closeRatingPopup(), delay);
}
function openRatingPopup() {
  if (!overlay) return;
  if (sessionStorage.getItem(RATING_DONE_KEY) === "1") return;
  overlay.hidden = false;
}
function closeRatingPopup() {
  if (!overlay) return;
  overlay.hidden = true;
  clearCloseTimer();
  sessionStorage.setItem(RATING_DONE_KEY, "1");
}

/* ---------- v3.15 : système d'étoiles interactives FOURNI (app.js) ----------
 * Tracés SVG ARRONDIS générés en JS : 10 sommets (rayons 21 / 9.4), coins
 * adoucis par courbes quadratiques Q — l'arrondi naturel est DANS la
 * géométrie du tracé, plus aucun stroke déformant. Dégradé satiné or injecté
 * par bouton via <radialGradient> (liseré fin #927936 au repos côté CSS).
 * Le résidu d'accolade du fichier de démo fourni est volontairement écarté. */
function createRoundedStarPath() {
  const vertices = Array.from({ length: 10 }, (_, index) => {
    const angle = -Math.PI / 2 + index * Math.PI / 5;
    const radius = index % 2 === 0 ? 21 : 9.4;
    return {
      x: 24 + Math.cos(angle) * radius,
      y: 24 + Math.sin(angle) * radius,
      corner: index % 2 === 0 ? 2.2 : 1.1,
    };
  });
  const rounded = vertices.map((vertex, index) => {
    const previous = vertices[(index + vertices.length - 1) % vertices.length];
    const next = vertices[(index + 1) % vertices.length];
    const point = (neighbor) => {
      const distance = Math.hypot(neighbor.x - vertex.x, neighbor.y - vertex.y);
      const ratio = vertex.corner / distance;
      return {
        x: vertex.x + (neighbor.x - vertex.x) * ratio,
        y: vertex.y + (neighbor.y - vertex.y) * ratio,
      };
    };
    return { vertex, entry: point(previous), exit: point(next) };
  });

  return 'M ' + rounded[0].entry.x + ' ' + rounded[0].entry.y + ' ' + rounded.map(({ vertex, exit }, index) => {
    const nextEntry = rounded[(index + 1) % rounded.length].entry;
    return 'Q ' + vertex.x + ' ' + vertex.y + ' ' + exit.x + ' ' + exit.y + ' L ' + nextEntry.x + ' ' + nextEntry.y;
  }).join(' ') + ' Z';
}

const starSvg = (gradientId) => '<svg viewBox="0 0 48 48" aria-hidden="true" focusable="false"><defs><radialGradient id="' + gradientId + '" cx="50%" cy="42%" r="72%"><stop offset="0%" stop-color="#f5dfa7"/><stop offset="42%" stop-color="#dfbd72"/><stop offset="78%" stop-color="#c7973d"/><stop offset="100%" stop-color="#a87824"/></radialGradient></defs><path class="star-shape" style="--star-gradient: url(#' + gradientId + ')" d="' + createRoundedStarPath() + '"/></svg>';

/* ---------- Soumission REST (note + texte optionnel) ---------- */
async function submitRating(rating, ctx, comment = "") {
  const payload = {
    rating: Number(rating),
    reviewer_name: String(ctx.name || "عميلة").slice(0, 50),
    reviewer_city: String(ctx.city || "المغرب").slice(0, 50),
    comment: comment,
    offer: ctx.offer || "",
    /* Contexte commande (mandat) : alimente la règle du maximum et la
     * modération intelligente côté serveur (hash_equals sur la clé). */
    order_id: ctx.order_id ? Number(ctx.order_id) : 0,
    order_key: ctx.order_key || "",
  };
  const result = await postWithLocalFallback(
    config.reviewEndpoint || "/wp-json/landing/v1/submit-review",
    payload,
    (body) => {
      /* Repli mock : la règle du maximum + la modération sont répliquées
       * localement (saveMockReview renvoie la note conservée et le statut). */
      const saved = saveMockReview({ ...payload, offer_id: payload.offer });
      return {
        success: true,
        rating: saved.rating,
        moderation: saved.status !== "approved",
        message: saved.status === "approved"
          ? "تم حفظ تقييمكِ التجريبي محلياً."
          : "تم حفظ تقييمكِ التجريبي محلياً (في انتظار المراجعة).",
      };
    }
  );
  return result;
}

function showFeedback(box, message, isError) {
  if (!box) return;
  box.textContent = message;
  box.classList.toggle("is-error", Boolean(isError));
  box.hidden = false;
}

/* ---------- Flux complet — branché sur le pop-up natif OU injecté (mock) ---------- */
function bindRatingFlow(root, ctx) {
  if (!root || root.dataset.bound === "1") return;
  root.dataset.bound = "1";

  const box      = root.querySelector(".abaya-rating-stars");
  const modal    = root.querySelector(".abaya-rating-modal");
  const feedback = root.querySelector(".abaya-rating-feedback");
  const after    = root.querySelector(".abaya-rating-after");
  const note     = root.querySelector(".abaya-rating-note");
  const send     = root.querySelector(".abaya-rating-send");
  const closeBtn = root.querySelector(".abaya-rating-close");
  const stars    = box ? [...box.querySelectorAll(".abaya-rating-star")] : [];
  let selectedRating = 0;
  let hoveredRating = 0;

  /* v3.15 : les boutons du markup sont VIDES — injection des tracés SVG
   * arrondis + dégradé satiné fournis (IDs uniques par note). */
  for (const star of stars) star.innerHTML = starSvg("gold-fill-" + star.dataset.rating);

  /* Rendu FOURNI (renderRating) : visible = survolé || choisi — is-active sur
   * les étoiles 1..N. En RTL les étoiles DOM 1→5 se rendent de droite à
   * gauche : le remplissage suit le sens de lecture arabe (comportement
   * validé depuis v2.2, identique en logique fournie `index < visible`).
   * A11y : sémantie radiogroup v2.2 conservée — aria-checked posé sur
   * l'étoile choisie uniquement (équivalent exact de l'aria-pressed fourni). */
  function renderRating() {
    const visibleRating = hoveredRating || selectedRating;
    stars.forEach((star, index) => {
      const active = index < visibleRating;
      star.classList.toggle("is-active", active);
      star.setAttribute("aria-checked", String(Number(star.dataset.rating) === selectedRating));
    });
  }

  /* Événements FOURNIS (app.js) : pointerenter filtré souris/plume (le
   * tactile n'a pas de survol — l'allumage tactile passe par le clic),
   * focus/blur clavier, pointerleave du groupe. */
  for (const star of stars) {
    const rating = Number(star.dataset.rating);

    star.addEventListener("pointerenter", (event) => {
      if (event.pointerType === "mouse" || event.pointerType === "pen") {
        hoveredRating = rating;
        renderRating();
      }
    });
    star.addEventListener("focus", () => {
      hoveredRating = rating;
      renderRating();
    });
    star.addEventListener("blur", () => {
      hoveredRating = 0;
      renderRating();
    });
  }

  box?.addEventListener("pointerleave", (event) => {
    if (event.pointerType === "mouse" || event.pointerType === "pen") {
      hoveredRating = 0;
      renderRating();
    }
  });

  /* 1-clic : enregistrement DIRECT (flux tunnel inchangé) + rendu fourni. */
  box?.addEventListener("click", async (event) => {
    const star = event.target.closest(".abaya-rating-star");
    if (!star || box.dataset.busy === "1") return;
    box.dataset.busy = "1";
    try {
      const result = await submitRating(Number(star.dataset.rating), ctx);
      if (!result.ok) throw new Error(result.body?.message || "تعذر تسجيل التقييم الآن.");
      /* Règle du maximum : le serveur renvoie la note conservée — le rendu
       * s'aligne sur max(note_existante, nouvelle_note). */
      selectedRating = Math.max(selectedRating, Number(result.body?.rating) || Number(star.dataset.rating));
      hoveredRating = 0;
      renderRating();
      /* Persistance (mandat) : le pop-up reste ouvert — message de
       * remerciement + zone de texte optionnelle, apparition douce. */
      if (after) {
        after.hidden = false;
        modal?.classList.add("is-rated");
        note?.focus();
      }
      feedback.hidden = true;
      scheduleClose(AUTO_CLOSE_DELAY); /* délai prolongé, cf. mandat */
    } catch (error) {
      showFeedback(feedback, error.message || "حدث خطأ، يرجى المحاولة مرة أخرى.", true);
    } finally {
      delete box.dataset.busy;
    }
  });

  /* Bouton valider : envoi du texte optionnel → modération (approved = 0).
   * Sans texte, « valider » ferme gracieusement le pop-up. */
  send?.addEventListener("click", async () => {
    if (send.dataset.busy === "1") return;
    const text = (note?.value || "").trim();
    if (text === "") { closeRatingPopup(); return; }
    if (text.length < 3) { showFeedback(feedback, "يرجى كتابة كلمة من 3 أحرف على الأقل.", true); return; }
    send.dataset.busy = "1";
    try {
      const result = await submitRating(selectedRating, ctx, text);
      if (!result.ok) throw new Error(result.body?.message || "تعذر إرسال ملاحظتك الآن.");
      const afterMsg = after?.querySelector(".abaya-rating-after-msg");
      if (afterMsg) afterMsg.textContent = "شكراً لكِ! سيُنشر رأيك بعد المراجعة 🌿";
      if (note) note.hidden = true;
      send.hidden = true;
      feedback.hidden = true;
      scheduleClose(SENT_CLOSE_DELAY);
    } catch (error) {
      showFeedback(feedback, error.message || "حدث خطأ، يرجى المحاولة مرة أخرى.", true);
    } finally {
      delete send.dataset.busy;
    }
  });

  /* Le délai prolongé est réinitialisé à chaque saisie (utilisatrice active). */
  modal?.addEventListener("input", () => scheduleClose(AUTO_CLOSE_DELAY));

  /* Fermeture : croix (X), clic sur le voile, touche Échap (listener global). */
  closeBtn?.addEventListener("click", closeRatingPopup);
  root.addEventListener("click", (event) => {
    if (event.target === root) closeRatingPopup();
  });
}

/* ---------- Pop-up natif (rendu par page-merci.php, commande valide) ---------- */
if (overlay) {
  bindRatingFlow(overlay, config.ratingCtx || {});
  /* Ouverture à l'arrivée sur /merci/ (commande active uniquement). */
  if (config.ratingCtx) openRatingPopup();
}

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape" && overlay && !overlay.hidden) closeRatingPopup();
});

/* ---------- Mode simulation (mock) : prévisualisation locale ---------- */
const mockOrder = getMockOrders().find((item) => item.order_id === orderId && item.order_key === orderKey);
const mockContainer = document.getElementById("mock-thanks-container");

if (mockOrder && mockContainer) {
  document.querySelector("[data-generic-thanks]")?.setAttribute("hidden", "");
  mockContainer.hidden = false;

  const fullName = mockOrder.customer.full_name || "";
  const selection = mockOrder.selection;
  const quantity = Number(selection.quantity) || 1;
  const mockOffer = (config.offers || []).find((offer) => offer.id === mockOrder.offer_id);
  const itemImg = mockOffer?.img || ((config.assetBase || "") + "/" + encodeURIComponent(mockOrder.offer_id) + "/beige.webp");
  const offerName = selection.offer || (quantity === 1 ? "قطعة واحدة" : quantity + " قطع");
  const address = [mockOrder.customer.address, mockOrder.customer.city].filter(Boolean).join("، ") || "—";
  const promoCode = [fullName.split(/\s+/)[0] || "CLIENTE", mockOrder.customer.city || "MAROC", "#" + mockOrder.order_id].filter(Boolean).join("-");

  /* Tunnel post-achat (mandat ⑭) : bandeau de confirmation au retour d'un
   * ajout upsell réussi (?added=1) — miroir du rendu natif. */
  const addedBanner = new URLSearchParams(location.search).get("added")
    ? '<div class="merci-added" role="status"><span aria-hidden="true">✓</span> تمت إضافة المنتج إلى طلبكِ بنجاح — المجموع المحدَّث أدناه</div>'
    : "";

  /* Récapitulatif en mode mock : miroir EXACT du design « maquette Canva 4PLP »
   * (carte blanche + logo شكراً, pastille n° commande, tableau 4 colonnes avec
   * cellule image, bloc cadeau turquoise, WhatsApp officiel, upsell 3:5).
   * v3.10 : dh() alignée sur la closure PHP $abaya_dh de page-merci.php —
   * montant PUIS درهم, espace insecable U+00A0 (fin du préfixe latin « DH »
   * jamais corrigé en v3.8 côté JS) ; les bdi de prix passent en dir="rtl"
   * (parité LP : درهم affiché à GAUCHE du montant). */
  const dh = (n) => String(n) + "\u00A0درهم";
  const logoSrc = config.logo || "";
  /* Mandat ⑭ : les offres déjà ajoutées à la commande (upsell mock) disparaissent
   * des cartes — chaque lien porte le duo order+key (déverrouillage de la
   * landing cible, vedette = racine). */
  const purchasedOffers = [mockOrder.offer_id].concat(mockOrder.upsell_offers || []);
  const upsellQuery = orderId && orderKey
    ? "?order=" + encodeURIComponent(String(orderId)) + "&key=" + encodeURIComponent(String(orderKey))
    : "";
  const otherOffers = (config.offers || [])
    .filter((offer) => !purchasedOffers.includes(offer.id))
    .map((offer) =>
      '<a class="merci-upsell-item" href="' + escapeHtml((offer.url || ("/" + encodeURIComponent(offer.id) + "/")).split("#")[0] + upsellQuery + "#order-form-section") + '">' +
      '<span class="merci-upsell-frame"><img src="' + escapeHtml(offer.img || "") + '" alt="' + escapeHtml(offer.headline) + '" loading="lazy" /></span>' +
      '<strong class="merci-upsell-name">' + escapeHtml(offer.headline) + '</strong>' +
      '<span class="merci-upsell-price"><bdi dir="rtl">' + escapeHtml(dh(offer.price || 299)) + '</bdi></span>' +
      '<span class="merci-upsell-btn">إضافة للطلب</span></a>'
    ).join("");

  mockContainer.innerHTML =
    '<header class="merci-head" aria-labelledby="mock-thanks-title">' +
      /* v3.12 : attributs = ratio naturel du visuel haute résolution (500×337,
       * anti-CLS) — la taille d'affichage (240px desktop / 170px mobile,
       * height auto) est pilotée par CSS dans le bloc abaya-merci. */
      (logoSrc ? '<img class="merci-logo" src="' + escapeHtml(logoSrc) + '" alt="شكراً" width="240" height="162" />' : "") +
      '<h1 id="mock-thanks-title">تم استلام طلبكِ بنجاح! يسعدنا انضمامكِ إلينا</h1>' +
      '<p class="merci-lead">طلب تجريبي محفوظ محلياً على هذا المتصفح فقط؛ لم يُرسل طلب إلى المتجر.</p>' +
      '<p class="merci-order-pill"><span>رقم الطلب:</span> <strong dir="ltr">#' + escapeHtml(String(mockOrder.order_id)) + '</strong></p>' +
    '</header>' + addedBanner +
    '<section class="merci-sec" aria-label="تفاصيل الطلب">' +
      '<h2 class="merci-sec-title">تفاصيل الطلب</h2>' +
      '<div class="merci-order"><div class="merci-table"><table><thead><tr><th scope="col">المنتج</th><th scope="col">اللون / المقاس</th><th scope="col">الكمية</th><th scope="col">السعر</th></tr></thead><tbody>' +
        '<tr><td><p class="merci-item-name">' + escapeHtml(offerName) + '</p></td>' +
        '<td class="merci-qty">' + escapeHtml(selection.color || "—") + ' / ' + escapeHtml(selection.size || "—") + '</td>' +
        '<td class="merci-qty">' + escapeHtml(String(quantity)) + '</td>' +
        '<td class="merci-price"><bdi dir="rtl">' + escapeHtml(dh(mockOrder.value)) + '</bdi></td></tr>' +
        '<tr class="merci-total-row"><td colspan="3">المجموع الإجمالي:</td>' +
        '<td class="merci-price"><bdi dir="rtl">' + escapeHtml(dh(mockOrder.value)) + '</bdi></td></tr>' +
      '</tbody></table></div>' +
      '<figure class="merci-product-img"><img src="' + escapeHtml(itemImg) + '" alt="العباية" loading="lazy" /></figure></div>' +
      '<aside class="merci-gift" aria-labelledby="mock-gift-title">' +
        '<div class="merci-gift-body">' +
          '<p class="merci-gift-title" id="mock-gift-title">🎁 هدية خاصة لطلبكِ القادم!</p>' +
          '<p class="merci-gift-desc">استلمي كود الخصم المخصص لكِ للحصول على <strong>خصم 20 درهم</strong> لكل عباية في طلبكِ القادم — لكِ أو لقريباتكِ.</p>' +
        '</div>' +
        '<div class="merci-gift-code"><small>كود الخصم الخاص بكِ</small><code dir="ltr">' + escapeHtml(promoCode) + '</code></div>' +
        '<div class="merci-gift-icon" aria-hidden="true">🎁</div>' +
      '</aside>' +
    '</section>' +
    '<section class="merci-sec" aria-label="معلومات المراسلة">' +
      '<h2 class="merci-sec-title">معلومات المراسلة</h2>' +
      '<div class="merci-contact"><dl>' +
        '<div><dt>الاسم:</dt><dd>' + escapeHtml(fullName || "—") + '</dd></div>' +
        '<div><dt>الرقم الهـ:</dt><dd dir="ltr">' + escapeHtml(mockOrder.customer.phone || "—") + '</dd></div>' +
        '<div><dt>العنوان:</dt><dd>' + escapeHtml(address) + '</dd></div>' +
      '</dl>' +
      '<a class="merci-wa-btn" href="https://wa.me/212698738664" target="_blank" rel="noopener">' +
        '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>' +
        '<span>تواصل معنا أو تابع طلبك عبر الواتساب</span></a></div>' +
    '</section>' +
    '<section class="merci-upsell" aria-labelledby="mock-upsell-title">' +
      '<h2 class="merci-upsell-title" id="mock-upsell-title">تشكيلة أخرى قد تعجبكِ أيضاً</h2>' +
      '<p class="merci-upsell-sub">أضيفي عباية أخرى لنستفيدي من الخصم المباشر في طلبكِ</p>' +
      '<div class="merci-upsell-grid">' + otherOffers + '</div></section>' +
    '<footer class="merci-foot">© ' + new Date().getFullYear() + ' Abaya Collection — جميع الحقوق محفوظة</footer>';

  /* Pop-up 1-clic en mode simulation : injection dynamique si absent
   * (page générique), même markup + même flux que le pop-up natif. */
  if (!overlay) {
    const popup = document.createElement("div");
    popup.className = "abaya-rating-overlay";
    popup.id = "abaya-rating-overlay";
    /* v3.15 : même gabarit que le pop-up natif — bande turquoise, boutons
     * VIDES (les tracés fournis sont injectés par bindRatingFlow), sans
     * étoile centrale du cercle. */
    popup.innerHTML =
      '<div class="abaya-rating-modal" role="dialog" aria-modal="true" aria-labelledby="abaya-rating-title">' +
        '<div class="abaya-rating-band" aria-hidden="true"></div>' +
        '<button class="abaya-rating-close" type="button" aria-label="إغلاق">×</button>' +
        '<h2 id="abaya-rating-title">يسعدنا كثيراً خدمتك!</h2>' +
        '<p class="abaya-rating-text">إذا أعجبتك تجربتك معنا، نرجو منك اختيار تقييمك بنقرة واحدة لمساعدتنا على تحسين خدمتنا.</p>' +
        '<div class="abaya-rating-stars" role="radiogroup" aria-label="اختياري التقييم بنقرة واحدة">' +
          [1, 2, 3, 4, 5].map((n) =>
            '<button class="abaya-rating-star" type="button" role="radio" aria-checked="false" data-rating="' + n + '" aria-label="' + n + ' من 5"></button>'
          ).join("") +
        '</div>' +
        '<p class="abaya-rating-feedback" role="status" hidden></p>' +
        '<div class="abaya-rating-after" hidden>' +
          '<p class="abaya-rating-after-msg">شكراً جزيلًا على تقييمك! 🌿 هل ترغبين في إضافة كلمة بسيطة عن تجربتك؟</p>' +
          '<textarea class="abaya-rating-note" rows="3" maxlength="2000" placeholder="اكتبي كلمة بسيطة عن تجربتكِ (اختياري)"></textarea>' +
          '<button class="abaya-rating-send" type="button">إرسال</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(popup);
    overlay = popup;
    /* Contexte mock : coordonnées de la commande simulée (order_id/order_key
     * inclus — la règle du maximum est répliquée dans saveMockReview). */
    const mockCtx = {
      offer: mockOrder.offer_id,
      name: fullName || "عميلة",
      city: mockOrder.customer.city || "المغرب",
      order_id: mockOrder.order_id,
      order_key: mockOrder.order_key,
    };
    bindRatingFlow(popup, mockCtx);
    if (!sessionStorage.getItem(RATING_DONE_KEY)) popup.hidden = false;
  }
}
