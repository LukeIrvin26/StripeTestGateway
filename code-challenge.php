<?php

// Defining constants
define('API_URL', 'https://api.stripe.com/v1/charges');
define('SOURCE_TOKEN', 'tok_visa');

/**
 * RESTful Code challenge. Create a simple payment gateway class that implements this
 * interface. The only thing we're looking for here is your ability to comprehend API
 * documentation and the quality of the resulting code. The only API method you need
 * to make is for a plain-jane charge. No preauth, postauth, refunds, void, etc. need
 * to be implemented. Just keep it simple and to the point.
 * ---
 * 1. You can choose between the following gateways. We've provided some API credentials
 *    from a few of our sandboxes to streamline the process for ya:.
 *
 *    a) Stripe      - https://stripe.com/docs/api#create_chargeS
 *       Secret Key  - sk_test_lBzwJ4lQzQvEPZwgl3s59Mal
 *
 *    b) Authorize.Net       - http://developer.authorize.net/api/reference/#payment-transactions-charge-a-credit-card
 *       API Login ID        - 925hDnUuTCZ
 *       API Transaction Key - 848V7x2Aq4BgFn9F
 * ---
 * 2. You cannot use SDKs (this is to test your ability to comprehend API documentation).
 * ---
 * 3. You cannot use third party libraries. Everything must be from scratch. You can use
 *    cURL and other native PHP libraries as needed.
 * ---
 * 4. Create a simple "test script" that will do a test charge when ran, like so:
 *
 * $gw = new FooBarGateway('sk_test_lBzwJ4lQzQvEPZwgl3s59Mal');
 * $gw->setName('Bob Smith')
 *    ->setAddress1('123 Test Street')
 *    ->setAddress2('Suite #4')
 *    ->setCity('Morristown')
 *    ->setProvince('TN')
 *    ->setPostal('37814')
 *    ->setCountry('US')
 *    ->setCardNumber('4007000000027)
 *    ->setExpirationDate('10', '2019')
 *    ->setCvv('123');
 *
 * if ($gw->charge(4999, 'USD')) {
 *     echo "Charge successful! Transaction ID: " . $gw->getTransactionId();
 * } else {
 *	   echo "Charge failed. Errors: " . print_r($gw->getErrors(), TRUE);
 * }
 * ---
 */
interface BasicPaymentGateway
{
    /**
     * Set the card holder's full name.
     * ---.
     *
     * @param   string  cardholder's full name
     *
     * @return object chainable instance of self
     */
    public function setName(string $name): BasicPaymentGateway;

    /**
     * Set the cardholder's billing address, line 1.
     * ---.
     *
     * @param   string  billing address line 1
     *
     * @return object chainable instance of self
     */
    public function setAddress1(string $address): BasicPaymentGateway;

    /**
     * Set the cardholder's billing address, line 2. Not used for some payment gateways.
     * ---.
     *
     * @param   string  billing address line 2 or NULL
     *
     * @return object chainable instance of self
     */
    public function setAddress2(?string $address): BasicPaymentGateway;

    /**
     * Set the city for the cardholder's billing address.
     * ---.
     *
     * @param   string  billing city
     *
     * @return object chainable instance of self
     */
    public function setCity(string $city): BasicPaymentGateway;

    /**
     * Set the state / province for the cardholder's billing address.
     * ---.
     *
     * @param   string  billing state/province
     *
     * @return object chainable instance of self
     */
    public function setProvince(string $province): BasicPaymentGateway;

    /**
     * Set the zip / postal code for the cardholder's billing address.
     * --.
     *
     * @param   string  billing zip/postal code
     *
     * @return object chainable instance of self
     */
    public function setPostal(string $postal): BasicPaymentGateway;

    /**
     * Set the ISO 3166-1 alpha-2 country code for the cardholder's billing address.
     * ---.
     *
     * @param   string  ISO 3166 country code
     *
     * @return object chainable instance of self
     */
    public function setCountry(string $country): BasicPaymentGateway;

    /**
     * Set the credit/debit card number.
     * ---.
     *
     * @param   string  card number
     *
     * @return object chainable instance of self
     */
    public function setCardNumber(string $number): BasicPaymentGateway;

    /**
     * Set the card's expiration date.
     * ---.
     *
     * @param   string  card expiration month in MM format
     * @param   string  card expiration year in YYYY format
     *
     * @return object chainable instance of self
     */
    public function setExpirationDate(string $month, string $year): BasicPaymentGateway;

    /**
     * Set the card's security code.
     * ---.
     *
     * @param   string  Card security code (CVV, CVV2, etc.).
     *
     * @return object chainable instance of self
     */
    public function setCvv(string $cvv): BasicPaymentGateway;

    /**
     * Charge the credit/debit card for a specified amount in a specified currency.
     * ---.
     *
     * @param   int     Charge amount currency's smallest monetary unit (i.e. 100 = 1.00 USD).
     * @param   string  [USD] ISO 4217 currency code. Default = USD.
     *
     * @return bool TRUE if charge successful, FALSE otherwise
     */
    public function charge(int $amount, string $currency = 'USD'): bool;

    /**
     * Errors returned from the last API request.
     * ---.
     *
     * @return array empty if no errors found
     */
    public function getErrors(): array;

    /**
     * Get the transaction ID from the last API request.
     * ---.
     *
     * @return string transaction ID of the last API request, or NULL
     */
    public function getTransactionId(): ?string;
}

class StripeGateway implements BasicPaymentGateway
{
    // Declaring needed variables
    private string $key;
    private string $name;
    private string $addressLine1;
    private ?string $addressLine2;
    private string $city;
    private string $province;
    private string $postalCode;
    private string $country;
    private string $cardNumber;
    private string $cardCVV;
    private string $expirationMonth;
    private string $expirationYear;
    private array $chargeErrors;
    private ?string $transactionId;

    // Constructor
    public function __construct(string $key)
    {
        $this->key = $key;
        $this->name = '';
        $this->addressLine1 = '';
        $this->addressLine2 = null;
        $this->city = '';
        $this->province = '';
        $this->postalCode = '';
        $this->country = '';
        $this->cardNumber = '';
        $this->cardCVV = '';
        $this->expirationMonth = '';
        $this->expirationYear = '';
        $this->chargeErrors = [];
        $this->transactionId = null;
    }

    public function setName(string $name): BasicPaymentGateway
    {
        $this->name = $name;

        return $this;
    }

    public function setAddress1(string $address): BasicPaymentGateway
    {
        $this->addressLine1 = $address;

        return $this;
    }

    public function setAddress2(?string $address): BasicPaymentGateway
    {
        $this->addressLine2 = $address;

        return $this;
    }

    public function setCity(string $city): BasicPaymentGateway
    {
        $this->city = $city;

        return $this;
    }

    public function setProvince(string $province): BasicPaymentGateway
    {
        $this->province = $province;

        return $this;
    }

    public function setPostal(string $postal): BasicPaymentGateway
    {
        $this->postalCode = $postal;

        return $this;
    }

    public function setCountry(string $country): BasicPaymentGateway
    {
        $this->country = $country;

        return $this;
    }

    public function setCardNumber(string $number): BasicPaymentGateway
    {
        $this->cardNumber = $number;

        return $this;
    }

    public function setExpirationDate(string $month, string $year): BasicPaymentGateway
    {
        $this->expirationMonth = $month;
        $this->expirationYear = $year;

        return $this;
    }

    public function setCvv(string $cvv): BasicPaymentGateway
    {
        $this->cardCVV = $cvv;

        return $this;
    }

    public function charge(int $amount, string $currency = 'USD'): bool
    {
        $this->chargeErrors = [];
        $headers = [];
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $ch = curl_init(API_URL);
        $post_data = [
            'amount' => 2000,
            'currency' => 'usd',
            'source' => SOURCE_TOKEN,
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERNAME, $this->key);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $charge = json_decode($result);

        if (curl_errno($ch)) {
            array_push($this->chargeErrors, curl_error($ch));

            return false;
        }

        if (isset($charge->error)) {
            array_push($this->chargeErrors, $charge->error);

            return false;
        }

        curl_close($ch);

        if (!$result) {
            return false;
        }

        $this->transactionId = $charge->balance_transaction;

        return true;
    }

    public function getErrors(): array
    {
        return $this->chargeErrors;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }
}
