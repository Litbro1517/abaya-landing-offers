const mainPhoto = document.querySelector("#main-photo");
const thumbnails = [...document.querySelectorAll(".thumbnail")];
const swatches = [...document.querySelectorAll(".swatch")];
const colorImages = new Map(
  thumbnails.map((thumbnail) => [
    thumbnail.dataset.color,
    { src: thumbnail.dataset.src, alt: thumbnail.dataset.alt },
  ]),
);
// v1.10 : couleurs temporairement épuisées (drapeau out_of_stock du catalogue
// unique abaya_catalog(), rendu en data-out-of-stock par front-page.php).
const outOfStockColors = new Set(
  swatches.filter((swatch) => swatch.dataset.outOfStock === "1").map((swatch) => swatch.dataset.color),
);
const stockOverlay = document.getElementById("stock-overlay");
const sizeOptions = [...document.querySelectorAll(".size-option")];
const bundleOptions = [...document.querySelectorAll(".bundle-card")];
// P0-3 : plus aucune présélection — la cliente DOIT choisir couleur et taille.
const state = { color: null, size: null, quantity: 1 };
// P0-1 : la grille tarifaire des offres vient du serveur
// (window.LANDING.catalog, injectée depuis abaya_catalog()) — le front n'est
// qu'un affichage ; repli local identique si l'injection manquerait.
const LANDING = window.LANDING || {};
// v3.28 : repli aligné sur la grille du moment — v3.30 (mandat
// « feat/wool-abaya-product-adaptation ») : grille « Abaya Laine » 479/799/1099
// (source autoritaire abaya_catalog() injectée côté serveur ; le repli ne sert
// que si l'injection manque).
const bundlePrices = (LANDING.catalog && LANDING.catalog.bundles) || { 1: 479, 2: 799, 3: 1099 };
const quantityLabels = { 1: "قطعة واحدة", 2: "قطعتان", 3: "3 قطع" };
let photoRequestId = 0;
// Mandat 4P : erreurs ciblées par bloc (couleur / taille). Plus aucun message
// global près du bouton : le groupe manquant est encadré en rouge avec une
// micro-secousse, et un petit texte discret (même style que les .field-error
// du formulaire) apparaît au-dessus du bloc, nettoyé dès la sélection.
const choiceBlocks = {
  color: document.querySelector('.choice-group[data-attribute="color"]'),
  size: document.querySelector('.choice-group[data-attribute="size"]'),
};
const choiceErrorSlots = {
  color: document.querySelector('[data-choice-error="color"]'),
  size: document.querySelector('[data-choice-error="size"]'),
};
const CHOICE_ERROR_MESSAGES = {
  color: "يرجى اختيار اللون.",
  size: "يرجى اختيار المقاس.",
};

/* =====================================================================
 * v3.30 (mandat 4P LP « feat/wool-abaya-product-adaptation ») — SÉLECTEUR
 * MULTI-PIÈCES (intégration de la logique abaya-multi-piece-picker.html).
 * VEDETTE UNIQUEMENT : window.LANDING.offer_id n'existe que sur les landings
 * secondaires — son absence identifie la vedette, qui seule possède le
 * conteneur #multi-picker (rendu conditionnel du gabarit). Sur les
 * secondaires IS_FRONT=false et AUCUN chemin de code ci-dessous ne
 * s'exécute : comportement historique bit-exact.
 * Comportement (contrat du fichier de dev handoff) :
 *   1. les cartes d'offre fixent le nombre de pièces et le prix total ;
 *   2. 1 pièce  -> swatches simple choix (UI historique, INCHANGÉE) ;
 *   3. 2 ou 3   -> un compteur −/+ par couleur (somme == pièces, « + »
 *      désactivé à l'atteinte, répartition par défaut 2→1+1 / 3→2+1,
 *      même couleur plusieurs fois autorisée) ;
 *   4. une rangée de tailles par pièce, couleur affichée automatiquement
 *      (aucun choix « avec/sans boutons » : modèle unique) ;
 *   5. bouton de commande désactivé tant que somme != pièces OU qu'une
 *      pièce est sans taille ;
 *   6. changer d'offre conserve les tailles déjà choisies (par index) ;
 *   7. chaque interaction couleur émet l'événement DOM « abaya:colorchange »
 *      (détail {color}) câblé sur l'image principale de la galerie.
 * Affichage conditionnel (consigne mandat « sans déformer le bloc
 * principal ») : conteneur masqué (hidden) au chargement ; offres 2/3 ->
 * conteneur affiché + simple choix masqué ; offre 1 -> UI historique.
 * ===================================================================== */
const IS_FRONT = !LANDING.offer_id;
const pickerRoot = IS_FRONT ? document.getElementById("multi-picker") : null;
const isMultiMode = () => Boolean(pickerRoot && state.quantity > 1);
// Options couleur lues depuis les swatches rendus serveur : nom = clé du
// catalogue (whitelist REST), teinte = variable --swatch-color du composant
// existant — AUCUNE duplication de données front/serveur.
const colorOptions = swatches.map((swatch) => ({
  name: swatch.dataset.color,
  hex: (swatch.querySelector("span")?.style.getPropertyValue("--swatch-color") || "").trim(),
}));
const sizeValues = sizeOptions.map((option) => option.dataset.size);
// État multi-pièces : répartition par couleur (compteurs) + taille par pièce.
const multi = { counts: {}, items: [] };

function defaultCounts(pieces) {
  /* Répartition par défaut : 1ʳᵉ couleur = arrondi supérieur, restante pour
   * la dernière — 2 -> 1+1, 3 -> 2+1 (contrat handoff). */
  const counts = {};
  let left = pieces;
  colorOptions.forEach((option, index) => {
    const isLast = index === colorOptions.length - 1;
    const take = isLast ? left : Math.ceil(left / (colorOptions.length - index));
    counts[option.name] = take;
    left -= take;
  });
  return counts;
}

function multiTotal() {
  return colorOptions.reduce((sum, option) => sum + (multi.counts[option.name] || 0), 0);
}

/* Expansion ordonnée des compteurs : [بني كريمي, بني كريمي, أسود]. */
function multiColors() {
  const list = [];
  colorOptions.forEach((option) => {
    for (let k = 0; k < (multi.counts[option.name] || 0); k++) list.push(option.name);
  });
  return list;
}

function syncMultiItems() {
  while (multi.items.length < state.quantity) multi.items.push({ size: null });
  multi.items.length = state.quantity; // changer d'offre conserve les tailles (par index)
}

function isValidMulti() {
  if (multiTotal() !== state.quantity) return false;
  return multi.items.every((item) => item && item.size);
}

function multiItemsText() {
  const parts = multiColors().map((color, index) => `ق${index + 1}: ${color} / ${multi.items[index].size || "?"}`);
  return `${quantityLabels[state.quantity]} | ${parts.join(" | ")}`;
}

/* Aperçu galerie : image principale + vignettes + légende couleur, SANS
 * toucher à la sélection simple choix (fieldsets masqués en mode multi). */
function applyGalleryPreview(color) {
  const image = colorImages.get(color);
  if (image) updatePhoto(image.src, image.alt);
  thumbnails.forEach((thumbnail) => {
    const current = thumbnail.dataset.color === color;
    thumbnail.classList.toggle("is-current", current);
    thumbnail.setAttribute("aria-pressed", String(current));
  });
  const colorValue = document.querySelector("#color-value");
  if (colorValue) colorValue.textContent = color;
}

function renderMultiPicker() {
  if (!pickerRoot) return;
  const complete = multiTotal() === state.quantity;
  const full = multiTotal() >= state.quantity;
  const colorLines = colorOptions.map((option) => {
    const count = multi.counts[option.name] || 0;
    return (
      `<div class="picker-color-line${count ? " is-on" : ""}">` +
      `<button type="button" class="picker-color-name" data-act="preview" data-color="${option.name}" aria-label="معاينة اللون ${option.name}">` +
      `<span class="picker-swatch" style="--swatch-color:${option.hex}"></span>${option.name}</button>` +
      `<div class="picker-counter">` +
      `<button type="button" data-act="inc" data-color="${option.name}"${full ? " disabled" : ""} aria-label="زيادة ${option.name}">+</button>` +
      `<output>${count}</output>` +
      `<button type="button" data-act="dec" data-color="${option.name}"${count === 0 ? " disabled" : ""} aria-label="إنقاص ${option.name}">−</button>` +
      `</div></div>`
    );
  }).join("");
  let piecesHtml = "";
  if (complete) {
    piecesHtml = `<div class="picker-pieces">` + multiColors().map((color, index) => {
      const item = multi.items[index] || { size: null };
      const option = colorOptions.find((candidate) => candidate.name === color);
      const chips = sizeValues.map((size) =>
        `<button type="button" class="size-option" data-act="size" data-i="${index}" data-size="${size}" aria-pressed="${item.size === size}">${size}</button>`
      ).join("");
      return (
        `<section class="picker-piece">` +
        `<header><span class="picker-swatch" style="--swatch-color:${option ? option.hex : ""}"></span>القطعة ${index + 1} (${color})</header>` +
        `<div class="picker-field${item.size ? "" : " is-missing"}"><span>المقاس</span><div class="picker-chips">${chips}</div></div>` +
        `</section>`
      );
    }).join("") + `</div>`;
  }
  const hint = complete
    ? (isValidMulti() ? "" : "اختاري المقاس لكل قطعة")
    : `اختاري ${state.quantity} قطع بالألوان (${multiTotal()}/${state.quantity})`;
  pickerRoot.innerHTML =
    `<div class="picker-colors">${colorLines}</div>${piecesHtml}` +
    `<button class="text-link picker-size-guide" type="button" data-modal="size-guide-dialog" aria-haspopup="dialog" aria-controls="size-guide-dialog">جدول المقاسات</button>` +
    `<p class="picker-hint" role="status">${hint}</p>`;
  // Contrat §DATA du handoff : champs cachés portés par le formulaire (vedette).
  const fPieces = document.getElementById("f_pieces");
  const fTotal = document.getElementById("f_total");
  const fItems = document.getElementById("f_items");
  const fJson = document.getElementById("f_json");
  if (fPieces) fPieces.value = String(state.quantity);
  if (fTotal) fTotal.value = String(bundlePrices[state.quantity]);
  if (fItems) fItems.value = multiItemsText();
  if (fJson) fJson.value = JSON.stringify(multiColors().map((color, index) => ({ n: index + 1, color, size: multi.items[index].size })));
}

/* Bascule d'affichage conditionnel : offres 2/3 -> conteneur multi-pièces
 * visible + simple choix masqué ; offre 1 -> UI historique inchangée. */
function setSelectionMode() {
  if (!pickerRoot) return;
  const multiMode = isMultiMode();
  pickerRoot.hidden = !multiMode;
  if (choiceBlocks.color) choiceBlocks.color.classList.toggle("is-hidden", multiMode);
  if (choiceBlocks.size) choiceBlocks.size.classList.toggle("is-hidden", multiMode);
}

function syncMultiOffer() {
  if (!pickerRoot) return;
  multi.counts = defaultCounts(state.quantity);
  syncMultiItems();
  renderMultiPicker();
}

pickerRoot?.addEventListener("click", (event) => {
  // v3.30 : le contenu du picker est RENDU dynamiquement (re-rendu à chaque
  // interaction) — les déclencheurs [data-modal] liés au chargement ne
  // s'y appliquent pas ; délégation scopée au conteneur (aucun double
  // binding possible : les nœuds du picker n'ont aucun listener direct).
  const modalTrigger = event.target.closest("[data-modal]");
  if (modalTrigger) {
    document.getElementById(modalTrigger.dataset.modal)?.showModal();
    return;
  }
  const target = event.target.closest("[data-act]");
  if (!target) return;
  const action = target.dataset.act;
  const color = target.dataset.color;
  if (action === "preview") {
    applyGalleryPreview(color);
    return;
  }
  if (action === "inc" && multiTotal() < state.quantity) {
    multi.counts[color] = (multi.counts[color] || 0) + 1;
    applyGalleryPreview(color);
    // Événement contractuel (handoff §7) câblé sur la galerie produit.
    document.dispatchEvent(new CustomEvent("abaya:colorchange", { detail: { color } }));
    syncMultiItems();
    renderMultiPicker();
    updateSummary();
    updateSubmitState();
    return;
  }
  if (action === "dec" && (multi.counts[color] || 0) > 0) {
    multi.counts[color]--;
    syncMultiItems();
    renderMultiPicker();
    updateSummary();
    updateSubmitState();
    return;
  }
  if (action === "size") {
    const index = Number(target.dataset.i);
    if (multi.items[index]) multi.items[index].size = target.dataset.size;
    renderMultiPicker();
    updateSummary();
    updateSubmitState();
  }
});

document.addEventListener("abaya:colorchange", (event) => {
  if (event.detail && event.detail.color) applyGalleryPreview(event.detail.color);
});

function setChoiceError(attribute, hasError) {
  const slot = choiceErrorSlots[attribute];
  if (slot) {
    slot.textContent = hasError ? CHOICE_ERROR_MESSAGES[attribute] : "";
    slot.hidden = !hasError;
  }
  const block = choiceBlocks[attribute];
  if (block) block.classList.toggle("attribute-error-box", hasError);
}

function flagChoiceError(attribute) {
  setChoiceError(attribute, true);
  // Relance de la micro-secousse à chaque tentative de soumission
  // (prefers-reduced-motion neutralise l'animation côté CSS).
  const block = choiceBlocks[attribute];
  if (block) {
    block.classList.remove("attribute-error-box");
    void block.offsetWidth; // reflow : réinitialise l'animation CSS
    block.classList.add("attribute-error-box");
  }
}

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
  setChoiceError("color", false);
  syncOutOfStockState(color);
  updateSummary();
}

// v1.10 : état « Stock épuisé » — voile fin + badge vitré sur #main-photo et
// bouton de commande désactivé tant qu'une couleur épuisée est affichée.
// L'autorité finale reste la garde serveur (abaya_out_of_stock, 422).
function syncOutOfStockState(color) {
  const blocked = outOfStockColors.has(color);
  if (stockOverlay) {
    stockOverlay.classList.toggle("is-visible", blocked);
    stockOverlay.setAttribute("aria-hidden", String(!blocked));
  }
  updateSubmitState();
}

/* v3.30 : état du bouton de commande unifié — couleur épuisée (hérité v1.10)
 * OU sélection multi-pièces incomplète (somme != pièces / taille manquante,
 * contrat handoff §5 : bouton désactivé tant que la sélection n'est pas
 * complète). Offre 1 pièce : comportement historique inchangé. */
function updateSubmitState() {
  const stockBlocked = outOfStockColors.has(state.color);
  const pickerBlocked = isMultiMode() && !isValidMulti();
  submitButton.disabled = stockBlocked || pickerBlocked;
  submitButton.classList.toggle("is-unavailable", stockBlocked);
}

function updateSummary() {
  const price = bundlePrices[state.quantity];
  const quantityLabel = quantityLabels[state.quantity];
  bundleOptions.forEach((option) => {
    const selected = Number(option.dataset.quantity) === state.quantity;
    option.classList.toggle("is-selected", selected);
    option.setAttribute("aria-pressed", String(selected));
  });
  // v3.30 : mode multi-pièces (offres 2/3, vedette) — le résumé affiche
  // l'offre tant que la sélection est incomplète, puis la ligne lisible des
  // pièces (même format que order_items : « قطعتان | ق1: بني كريمي / M | … »).
  if (isMultiMode()) {
    document.querySelector("#summary-choice").textContent = isValidMulti() ? multiItemsText() : quantityLabel;
    document.querySelector("#summary-price").textContent = `${price} درهم`;
    return;
  }
  // P0-3 : gère l'état « non choisi » (color/size à null).
  // v3.28 : résumé initial — vedette = mot-à-mot du mandat (« اختاري اللون
  // والمقاس ») ; landing secondaire (LANDING.offer_id présent) = libellé
  // validé inchangé (hors périmètre du mandat).
  const summaryDefault = LANDING.offer_id
    ? `اختاري اللون والمقاس - ${quantityLabel}`
    : `اختاري اللون والمقاس`;
  const choiceText = state.color && state.size
    ? `عباية ${state.color} - مقاس ${state.size} - ${quantityLabel}`
    : summaryDefault;
  document.querySelector("#summary-choice").textContent = choiceText;
  document.querySelector("#summary-price").textContent = `${price} درهم`;
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
    setChoiceError("size", false);
    updateSummary();
  });
});

bundleOptions.forEach((option) => {
  option.addEventListener("click", () => {
    state.quantity = Number(option.dataset.quantity);
    // v3.30 (vedette) : répartition par défaut des compteurs de l'offre
    // (tailles déjà choisies conservées par index — contrat handoff §6) puis
    // bascule d'affichage conditionnel multi-pièces ↔ simple choix.
    syncMultiOffer();
    setSelectionMode();
    updateSummary();
    updateSubmitState();
  });
});

thumbnails.forEach((thumbnail) => {
  thumbnail.addEventListener("click", () => {
    // v3.30 : en mode multi-pièces la vignette pilote l'APERÇU galerie sans
    // toucher à la sélection simple choix (réservée à l'offre 1 pièce).
    if (isMultiMode()) applyGalleryPreview(thumbnail.dataset.color);
    else selectColor(thumbnail.dataset.color);
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
const reviewSubmitButton = reviewForm.querySelector(".review-submit");

function showReviewFeedback(message, isError = false) {
  reviewFeedback.textContent = message;
  reviewFeedback.classList.toggle("is-error", Boolean(isError));
  reviewFeedback.hidden = false;
}

/* Découplage étoiles/avis (mandat fix/rating-comments-decoupling) : le score
   global sous le H1 (Global Score Ratio) est recalculé en base à chaque note
   et mis à jour IMMÉDIATEMENT depuis la réponse du serveur — indépendamment
   du corps du commentaire et de sa modération. Le rendu de la section avis
   reste étanche : seuls les avis textuels validés par l'admin y apparaissent. */
function updateRatingBadge(out) {
  const badge = document.querySelector(".landing-rating");
  const average = Number(out?.average);
  const count = Number(out?.count);
  if (!badge || !Number.isFinite(average) || average <= 0 || !Number.isFinite(count) || count <= 0) return;
  const score = average.toFixed(1);
  badge.dataset.average = String(average);
  badge.dataset.count = String(count);
  badge.setAttribute("aria-label", score + " / 5");
  const stars = badge.querySelector(".landing-rating-stars");
  if (stars) stars.style.setProperty("--rating-fill", (average / 5 * 100).toFixed(1) + "%");
  const scoreEl = badge.querySelector(".landing-rating-score");
  if (scoreEl) scoreEl.textContent = score + " / 5";
  const countEl = badge.querySelector(".landing-rating-count");
  if (countEl) countEl.textContent = "(" + count + " تقييم)";
}

reviewForm.addEventListener("submit", async (event) => {
  event.preventDefault();
  if (reviewSubmitButton?.disabled) return;
  if (!reviewForm.reportValidity()) return;

  const reviewEndpoint = (window.LANDING || {}).review_endpoint;
  if (!reviewEndpoint) {
    showReviewFeedback("شكراً لمشاركة تقييمك.");
    reviewForm.reset();
    reviewDialog.close();
    return;
  }

  // Correctif C2 : l'avis est désormais envoyé au serveur (wp_insert_comment, modération) au lieu d'être injecté dans le DOM
  // Mandat 4P LP (fix/review-form-fields-and-stars) : ajout du nom + ville de la cliente pour affichage "name — city".
  const reviewPayload = {
    rating: Number(reviewForm.elements.rating.value),
    comment: reviewForm.elements.review.value.trim(),
    reviewer_name: reviewForm.elements.reviewer_name.value.trim(),
    reviewer_city: reviewForm.elements.reviewer_city.value.trim(),
  };

  reviewSubmitButton.disabled = true;
  try {
    const response = await fetch(reviewEndpoint, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify(reviewPayload),
    });
    const out = await response.json().catch(() => ({}));
    reviewDialog.close();
    if (out.success) {
      // Enregistré en base avec statut « en attente » pour le texte : publié après validation manuelle.
      // La note associée alimente déjà la moyenne — badge sous le H1 actualisé immédiatement.
      showReviewFeedback(out.message || "شكراً لك! تم استلام تقييمك وسيُنشر بعد المراجعة.");
      reviewForm.reset();
      updateRatingBadge(out);
    } else {
      showReviewFeedback(out.message || "حدث خطأ، يرجى المحاولة مرة أخرى.", true);
    }
  } catch (_) {
    reviewDialog.close();
    showReviewFeedback("خطأ في الشبكة. يرجى المحاولة مرة أخرى.", true);
  }
  reviewSubmitButton.disabled = false;
});

function setFieldError(field, message) {
  const wrapper = field.closest(".form-field");
  wrapper.querySelector(".field-error").textContent = message;
  field.setAttribute("aria-invalid", message ? "true" : "false");
}

orderForm.querySelectorAll(".form-field input, .form-field select").forEach((field) => {
  field.addEventListener("input", () => setFieldError(field, ""));
  field.addEventListener("change", () => setFieldError(field, ""));
});

const LANDING_ENDPOINT = LANDING.endpoint;
// Tunnel post-achat (mandat ⑭) : sur une landing secondaire déverrouillée
// depuis la Thank You Page (?order=&key= valide), la soumission AJOUTE une
// ligne à la commande existante (route add-to-order) puis ramène à la Thank
// You Page personnalisée — le récapitulatif est recalculé serveur. La racine
// et les landings sans contexte suivent le flux create-order historique.
const UPSELL = LANDING.upsell || null;
const UPSELL_ENDPOINT = LANDING.upsell_endpoint || "";
const submitButton = orderForm.querySelector(".form-submit");
const submitLabel = submitButton.textContent;
// Un seul event_id par chargement de page : un nouvel essai ne crée jamais de doublon
const pageEventId = "order_" + Date.now() + "_" + Math.random().toString(36).slice(2, 9);
const pageParams = new URLSearchParams(location.search);
let checkoutTracked = false;

function track(...args) {
  try { if (window.fbq) window.fbq(...args); } catch (_) {}
}

function showFormError(message) {
  formResult.textContent = message;
  formResult.classList.add("is-error");
  formResult.hidden = false;
}

orderForm.addEventListener("focusin", () => {
  if (checkoutTracked) return;
  checkoutTracked = true;
  track("track", "InitiateCheckout", { currency: "MAD", value: bundlePrices[state.quantity] });
});

orderForm.addEventListener("submit", async (event) => {
  event.preventDefault();
  if (submitButton.disabled) return;
  // v1.10 : double garde front — jamais de soumission sur couleur épuisée
  // (couvre aussi la soumission clavier avec le bouton désactivé).
  if (outOfStockColors.has(state.color)) return;
  formResult.hidden = true;
  formResult.classList.remove("is-error");

  const name = orderForm.elements.name;
  const phone = orderForm.elements.phone;
  const city = orderForm.elements.city;
  const address = orderForm.elements.address;
  const normalizedPhone = phone.value.replace(/[\s().-]/g, "");
  const moroccanPhone = /^(?:(?:\+|00)212)?0?[67]\d{8}$/.test(normalizedPhone);
  const validName = name.value.trim().length >= 3;
  const validCity = city.value.trim().length >= 2;
  const validAddress = address.value.trim().length >= 4;
  // P0-3 : couleur et taille obligatoires, aucune valeur par défaut.
  // v3.30 : en mode multi-pièces (offres 2/3, vedette), la validité porte sur
  // la répartition complète des compteurs + une taille par pièce ; la couleur
  // et la taille transmises au serveur sont celles de la 1ʳᵇ pièce.
  const multiMode = isMultiMode();
  const validMulti = multiMode ? isValidMulti() : true;
  const validColor = multiMode ? Boolean(multiColors()[0]) : Boolean(state.color);
  const validSize = multiMode ? Boolean(multi.items[0] && multi.items[0].size) : Boolean(state.size);

  setFieldError(name, validName ? "" : "يرجى إدخال الاسم الكامل.");
  setFieldError(phone, moroccanPhone ? "" : "أدخلي رقم هاتف مغربي صحيحاً.");
  setFieldError(city, validCity ? "" : "يرجى اختيار المدينة.");
  setFieldError(address, validAddress ? "" : "يرجى إدخال العنوان الكامل.");

  // Mandat 4P : chaque groupe manquant est signalé indépendamment (contour
  // rouge + micro-secousse + micro-texte au-dessus du bloc) — plus aucun
  // message global près du bouton. Si aucun choix n'a été fait, les deux
  // blocs sont encadrés simultanément ; le défilement va au premier manquant.
  // v3.30 : en mode multi-pièces, le seul bloc visible est le conteneur
  // picker (les fieldsets simple choix sont masqués) — c'est lui qui reçoit
  // le défilement si la répartition ou les tailles sont incomplètes.
  let missingChoice = null;
  if (multiMode) {
    if (!validMulti || !validColor || !validSize) missingChoice = pickerRoot;
  } else {
    if (!validColor) {
      flagChoiceError("color");
      missingChoice = choiceBlocks.color;
    } else {
      setChoiceError("color", false);
    }
    if (!validSize) {
      flagChoiceError("size");
      if (!missingChoice) missingChoice = choiceBlocks.size;
    } else {
      setChoiceError("size", false);
    }
  }

  if (!validName || !moroccanPhone || !validCity || !validAddress || !validColor || !validSize || (multiMode && !validMulti)) {
    if (missingChoice) {
      const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
      missingChoice.scrollIntoView({ behavior: reduceMotion ? "auto" : "smooth", block: "center" });
    } else {
      const firstInvalid = orderForm.querySelector('[aria-invalid="true"]');
      firstInvalid?.focus();
    }
    return;
  }

  const isUpsell = Boolean(UPSELL && UPSELL_ENDPOINT);
  if (!isUpsell && !LANDING_ENDPOINT) {
    showFormError("خطأ في الإعداد. يرجى الاتصال بنا هاتفياً.");
    return;
  }

  submitButton.disabled = true;
  submitButton.textContent = "جارٍ إرسال الطلب...";

  const payload = {
    full_name: name.value.trim(),
    phone: phone.value.trim(),
    city: city.value.trim(),
    address: address.value.trim(),
    // v3.30 : en mode multi-pièces, couleur/taille de la 1ʳᵇ pièce (clés du
    // catalogue — whitelist serveur + méta لون/مقاس de la ligne d'article) ;
    // le détail complet des pièces part dans order_items / order_items_json.
    color: multiMode ? multiColors()[0] : state.color,
    size: multiMode ? multi.items[0].size : state.size,
    quantity: state.quantity,
    website_hp: orderForm.elements.extra_note ? orderForm.elements.extra_note.value : "",
    event_id: pageEventId,
    page_url: location.href.split("#")[0],
  };
  // v3.30 (contrat §DATA du handoff, offres multi-pièces uniquement) : le
  // handler REST persiste ces champs en méta (commande + ligne d'article) —
  // offre 1 pièce : payload historique STRICTEMENT inchangé.
  if (multiMode) {
    payload.offer_pieces = state.quantity;
    payload.offer_total = bundlePrices[state.quantity];
    payload.order_items = multiItemsText();
    payload.order_items_json = JSON.stringify(multiColors().map((color, index) => ({ n: index + 1, color, size: multi.items[index].size })));
  }
  ["utm_source", "utm_medium", "utm_campaign", "utm_content", "utm_term"].forEach((key) => {
    if (pageParams.get(key)) payload[key] = pageParams.get(key);
  });
  /* Mandat multi-landing : le paramètre `offer` (slug) n'est envoyé QUE sur
   * les landings secondaires (window.LANDING.offer_id injecté par functions.php).
   * Racine : aucun paramètre — « offer absent = offre n°1 » (compat caches). */
  if (LANDING.offer_id) payload.offer = LANDING.offer_id;
  if (LANDING.sku) payload.sku = LANDING.sku;
  /* Mandat ⑭ : en mode upsell, la ligne est rattachée à la commande tunnel
   * référencée (même garde serveur que /merci/ : hash_equals + 48 h) et
   * l'offre cible est celle de la landing courante (vedette incluse). */
  if (isUpsell) {
    payload.order_id = UPSELL.order_id;
    payload.order_key = UPSELL.order_key;
    if (UPSELL.offer) payload.offer = UPSELL.offer;
  }

  try {
    const response = await fetch(isUpsell ? UPSELL_ENDPOINT : LANDING_ENDPOINT, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin", // envoie les cookies _fbp / _fbc au serveur
      body: JSON.stringify(payload),
    });
    const out = await response.json().catch(() => ({}));

    if (out.success) {
      // Même event_id que le serveur + valeur réelle calculée côté serveur
      track("track", "Purchase", {
        currency: "MAD",
        value: out.value,
        content_type: "product",
        content_ids: [String(out.product_id)],
      }, { eventID: out.event_id });
      // Bouton laissé désactivé : redirection vers la page de remerciement.
      if (isUpsell) {
        // Mandat ⑭ : retour Thank You Page (MÊME commande, récap recalculé
        // serveur depuis la base) — &added=1 déclenche le bandeau de
        // confirmation. La persistance du contexte est garantie par le duo
        // order+key inchangé porté par UPSELL.return.
        setTimeout(() => {
          location.href = UPSELL.return + (UPSELL.return.includes("?") ? "&" : "?") + "added=1";
        }, 500);
        return;
      }
      // P1-1 : passage de order_id + order_key pour afficher le
      // récapitulatif dynamique sécurisé (sans clé valide, /merci/ reste générique).
      setTimeout(() => {
        const thanksBase = LANDING.thanks || "/";
        const sep = thanksBase.includes("?") ? "&" : "?";
        const orderQuery = out.order_key
          ? `${sep}order=${encodeURIComponent(out.order_id)}&key=${encodeURIComponent(out.order_key)}`
          : "";
        location.href = thanksBase + orderQuery;
      }, 500);
      return;
    }
    showFormError(out.message || "حدث خطأ، يرجى المحاولة مرة أخرى.");
  } catch (_) {
    showFormError("خطأ في الشبكة. يرجى المحاولة مرة أخرى.");
  }
  submitButton.textContent = submitLabel;
  // v1.10 : réaligne le bouton sur l'état courant (une couleur épuisée
  // sélectionnée pendant l'envoi reste bloquée au lieu d'être réactivée).
  syncOutOfStockState(state.color);
});

updateSummary();
