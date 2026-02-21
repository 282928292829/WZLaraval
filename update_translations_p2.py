import json

def update_lang(file_path, new_keys):
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            data = json.load(f)
    except Exception:
        data = {}
        
    updated = False
    for k, v in new_keys.items():
        if k not in data or data[k] == "" or data[k] != v:
            data[k] = v
            updated = True
            
    if updated:
        with open(file_path, 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False, indent=4)
            f.write('\n')
        print(f"Updated {file_path}")
    else:
        print(f"No changes for {file_path}")

en_keys = {
    "Showing": "Showing",
    "of": "of",
    "Trusted Service": "Trusted Service",
    "Paste a product link, or describe it if you don't have one": "Paste a product link, or describe it if you don't have one",
    "Start Order": "Start Order",
    "Or order on WhatsApp": "Or order on WhatsApp",
    "Why Wasetzon?": "Why Wasetzon?",
    "or": "or",
    "Close": "Close",
    "English": "English",
    "Arabic": "عربي"
}

ar_keys = {
    "Showing": "عرض",
    "of": "من",
    "Trusted Service": "خدمة موثوقة",
    "Paste a product link, or describe it if you don't have one": "الصق رابط المنتج، أو وصفه إذا لم يكن لديك رابط",
    "Start Order": "ابدأ الطلب",
    "Or order on WhatsApp": "أو اطلب على الواتساب",
    "Why Wasetzon?": "لماذا وسيط زون؟",
    "or": "أو",
    "Close": "إغلاق",
    "English": "English",
    "Arabic": "عربي"
}

update_lang('/Users/abdul/Desktop/Wasetzon/wasetzonlaraval/lang/en.json', en_keys)
update_lang('/Users/abdul/Desktop/Wasetzon/wasetzonlaraval/lang/ar.json', ar_keys)
