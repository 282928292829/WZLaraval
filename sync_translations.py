import os, re, json

lang_dir = 'lang'
en_file = os.path.join(lang_dir, 'en.json')
ar_file = os.path.join(lang_dir, 'ar.json')

with open(en_file, 'r', encoding='utf-8') as f:
    en_dict = json.load(f)

with open(ar_file, 'r', encoding='utf-8') as f:
    ar_dict = json.load(f)

# Find all __() occurrences in resources/views and app/
regex = re.compile(r"__\(\s*(['\"])(.*?)(?<!\\)\1\s*\)")

found_keys = set()

for root_dir in ['resources/views', 'app']:
    for dirpath, dirnames, filenames in os.walk(root_dir):
        for filename in filenames:
            if filename.endswith('.php'):
                file_path = os.path.join(dirpath, filename)
                with open(file_path, 'r', encoding='utf-8') as f:
                    content = f.read()
                    matches = regex.findall(content)
                    for match in matches:
                        key = match[1].replace("\\'", "'").replace('\\"', '"')
                        found_keys.add(key)

added_en = 0
added_ar = 0

for key in found_keys:
    if key not in en_dict:
        en_dict[key] = key
        added_en += 1
    if key not in ar_dict:
        # For missing Arabic, just copy the English key so it's registered
        ar_dict[key] = key
        added_ar += 1

with open(en_file, 'w', encoding='utf-8') as f:
    json.dump(en_dict, f, ensure_ascii=False, indent=4)

with open(ar_file, 'w', encoding='utf-8') as f:
    json.dump(ar_dict, f, ensure_ascii=False, indent=4)

print(f"Added {added_en} missing keys to en.json")
print(f"Added {added_ar} missing keys to ar.json")
