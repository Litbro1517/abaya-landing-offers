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

reviewForm.addEventListener("submit", (event) => {
  event.preventDefault();
  if (!reviewForm.reportValidity()) return;

  const reviewPayload = {
    rating: Number(reviewForm.elements.rating.value),
    comment: reviewForm.elements.review.value.trim(),
    createdAt: new Date().toISOString(),
  };
  console.log("Review payload ready for backend:\n" + JSON.stringify(reviewPayload, null, 2));

  const card = document.createElement("article");
  card.className = "review-card";

  const cardHeader = document.createElement("div");
  cardHeader.className = "review-card-header";

  const stars = document.createElement("span");
  stars.className = "review-card-stars";
  stars.setAttribute("aria-label", `${reviewPayload.rating} / 5`);
  for (let index = 0; index < 5; index += 1) {
    const star = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    star.classList.add("review-star");
    if (index >= reviewPayload.rating) star.classList.add("is-empty");
    star.setAttribute("viewBox", "0 0 24 24");
    star.setAttribute("aria-hidden", "true");
    const shape = document.createElementNS("http://www.w3.org/2000/svg", "path");
    shape.setAttribute("d", "m12 2.5 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3.1-5.8 3.1 1.1-6.5-4.7-4.6 6.5-.9z");
    star.append(shape);
    stars.append(star);
  }

  const score = document.createElement("span");
  score.className = "review-card-score";
  score.textContent = `${reviewPayload.rating}/5`;

  const comment = document.createElement("p");
  comment.className = "review-card-comment";
  comment.textContent = reviewPayload.comment;

  cardHeader.append(stars, score);
  card.append(cardHeader, comment);
  reviewsContainer.prepend(card);

  reviewFeedback.textContent = "شكراً لمشاركة تقييمك.";
  reviewFeedback.hidden = false;
  reviewForm.reset();
  reviewDialog.close();
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

orderForm.addEventListener("submit", (event) => {
  event.preventDefault();
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
    const firstInvalid = orderForm.querySelector('[aria-invalid="true"]');
    firstInvalid?.focus();
    return;
  }

  const orderPayload = {
    customer: {
      name: name.value.trim(),
      phone: phone.value.trim(),
      city: city.value.trim(),
      address: address.value.trim(),
    },
    selection: {
      color: state.color,
      size: state.size,
      quantity: state.quantity,
      offer: quantityLabel(state.quantity),
    },
    total: {
      amount: bundlePrices[state.quantity],
      currency: "MAD",
    },
    createdAt: new Date().toISOString(),
  };
  console.log("Order payload ready for backend:\n" + JSON.stringify(orderPayload, null, 2));

  formResult.textContent = `تم التحقق من المعلومات. اختيارك: ${state.color} · ${state.size} · ${quantityLabel(state.quantity)}، ${bundlePrices[state.quantity]} درهم. لم يتم إرسال الطلب؛ النموذج غير مرتبط حالياً.`;
  formResult.hidden = false;
});

function quantityLabel(quantity) {
  return quantityLabels[quantity];
}

updateSummary();
