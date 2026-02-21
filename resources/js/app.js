import './bootstrap';

import Collapse from '@alpinejs/collapse';

// Livewire 3 bundles and initialises Alpine automatically.
// We only need to register plugins BEFORE Alpine starts.
document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(Collapse);
});

/**
 * toEnglishDigits — Convert Arabic/Persian digits to English digits.
 * Matches WordPress's toEnglishDigits() function exactly.
 * Handles both Persian digits [۰۱۲۳۴۵۶۷۸۹] and Arabic-Indic digits [٠١٢٣٤٥٦٧٨٩].
 * Exposed globally so Alpine expressions and inline scripts can call it directly.
 */
window.toEnglishDigits = function (str) {
    if (!str) return str;
    
    str = String(str);
    
    // Convert Persian digits [۰۱۲۳۴۵۶۷۸۹]
    str = str.replace(/[۰-۹]/g, function(c) {
        return c.charCodeAt(0) - '۰'.charCodeAt(0);
    });
    
    // Convert Arabic-Indic digits [٠١٢٣٤٥٦٧٨٩]
    str = str.replace(/[٠-٩]/g, function(c) {
        return c.charCodeAt(0) - '٠'.charCodeAt(0);
    });
    
    return str;
};

/**
 * Legacy alias for backward compatibility
 */
window.convert2num = window.toEnglishDigits;

/**
 * Global input handler: automatically converts Arabic/Persian digits to English digits
 * on ALL input fields and textareas site-wide.
 * Uses event delegation so it works with dynamically added Livewire elements.
 * 
 * Applies to: text, number, tel, email, password, search, url, textarea, and all other input types.
 */
function convertDigitsOnInput(e) {
    const el = e.target;
    
    // Only process INPUT and TEXTAREA elements
    if (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA') return;
    
    // Skip readonly and disabled fields
    if (el.readOnly || el.disabled) return;
    
    // Skip if field has data-no-convert attribute (opt-out)
    if (el.dataset.noConvert === 'true') return;
    
    const converted = window.toEnglishDigits(el.value);
    if (el.value !== converted) {
        const start = el.selectionStart;
        const end   = el.selectionEnd;
        el.value = converted;
        // Preserve cursor position
        try { 
            el.setSelectionRange(start, end); 
        } catch (_) {
            // Fallback for elements that don't support setSelectionRange
            try {
                el.focus();
            } catch (_) {}
        }
    }
}

// Apply conversion on input (real-time as user types)
document.addEventListener('input', convertDigitsOnInput, true);

// Apply conversion on blur (catches any missed conversions)
document.addEventListener('blur', convertDigitsOnInput, true);
