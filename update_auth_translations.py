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
    "Check your email": "Check your email",
    "Enter your credentials to access your account": "Enter your credentials to access your account",
    "Sign in with Google": "Sign in with Google",
    "Don't have an account?": "Don't have an account?",
    "your password": "your password",
    "Choose a new password for your account": "Choose a new password for your account",
    "Create your new account": "Create your new account",
    "Sign up with Google": "Sign up with Google",
    "Account Created Successfully!": "Account Created Successfully!",
    "Welcome to Wasetzon. You will be redirected to your dashboard in": "Welcome to Wasetzon. You will be redirected to your dashboard in",
    "seconds.": "seconds.",
    "Or": "Or",
    "go now": "go now"
}

ar_keys = {
    "Check your email": "تحقق من بريدك الإلكتروني",
    "Enter your credentials to access your account": "أدخل بياناتك للدخول إلى حسابك",
    "Sign in with Google": "تسجيل الدخول بـ Google",
    "Don't have an account?": "ليس لديك حساب؟",
    "your password": "كلمة مرورك",
    "Choose a new password for your account": "أدخل كلمة مرور جديدة لحسابك",
    "Create your new account": "أنشئ حسابك الجديد",
    "Sign up with Google": "التسجيل بـ Google",
    "Account Created Successfully!": "تم إنشاء حسابك بنجاح!",
    "Welcome to Wasetzon. You will be redirected to your dashboard in": "مرحباً بك في وسيط زون. سيتم تحويلك إلى لوحة التحكم خلال",
    "seconds.": "ثوان.",
    "Or": "أو",
    "go now": "اذهب الآن"
}

update_lang('/Users/abdul/Desktop/Wasetzon/wasetzonlaraval/lang/en.json', en_keys)
update_lang('/Users/abdul/Desktop/Wasetzon/wasetzonlaraval/lang/ar.json', ar_keys)
