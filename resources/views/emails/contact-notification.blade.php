پیام جدید از فرم تماس
======================
نام: {{ $contact->name }}
ایمیل: {{ $contact->email }}
تلفن: {{ $contact->phone ?? '-' }}
شرکت: {{ $contact->company ?? '-' }}
موضوع: {{ $contact->subject }}
پیام:
{{ $contact->message }}
IP: {{ $contact->ip_address }}
زمان: {{ $contact->created_at }}
