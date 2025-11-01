# 🚀 LaraSocket – وب‌سوکت بومی لاراول

**LaraSocket** یک سرور WebSocket بومی برای **Laravel** است که بدون نیاز به هیچ پکیج خارجی (مثل Pusher، Redis یا Socket.IO) اجرا می‌شود و مستقیماً با ساختار لاراول یکپارچه است.

---

## ✨ ویژگی‌ها

- 🧩 اجرای کاملاً مستقل بدون هیچ وابستگی خارجی
- 🔐 احراز هویت اختیاری با **Sanctum** یا بدون توکن
- ⚙️ محدودیت نرخ ارسال پیام (Rate Limiting) و کنترل تعداد کلاینت‌ها
- 🧾 پشتیبانی از سیستم لاگ لاراول (Log Channels)
- 💡 قابل تنظیم از طریق فایل `config/larasocket.php`
- ⚡️ مناسب برای پروژه‌های داخلی، پنل‌ها، داشبوردها و ارتباط زنده بین کلاینت و سرور

---

## ⚙️ نصب پکیج 

پکیج را به پروژه لاراول خود اضافه کنید:

```bash
composer require bagherkeshmiri/larasocket
```

---

## سپس فایل تنظیمات را منتشر (publish) کنید :

پکیج را به پروژه لاراول خود اضافه کنید:

```bash
composer require bagherkeshmiri/larasocket
```

---

## 🧩 تنظیمات :

در فایل .env مقادیر مورد نیاز را تنظیم کنید:

```bash
LARASOCKET_HOST=127.0.0.1
LARASOCKET_CLIENT_PORT=9000
LARASOCKET_SERVER_PORT=9001
LARASOCKET_AUTH_MODE=sanctum
LARASOCKET_MAX_CLIENTS=200
LARASOCKET_RATE_MESSAGES=20
LARASOCKET_RATE_SECONDS=10
LARASOCKET_LOG_CHANNEL=stack
```
| کلید                       | توضیحات                                                                |
| -------------------------- | ---------------------------------------------------------------------- |
| `LARASOCKET_HOST`          | آدرس یا IP سرور وب‌سوکت (برای همه‌ی شبکه‌ها از `0.0.0.0` استفاده کنید) |
| `LARASOCKET_CLIENT_PORT`   | پورتی که مرورگرها به آن وصل می‌شوند (مثل `ws://127.0.0.1:9000`)        |
| `LARASOCKET_SERVER_PORT`   | پورتی داخلی برای ارسال پیام از طرف لاراول (فقط محلی و امن باشد)        |
| `LARASOCKET_AUTH_MODE`     | حالت احراز هویت (`none` یا `sanctum`)                                  |
| `LARASOCKET_MAX_CLIENTS`   | حداکثر تعداد کاربران متصل هم‌زمان                                      |
| `LARASOCKET_RATE_MESSAGES` | تعداد پیام مجاز هر کلاینت                                              |
| `LARASOCKET_RATE_SECONDS`  | بازه زمانی برای محدودسازی پیام‌ها                                      |
| `LARASOCKET_LOG_CHANNEL`   | کانال لاگ لاراول (مثلاً `single` یا `stack`)                           |

---

## 🔐 حالت‌های احراز هویت :

LaraSocket از دو حالت ساده پشتیبانی می‌کند:

| حالت      | توضیحات                                 |
| --------- | --------------------------------------- |
| `none`    | بدون نیاز به توکن (اتصال آزاد)          |
| `sanctum` | اتصال فقط با توکن معتبر Laravel Sanctum |

---

## 🧠 ساخت کلاس اعتبارسنجی توکن (Sanctum) :

منطق بررسی توکن در پروژه شما قرار دارد، نه در پکیج.
در مسیر زیر در پروژه‌ی لاراول خود یک فایل بسازید:

```bash
<?php

namespace App\Support;

use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;

class LaraSocketValidator
{
    public static function check(?string $token): bool
    {
        $mode = config('larasocket.auth_mode');

        // حالت عمومی (بدون احراز)
        if ($mode === 'none') {
            return true;
        }

        // حالت Sanctum
        if ($mode === 'sanctum') {
            if (empty($token)) return false;

            try {
                $accessToken = PersonalAccessToken::findToken($token);
                return $accessToken?->tokenable !== null;
            } catch (\Throwable $e) {
                Log::error('LaraSocket Sanctum token validation failed: ' . $e->getMessage());
                return false;
            }
        }

        return false;
    }
}

```

---

## 🚀 اجرای سرور WebSocket :

برای راه‌اندازی سرور:

```bash
php artisan larasocket:serve
```

برای مشخص کردن host یا port دلخواه:

```bash
php artisan larasocket:serve --host=0.0.0.0 --port=9000
```

در صورت موفقیت، پیغامی مشابه زیر را می‌بینید:

```bash
🚀 Starting LaraSocket WebSocket server on ws://127.0.0.1:9000
Press Ctrl+C to stop the server
```

---

## 🌐 نمونه اتصال از سمت مرورگر :

اگر احراز هویت غیرفعال باشد (LARASOCKET_AUTH_MODE=none):

```bash
const ws = new WebSocket("ws://127.0.0.1:9000");

ws.onopen = () => console.log("اتصال برقرار شد");
ws.onmessage = e => console.log("پیام دریافتی:", e.data);
```

اگر احراز هویت فعال باشد (LARASOCKET_AUTH_MODE=sanctum):

```bash
const token = "توکن_sanctum_کاربر";
const ws = new WebSocket(`ws://127.0.0.1:9000/?token=${token}`);

ws.onopen = () => console.log("اتصال احراز شده با Sanctum");
ws.onmessage = e => console.log("پیام:", e.data);
```

---

## 📡 ارسال پیام از سمت لاراول :

می‌توانید از هر کنترلر یا Job برای ارسال پیام استفاده کنید:

```bash
Route::get('/broadcast', function () {
    $message = json_encode([
        'event' => 'ping',
        'data' => 'سلام از لاراول!'
    ]);

    $socket = stream_socket_client('tcp://127.0.0.1:9001');
    fwrite($socket, $message);
    fclose($socket);

    return 'پیام ارسال شد!';
});
```

---

## 🧰 نکات فنی برای توسعه‌دهندگان :

می‌توانید از هر کنترلر یا Job برای ارسال پیام استفاده کنید:

*   سرور بدون حالت (stateless) است و هر پیام به‌صورت مستقل پردازش می‌شود.
*  رای استفاده در محیط تولید (Production)، پیشنهاد می‌شود پشت Nginx یا Caddy برای پشتیبانی از wss:// قرار گیرد.
*   پورت داخلی (server_port) فقط باید در دسترس خود لاراول باشد.
*   در صورت نیاز به عملکرد بالا، می‌توان چند سرور وب‌سوکت را به‌صورت جداگانه اجرا کرد.

---

## 🧭 نقشه راه (Roadmap) :

می‌توانید از هر کنترلر یا Job برای ارسال پیام استفاده کنید:

*  پشتیبانی از احراز JWT
*  پشتیبانی از Room و کانال‌های مجزا
*  پشتیبانی از SSL/TLS داخلی

---

## 👨‍💻 توسعه دهنده :

باقر کشمیری

📧 bagherkeshmiri@gmail.com

🌐 GitHub: @bagherkeshmiri

---

## 📝 مجوز :

این پکیج تحت لایسنس MIT منتشر شده است و استفاده، تغییر و انتشار آن آزاد است.

---


