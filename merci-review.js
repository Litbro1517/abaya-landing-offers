/* =====================================================================
 * Page /merci/ — Pop-up d'évaluation « 1-clic » perfectionné
 * (mandat 4P LP « Thank You Page Redesign »)
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

/* ---------- Remplissage progressif : étoiles 1..N allumées (sens RTL) ---------- */
/* En RTL les étoiles DOM 1→5 se rendent de droite à gauche : allumer les
 * étoiles de rang <= N allume les N premières — le remplissage suit le sens
 * de lecture arabe. Le même mécanisme sert l'état « survolé » (is-hot) et
 * l'état « enregistré » (is-picked). */
function paintStars(box, upTo, cls) {
  box.querySelectorAll(".abaya-rating-star").forEach((el) => {
    const active = Number(el.dataset.rating) <= upTo;
    el.classList.toggle(cls, active);
    if (cls === "is-picked") el.setAttribute("aria-checked", active ? "true" : "false");
  });
}

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
  let currentRating = 0;

  /* Remplissage progressif : survol ET focus clavier (sens RTL). */
  const hotTo = (star) => paintStars(box, star ? Number(star.dataset.rating) : currentRating, "is-hot");
  box?.addEventListener("mouseover", (event) => hotTo(event.target.closest(".abaya-rating-star")));
  box?.addEventListener("mouseleave", () => hotTo(null));
  box?.addEventListener("focusin", (event) => hotTo(event.target.closest(".abaya-rating-star")));
  box?.addEventListener("focusout", () => hotTo(null));

  /* 1-clic : enregistrement direct puis PERSISTANCE (message + texte optionnel). */
  box?.addEventListener("click", async (event) => {
    const star = event.target.closest(".abaya-rating-star");
    if (!star || box.dataset.busy === "1") return;
    box.dataset.busy = "1";
    try {
      const result = await submitRating(Number(star.dataset.rating), ctx);
      if (!result.ok) throw new Error(result.body?.message || "تعذر تسجيل التقييم الآن.");
      /* Règle du maximum : le serveur renvoie la note conservée — l'affichage
       * s'aligne sur max(note_existante, nouvelle_note). */
      currentRating = Math.max(currentRating, Number(result.body?.rating) || Number(star.dataset.rating));
      paintStars(box, currentRating, "is-picked");
      paintStars(box, currentRating, "is-hot");
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
      const result = await submitRating(currentRating, ctx, text);
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
  const firstName = fullName.split(/\s+/)[0];
  const selection = mockOrder.selection;
  const quantity = Number(selection.quantity) || 1;
  const mockOffer = (config.offers || []).find((offer) => offer.id === mockOrder.offer_id);
  const itemImg = mockOffer?.img || ((config.assetBase || "") + "/" + encodeURIComponent(mockOrder.offer_id) + "/beige.webp");
  const offerName = selection.offer || (quantity === 1 ? "قطعة واحدة" : quantity + " قطع");
  const address = [mockOrder.customer.address, mockOrder.customer.city].filter(Boolean).join("، ") || "—";
  const promoCode = [fullName.split(/\s+/)[0] || "CLIENTE", mockOrder.customer.city || "MAROC", "#" + mockOrder.order_id].filter(Boolean).join("-");

  /* Récapitulatif en mode mock : miroir EXACT du nouveau design (en-tête épuré,
   * tableau 2 colonnes, grille client compacte, promo fin, upsell sans cadres). */
  const otherOffers = (config.offers || [])
    .filter((offer) => offer.id !== mockOrder.offer_id)
    .map((offer) =>
      '<a class="merci-upsell-item" href="' + escapeHtml(offer.url || ("/" + encodeURIComponent(offer.id) + "/")) + '">' +
      '<img src="' + escapeHtml(offer.img || "") + '" alt="' + escapeHtml(offer.headline) + '" width="240" height="320" loading="lazy" />' +
      '<strong class="merci-upsell-name">' + escapeHtml(offer.headline) + '</strong>' +
      '<span class="merci-upsell-price">ابتداءً من ' + escapeHtml(offer.price || 299) + ' درهم</span>' +
      '<span class="merci-upsell-btn">اطلبي الآن</span></a>'
    ).join("");

  mockContainer.innerHTML =
    '<header class="merci-head" aria-labelledby="mock-thanks-title">' +
      '<span class="merci-check" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4.5 12.5l5 5 10-11"/></svg></span>' +
      '<h1 id="mock-thanks-title">شكراً لكِ ' + escapeHtml(firstName) + '! تم استلام طلبكِ بنجاح</h1>' +
      '<p class="merci-lead">طلب تجريبي محفوظ محلياً على هذا المتصفح فقط؛ لم يُرسل طلب إلى المتجر.</p>' +
      '<p class="merci-ref">رقم الطلب التجريبي: <strong>#' + escapeHtml(String(mockOrder.order_id)) + '</strong></p>' +
    '</header>' +
    '<section class="merci-summary" aria-label="ملخص الطلب"><table><thead><tr><th scope="col">المنتج</th><th scope="col">السعر</th></tr></thead><tbody>' +
      '<tr><td><div class="merci-item">' +
        '<img src="' + escapeHtml(itemImg) + '" alt="العباية" width="58" height="72" loading="lazy" />' +
        '<div><p class="merci-item-name">' + escapeHtml(offerName) + ' × ' + escapeHtml(String(quantity)) + '</p>' +
        '<p class="merci-item-meta">اللون: ' + escapeHtml(selection.color || "—") + ' · المقاس: ' + escapeHtml(selection.size || "—") + '</p></div>' +
      '</div></td><td class="merci-price">' + escapeHtml(String(mockOrder.value)) + ' درهم</td></tr>' +
      '<tr class="merci-total-row"><td>المجموع</td><td class="merci-price">' + escapeHtml(String(mockOrder.value)) + ' درهم</td></tr>' +
      '<tr class="merci-pay-row"><td>طريقة الدفع</td><td class="merci-price" style="font-weight:400;">الدفع عند الاستلام</td></tr>' +
    '</tbody></table></section>' +
    '<dl class="merci-client">' +
      '<div><dt>الهاتف</dt><dd dir="ltr">' + escapeHtml(mockOrder.customer.phone || "—") + '</dd></div>' +
      '<div><dt>المدينة</dt><dd>' + escapeHtml(mockOrder.customer.city || "—") + '</dd></div>' +
      '<div class="merci-wide"><dt>العنوان</dt><dd>' + escapeHtml(address) + '</dd></div>' +
      '<div><dt>الحالة</dt><dd>طلب تجريبي</dd></div>' +
    '</dl>' +
    '<div class="merci-promo"><p class="merci-promo-eyebrow">كود خصم خاص بكِ للطلب القادم</p>' +
      '<code class="merci-promo-code" dir="ltr">' + escapeHtml(promoCode) + '</code></div>' +
    '<section class="merci-upsell" aria-labelledby="mock-upsell-title">' +
      '<h2 id="mock-upsell-title">اكتشفي عروضاً أخرى قد تناسبك</h2>' +
      '<div class="merci-upsell-grid">' + otherOffers + '</div></section>';

  /* Pop-up 1-clic en mode simulation : injection dynamique si absent
   * (page générique), même markup + même flux que le pop-up natif. */
  if (!overlay) {
    const popup = document.createElement("div");
    popup.className = "abaya-rating-overlay";
    popup.id = "abaya-rating-overlay";
    popup.innerHTML =
      '<div class="abaya-rating-modal" role="dialog" aria-modal="true" aria-labelledby="abaya-rating-title">' +
        '<button class="abaya-rating-close" type="button" aria-label="إغلاق">×</button>' +
        '<div class="abaya-rating-emoji" aria-hidden="true">★</div>' +
        '<h2 id="abaya-rating-title">يسعدنا كثيراً خدمتك!</h2>' +
        '<p class="abaya-rating-text">إذا أعجبتك تجربتك معنا، نرجو منك اختيار تقييمك بنقرة واحدة لمساعدتنا على تحسين خدمتنا.</p>' +
        '<div class="abaya-rating-stars" role="radiogroup" aria-label="اختياري التقييم بنقرة واحدة">' +
          [1, 2, 3, 4, 5].map((n) =>
            '<button class="abaya-rating-star" type="button" role="radio" aria-checked="false" data-rating="' + n + '" aria-label="' + n + ' من 5">' +
            '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2.5 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3.1-5.8 3.1 1.1-6.5-4.7-4.6 6.5-.9z"/></svg></button>'
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
