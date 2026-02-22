# Prompt: Audit Laravel Pages & Blog Posts vs WordPress

Use this prompt to check — **read-only, no changes** — whether each Laravel page and blog post matches its WordPress counterpart. Report any mismatches.

---

## Task

Compare every Laravel page and blog post against its WordPress source. **Do not write, edit, or change any file.** Only read and report.

For each item, output one of:
- ✅ **Match** — content is ~100% the same
- ⚠️ **Mismatch** — list exactly what is missing, extra, or different (headings, paragraphs, list items, button labels)

---

## Paths

| Source | Path |
|--------|------|
| **WordPress theme** | `Wordpress/pwa3/app/public/wp-content/themes/wasetzon-modern/` |
| **WordPress DB dump** | `Wordpress/pwa3/app/sql/local.sql` (search `wp_posts`) |
| **Laravel PageSeeder** | `wasetzonlaraval/database/seeders/PageSeeder.php` |
| **Laravel page Blades** | `wasetzonlaraval/resources/views/pages/` |
| **Laravel blog Blades** | `wasetzonlaraval/resources/views/blog/` |
| **Laravel translations** | `wasetzonlaraval/lang/ar.json` + `lang/en.json` |

---

## Pages to Audit

| Laravel slug | WordPress template | WP post_name |
|---|---|---|
| how-to-order | `template-how-to-order.php` | how-to-order |
| faq | `template-faq.php` | faq |
| payment-methods | `template-payment-methods.php` | payment-methods |
| refund-policy | `page-refund-policy.php` | refund-policy |
| testimonials | `template-testimonials.php` | testimonials |
| calculator | `calculator.php` | calculator |
| shipping-calculator | `shipping.php` | shipping-calculator |
| membership | (default page template) | membership |
| wasetamazon-to-wasetzon | `page-wasetamazon-to-wasetzon.php` | wasetamazon-to-wasetzon |
| terms-and-conditions | (db content only) | terms-and-conditions |
| privacy-policy | (db content only) | privacy-policy |

Also audit all **blog posts**: find every `post_type = 'post'` and `post_status = 'publish'` row in `wp_posts`, then find the matching Laravel blog post (by slug) in the `posts` table / blog Blade views.

---

## How to Audit Each Page

### Step 1 — Extract WordPress text
1. Read the WordPress template file (if one exists) — extract all visible user-facing text: headings, paragraphs, list items, button labels. Ignore PHP, HTML tags, CSS, SVG.
2. Find the `wp_posts` row where `post_name = '{slug}'` and `post_type = 'page'` (or `'post'` for blog). Extract `post_title` and `post_content` (strip HTML/Gutenberg block syntax to get plain text).
3. Combined WordPress content = `post_title` + `post_content` (db) + hardcoded template text.

### Step 2 — Extract Laravel text
1. For **PageSeeder pages**: read `body_ar` / `body_en` and `title_ar` / `title_en` from `PageSeeder.php` for that slug.
2. For **template-based pages** (where body = a Blade partial reference): read the corresponding Blade file in `resources/views/pages/`. Resolve any `__('key')` calls via `lang/ar.json` and `lang/en.json` to get the actual strings.
3. For **blog posts**: read the post body from the `posts` table seeder or Blade view.

### Step 3 — Compare
- Compare visible text only (ignore HTML structure, CSS classes, PHP logic).
- Flag any heading, paragraph, list item, or button label that is:
  - **Missing** in Laravel but present in WordPress
  - **Extra** in Laravel but not in WordPress
  - **Different** (paraphrased, truncated, or translated differently)

---

## Output Format

Print a section for each page/post:

```
## [slug]
Status: ✅ Match | ⚠️ Mismatch

Mismatches:
- [MISSING] "text that exists in WP but not in Laravel"
- [EXTRA]   "text in Laravel not in WP"
- [DIFFERS] WP: "original text" → Laravel: "different text"
```

After all items, print a **Summary** table:

```
| Slug | Status |
|------|--------|
| how-to-order | ✅ |
| faq | ⚠️ |
| ... | ... |
```

---

## Rules

1. **Read only — make zero changes to any file.**
2. Check all pages in the mapping table plus all published WP blog posts.
3. If a Laravel page or blog post is entirely missing (no seeder entry, no Blade, no DB row), report it as ⚠️ **Missing entirely**.
4. If a WordPress template file does not exist for a slug, note it and rely on `wp_posts` content only.
5. If `wp_posts` has no row for a slug, note it and rely on the template file only.
6. Do not guess or hallucinate — if you cannot find the source, say so explicitly.
7. Arabic and English are both checked — report mismatches in either language.
