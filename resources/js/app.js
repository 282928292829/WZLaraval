import './bootstrap';

import Collapse from '@alpinejs/collapse';

// Livewire 3 bundles and initialises Alpine automatically.
// We only need to register plugins BEFORE Alpine starts.
document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(Collapse);
});
