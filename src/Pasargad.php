<?php

namespace Pasrgad;

use Pasargad\Classes\AbstractPayment;
use Pasargad\Classes\RequestBuilder;
use Pasargad\Classes\RSA\RSAProcessor;

class Pasargad extends AbstractPayment
{
    /** @var RequestBuilder $api */
    private $api;

    /** @var string $token */
    private $token = null;

    /**
     * Address of gateway for getting token
     * @var string
     */
    const URL_GET_TOKEN = "https://pep.shaparak.ir/Api/v1/Payment/GetToken";
 
    /**
     * Redirect User with token to this URL
     * e.q: https://pep.shaparak.ir/payment.aspx?n=Token
     */
    const URL_PAYMENT_GATEWAY = "https://pep.shaparak.ir/payment.aspx";
    const URL_CHECK_TRANSACTION = 'https://pep.shaparak.ir/Api/v1/Payment/CheckTransactionResult';
    const URL_VERIFY_PAYMENT = 'https://pep.shaparak.ir/Api/v1/Payment/VerifyPayment';
    const URL_REFUND = 'https://pep.shaparak.ir/Api/v1/Payment/RefundPayment';

    /**
     * Pasargad Constructor
     * @var int $merchantCode
     * @var int $terminalCode
     * @var string $redirectAddress
     * @var string $certificateFile
     * @var string $action
     */
    public function __construct($merchantCode, $terminalCode, $redirectAddress, $certificateFile, $action = "1003")
    {
        $this->merchantId = $merchantCode;
        $this->terminalId = $terminalCode;
        $this->redirectUrl = $redirectAddress;
        $this->certificate = $certificateFile;
        $this->action = $action;
        $this->api = new RequestBuilder();
    }

    /**
     * Sign data using RSA key
     * @var array $data
     */
    private function sign($data)
    {
        $processor = new RSAProcessor($this->certificate);
        return base64_encode($processor->sign(sha1($data, true)));
    }

    /**
     * Get Token to prepare user for redirecting to payment gateway
     */
    public function getToken()
    {
        $params["amount"] = $this->getAmount();
        $params["invoiceNumber"] = $this->getInvoiceNumber();
        $params["invoiceDate"] = $this->getInvoiceDate();
        $params['action'] = $this->getAction();
        $params['merchantCode'] = $this->getMerchantId();
        $params['terminalCode'] = $this->getTerminalId();
        $params['redirectAddress'] = $this->getRedirectUrl();
        $params['timeStamp'] = date("Y/m/d H:i:s");
        $sign = $this->sign(json_encode($params));
        $this->token = $this->api->send(static::URL_GET_TOKEN, RequestBuilder::POST, ["Sign" => $sign], $params, true);
        return $this->token;
    }

    /**
     * Redirect User to Gateway
     */
    public function redirect()
    {
        if (!$this->token) {
            $this->token = $this->getToken();
        }
        return static::URL_PAYMENT_GATEWAY . "?n=" . $this->token;
    }

    /**
     * Verify Payment
     */
    public function verifyPayment()
    {
        $params['amount'] = $this->getAmount();
        $params['invoiceNumber'] = $this->getInvoiceNumber();
        $params['invoiceDate'] = $this->getInvoiceDate();
        $params['merchantCode'] = $this->getMerchantId();
        $params['terminalCode'] = $this->getTerminalId();
        $params['timeStamp'] = date("Y/m/d H:i:s");
        $sign = $this->sign(json_encode($params));
        $response = $this->api->send(static::URL_VERIFY_PAYMENT,RequestBuilder::POST,["Sign" => $sign],$params, true);
        return $response;
    }

    /**
     * Check Transaction with referenceId
     */
    public function checkTransaction()
    {
        $params['invoiceNumber'] = $this->getInvoiceNumber();
        $params['invoiceDate'] = $this->getInvoiceDate();
        $params['merchantCode'] = $this->getMerchantId();
        $params['terminalCode'] = $this->getTerminalId();
        $params['transactionReferenceID'] = $this->getTransactionReferenceId();
        $response = $this->api->send(static::URL_CHECK_TRANSACTION,RequestBuilder::POST,[],$params, true);
        return $response;
    }

    /**
     * Refund Payment
     */
    public function refundPayment()
    {
        $params['invoiceNumber'] = $this->getInvoiceNumber();
        $params['invoiceDate'] = $this->getInvoiceDate();
        $params['merchantCode'] = $this->getMerchantId();
        $params['terminalCode'] = $this->getTerminalId();
        $params['timeStamp'] = date("Y/m/d H:i:s");
        $sign = $this->sign(json_encode($params));
        $response = $this->api->send(static::URL_VERIFY_PAYMENT,RequestBuilder::POST,["Sign" => $sign],$params, true);
        return $response;
    }
}
