/* =====================================================================
 * Page /merci/ — Pop-up d'évaluation « 1-clic » (mandat multi-landing)
 * ---------------------------------------------------------------------
 * Un clic sur une des 5 étoiles enregistre DIRECTEMENT la note en base de
 * données (route REST landing/v1/submit-review) liée aux coordonnées de la
 * commande active (prénom + ville), affiche la confirmation puis ferme le
 * pop-up. Le bouton X ferme discrètement (non réaffiché dans la session).
 *
 * Mode simulation (mock-api.js) : si le serveur WordPress réel est
 * injoignable (404/50x réseau), les données passent en localStorage —
 * prévisualisation locale des redirections, du pop-up et du recalcul des
 * étoiles. Une réponse HTTP réelle désactive le mock correspondant ;
 * les erreurs métier 4xx restent des erreurs (jamais simulées).
 * ===================================================================== */
import { getMockOrders, postWithLocalFallback, saveMockReview } from "./assets/js/mock-api.js";

const config = window.ABAYA_MOCK || {};
const params = new URLSearchParams(location.search);
const orderId = Number(params.get("order"));
const orderKey = params.get("key");
const RATING_DONE_KEY = "abaya_rating_done";

const overlay = document.getElementById("abaya-rating-overlay");
const starsBox = document.getElementById("abaya-rating-stars");
const feedback = document.getElementById("abaya-rating-feedback");
const closeBtn = document.getElementById("abaya-rating-close");
const mockContainer = document.getElementById("mock-thanks-container");

function escapeHtml(value) {
  const replacements = { "&": "&amp;", "<": "&lt;", ">": "&gt;", "\"": "&quot;", "'": "&#39;" };
  return String(value ?? "").replace(/[&<>"']/g, (char) => replacements[char]);
}

/* ---------- Pop-up : ouverture / fermeture ---------- */
function openRatingPopup() {
  if (!overlay) return;
  if (sessionStorage.getItem(RATING_DONE_KEY) === "1") return;
  overlay.hidden = false;
}
function closeRatingPopup() {
  if (!overlay) return;
  overlay.hidden = true;
  sessionStorage.setItem(RATING_DONE_KEY, "1");
}

/* ---------- Soumission 1-clic ---------- */
async function submitOneClickRating(rating, ctx) {
  const payload = {
    rating: Number(rating),
    reviewer_name: String(ctx.name || "عميلة").slice(0, 50),
    reviewer_city: String(ctx.city || "المغرب").slice(0, 50),
    comment: "تقييم بنقرة واحدة",
    offer: ctx.offer || "",
  };
  const result = await postWithLocalFallback(
    config.reviewEndpoint || "/wp-json/landing/v1/submit-review",
    payload,
    (body) => {
      saveMockReview({ ...payload, offer_id: payload.offer });
      return { success: true, message: "تم حفظ تقييمكِ التجريبي محلياً." };
    }
  );
  return result;
}

function showFeedback(message, isError) {
  if (!feedback) return;
  feedback.textContent = message;
  feedback.classList.toggle("is-error", Boolean(isError));
  feedback.hidden = false;
}

if (starsBox) {
  starsBox.addEventListener("click", async (event) => {
    const star = event.target.closest(".abaya-rating-star");
    if (!star || starsBox.dataset.busy === "1") return;
    starsBox.dataset.busy = "1";
    starsBox.querySelectorAll(".abaya-rating-star").forEach((el) => el.classList.remove("is-picked"));
    star.classList.add("is-picked");
    star.setAttribute("aria-checked", "true");
    const ctx = config.ratingCtx || {};
    try {
      const result = await submitOneClickRating(star.dataset.rating, ctx);
      if (!result.ok) throw new Error(result.body?.message || "تعذر تسجيل التقييم الآن.");
      /* Confirmation rapide puis fermeture automatique du pop-up. */
      showFeedback("شكراً لك! تم تسجيل تقييمك بنجاح", false);
      setTimeout(closeRatingPopup, 1800);
    } catch (error) {
      showFeedback(error.message || "حدث خطأ، يرجى المحاولة مرة أخرى.", true);
      star.classList.remove("is-picked");
      star.setAttribute("aria-checked", "false");
    } finally {
      delete starsBox.dataset.busy;
    }
  });
}
closeBtn?.addEventListener("click", closeRatingPopup);
overlay?.addEventListener("click", (event) => {
  if (event.target === overlay) closeRatingPopup(); // clic sur le voile = fermeture discrète
});
document.addEventListener("keydown", (event) => {
  if (event.key === "Escape" && overlay && !overlay.hidden) closeRatingPopup();
});

/* Ouverture à l'arrivée sur /merci/ (commande active uniquement). */
if (config.ratingCtx) openRatingPopup();

/* ---------- Mode simulation (mock) : prévisualisation locale ---------- */
const mockOrder = getMockOrders().find((item) => item.order_id === orderId && item.order_key === orderKey);

if (mockOrder && mockContainer) {
  document.querySelector("[data-generic-thanks]")?.setAttribute("hidden", "");
  mockContainer.hidden = false;
  const fullName = mockOrder.customer.full_name || "";
  const firstName = fullName.split(/\s+/)[0];
  const selection = mockOrder.selection;
  const offerName = selection.offer || (Number(selection.quantity) === 1 ? "قطعة واحدة" : selection.quantity + " قطع");
  const otherOffers = (config.offers || []).filter((offer) => offer.id !== mockOrder.offer_id).map((offer) =>
    '<a class="merci-cross-sell-card" href="' + escapeHtml(offer.url || ("/" + encodeURIComponent(offer.id) + "/")) + '">' +
    '<img src="' + escapeHtml(offer.img || ((config.assetBase || "") + "/" + encodeURIComponent(offer.id) + "/beige.webp")) + '" alt="' + escapeHtml(offer.headline) + '" width="86" height="96" />' +
    '<div class="merci-cross-sell-copy"><strong>' + escapeHtml(offer.headline) + '</strong><span>ابتداءً من ' + escapeHtml(offer.price || 299) + ' درهم · اكتشفي العرض</span></div></a>'
  ).join("");
  mockContainer.innerHTML =
    '<section class="merci-card" aria-labelledby="mock-thanks-title">' +
      '<div class="merci-check" aria-hidden="true">✓</div>' +
      '<h1 id="mock-thanks-title">شكراً لكِ ' + escapeHtml(firstName) + '! تم استلام طلبكِ بنجاح</h1>' +
      '<p class="merci-lead">طلب تجريبي محفوظ محلياً على هذا المتصفح فقط؛ لم يُرسل طلب إلى المتجر.</p>' +
      '<dl class="merci-details">' +
        '<div><dt>اللون</dt><dd>' + escapeHtml(selection.color || "—") + '</dd></div>' +
        '<div><dt>المقاس</dt><dd>' + escapeHtml(selection.size || "—") + '</dd></div>' +
        '<div><dt>العرض</dt><dd>' + escapeHtml(offerName) + '</dd></div>' +
        '<div><dt>SKU</dt><dd dir="ltr">' + escapeHtml(mockOrder.sku || "—") + '</dd></div>' +
        '<div><dt>المجموع</dt><dd>' + escapeHtml(String(mockOrder.value)) + ' درهم</dd></div>' +
        '<div><dt>الهاتف</dt><dd dir="ltr">' + escapeHtml(mockOrder.customer.phone || "—") + '</dd></div>' +
        '<div><dt>العنوان</dt><dd>' + escapeHtml([mockOrder.customer.address, mockOrder.customer.city].filter(Boolean).join("، ") || "—") + '</dd></div>' +
      '</dl>' +
      '<div class="merci-promo"><p class="merci-promo-eyebrow">كود خصم خاص بكِ للطلب القادم</p>' +
      '<code class="merci-promo-code" dir="ltr">' + escapeHtml([mockOrder.customer.full_name.split(/\s+/)[0] || "CLIENTE", mockOrder.customer.city || "MAROC", "#" + mockOrder.order_id].filter(Boolean).join("-")) + '</code></div>' +
      '<p class="merci-ref">رقم الطلب التجريبي: <strong>#' + escapeHtml(String(mockOrder.order_id)) + '</strong></p>' +
    '</section>' +
    '<section class="merci-cross-sell" aria-labelledby="mock-cross-sell-title"><h2 id="mock-cross-sell-title">اكتشفي عروضاً أخرى من تشكيلتنا</h2>' +
      '<div class="merci-cross-sell-grid">' + otherOffers + '</div></section>';

  /* Pop-up 1-clic en mode simulation : injection dynamique si absent. */
  if (!overlay && !sessionStorage.getItem(RATING_DONE_KEY)) {
    const popup = document.createElement("div");
    popup.className = "abaya-rating-overlay";
    popup.id = "abaya-rating-overlay";
    popup.innerHTML =
      '<div class="abaya-rating-modal" role="dialog" aria-modal="true" aria-labelledby="abaya-rating-title">' +
        '<button class="abaya-rating-close" type="button" id="abaya-rating-close" aria-label="إغلاق">×</button>' +
        '<div class="abaya-rating-emoji" aria-hidden="true">★</div>' +
        '<h2 id="abaya-rating-title">يسعدنا كثيراً خدمتك!</h2>' +
        '<p class="abaya-rating-text">إذا أعجبتك تجربتك معنا، نرجو منك اختيار تقييمك بنقرة واحدة لمساعدتنا على تحسين خدمتنا.</p>' +
        '<div class="abaya-rating-stars" id="abaya-rating-stars" role="radiogroup" aria-label="اختياري التقييم بنقرة واحدة">' +
          [1, 2, 3, 4, 5].map((n) =>
            '<button class="abaya-rating-star" type="button" role="radio" aria-checked="false" data-rating="' + n + '" aria-label="' + n + ' من 5">' +
            '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2.5 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3.1-5.8 3.1 1.1-6.5-4.7-4.6 6.5-.9z"/></svg></button>'
          ).join("") +
        '</div>' +
        '<p class="abaya-rating-feedback" id="abaya-rating-feedback" role="status" hidden></p>' +
      '</div>';
    document.body.appendChild(popup);
    /* Contexte mock : coordonnées de la commande simulée. */
    config.ratingCtx = { offer: mockOrder.offer_id, name: mockOrder.customer.full_name || "عميلة", city: mockOrder.customer.city || "المغرب" };
    /* Réattache la logique sur le pop-up injecté. */
    popup.addEventListener("click", async (event) => {
      const star = event.target.closest(".abaya-rating-star");
      if (star) {
        popup.querySelectorAll(".abaya-rating-star").forEach((el) => el.classList.remove("is-picked"));
        star.classList.add("is-picked");
        const result = await submitOneClickRating(star.dataset.rating, config.ratingCtx);
        const fb = popup.querySelector(".abaya-rating-feedback");
        if (fb) {
          fb.hidden = false;
          fb.classList.toggle("is-error", !result.ok);
          fb.textContent = result.ok ? "شكراً لك! تم تسجيل تقييمك بنجاح" : (result.body?.message || "حدث خطأ، يرجى المحاولة مرة أخرى.");
        }
        if (result.ok) setTimeout(() => { popup.hidden = true; sessionStorage.setItem(RATING_DONE_KEY, "1"); }, 1800);
      } else if (event.target === popup || event.target.id === "abaya-rating-close") {
        popup.hidden = true;
        sessionStorage.setItem(RATING_DONE_KEY, "1");
      }
    });
    popup.hidden = false;
  }
}
