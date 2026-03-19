{{--
  URL Paste | Open buttons partial.

  @param string $mode  'items'   — inside x-for (item, idx); calls doPasteForItem / doOpenForItem.
                        'current' — single currentItem form; calls doPasteCurrentItem / doOpenCurrentItem.

  Feedback state used:
    items   mode: pasteFeedbackIdx / pasteFeedbackField / openFeedbackIdx / openFeedbackLabel
    current mode: currentItemPasteFeedback ('pasted' | 'opened' | null)
--}}
<span class="text-[11px] text-slate-400">
    @if ($mode === 'items')
    <button type="button"
            @click="doPasteForItem(idx, $event)"
            :aria-label="pasteFeedbackIdx === idx && pasteFeedbackField === 'url' ? pastedLabel : pasteLabel"
            class="hover:text-slate-500 hover:underline focus:outline-none focus:underline py-2 -my-1">
        <span x-text="pasteFeedbackIdx === idx && pasteFeedbackField === 'url' ? pastedLabel : pasteLabel"></span>
    </button>
    <span class="text-slate-300">|</span>
    <button type="button"
            @click="doOpenForItem(idx)"
            :aria-label="openFeedbackIdx === idx ? openFeedbackLabel : openLabel"
            class="hover:text-slate-500 hover:underline focus:outline-none focus:underline py-2 -my-1">
        <span x-text="openFeedbackIdx === idx ? (openFeedbackLabel || openedLabel) : openLabel"></span>
    </button>
    @else
    <button type="button"
            @click="doPasteCurrentItem($event)"
            :aria-label="currentItemPasteFeedback === 'pasted' && currentItemPasteField === 'url' ? pastedLabel : pasteLabel"
            class="hover:text-slate-500 hover:underline focus:outline-none focus:underline py-2 -my-1">
        <span x-text="currentItemPasteFeedback === 'pasted' && currentItemPasteField === 'url' ? pastedLabel : pasteLabel"></span>
    </button>
    <span class="text-slate-300">|</span>
    <button type="button"
            @click="doOpenCurrentItem()"
            :aria-label="currentItemPasteFeedback === 'opened' ? openedLabel : openLabel"
            class="hover:text-slate-500 hover:underline focus:outline-none focus:underline py-2 -my-1">
        <span x-text="currentItemPasteFeedback === 'opened' ? openedLabel : openLabel"></span>
    </button>
    @endif
</span>
