<?php

/**
 * PayTR Gateway
 *
 * @link https://www.alierenkaya.com/ Ali Eren KAYA
 */
class Paytr extends NonmerchantGateway
{
    /**
     * @var array An array of meta data for this gateway
     */
    private $meta;

    /**
     * Construct a new merchant gateway
     */
    public function __construct()
    {
        // Load configuration required by this gateway
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this gateway
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this gateway
        Language::loadLang('paytr', null, dirname(__FILE__) . DS . 'language' . DS);
    }

    /**
     * Sets the meta data for this particular gateway
     *
     * @param array $meta An array of meta data to set for this gateway
     */
    public function setMeta(array $meta = null)
    {
        $this->meta = $meta;
    }

    /**
     * Create and return the view content required to modify the settings of this gateway
     *
     * @param array $meta An array of meta (settings) data belonging to this gateway
     * @return string HTML content containing the fields to update the meta data for this gateway
     */
    public function getSettings(array $meta = null)
    {
        // Load the view into this object, so helpers can be automatically add to the view
        $this->view = new View('settings', 'default');
        $this->view->setDefaultView('components' . DS . 'gateways' . DS . 'nonmerchant' . DS . 'paytr' . DS);
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('meta', $meta);

        return $this->view->fetch();
    }

    /**
     * Performs migration of data from $current_version (the current installed version)
     * to the given file set version
     *
     * @param string $current_version The current installed version of this gateway
     */
    public function upgrade($current_version) {}

    /**
     * Validates the given meta (settings) data to be updated for this gateway
     *
     * @param array $meta An array of meta (settings) data to be updated for this gateway
     * @return array The meta data to be updated in the database for this gateway, or reset into the form on failure
     */
    public function editSettings(array $meta)
    {
        $rules = [
            'merchant_id' => [
                'valid' => [
                    'rule' => true,
                    'message' => Language::_('Paytr.!error.merchant_id.valid', true)
                ]
            ],
            'merchant_key' => [
                'valid' => [
                    'rule' => true,
                    'message' => Language::_('Paytr.!error.merchant_key.valid', true)
                ]
            ],
            'merchant_salt' => [
                'valid' => [
                    'rule' => true,
                    'message' => Language::_('Paytr.!error.merchant_salt.valid', true)
                ]
            ],
            'merchant_test_mode' => [
                'valid' => [
                    'rule' => true,
                    'message' => Language::_('Paytr.!error.merchant_test_mode.valid', true)
                ]
            ]
        ];
        $this->Input->setRules($rules);

        // Set unset checkboxes
        $checkbox_fields = [];

        foreach ($checkbox_fields as $checkbox_field) {
            if (!isset($meta[$checkbox_field])) {
                $meta[$checkbox_field] = 'false';
            }
        }

        // Validate the given meta data to ensure it meets the requirements
        $this->Input->validates($meta);
        // Return the meta data, no changes required regardless of success or failure for this gateway
        return $meta;
    }

    /**
     * Returns an array of all fields to encrypt when storing in the database
     *
     * @return array An array of the field names to encrypt when storing in the database
     */
    public function encryptableFields()
    {
        return [];
    }

    /**
     * Sets the currency code to be used for all subsequent payments
     *
     * @param string $currency The ISO 4217 currency code to be used for subsequent payments
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * Returns all HTML markup required to render an authorization and capture payment form
     *
     * @param array $contact_info An array of contact info including:
     *  - id The contact ID
     *  - client_id The ID of the client this contact belongs to
     *  - user_id The user ID this contact belongs to (if any)
     *  - contact_type The type of contact
     *  - contact_type_id The ID of the contact type
     *  - first_name The first name on the contact
     *  - last_name The last name on the contact
     *  - title The title of the contact
     *  - company The company name of the contact
     *  - address1 The address 1 line of the contact
     *  - address2 The address 2 line of the contact
     *  - city The city of the contact
     *  - state An array of state info including:
     *      - code The 2 or 3-character state code
     *      - name The local name of the country
     *  - country An array of country info including:
     *      - alpha2 The 2-character country code
     *      - alpha3 The 3-cahracter country code
     *      - name The english name of the country
     *      - alt_name The local name of the country
     *  - zip The zip/postal code of the contact
     * @param float $amount The amount to charge this contact
     * @param array $invoice_amounts An array of invoices, each containing:
     *  - id The ID of the invoice being processed
     *  - amount The amount being processed for this invoice (which is included in $amount)
     * @param array $options An array of options including:
     *  - description The Description of the charge
     *  - return_url The URL to redirect users to after a successful payment
     *  - recur An array of recurring info including:
     *      - amount The amount to recur
     *      - term The term to recur
     *      - period The recurring period (day, week, month, year, onetime) used in conjunction
     *          with term in order to determine the next recurring payment
     * @return string HTML markup required to render an authorization and capture payment form
     */
    public function buildProcess(array $contact_info, $amount, array $invoice_amounts = null, array $options = null)
    {
        $amount = round($amount, 2);
        if (isset($options['recur']['amount'])) {
            $options['recur']['amount'] = round($options['recur']['amount'], 2);
        }

        $this->view = $this->makeView('process', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));

        Loader::loadHelpers($this, ['Form', 'Html']);
        Loader::loadModels($this, ['Clients', 'Contacts']);
        Loader::loadModels($this, ['Invoices']);

        $invoice_id = $invoice_amounts[0]['id'];

        $invoice_items = $this->Invoices->getLineItems($invoice_id);

        $invoice_json_data = json_encode($invoice_amounts);

        $url_encoded_invoice_encoded_data = urlencode($invoice_json_data);

        $user_basket_array = array_map(function ($item) {
            return [
                $item->description, // Ürün adı
                number_format($item->amount, 2, ".", ""), // Birim fiyat (ondalık format)
                (int)$item->qty // Adet (integer olarak)
            ];
        }, $invoice_items);

        $client = $this->Clients->get($contact_info['client_id']);

        $client_id = $contact_info['client_id'];

        $contact_numbers = $this->Contacts->getNumbers($client->contact_id);

        $phone = isset($contact_numbers[0]->number) ? $contact_numbers[0]->number : 'Varsayılan Numara';

        $user_basket = base64_encode(json_encode($user_basket_array));

        $merchant_url = Configure::get("Blesta.gw_callback_url");
        $merchant_url = parse_url($merchant_url);
        $merchant_url = $merchant_url['scheme'] . '://' . $merchant_url['host'];

        $return_url =  (isset($options['return_url']) ? $options['return_url'] : null);

        if (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else {
            $ip = $_SERVER["REMOTE_ADDR"];
        }

        $user_ip = $ip;

        $first_name = $client->first_name;

        $email = $client->email;

        $address = $client->address1;

        $payment_amount = $amount * 100;

        $currency = $this->currency;

        $no_installment = 0;

        $max_installment = 0;

        $merchant_id = isset($this->meta['merchant_id']) ? $this->meta['merchant_id'] : 'Merchant ID Not Found';

        $merchant_key = isset($this->meta['merchant_key']) ? $this->meta['merchant_key'] : 'Merchant KEY Not Found';

        $merchant_salt = isset($this->meta['merchant_salt']) ? $this->meta['merchant_salt'] : 'Merchant SALT Not Found';

        $merchant_test_mode = isset($this->meta['merchant_test_mode']) ? $this->meta['merchant_test_mode'] : 'Merchant Test Value Not Found';

        $merchant_oid = rand() . "PTR" . $client_id;

        if ($return_url) {
            if (strpos($return_url, '?client_id') !== false) {
                $merchant_ok_url = $return_url . "&amount=" . $payment_amount . "&currency=" . $currency . "&invoices=" . $url_encoded_invoice_encoded_data . "&transaction_id=" . $merchant_oid;
            } else {
                $merchant_ok_url = $return_url;
            }
        } else {
            $merchant_ok_url = $merchant_url . "/client/invoices/index/open";
        }

        $merchant_fail_url = $merchant_url . "/client/invoices/index/open";

        $hash_str = $merchant_id . $user_ip . $merchant_oid . $email . $payment_amount . $user_basket . $no_installment . $max_installment . $currency . $merchant_test_mode;
        $token = base64_encode(hash_hmac('sha256', $hash_str . $merchant_salt, $merchant_key, true));

        $postData = [
            'merchant_id' => $merchant_id,
            'user_ip' => $user_ip,
            'merchant_oid' => $merchant_oid,
            'email' => $email,
            'payment_amount' => $payment_amount,
            'paytr_token' => $token,
            'user_basket' => $user_basket,
            'debug_on' => 1,
            'no_installment' => $no_installment,
            'max_installment' => $max_installment,
            'user_name' => $first_name,
            'user_address' => $address,
            'user_phone' => $phone,
            'merchant_ok_url' => $merchant_ok_url,
            'merchant_fail_url' => $merchant_fail_url,
            'timeout_limit' => 5,
            'currency' => $currency,
            'test_mode' => $merchant_test_mode
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://www.paytr.com/odeme/api/get-token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
        }

        curl_close($ch);

        $response = json_decode($result, true);

        if ($response && isset($response['status']) && $response['status'] === 'success') {
            $token = $response['token'];
        } else {
            echo "Error occurred or status is not success.";
        }

        header("Location: https://www.paytr.com/odeme/guvenli/" . $token);
        die();

        return $this->view->fetch();
    }

    /**
     * Validates the incoming POST/GET response from the gateway to ensure it is
     * legitimate and can be trusted.
     *
     * @param array $get The GET data for this request
     * @param array $post The POST data for this request
     * @return array An array of transaction data, sets any errors using Input if the data fails to validate
     *  - client_id The ID of the client that attempted the payment
     *  - amount The amount of the payment
     *  - currency The currency of the payment
     *  - invoices An array of invoices and the amount the payment should be applied to (if any) including:
     *      - id The ID of the invoice to apply to
     *      - amount The amount to apply to the invoice
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the gateway to identify this transaction
     *  - parent_transaction_id The ID returned by the gateway to identify this
     *      transaction's original transaction (in the case of refunds)
     */
    public function validate(array $get, array $post)
    {
        if (!isset($this->Http)) {
            Loader::loadComponents($this, array("Net"));
            $this->Http = $this->Net->create("Http");
        };

        $this->log($this->ifSet($_SERVER['REQUEST_URI']), serialize($post), "input", true);

        Loader::loadModels($this, ['Transactions']);

        $merchant_oid = isset($post['merchant_oid']) ? $post['merchant_oid'] : null;

        $client_id = isset($get['client_id']) ? $get['client_id'] : (isset($post['merchant_oid']) ? explode('PTR', $post['merchant_oid'])[1] : null);

        $merchant_key = isset($this->meta['merchant_key']) ? $this->meta['merchant_key'] : null;

        $merchant_salt = isset($this->meta['merchant_salt']) ? $this->meta['merchant_salt'] : null;

        $status = isset($post['status']) ? $post['status'] : null;

        $total_amount = isset($post['total_amount']) ? $post['total_amount'] : null;

        $posthash = isset($post['hash']) ? $post['hash'] : null;

        if (!$client_id) {
            die('Client ID cannot.');
        }

        if (isset($_POST['currency'])) {
            $currency = $_POST['currency'];
            if ($currency == 'TL') {
                $currency = 'TRY';
            }
        }

        if ($status != "success") {
            die('OK');
        };

        $hash = base64_encode(hash_hmac('sha256', $merchant_oid . $merchant_salt . $status . $total_amount, $merchant_key, true));

        if ($hash != $posthash) {
            die('PAYTR notification failed: bad hash');
        }

        $tid = $this->Transactions->search($merchant_oid);

        if ($tid) {
            die("OK");
        }

        $params = [
            'client_id' => $client_id,
            'amount' => $this->ifSet($post['total_amount']) / 100,
            'currency' => $currency,
            'status' => "approved",
            'reference_id' => null,
            'transaction_id' => $this->ifSet($post['merchant_oid']),
        ];

        $this->log($this->ifSet($_SERVER['REQUEST_URI']), serialize($params), "output", true);

        return $params;
    }

    /**
     * Returns data regarding a success transaction. This method is invoked when
     * a client returns from the non-merchant gateway's web site back to Blesta.
     *
     * @param array $get The GET data for this request
     * @param array $post The POST data for this request
     * @return array An array of transaction data, may set errors using Input if the data appears invalid
     *  - client_id The ID of the client that attempted the payment
     *  - amount The amount of the payment
     *  - currency The currency of the payment
     *  - invoices An array of invoices and the amount the payment should be applied to (if any) including:
     *      - id The ID of the invoice to apply to
     *      - amount The amount to apply to the invoice
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - transaction_id The ID returned by the gateway to identify this transaction
     *  - parent_transaction_id The ID returned by the gateway to identify this transaction's original transaction
     */
    public function success(array $get, array $post)
    {
        $client_id = $get['client_id'];
        $amount = $get['amount'] / 100;
        $currency = $get['currency'];
        $invoices = json_decode(urldecode($_GET['invoices']), true);
        $transaction_id = $get['transaction_id'];

        $params = [
            'client_id' => $client_id,
            'amount' => $amount,
            'currency' => $currency,
            'invoices' => $invoices,
            'status' => 'approved',
            'transaction_id' => $transaction_id,
            'parent_transaction_id' => null
        ];

        return $params;
    }

    /**
     * Refund a payment
     *
     * @param string $reference_id The reference ID for the previously submitted transaction
     * @param string $transaction_id The transaction ID for the previously submitted transaction
     * @param float $amount The amount to refund this transaction
     * @param string $notes Notes about the refund that may be sent to the client by the gateway
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function refund($reference_id, $transaction_id, $amount, $notes = null)
    {
        $this->Input->setErrors($this->getCommonError('unsupported'));
    }

    /**
     * Void a payment or authorization.
     *
     * @param string $reference_id The reference ID for the previously submitted transaction
     * @param string $transaction_id The transaction ID for the previously submitted transaction
     * @param string $notes Notes about the void that may be sent to the client by the gateway
     * @return array An array of transaction data including:
     *  - status The status of the transaction (approved, declined, void, pending, reconciled, refunded, returned)
     *  - reference_id The reference ID for gateway-only use with this transaction (optional)
     *  - transaction_id The ID returned by the remote gateway to identify this transaction
     *  - message The message to be displayed in the interface in addition to the standard
     *      message for this transaction status (optional)
     */
    public function void($reference_id, $transaction_id, $notes = null)
    {
        $this->Input->setErrors($this->getCommonError('unsupported'));
    }
}
