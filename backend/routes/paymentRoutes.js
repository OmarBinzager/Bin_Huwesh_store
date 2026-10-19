const express = require('express');
const router = express.Router();
const paymentController = require('../controllers/paymentController');

router.post('/checkout', paymentController.initiatePayment);
router.post('/webhook', paymentController.handleWebhook);
router.get('/points/:userWallet', paymentController.getPointsBalance);

module.exports = router;
