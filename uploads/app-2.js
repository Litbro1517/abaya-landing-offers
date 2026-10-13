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
  updateSummary();
}

function updateSummary() {
  const price = bundlePrices[state.quantity];
  const regularPrice = state.quantity * 299;
  const quantityLabel = state.quantity === 1 ? "قطعة واحدة" : `${state.quantity} قطع`;
  document.querySelector("#color-value").textContent = state.color;
  document.querySelector("#size-value").textContent = state.size;
  document.querySelector("#current-price").innerHTML = `${price} <small>درهم</small>`;
  document.querySelector("#old-price").textContent = `${regularPrice} درهم`;
  document.querySelector(".discount-pill").textContent = `توفير ${regularPrice - price} درهم`;
  document.querySelector("#summary-choice").textContent =
    `عباية ${state.color} · مقاس ${state.size} · ${quantityLabel}`;
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
const reviewResult = document.querySelector("#review-result");

reviewForm.addEventListener("submit", (event) => {
  event.preventDefault();
  if (!reviewForm.reportValidity()) return;
  reviewResult.textContent = "شكراً لمشاركة تقييمك.";
  reviewResult.hidden = false;
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

  formResult.textContent = `تم التحقق من المعلومات. اختيارك: ${state.color} · ${state.size} · ${quantityLabel(state.quantity)}، ${bundlePrices[state.quantity]} درهم. لم يتم إرسال الطلب؛ النموذج غير مرتبط حالياً.`;
  formResult.hidden = false;
});

function quantityLabel(quantity) {
  return quantity === 1 ? "قطعة واحدة" : `${quantity} قطع`;
}

updateSummary();
