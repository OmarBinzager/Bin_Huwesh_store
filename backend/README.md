# Shabakati Backend

نظام خلفي (Backend) لإدارة بطاقات Wi-Fi المحلية في اليمن ونظام دفع مع نقاط ولاء.

## الملفات الرئيسية

- `package.json`: إعداد المشروع واعتماداتنا.
- `.env`: إعدادات البيئة السرية.
- `config/db.js`: اتصال MongoDB.
- `models/NetworkCard.js`: نموذج بطاقات الإنترنت.
- `models/UserWallet.js`: نموذج نقاط ولاء المستخدم.
- `models/Transaction.js`: نموذج المعاملات.
- `controllers/paymentController.js`: منطق الدفع والويب هوك وتحديث النقاط.
- `routes/paymentRoutes.js`: مسارات API.
- `server.js`: نقطة التشغيل الأساسية.

## الإعداد

1. انسخ `.env.example` إلى `.env`.
2. عدّل القيم التالية في `.env`:
   - `PORT`
   - `MONGO_URI`
   - `MERCHANT_ID`
   - `WALLET_API_KEY`
   - `WALLET_SECRET`
   - `CALLBACK_SECRET`

## تشغيل محلي

```bash
cd C:\src\shabakati-backend
npm install
npm start
```

## واجهات API

- `POST /api/payment/checkout`
  - body: `{ "amount": 100, "userWallet": "00967123456789", "networkOwnerId": "owner-1" }`

- `POST /api/payment/webhook`
  - body: `{ "transactionId": "...", "status": "success", "amount": 100, "userWallet": "00967123456789", "hash": "..." }`

- `GET /api/payment/points/:userWallet`
  - مثال: `/api/payment/points/00967123456789`

## ملاحظات

- رمز التحقق `CALLBACK_SECRET` يستخدم للتحقق من webhook.
- يتم منح 1 نقطة لكل 100 ريال يمني يتم إنفاقها.
- المعاملات تحفظ بنظام `pending`, `success`, `failed`.
- يمكنك تعديل `simulateSendToWallet` لاحقاً لتتكامل مع بوابة دفع حقيقية.
