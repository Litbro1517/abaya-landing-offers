import { getMockOrders, postWithLocalFallback, saveMockReview } from "/mock-api.js";

const params = new URLSearchParams(location.search);
const orderId = Number(params.get("order"));
const orderKey = params.get("key");
const config = window.ABAYA_MOCK || {};
const mockOrder = getMockOrders().find((item) => item.order_id === orderId && item.order_key === orderKey);
const container = document.querySelector("#mock-thanks-container");

function escapeHtml(value) {
  const replacements = { "&": "&amp;", "<": "&lt;", ">": "&gt;", "\"": "&quot;", "'": "&#39;" };
  return String(value ?? "").replace(/[&<>"']/g, (char) => replacements[char]);
}

if (mockOrder && container) {
  document.querySelector("[data-generic-thanks]")?.setAttribute("hidden", "");
  container.hidden = false;
  const fullName = mockOrder.customer.full_name || "";
  const firstName = fullName.split(/\s+/)[0];
  const selection = mockOrder.selection;
  const offerName = selection.offer || (Number(selection.quantity) === 1 ? "قطعة واحدة" : selection.quantity + " قطع");
  const otherOffers = (config.offers || []).filter((offer) => offer.id !== mockOrder.offer_id).map((offer) =>
    '<a class="merci-cross-sell-card" href="' + escapeHtml(offer.url || ("/" + encodeURIComponent(offer.id) + "/")) + '">' +
    '<img src="' + escapeHtml(config.assetBase) + '/' + encodeURIComponent(offer.id) + '/beige.webp" alt="' + escapeHtml(offer.headline) + '" width="86" height="96" />' +
    '<span><strong>' + escapeHtml(offer.headline) + '</strong><b>ابتداءً من 299 درهم · اكتشفي العرض</b></span></a>'
  ).join("");
  container.innerHTML =
    '<section class="merci-card" aria-labelledby="mock-thanks-title">' +
      '<div class="merci-check" aria-hidden="true">✓</div>' +
      '<h1 id="mock-thanks-title">شكراً لكِ ' + escapeHtml(firstName) + '! تم استلام طلبكِ بنجاح</h1>' +
      '<p class="merci-lead">طلب تجريبي محفوظ محلياً على هذا المتصفح فقط؛ لم يُرسل طلب إلى المتجر.</p>' +
      '<dl class="merci-details">' +
        '<div><dt>اللون</dt><dd>' + escapeHtml(selection.color || "—") + '</dd></div>' +
        '<div><dt>المقاس</dt><dd>' + escapeHtml(selection.size || "—") + '</dd></div>' +
        '<div><dt>العرض</dt><dd>' + escapeHtml(offerName) + '</dd></div>' +
        '<div><dt>SKU</dt><dd dir="ltr">' + escapeHtml(mockOrder.sku || '—') + '</dd></div>' +
        '<div><dt>المجموع</dt><dd>' + escapeHtml(mockOrder.value) + ' درهم</dd></div>' +
        '<div><dt>الهاتف</dt><dd dir="ltr">' + escapeHtml(mockOrder.customer.phone || "—") + '</dd></div>' +
        '<div><dt>العنوان</dt><dd>' + escapeHtml([mockOrder.customer.address, mockOrder.customer.city].filter(Boolean).join('، ') || "—") + '</dd></div>' +
      '</dl><p class="merci-ref">رقم الطلب التجريبي: <strong>#' + escapeHtml(mockOrder.order_id) + '</strong></p>' +
    '</section>' +
    '<section class="merci-review" aria-labelledby="mock-review-title">' +
      '<h2 id="mock-review-title">رأيكِ يهمنا</h2><p>يسعدنا أن تخبرينا عن تجربتكِ. تقييمكِ وملاحظاتكِ تساعدنا على تحسين خدمتنا.</p>' +
      '<form id="mock-review-form" data-offer-id="' + escapeHtml(mockOrder.offer_id) + '">' +
        '<div class="merci-review-grid">' +
          '<label>الاسم<input name="reviewer_name" required minlength="2" maxlength="50" value="' + escapeHtml(fullName) + '" /></label>' +
          '<label>المدينة<input name="reviewer_city" required minlength="2" maxlength="50" value="' + escapeHtml(mockOrder.customer.city) + '" /></label>' +
          '<label>تقييمكِ<select name="rating" required><option value="" selected disabled>اختاري عدد النجوم</option><option value="5">★★★★★ — 5</option><option value="4">★★★★☆ — 4</option><option value="3">★★★☆☆ — 3</option><option value="2">★★☆☆☆ — 2</option><option value="1">★☆☆☆☆ — 1</option></select></label>' +
        '</div><label>مشاركتكِ<textarea name="review" required minlength="3" maxlength="2000" placeholder="اكتبي لنا عن تجربتكِ"></textarea></label>' +
        '<button class="merci-review-submit" type="submit">إرسال التقييم</button><p id="mock-review-feedback" class="merci-review-feedback" role="status" hidden></p>' +
      '</form>' +
    '</section>' +
    '<section class="merci-cross-sell" aria-labelledby="mock-cross-sell-title"><h2 id="mock-cross-sell-title">اكتشفي عروضاً أخرى من تشكيلتنا</h2>' +
      '<div class="merci-cross-sell-grid">' + otherOffers + '</div></section>';
}

const reviewForm = document.querySelector("#mock-review-form") || document.querySelector("#merci-review-form");
reviewForm?.addEventListener("submit", async (event) => {
  event.preventDefault();
  if (!reviewForm.reportValidity()) return;
  const button = reviewForm.querySelector("[type=submit]");
  if (button.disabled) return;
  const feedback = reviewForm.querySelector("[role=status]");
  const payload = {
    reviewer_name: reviewForm.elements.reviewer_name.value.trim(),
    reviewer_city: reviewForm.elements.reviewer_city.value.trim(),
    rating: Number(reviewForm.elements.rating.value),
    comment: reviewForm.elements.review.value.trim(),
    offer_id: reviewForm.dataset.offerId || mockOrder?.offer_id || "produit-1",
  };
  button.disabled = true;
  try {
    const result = await postWithLocalFallback(config.reviewEndpoint || window.ABAYA_REVIEW_ENDPOINT || "/wp-json/landing/v1/submit-review", payload, (review) => {
      saveMockReview(review);
      return { success: true, message: "تم حفظ تقييمكِ التجريبي محلياً. سيظهر مباشرة على صفحة العرض." };
    });
    if (!result.ok) throw new Error(result.body.message || "تعذر إرسال التقييم الآن.");
    feedback.textContent = result.body.message || "شكراً لكِ، وصلنا تقييمكِ وسيُنشر بعد المراجعة.";
    feedback.classList.remove("is-error");
    feedback.hidden = false;
    reviewForm.reset();
  } catch (error) {
    feedback.textContent = error.message || "حدث خطأ، يرجى المحاولة مرة أخرى.";
    feedback.classList.add("is-error");
    feedback.hidden = false;
  } finally {
    button.disabled = false;
  }
});
