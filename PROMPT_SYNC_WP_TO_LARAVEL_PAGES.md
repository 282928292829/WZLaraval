# Prompt: Sync Laravel Pages with WordPress Content

Use this prompt when you need an AI to replace each Laravel page with its WordPress counterpart so the text matches exactly.

---

## Task

Replace each Laravel page with the equivalent content from the new WordPress site. The Laravel page must have **identical text** to:
1. **Hardcoded text** in the WordPress template file
2. **Database content** (`the_content()`) from `wp_posts` for that page

Do not paraphrase, summarize, or add content. Copy text exactly as it appears in WordPress.

---

## Paths

| Source | Path |
|-------|------|
| **WordPress theme** | `Wordpress/pwa3/app/public/wp-content/themes/wasetzon-modern/` |
| **WordPress DB dump** | `Wordpress/pwa3/app/sql/local.sql` (search `wp_posts` for page content) |
| **Laravel PageSeeder** | `wasetzonlaraval/database/seeders/PageSeeder.php` |
| **Laravel page views** | `wasetzonlaraval/resources/views/pages/` (for template-based pages like FAQ, calculator) |

---

## Page Mapping (Laravel slug → WordPress)

| Laravel slug | WordPress template | WP post_name (for DB content) |
|--------------|-------------------|-------------------------------|
| how-to-order | `template-how-to-order.php` | how-to-order |
| faq | `template-faq.php` | faq |
| payment-methods | `template-payment-methods.php` | payment-methods |
| refund-policy | `page-refund-policy.php` | refund-policy |
| testimonials | `template-testimonials.php` | testimonials |
| calculator | `calculator.php` | calculator |
| shipping-calculator | `shipping.php` | shipping-calculator |
| membership | Default page template or custom | membership |
| wasetamazon-to-wasetzon | `page-wasetamazon-to-wasetzon.php` | wasetamazon-to-wasetzon |
| terms-and-conditions | Default page (content from wp_posts only) | terms-and-conditions |
| privacy-policy | Default page (content from wp_posts only) | privacy-policy |

---

## Process for Each Page

### Step 1: Extract WordPress content

1. **Read the template file** at `Wordpress/pwa3/app/public/wp-content/themes/wasetzon-modern/{template}.php`
   - Extract all user-facing text (headings, paragraphs, labels, button text) that is hardcoded in the template
   - Ignore PHP, HTML structure, CSS, and SVG markup — only the visible text

2. **Get database content** (`the_content()`)
   - Search `Wordpress/pwa3/app/sql/local.sql` for `wp_posts` where `post_name = '{slug}'` and `post_type = 'page'`
   - The `post_content` column contains the page body (may be Gutenberg blocks or HTML)
   - Strip HTML tags to get plain text for comparison, or extract the semantic content

3. **Check for conflicts**: If the database content (`the_content`) and the hardcoded template text overlap, contradict each other, or describe the same thing differently, **stop and report this to the user**. List the conflicting passages and ask how they want to resolve it (e.g. use DB only, use template only, or a specific order). Do not guess or pick one silently.

4. **Combine in order**: The WordPress page renders as `the_title()` + `the_content()` + `{template hardcoded sections}`. The Laravel page must contain the same combined text.

### Step 2: Update Laravel (only after Step 1 has no conflicts)

1. **For PageSeeder pages** (body stored in DB):
   - Update `body_ar` and `body_en` in `PageSeeder.php` for that slug
   - Update `title_ar` and `title_en` if they differ from WordPress title
   - Use Tailwind classes for styling (e.g. `text-2xl`, `font-bold`, `rounded-xl`) — do not copy WordPress inline styles

2. **For template-based pages** (body = `faq-template`, `calculator-template`, etc.):
   - The body in PageSeeder references a Blade partial (e.g. `resources/views/pages/faq.blade.php`)
   - Update that Blade file to match the WordPress template content
   - Ensure all text is wrapped in `__()` for the bilingual rule — use translation keys from `lang/ar.json` and `lang/en.json`

### Step 3: Verify

- Compare the final Laravel page text (Arabic and English) with the WordPress page text
- Every heading, paragraph, list item, and button label must match exactly
- No missing sections, no extra text, no paraphrasing

---

## Rules

1. **One page per run** — process a single page, then wait for user confirmation before the next
2. **Report conflicts** — if WP database content and hardcoded template text conflict or overlap, tell the user and ask how to resolve
3. **Exact text only** — do not paraphrase or "improve" wording
4. **Bilingual** — Laravel uses `__()` and `lang/ar.json` / `lang/en.json`; page body HTML can contain Arabic/English directly if it comes from the DB/Seeder
5. **Styling** — use Tailwind classes (Laravel convention), not WordPress CSS
6. **Links** — use Laravel routes: `/new-order`, `/orders`, `/pages/{slug}`; WhatsApp: `https://wa.me/00966556063500?text=...`
7. **No hallucination** — if WordPress content is missing or unclear, say so; do not invent text

---

## Example: How-to-Order Page

**WordPress template** (`template-how-to-order.php`) hardcoded text:

- "طرق الطلب المتاحة"
- "نوفر لك عدة طرق سهلة ومرنة لتقديم طلبك:"
- "الطلب عبر الموقع (الطريقة الموصى بها)"
- "استخدم موقعنا الإلكتروني للحصول على تجربة طلب سلسة ومنظمة:"
- "تصفح المنتجات: استعرض جداول المنتجات والأسعار والمعلومات التفصيلية بكل سهولة"
- "أضف إلى الطلب: اختر المنتجات التي تحتاجها مباشرة من الموقع"
- "رفع ملف Excel: إذا كنت تفضل ذلك، يمكنك رفع ملف Excel يحتوي على جميع المنتجات التي تحتاجها"
- "إرسال الطلب: أكمل معلومات الطلب وأرسله مباشرة"
- "ابدأ طلبك الآن"
- "الطلب عبر واتساب"
- "تواصل معنا مباشرة عبر واتساب:"
- "رفع ملف Excel" (info box title)
- "يمكنك تحضير قائمة المنتجات في ملف Excel ورفعه مباشرة عبر الموقع أو إرساله عبر واتساب. هذا يوفر عليك الوقت خاصة عند طلب كميات كبيرة."
- "لماذا نوصي بالطلب عبر الموقع؟"
- "سهولة التصفح" / "توفير الوقت" / "رفع ملفات Excel" / "متابعة الطلب"
- "جاهز لتقديم طلبك؟"
- "اختر الطريقة الأنسب لك وابدأ الآن"
- "طلب عبر الموقع" / "طلب عبر واتساب"

**WordPress database** (`the_content`): The `wp_posts` row for `post_name = 'how-to-order'` contains the intro/body content (e.g. "طريقة الطلب عبر وسيط زون", "خطوات الطلب", etc.). Include that content before or after the template sections as appropriate.

**Laravel output**: The combined content must appear on `http://wasetzonlaraval.test/pages/how-to-order` with identical text.

---

## Quick Start

Copy this into your prompt:

```
Sync the Laravel page [PAGE_SLUG] with its WordPress counterpart. Follow PROMPT_SYNC_WP_TO_LARAVEL_PAGES.md.

- Process ONLY this one page. Wait for my confirmation before doing any other page.
- If WP database content and hardcoded template text conflict, tell me and ask how to resolve.
- 1. Read template: Wordpress/pwa3/app/public/wp-content/themes/wasetzon-modern/[TEMPLATE_FILE]
- 2. Get post content from wp_posts in Wordpress/pwa3/app/sql/local.sql where post_name = '[PAGE_SLUG]'
- 3. Update Laravel: database/seeders/PageSeeder.php (or the Blade template if body is a template reference)
- 4. Ensure text matches exactly — no paraphrasing or additions.
- 5. Run: php artisan db:seed --class=PageSeeder
```

Replace `[PAGE_SLUG]` and `[TEMPLATE_FILE]` with the correct values from the mapping table.
