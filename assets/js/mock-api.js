const ORDER_KEY = "mock_orders";
const REVIEW_KEY = "mock_reviews";
const ORDER_MODE_KEY = "mock_order_mode";
const REVIEW_MODE_KEY = "mock_review_mode";
const PRICES = { 1: 299, 2: 499, 3: 699 }; // v3.39 : GRILLE OFFICIELLE restaurée (299/499/699) — repli dev uniquement

function readRows(key) {
  try {
    const value = JSON.parse(localStorage.getItem(key) || "[]");
    return Array.isArray(value) ? value : [];
  } catch {
    return [];
  }
}

function writeRows(key, rows) {
  localStorage.setItem(key, JSON.stringify(rows));
}

function isMockMode(key) {
  return localStorage.getItem(key) === "1" || localStorage.getItem("mock_api_mode") === "1";
}

function token() {
  return globalThis.crypto?.randomUUID?.() || String(Date.now()) + "_" + Math.random().toString(36).slice(2);
}

export function createMockOrder(payload) {
  const selection = payload.selection || payload;
  const customer = payload.customer || payload;
  const quantity = Number(selection.quantity || 1);
  const orderId = 10000 + Math.floor(Math.random() * 90000);
  const eventId = "evt_mock_" + token();
  const orderKey = "wc_order_mock_" + token();
  const value = Number(payload.value ?? payload.total?.amount ?? PRICES[quantity] ?? PRICES[1]);
  const order = {
    order_id: orderId,
    order_key: orderKey,
    event_id: eventId,
    value,
    product_id: Number(payload.product_id || document.body.dataset.productId || 15),
    sku: payload.sku || document.body.dataset.sku || "",
    offer_id: payload.offer_id || document.body.dataset.offerId || "produit-1",
    customer: {
      full_name: customer.full_name || customer.name || "",
      phone: customer.phone || "",
      city: customer.city || "",
      address: customer.address || "",
    },
    selection: {
      color: selection.color || "",
      size: selection.size || "",
      quantity,
      offer: selection.offer || "",
    },
    created_at: new Date().toISOString(),
  };
  writeRows(ORDER_KEY, [...readRows(ORDER_KEY), order]);
  return { success: true, order_id: orderId, value, event_id: eventId, product_id: order.product_id, offer_id: order.offer_id, order_key: orderKey };
}

export function saveMockReview(payload) {
  /* Règle du maximum + modération intelligente (miroir du handler REST) :
   * - un seul avis par commande (order_id) — re-soumission = mise à jour ;
   * - note conservée = max(note_existante, nouvelle_note) ;
   * - étoiles seules = « approved » ; avec texte = « pending » (modération). */
  const rows = readRows(REVIEW_KEY);
  const orderId = Number(payload.order_id) || 0;
  const text = String(payload.comment || "").trim();
  let rating = Math.min(5, Math.max(1, Number(payload.rating) || 1));
  let target = orderId ? rows.find((row) => Number(row.order_id) === orderId) : null;
  if (target) {
    rating = Math.max(Number(target.rating) || 0, rating);
    target.rating = rating;
    target.reviewer_name = payload.reviewer_name || target.reviewer_name || "";
    target.reviewer_city = payload.reviewer_city || target.reviewer_city || "";
    target.offer_id = payload.offer_id || target.offer_id || "produit-1";
    if (text !== "") { target.comment = text; target.status = "pending"; }
  } else {
    target = {
      reviewer_name: payload.reviewer_name || "",
      reviewer_city: payload.reviewer_city || "",
      rating: rating,
      comment: text,
      offer_id: payload.offer_id || "produit-1",
      order_id: orderId,
      status: text !== "" ? "pending" : "approved",
      created_at: new Date().toISOString(),
    };
    rows.push(target);
  }
  writeRows(REVIEW_KEY, rows);
  return { rating: target.rating, status: target.status };
}

export function getMockOrders() {
  return isMockMode(ORDER_MODE_KEY) ? readRows(ORDER_KEY) : [];
}

export function getMockReviews(offerId) {
  if (!isMockMode(REVIEW_MODE_KEY)) return [];
  return readRows(REVIEW_KEY).filter((review) => review.status === "approved" && (!offerId || review.offer_id === offerId));
}

export function getReviewSummary(offerId, baseAverage = 0, baseCount = 0) {
  /* Découplage étoiles/avis (miroir du handler REST) : l'agrégat du score global
   * porte sur TOUTES les notes (approuvées ET en attente), indépendamment du
   * corps du commentaire — chaque note attribuée met la moyenne à jour
   * immédiatement. Le listing public (getMockReviews) reste étanche :
   * seuls les avis approuvés y sont éligibles. */
  const reviews = readRows(REVIEW_KEY).filter((review) => {
    const rating = Number(review.rating);
    return rating >= 1 && rating <= 5 && (!offerId || review.offer_id === offerId);
  });
  const count = Number(baseCount) + reviews.length;
  const sum = Number(baseAverage) * Number(baseCount) + reviews.reduce((total, review) => total + Number(review.rating), 0);
  return { count, average: count ? sum / count : 0 };
}

export async function postWithLocalFallback(endpoint, payload, mockHandler) {
  const modeKey = endpoint.includes("submit-review") ? REVIEW_MODE_KEY : ORDER_MODE_KEY;
  try {
    const response = await fetch(endpoint, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify(payload),
    });
    if ([404, 502, 503, 504].includes(response.status)) throw new Error("REST endpoint unavailable");
    const body = await response.json();
    localStorage.setItem(modeKey, "0");
    localStorage.removeItem("mock_api_mode");
    return { ok: response.ok && Boolean(body.success), body, mocked: false };
  } catch (_) {
    localStorage.setItem(modeKey, "1");
    return { ok: true, body: mockHandler(payload), mocked: true };
  }
}
