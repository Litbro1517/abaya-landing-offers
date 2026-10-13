import { createMockOrder, getReviewSummary, postWithLocalFallback, saveMockReview } from "./mock-api.js";

const mainPhoto = document.querySelector("#main-photo");
const thumbnails = [...document.querySelectorAll(".thumbnail")];
const swatches = [...document.querySelectorAll(".swatch")];
const colorImages = new Map(
  thumbnails.map((thumbnail) => [
    thumbnail.dataset.color,
    { src: thumbnail.dataset.src, alt: thumbnail.dataset.alt },
  ]),
);
const sizeOptions = [...document.querySelectorAll(".size-option")];
const bundleOptions = [...document.querySelectorAll(".bundle-card")];
const state = { color: "بيج", size: "M", quantity: 1 };
const bundlePrices = { 1: 299, 2: 499, 3: 699 };
const quantityLabels = { 1: "قطعة واحدة", 2: "قطعتان", 3: "3 قطع" };
const offerId = document.body.dataset.offerId || "produit-1";
const productId = Number(document.body.dataset.productId || 15);
const productSku = document.body.dataset.sku || "SIM-" + offerId.toUpperCase();
const offerContent = {
  "produit-1": { title: "عباية خريفية من توب لولان – أناقة وانسيابية", slogan: "اختيار يومي أنيق", intro: "عباية خريفية أنيقة وانسيابية، بتوب مميز وخياطة متقنة لراحة يومية." },
  "produit-2": { title: "عباية لولان تجمع بين الراحة والأناقة", slogan: "أناقة ترافق إطلالتك كل يوم", intro: "اختاري عباية لولان بإطلالة انسيابية وتفاصيل مناسبة لراحتك اليومية." },
  "produit-3": { title: "عباية لولان بلمسة راقية تناسبك", slogan: "إطلالة مميزة بأسلوبك الخاص", intro: "عباية عملية بإطلالة راقية، اختاري اللون والمقاس المناسبين لكِ." },
};
const currentOffer = offerContent[offerId] || offerContent["produit-1"];
const ratingElement = document.querySelector(".landing-rating");
function refreshLandingRating() {
  if (!ratingElement) return;
  const summary = getReviewSummary(offerId, Number(ratingElement.dataset.average || 0), Number(ratingElement.dataset.count || 0));
  const score = summary.average.toFixed(1);
  ratingElement.querySelector(".landing-rating-stars")?.style.setProperty("--rating-fill", `${summary.average / 5 * 100}%`);
  ratingElement.querySelector(".landing-rating-score").textContent = `${score} / 5`;
  ratingElement.querySelector(".landing-rating-count").textContent = `(${summary.count} تقييم)`;
  ratingElement.setAttribute("aria-label", `${score} / 5`);
}

window.addEventListener("pageshow", refreshLandingRating);
window.addEventListener("storage", refreshLandingRating);

document.querySelector("#product-title").textContent = currentOffer.title;
document.querySelector(".photo-tag").textContent = currentOffer.slogan;
document.querySelector(".product-intro").textContent = currentOffer.intro;
refreshLandingRating();
let photoRequestId = 0;

function updatePhoto(src, alt) {
  const requestId = ++photoRequestId;
  mainPhoto.classList.add("is-changing");
  const nextImage = new Image();
  nextImage.onload = () => {
    if (requestId !== photoRequestId) return;
    mainPhoto.src = src;
    mainPhoto.alt = alt;
    mainPhoto.classList.remove("is-changing");
  };
  nextImage.onerror = () => {
    if (requestId === photoRequestId) mainPhoto.classList.remove("is-changing");
  };
  nextImage.src = src;
}

function selectColor(color) {
  const image = colorImages.get(color);
  if (!image) return;

  state.color = color;
  swatches.forEach((swatch) => {
    const selected = swatch.dataset.color === color;
    swatch.classList.toggle("is-selected", selected);
    swatch.setAttribute("aria-pressed", String(selected));
  });
  thumbnails.forEach((thumbnail) => {
    const selected = thumbnail.dataset.color === color;
    thumbnail.classList.toggle("is-current", selected);
    thumbnail.setAttribute("aria-pressed", String(selected));
  });
  updatePhoto(image.src, image.alt);
  document.querySelector("#color-value").textContent = color;
  updateSummary();
}

function updateSummary() {
  const price = bundlePrices[state.quantity];
  const quantityLabel = quantityLabels[state.quantity];
  document.querySelector("#summary-choice").textContent =
    `عباية ${state.color} - مقاس ${state.size} - ${quantityLabel}`;
  document.querySelector("#summary-price").textContent = `${price} درهم`;
  bundleOptions.forEach((option) => {
    const selected = Number(option.dataset.quantity) === state.quantity;
    option.classList.toggle("is-selected", selected);
    option.setAttribute("aria-pressed", String(selected));
  });
}

swatches.forEach((swatch) => {
  swatch.addEventListener("click", () => {
    selectColor(swatch.dataset.color);
  });
});

sizeOptions.forEach((option) => {
  option.addEventListener("click", () => {
    state.size = option.dataset.size;
    sizeOptions.forEach((item) => item.classList.toggle("is-selected", item === option));
    document.querySelector("#size-value").textContent = state.size;
    updateSummary();
  });
});

bundleOptions.forEach((option) => {
  option.addEventListener("click", () => {
    state.quantity = Number(option.dataset.quantity);
    updateSummary();
  });
});

thumbnails.forEach((thumbnail) => {
  thumbnail.addEventListener("click", () => {
    selectColor(thumbnail.dataset.color);
    thumbnail.scrollIntoView({ behavior: "smooth", block: "nearest", inline: "nearest" });
  });
});

document.querySelector(".gallery-prev").addEventListener("click", () => {
  const current = thumbnails.findIndex((item) => item.classList.contains("is-current"));
  const previous = (current - 1 + thumbnails.length) % thumbnails.length;
  thumbnails[previous].click();
});
document.querySelector(".gallery-next").addEventListener("click", () => {
  const current = thumbnails.findIndex((item) => item.classList.contains("is-current"));
  const next = (current + 1) % thumbnails.length;
  thumbnails[next].click();
});

document.querySelectorAll(".product-image-wrapper").forEach((wrapper) => {
  const image = wrapper.querySelector("img");
  wrapper.addEventListener("pointermove", (event) => {
    if (event.pointerType !== "mouse") return;
    const rect = wrapper.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 100;
    const y = ((event.clientY - rect.top) / rect.height) * 100;
    image.style.transformOrigin = `${x}% ${y}%`;
  });
  wrapper.addEventListener("pointerleave", () => {
    image.style.transformOrigin = "center center";
  });
});

const orderTarget = document.querySelector("#order-form-section");
document.querySelectorAll(".offer-card, .promo-option, [data-offer]").forEach((card) => {
  card.addEventListener("click", () => {
    if (!orderTarget) return;
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    orderTarget.scrollIntoView({
      behavior: reduceMotion ? "auto" : "smooth",
      block: "start",
    });
  });
});

document.querySelectorAll("[data-modal]").forEach((trigger) => {
  trigger.addEventListener("click", () => {
    document.getElementById(trigger.dataset.modal)?.showModal();
  });
});
document.querySelectorAll(".info-modal").forEach((modal) => {
  modal.querySelector(".modal-close")?.addEventListener("click", () => modal.close());
  modal.addEventListener("click", (event) => {
    if (event.target === modal) modal.close();
  });
});

const orderForm = document.querySelector("#order-form");
const formResult = document.querySelector("#form-result");
const reviewForm = document.querySelector("#review-form");
const reviewDialog = document.querySelector("#review-dialog");
const reviewFeedback = document.querySelector("#reviews-feedback");
const reviewsContainer = document.querySelector(".reviews-list-container");

reviewForm.addEventListener("submit", async (event) => {
  event.preventDefault();
  if (!reviewForm.reportValidity()) return;
  const reviewPayload = {
    rating: Number(reviewForm.elements.rating.value),
    comment: reviewForm.elements.review.value.trim(),
    reviewer_name: reviewForm.elements.reviewer_name.value.trim(),
    reviewer_city: reviewForm.elements.reviewer_city.value.trim(),
    offer_id: offerId,
  };
  const reviewButton = reviewForm.querySelector(".review-submit");
  reviewButton.disabled = true;
  try {
    const result = await postWithLocalFallback("/wp-json/landing/v1/submit-review", reviewPayload, (payload) => {
      saveMockReview(payload);
      return { success: true, message: "تم حفظ تقييمكِ التجريبي محلياً. شكراً لمساعدتنا على التحسين." };
    });
    if (!result.ok) throw new Error(result.body.message || "تعذر إرسال التقييم الآن.");
    if (result.mocked) refreshLandingRating();
    reviewFeedback.textContent = result.body.message || "شكراً لك! تم استلام تقييمك وسيُنشر بعد المراجعة.";
    reviewFeedback.hidden = false;
    reviewForm.reset();
    reviewDialog.close();
  } catch (error) {
    reviewFeedback.textContent = error.message || "حدث خطأ، يرجى المحاولة مرة أخرى.";
    reviewFeedback.classList.add("is-error");
    reviewFeedback.hidden = false;
  } finally {
    reviewButton.disabled = false;
  }
});

function setFieldError(field, message) {
  const wrapper = field.closest(".form-field");
  wrapper.querySelector(".field-error").textContent = message;
  field.setAttribute("aria-invalid", message ? "true" : "false");
}

orderForm.querySelectorAll("input, select").forEach((field) => {
  field.addEventListener("input", () => setFieldError(field, ""));
  field.addEventListener("change", () => setFieldError(field, ""));
});

orderForm.addEventListener("submit", async (event) => {
  event.preventDefault();
  if (!orderForm.reportValidity()) return;
  formResult.hidden = true;

  const name = orderForm.elements.name;
  const phone = orderForm.elements.phone;
  const city = orderForm.elements.city;
  const address = orderForm.elements.address;
  const normalizedPhone = phone.value.replace(/[\s().-]/g, "");
  const moroccanPhone = /^(?:(?:\+|00)212)?0?[67]\d{8}$/.test(normalizedPhone);
  const validName = name.value.trim().length >= 3;
  const validCity = city.value.trim().length >= 2;
  const validAddress = address.value.trim().length >= 4;
  setFieldError(name, validName ? "" : "يرجى إدخال الاسم الكامل.");
  setFieldError(phone, moroccanPhone ? "" : "أدخلي رقم هاتف مغربي صحيحاً.");
  setFieldError(city, validCity ? "" : "يرجى اختيار المدينة.");
  setFieldError(address, validAddress ? "" : "يرجى إدخال العنوان الكامل.");
  if (!validName || !moroccanPhone || !validCity || !validAddress) {
    orderForm.querySelector('[aria-invalid="true"]')?.focus();
    return;
  }

  const submitButton = orderForm.querySelector(".form-submit");
  const submitLabel = submitButton.textContent;
  submitButton.disabled = true;
  submitButton.textContent = "جارٍ إرسال الطلب...";
  const payload = {
    full_name: name.value.trim(),
    phone: phone.value.trim(),
    city: city.value.trim(),
    address: address.value.trim(),
    color: state.color,
    size: state.size,
    quantity: state.quantity,
    website_hp: "",
    event_id: "evt_" + Date.now() + "_" + Math.random().toString(36).slice(2),
    page_url: location.href.split("#")[0],
    offer_id: offerId,
    product_id: productId,
    sku: productSku,
    value: bundlePrices[state.quantity],
  };
  try {
    const result = await postWithLocalFallback("/wp-json/landing/v1/create-order", payload, createMockOrder);
    if (!result.ok || !result.body.success) throw new Error(result.body.message || "حدث خطأ، يرجى المحاولة مرة أخرى.");
    const out = result.body;
    const query = new URLSearchParams({ order: String(out.order_id), key: out.order_key });
    window.location.assign("/merci.html?" + query.toString());
  } catch (error) {
    submitButton.disabled = false;
    submitButton.textContent = submitLabel;
    formResult.textContent = error.message || "تعذر إرسال الطلب، يرجى المحاولة مرة أخرى.";
    formResult.classList.add("is-error");
    formResult.hidden = false;
  }
});

function quantityLabel(quantity) {
  return quantityLabels[quantity];
}

updateSummary();
