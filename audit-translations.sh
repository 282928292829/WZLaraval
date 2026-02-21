#!/usr/bin/env bash
# Audit for hardcoded English strings not wrapped in __()
# Run from project root: bash audit-translations.sh

RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "=== Translation Audit ==="
echo ""

echo -e "${YELLOW}[Blade] Hardcoded text (not using __())${NC}"
rg --type-add 'blade:*.blade.php' --type blade \
  -n \
  '>[A-Z][a-zA-Z ]{3,}<|placeholder="[A-Za-z]|title="[A-Za-z]' \
  resources/views/ \
  | grep -v '__(' | grep -v '{{--' | grep -v '@' | head -40

echo ""
echo -e "${YELLOW}[PHP/Filament] ->label() without __()${NC}"
rg -n -- "->label\('[A-Z]" app/Filament/ | grep -v '__(' | head -40

echo ""
echo -e "${YELLOW}[PHP/Filament] ->title() without __()${NC}"
rg -n -- "->title\('[A-Z]" app/Filament/ | grep -v '__(' | head -40

echo ""
echo -e "${YELLOW}[PHP/Filament] ->helperText() without __()${NC}"
rg -n -- "->helperText\('[A-Z]" app/Filament/ | grep -v '__(' | head -40

echo ""
echo -e "${YELLOW}[PHP/Filament] ->description() without __()${NC}"
rg -n -- "->description\('[A-Z]" app/Filament/ | grep -v '__(' | head -40

echo ""
echo -e "${YELLOW}[PHP/Filament] ->placeholder() without __()${NC}"
rg -n -- "->placeholder\('[A-Za-z]" app/Filament/ | grep -v '__(' | head -40

echo ""
echo -e "${YELLOW}[PHP/Filament] static navigationLabel/title hardcoded${NC}"
rg -n -- "navigationLabel\s*=\s*'[A-Z]|protected static \?string \\\$title\s*=\s*'[A-Z]" app/Filament/ | head -20

echo ""
echo -e "${YELLOW}[PHP] Notification titles without __()${NC}"
rg -n -- "->title\('[A-Z]" app/ | grep -v '__(' | grep -v 'Filament/Pages/SettingsPage' | head -40

echo ""
echo "=== Done ==="
