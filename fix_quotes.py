import os

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

for file_rel in files:
    filepath = os.path.join(base_path, file_rel)
    if not os.path.exists(filepath):
        continue
    
    with open(filepath, "r", encoding="utf-8") as f:
        content = f.read()

    # The regex wrapped "->label('Name')" -> "->label('__('Name')')"
    # Which we want to be "->label(__('Name'))"
    content = content.replace("'__('", "__('")
    content = content.replace("')'", "')")

    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)
