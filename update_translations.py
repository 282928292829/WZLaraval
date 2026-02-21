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
    "blog_show.text": " ",
    "Order": "Order",
    "Orders": "Orders",
    "Filters": "Filters",
    "▲ Hide": "▲ Hide",
    "▼ Filter": "▼ Filter",
    "Search": "Search",
    "Order number...": "Order number...",
    "All Statuses": "All Statuses",
    "Sort": "Sort",
    "Newest first": "Newest first",
    "Oldest first": "Oldest first",
    "Per page": "Per page",
    "Apply": "Apply",
    "Reset": "Reset",
    "No orders match your filters. Try different criteria.": "No orders match your filters. Try different criteria.",
    "Order #": "Order #",
    "Date": "Date",
    "Status": "Status",
    "Items": "Items",
    "Open": "Open",
    "Actions": "Actions"
}

ar_keys = {
    "blog_show.text": "rotate-180",
    "Order": "طلب",
    "Orders": "طلبات",
    "Filters": "الفلاتر",
    "▲ Hide": "▲ إخفاء",
    "▼ Filter": "▼ فلتر",
    "Search": "البحث",
    "Order number...": "رقم الطلب...",
    "All Statuses": "جميع الحالات",
    "Sort": "الترتيب",
    "Newest first": "الأحدث أولاً",
    "Oldest first": "الأقدم أولاً",
    "Per page": "عدد الطلبات بالصفحة",
    "Apply": "تطبيق",
    "Reset": "مسح",
    "No orders match your filters. Try different criteria.": "لا توجد طلبات تطابق البحث. جرّب فلاتر مختلفة.",
    "Order #": "رقم الطلب",
    "Date": "التاريخ",
    "Status": "الحالة",
    "Items": "المنتجات",
    "Open": "فتح",
    "Actions": "الإجراءات"
}

update_lang('/Users/abdul/Desktop/Wasetzon/wasetzonlaraval/lang/en.json', en_keys)
update_lang('/Users/abdul/Desktop/Wasetzon/wasetzonlaraval/lang/ar.json', ar_keys)
