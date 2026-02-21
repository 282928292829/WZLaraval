import sys, os, re, json, string

def slugify(text):
    text = re.sub(r'<[^>]+>', '', text)
    text = text.translate(str.maketrans('', '', string.punctuation))
    words = text.lower().split()
    return "_".join(words[:5])

files = {
    "resources/views/pages/calculator.blade.php": "calc",
    "resources/views/pages/shipping_calculator.blade.php": "shipping",
    "resources/views/pages/membership.blade.php": "membership",
    "resources/views/pages/payment_methods.blade.php": "payment",
    "resources/views/pages/faq.blade.php": "faq",
    "resources/views/pages/testimonials.blade.php": "testimonials",
    "resources/views/blog/show.blade.php": "blog_show",
    "resources/views/blog/index.blade.php": "blog_idx",
    "resources/views/orders/staff.blade.php": "staff",
}

regex = re.compile(r"app\(\)->getLocale\(\)\s*==(?:=)?\s*['\"]ar['\"]\s*\?\s*(['\"])(.*?)(?<!\\)\1\s*:\s*(['\"])(.*?)(?<!\\)\3", re.DOTALL)

try:
    with open('lang/en.json', 'r', encoding='utf-8') as f:
        en_lang = json.load(f)
    with open('lang/ar.json', 'r', encoding='utf-8') as f:
        ar_lang = json.load(f)
except Exception as e:
    print(f"Error loading JSON files: {e}")
    sys.exit(1)

added_keys = []

for filepath, prefix in files.items():
    if not os.path.exists(filepath):
        print(f"File {filepath} not found!")
        continue
    
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    def replacer(match):
        q_ar = match.group(1)
        ar_text = match.group(2)
        q_en = match.group(3)
        en_text = match.group(4)
        
        ar_clean = ar_text.replace('\\' + q_ar, q_ar)
        en_clean = en_text.replace('\\' + q_en, q_en)

        slug = slugify(en_clean)
        if not slug:
            slug = "text"
            
        key = f"{prefix}.{slug}"
        original_key = key
        counter = 1
        
        while key in en_lang and en_lang[key] != en_clean:
            counter += 1
            key = f"{original_key}_{counter}"
            
        en_lang[key] = en_clean
        ar_lang[key] = ar_clean
        
        added_keys.append(key)
        
        return f"__('{key}')"

    new_content, count = regex.subn(replacer, content)
    print(f"Replaced {count} instances in {filepath}")
    
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)

with open('lang/en.json', 'w', encoding='utf-8') as f:
    json.dump(en_lang, f, ensure_ascii=False, indent=4)

with open('lang/ar.json', 'w', encoding='utf-8') as f:
    json.dump(ar_lang, f, ensure_ascii=False, indent=4)

print(f"\nSuccessfully added {len(added_keys)} keys.")
