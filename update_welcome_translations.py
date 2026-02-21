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
    "Shop from :store worldwide": "Shop from :store worldwide",
    "any store": "any store",
    "Send us the product links you want to buy. We handle the purchase, packaging, and shipping straight to your door — from Amazon and all global stores.": "Send us the product links you want to buy. We handle the purchase, packaging, and shipping straight to your door — from Amazon and all global stores.",
    "How it works": "How it works",
    "Three simple steps and your order is on its way": "Three simple steps and your order is on its way",
    "Send the product link": "Send the product link",
    "Copy the product URL from any store and paste it into the order form.": "Copy the product URL from any store and paste it into the order form.",
    "We handle the purchase": "We handle the purchase",
    "Our team reviews your order and purchases the items on your behalf.": "Our team reviews your order and purchases the items on your behalf.",
    "We ship to you": "We ship to you",
    "We ship your order directly to your address with full tracking.": "We ship your order directly to your address with full tracking.",
    "Remove excess packaging": "Remove excess packaging",
    "We reduce package size by removing excess packaging to lower your shipping costs.": "We reduce package size by removing excess packaging to lower your shipping costs.",
    "Based in Delaware — tax free": "Based in Delaware — tax free",
    "Our warehouse in Delaware is fully exempt from US sales tax.": "Our warehouse in Delaware is fully exempt from US sales tax.",
    "Save up to 70%": "Save up to 70%",
    "We consolidate your orders from different stores into one package and save you a fortune.": "We consolidate your orders from different stores into one package and save you a fortune.",
    "90 days free storage": "90 days free storage",
    "We give you the freedom to shop for 3 months with free and secure storage for your products.": "We give you the freedom to shop for 3 months with free and secure storage for your products.",
    "Ready to start? Place your order now": "Ready to start? Place your order now",
    "Create a free account and place your first order in minutes.": "Create a free account and place your first order in minutes.",
    "You're Offline": "You're Offline",
    "Check your internet connection and try again.": "Check your internet connection and try again.",
    "Back to Home": "Back to Home",
    "Switch language text": "🌐 عربي"
}

ar_keys = {
    "Shop from :store worldwide": "اشترِ من :store حول العالم",
    "any store": "أي متجر",
    "Send us the product links you want to buy. We handle the purchase, packaging, and shipping straight to your door — from Amazon and all global stores.": "أرسل لنا روابط المنتجات التي ترغب بشرائها، واترك الشراء والتغليف والشحن لباب بيتك علينا. نشتري من أمازون وجميع المواقع العالمية.",
    "How it works": "كيف نعمل؟",
    "Three simple steps and your order is on its way": "ثلاث خطوات بسيطة وطلبك في طريقه إليك",
    "Send the product link": "أرسل رابط المنتج",
    "Copy the product URL from any store and paste it into the order form.": "انسخ رابط المنتج من أي متجر وأضفه إلى نموذج الطلب.",
    "We handle the purchase": "نتولى الشراء",
    "Our team reviews your order and purchases the items on your behalf.": "فريقنا يراجع طلبك ويشتري المنتجات نيابةً عنك.",
    "We ship to you": "نشحن إليك",
    "We ship your order directly to your address with full tracking.": "نشحن طلبك مباشرةً إلى عنوانك مع متابعة كاملة.",
    "Remove excess packaging": "إزالة التغليف الزائد",
    "We reduce package size by removing excess packaging to lower your shipping costs.": "نقلل حجم الطرد عبر إزالة التغليف الزائد لخفض تكاليف الشحن.",
    "Based in Delaware — tax free": "مقرنا في ديلاوير — بدون ضريبة",
    "Our warehouse in Delaware is fully exempt from US sales tax.": "مستودعنا في ولاية ديلاوير معفى تماماً من ضرائب المبيعات الأمريكية.",
    "Save up to 70%": "وفّر حتى 70%",
    "We consolidate your orders from different stores into one package and save you a fortune.": "نجمع طلباتك من متاجر مختلفة في طرد واحد ونوفر عليك مبالغ طائلة.",
    "90 days free storage": "90 يوم تخزين مجاني",
    "We give you the freedom to shop for 3 months with free and secure storage for your products.": "نمنحك حرية التسوق على مدار 3 أشهر مع تخزين مجاني وآمن لمنتجاتك.",
    "Ready to start? Place your order now": "جاهز للبدء؟ قدّم طلبك الآن",
    "Create a free account and place your first order in minutes.": "سجّل حساباً مجانياً وقدّم طلبك الأول في دقائق.",
    "You're Offline": "لا يوجد اتصال بالإنترنت",
    "Check your internet connection and try again.": "تحقق من اتصالك بالإنترنت وحاول مرة أخرى.",
    "Back to Home": "العودة للرئيسية",
    "Switch language text": "🌐 English"
}

update_lang('/Users/abdul/Desktop/Wasetzon/wasetzonlaraval/lang/en.json', en_keys)
update_lang('/Users/abdul/Desktop/Wasetzon/wasetzonlaraval/lang/ar.json', ar_keys)
