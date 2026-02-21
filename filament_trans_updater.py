import os
import re
import json

files = [
    "app/Filament/Pages/TranslationsPage.php",
    "app/Filament/Resources/Content/PageResource.php",
    "app/Filament/Resources/Blog/PostResource.php",
    "app/Filament/Resources/Blog/PostCommentResource.php",
    "app/Filament/Resources/Blog/PostCategoryResource.php",
    "app/Filament/Resources/Users/UserResource.php",
    "app/Filament/Resources/Users/Pages/EditUser.php",
]

base_path = "/Users/abdul/Desktop/Wasetzon/wasetzonlaraval"
en_path = os.path.join(base_path, "lang/en.json")
ar_path = os.path.join(base_path, "lang/ar.json")

extracted_strings = set()

def wrap_string(match):
    prefix = match.group(1)
    string_val = match.group(2)
    suffix = match.group(3)
    if string_val.startswith("__("):
        return match.group(0) # already wrapped
    extracted_strings.add(string_val)
    return f"{prefix}__('{string_val}'){suffix}"

patterns_to_wrap = [
    r"(->label\(\s*')([^']+)('\s*\))",
    r"(->helperText\(\s*')([^']+)('\s*\))",
    r"(->placeholder\(\s*')([^']+)('\s*\))",
    r"(->trueLabel\(\s*')([^']+)('\s*\))",
    r"(->falseLabel\(\s*')([^']+)('\s*\))",
    r"(Section::make\(\s*')([^']+)('\s*\))",
    r"(->title\(\s*')([^']+)('\s*\))",
    r"(->description\(\s*')([^']+)('\s*\))",
]

for file_rel in files:
    filepath = os.path.join(base_path, file_rel)
    if not os.path.exists(filepath):
        print(f"Skipping missing: {filepath}")
        continue
    
    with open(filepath, "r", encoding="utf-8") as f:
        content = f.read()

    # Generic method calls
    for pat in patterns_to_wrap:
        content = re.sub(pat, wrap_string, content)

    # Convert static properties
    nav_label_pat = r"protected static \?string \$navigationLabel = '([^']+)';"
    if re.search(nav_label_pat, content):
        match_str = re.search(nav_label_pat, content).group(0)
        val = re.search(nav_label_pat, content).group(1)
        extracted_strings.add(val)
        replacement = f"public static function getNavigationLabel(): string\n    {{\n        return __('{val}');\n    }}"
        content = content.replace(match_str, replacement)

    nav_group_pat = r"protected static string\|\\?UnitEnum\|null \$navigationGroup = '([^']+)';"
    if re.search(nav_group_pat, content):
        match_str = re.search(nav_group_pat, content).group(0)
        val = re.search(nav_group_pat, content).group(1)
        extracted_strings.add(val)
        replacement = f"public static function getNavigationGroup(): ?string\n    {{\n        return __('{val}');\n    }}"
        content = content.replace(match_str, replacement)

    title_pat = r"protected static \?string \$title = '([^']+)';"
    if re.search(title_pat, content):
        match_str = re.search(title_pat, content).group(0)
        val = re.search(title_pat, content).group(1)
        extracted_strings.add(val)
        replacement = f"public function getTitle(): string|\\Illuminate\\Contracts\\Support\\Htmlable\n    {{\n        return __('{val}');\n    }}"
        content = content.replace(match_str, replacement)

    # Hardcoded assoc values (like 'draft' => 'Draft')
    assocs = [
        (r"('draft'\s*=>\s*)'Draft'", r"\1__('Draft')"),
        (r"('published'\s*=>\s*)'Published'", r"\1__('Published')"),
        (r"('pending'\s*=>\s*)'Pending'", r"\1__('Pending')"),
        (r"('approved'\s*=>\s*)'Approved'", r"\1__('Approved')"),
        (r"('spam'\s*=>\s*)'Spam'", r"\1__('Spam')"),
    ]
    for search_pat, replacement in assocs:
        if re.search(search_pat, content):
            content = re.sub(search_pat, replacement, content)
            str_val = replacement.replace(r"\1__('", "").replace("')", "")
            extracted_strings.add(str_val)

    # Some ternary ops
    if "'Reply' : 'Top-level'" in content:
        content = content.replace("'Reply' : 'Top-level'", "__('Reply') : __('Top-level')")
        extracted_strings.update(['Reply', 'Top-level'])
        
    if "? 'Unban' : 'Ban'" in content:
        content = content.replace("? 'Unban' : 'Ban'", "? __('Unban') : __('Ban')")
        extracted_strings.update(['Unban', 'Ban'])

    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)

print(f"Extracted {len(extracted_strings)} strings.")

# Load JSON trans
with open(en_path, "r", encoding="utf-8") as f:
    en_dict = json.load(f)
with open(ar_path, "r", encoding="utf-8") as f:
    ar_dict = json.load(f)

for s in sorted(list(extracted_strings)):
    if s not in en_dict:
        en_dict[s] = s
    if s not in ar_dict:
        ar_dict[s] = s

# Save JSON
with open(en_path, "w", encoding="utf-8") as f:
    json.dump(en_dict, f, ensure_ascii=False, indent=4)
with open(en_path, "a", encoding="utf-8") as f:
    f.write("\n")
    
with open(ar_path, "w", encoding="utf-8") as f:
    json.dump(ar_dict, f, ensure_ascii=False, indent=4)
with open(ar_path, "a", encoding="utf-8") as f:
    f.write("\n")
