import re

# 1. faq.blade.php
with open('resources/views/pages/faq.blade.php', 'r', encoding='utf-8') as f:
    faq_content = f.read()

faq_content = faq_content.replace('الاسئلة الشائعة والمتكررة عن أمازون و وسيط أمازون', "{{ __('faq.subtitle') }}")

# we need to replace the faqs array. It's too complex to regex, let's just use string replacement for the PHP block
faqs_php = """            $faqs = [
                [
                    'q' => __('faq.q1'),
                    'a' => __('faq.a1'),
                ],
                [
                    'q' => __('faq.q2'),
                    'a' => __('faq.a2'),
                ],
                [
                    'q' => __('faq.q3'),
                    'a' => __('faq.a3'),
                ],
                [
                    'q' => __('faq.q4'),
                    'a' => __('faq.a4'),
                ],
                [
                    'q' => __('faq.q5'),
                    'a' => __('faq.a5'),
                ],
                [
                    'q' => __('faq.q6'),
                    'a' => __('faq.a6'),
                ],
                [
                    'q' => __('faq.q7'),
                    'a' => __('faq.a7'),
                ],
                [
                    'q' => __('faq.q8'),
                    'a' => __('faq.a8'),
                ],
                [
                    'q' => __('faq.q9'),
                    'a' => __('faq.a9'),
                ],
                [
                    'q' => __('faq.q10'),
                    'a' => __('faq.a10'),
                ],
                [
                    'q' => __('faq.q11'),
                    'a' => __('faq.a11'),
                ],
                [
                    'q' => __('faq.q12'),
                    'a' => __('faq.a12'),
                ],
                [
                    'q' => __('faq.q13'),
                    'a' => __('faq.a13'),
                ],
                [
                    'q' => __('faq.q14'),
                    'a' => __('faq.a14'),
                ],
                [
                    'q' => __('faq.q15'),
                    'a' => __('faq.a15'),
                ],
                [
                    'q' => __('faq.q16'),
                    'a' => __('faq.a16'),
                ],
                [
                    'q' => __('faq.q17'),
                    'a' => __('faq.a17'),
                ],
                [
                    'q' => __('faq.q18'),
                    'a' => __('faq.a18'),
                ],
                [
                    'q' => __('faq.q19'),
                    'a' => __('faq.a19'),
                ],
                [
                    'q' => __('faq.q20'),
                    'a' => __('faq.a20'),
                ],
                [
                    'q' => __('faq.q21'),
                    'a' => __('faq.a21'),
                ],
            ];"""

# Replace the array definition
start_idx = faq_content.find('$faqs = [')
end_idx = faq_content.find('];\n            @endphp') + 2
if start_idx != -1 and end_idx != -1:
    faq_content = faq_content[:start_idx] + faqs_php + faq_content[end_idx:]

with open('resources/views/pages/faq.blade.php', 'w', encoding='utf-8') as f:
    f.write(faq_content)

print("faq.blade.php refactored.")

