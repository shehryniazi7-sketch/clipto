/**
 * URL fragment helpers. A fragment may hold a literal '%' (an author-set anchor such as
 * "save-50%"), which makes decodeURIComponent() throw; resolve it the way browsers do.
 */

/** decodeURIComponent() that returns the input unchanged instead of throwing. */
export const safeDecode = (s) => {
	try {
		return decodeURIComponent(s);
	} catch (e) {
		return s;
	}
};

/**
 * The element id a fragment (without the '#') points at: the id as written when an
 * element has it, otherwise the percent-decoded form.
 *
 * @param {string} raw
 * @returns {string}
 */
export const fragmentId = (raw) => (!raw || document.getElementById(raw) ? raw : safeDecode(raw));
