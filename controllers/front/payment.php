<?php
/**
 * Copyright (c) 2012-2020, Mollie B.V.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 *
 * @author     Mollie B.V. <info@mollie.nl>
 * @copyright  Mollie B.V.
 * @license    Berkeley Software Distribution License (BSD-License 2) http://www.opensource.org/licenses/bsd-license.php
 * @category   Mollie
 * @package    Mollie
 * @link       https://www.mollie.nl
 * @codingStandardsIgnoreStart
 */

use Mollie\Api\Exceptions\ApiException;

if (!defined('_PS_VERSION_')) {
    return;
}

require_once dirname(__FILE__).'/../../mollie.php';

/**
 * Class MolliePaymentModuleFrontController
 *
 * @property Context? $context
 * @property Mollie       $module
 */
class MolliePaymentModuleFrontController extends ModuleFrontController
{
    /** @var bool $ssl */
    public $ssl = true;
    /** @var bool $display_column_left */
    public $display_column_left = false;
    /** @var bool $display_column_right */
    public $display_column_right = false;

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Adapter_Exception
     * @throws SmartyException
     * @throws \PrestaShop\PrestaShop\Adapter\CoreException
     * @throws Exception
     */
    public function initContent()
    {
        parent::initContent();
        /** @var Cart $cart */
        $cart = $this->context->cart;
        $customer = new Customer($cart->id_customer);
        $this->context->smarty->assign('link', $this->context->link);

        if (!$this->validate(
            $cart,
            $customer
        )) {
            /** @var \Mollie\Utility\LanguageUtility $langUtility */
            $langUtility = $this->module->getContainer(\Mollie\Utility\LanguageUtility::class);
            $this->errors[] = $langUtility->getLang()['This payment method is not available.'];
            $this->setTemplate('error.tpl');

            return;
        }

        $method = Tools::getValue('method');
        if (in_array($method, array('cartesbancaires'))) {
            $method = 'creditcard';
        }
        $issuer = Tools::getValue('issuer') ?: null;

        $originalAmount = $cart->getOrderTotal(
            true,
            Cart::BOTH
        );
        $amount = $originalAmount;
        if (!$amount) {
            Tools::redirectLink('index.php');
        }

        /** @var \Mollie\Repository\PaymentMethodRepository $paymentMethodRepo */
        $paymentMethodRepo = $this->module->getContainer(\Mollie\Repository\PaymentMethodRepository::class);

        $paymentMethodId = $paymentMethodRepo->getPaymentMethodIdByMethodId($method);
        $paymentMethodObj = new MolPaymentMethod($paymentMethodId);
        // Prepare payment
        do {
            $orderReference = Order::generateReference();
        } while (Order::getByReference($orderReference)->count());
        $paymentData = Mollie::getPaymentData(
            $amount,
            Tools::strtoupper($this->context->currency->iso_code),
            $method,
            $issuer,
            (int) $cart->id,
            $customer->secure_key,
            $paymentMethodObj,
            false,
            $orderReference
        );
        try {
            $apiPayment = $this->createPayment($paymentData, $paymentMethodObj->method);
        } catch (ApiException $e) {
            $this->setTemplate('error.tpl');
            $this->errors[] = Configuration::get(Mollie\Config\Config::MOLLIE_DISPLAY_ERRORS)
                ? $e->getMessage().'. Cart Dump: '.json_encode($paymentData, JSON_PRETTY_PRINT)
                : $this->module->l('An error occurred while initializing your payment. Please contact our customer support.', 'payment');
            return;
        } catch (PrestaShopException $e) {
            $this->setTemplate('error.tpl');
            $this->errors[] = Configuration::get(Mollie\Config\Config::MOLLIE_DISPLAY_ERRORS)
                ? $e->getMessage().' Cart Dump: '.json_encode($paymentData, JSON_PRETTY_PRINT)
                : $this->module->l('An error occurred while initializing your payment. Please contact our customer support.', 'payment');
            return;
        }
        $this->createOrder($method, $apiPayment, $cart->id, $originalAmount, $customer->secure_key, $orderReference);
        $orderReference = isset($apiPayment->metadata->order_reference) ? pSQL($apiPayment->metadata->order_reference) : '';

        // Store payment linked to cart
        if ($apiPayment->method !== \Mollie\Api\Types\PaymentMethod::BANKTRANSFER) {
            try {
                Db::getInstance()->insert(
                    'mollie_payments',
                    array(
                        'cart_id'         => (int) $cart->id,
                        'method'          => pSQL($apiPayment->method),
                        'transaction_id'  => pSQL($apiPayment->id),
                        'order_reference' => pSQL($orderReference),
                        'bank_status'     => \Mollie\Api\Types\PaymentStatus::STATUS_OPEN,
                        'created_at'      => array('type' => 'sql', 'value' => 'NOW()'),
                    )
                );
            } catch (PrestaShopDatabaseException $e) {
                $paymentMethodRepo->tryAddOrderReferenceColumn();
                throw $e;
            }
        }

        $status = $apiPayment->status;
        if (!isset(Mollie\Config\Config::getStatuses()[$apiPayment->status])) {
            $status = 'open';
        }

        $paymentStatus = (int) Mollie\Config\Config::getStatuses()[$status];

        if ($paymentStatus < 1) {
            $paymentStatus = Configuration::get(Mollie\Config\Config::STATUS_MOLLIE_AWAITING);
        }

        if ($apiPayment->method === \Mollie\Api\Types\PaymentMethod::BANKTRANSFER) {
            // Set the `banktransfer` details
            if ($apiPayment instanceof \Mollie\Api\Resources\Order) {
                // If this is an order, take the first payment
                $apiPayment = $apiPayment->payments();
                $apiPayment = $apiPayment[0];
            }

            $details = $apiPayment->details->transferReference;
            $address = "IBAN: {$apiPayment->details->bankAccount} / BIC: {$apiPayment->details->bankBic}";

            $extraVars = array(
                '{bankwire_owner}'   => 'Stichting Mollie Payments',
                '{bankwire_details}' => $details,
                '{bankwire_address}' => $address,
            );

            $this->module->currentOrderReference = $orderReference;
            $this->module->validateOrder(
                (int) $cart->id,
                $paymentStatus,
                $originalAmount,
                isset(Mollie\Config\Config::$methods[$apiPayment->method]) ? Mollie\Config\Config::$methods[$apiPayment->method] : $this->module->name,
                null,
                $extraVars,
                null,
                false,
                $customer->secure_key
            );

            $orderId = version_compare(_PS_VERSION_, '1.7.1.0', '>=')
                ? Order::getIdByCartId((int) $cart->id)
                : Order::getOrderByCartId((int) $cart->id);

            try {
                Db::getInstance()->insert(
                    'mollie_payments',
                    array(
                        'cart_id'         => (int) $cart->id,
                        'order_id'        => (int) $orderId,
                        'order_reference' => pSQL($orderReference),
                        'method'          => pSQL($apiPayment->method),
                        'transaction_id'  => pSQL($apiPayment->id),
                        'bank_status'     => \Mollie\Api\Types\PaymentStatus::STATUS_OPEN,
                        'created_at'      => array('type' => 'sql', 'value' => 'NOW()'),
                    )
                );
            } catch (PrestaShopDatabaseException $e) {
                $paymentMethodRepo->tryAddOrderReferenceColumn();
                $redirectLink = $this->context->link->getPageLink(
                    'order',
                    true,
                    null,
                    [
                        'step' => 1,
                    ]
                );
                $this->errors[] = $this->l('Failed to validate order', 'payment');
                $this->redirectWithNotifications($redirectLink);
            }
        }


        // Go to payment url
        Tools::redirect($apiPayment->getCheckoutUrl());
    }

    /**
     * Checks if this payment option is still available
     * May redirect the user to a more appropriate page
     *
     * @param Cart     $cart
     * @param Customer $customer
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function validate($cart, $customer)
    {
        if (!$cart->id_customer || !$cart->id_address_delivery || !$cart->id_address_invoice || !$this->module->active) {
            // We be like: how did you even get here?
            Tools::redirect(Context::getContext()->link->getPageLink('index', true));
            return false;
        }

        $authorized = false;

        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] === $this->module->name) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            return false;
        }

        if (!Validate::isLoadedObject($customer)) {
            return false;
        }

        return true;
    }

    /**
     * @param array $data
     *
     * @return \Mollie\Api\Resources\Payment|\Mollie\Api\Resources\Order|null
     *
     * @throws PrestaShopException
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    protected function createPayment($data, $selectedApi)
    {
        try {
            if ($selectedApi === Mollie\Config\Config::MOLLIE_ORDERS_API) {
                /** @var \Mollie\Api\Resources\Order $payment */
                $payment = $this->module->api->orders->create($data, array('embed' => 'payments'));
            } else {
                /** @var \Mollie\Api\Resources\Payment $payment */
                $payment = $this->module->api->payments->create($data);
            }
        } catch (Exception $e) {
            throw new ApiException($e->getMessage());
        }
        return $payment;
    }

    /**
     * Prepend module path if PS version >= 1.7
     *
     * @param string      $template
     * @param array       $params
     * @param string|null $locale
     *
     * @throws PrestaShopException
     *
     * @since 3.3.2
     */
    public function setTemplate($template, $params = array(), $locale = null)
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            $template = "module:mollie/views/templates/front/17_{$template}";
        }

        parent::setTemplate($template, $params, $locale);
    }

    private function createOrder($method, $apiPayment, $cartId, $originalAmount, $secureKey, $orderReference)
    {
        $extraVars = [];
        if ($method === \Mollie\Api\Types\PaymentMethod::BANKTRANSFER) {
            // Set the `banktransfer` details
            if ($apiPayment instanceof \Mollie\Api\Resources\Order) {
                // If this is an order, take the first payment
                $apiPayment = $apiPayment->payments();
                $apiPayment = $apiPayment[0];
            }

            $details = $apiPayment->details->transferReference;
            $address = "IBAN: {$apiPayment->details->bankAccount} / BIC: {$apiPayment->details->bankBic}";

            $extraVars = [
                '{bankwire_owner}' => 'Stichting Mollie Payments',
                '{bankwire_details}' => $details,
                '{bankwire_address}' => $address,
            ];
        }
        /** @var \Mollie\Repository\PaymentMethodRepository $paymentMethodRepo */
        $paymentMethodRepo = $this->module->getContainer(\Mollie\Repository\PaymentMethodRepository::class);

        $orderFee = Mollie::getPaymentFee(
            new MolPaymentMethod($paymentMethodRepo->getPaymentMethodIdByMethodId($apiPayment->method)),
            $this->context->cart->getOrderTotal()
        );

        $totalPrice = new \PrestaShop\Decimal\Number((string) $originalAmount);

        if ($orderFee) {
            $orderFeeObj = new MolOrderFee();
            $orderFeeObj->id_cart = (int) $cartId;
            $orderFeeObj->order_fee = Mollie::getPaymentFee(
                new MolPaymentMethod($paymentMethodRepo->getPaymentMethodIdByMethodId($apiPayment->method)),
                $this->context->cart->getOrderTotal()
            );
            try {
                $orderFeeObj->add();
            } catch (Exception $e) {
                throw new PrestaShopException('Can\'t save Order fee');
            }
            $orderFeeNumber = new \PrestaShop\Decimal\Number((string) $orderFeeObj->order_fee);
            $totalPrice = $orderFeeNumber->plus($totalPrice);
        }

        $this->module->validateOrder(
            (int) $cartId,
            (int) Configuration::get(Mollie\Config\Config::STATUS_MOLLIE_AWAITING),
            $totalPrice->toPrecision(2),
            isset(Mollie\Config\Config::$methods[$apiPayment->method]) ? Mollie\Config\Config::$methods[$method] : $this->module->name,
            null,
            $extraVars,
            null,
            false,
            $secureKey
        );

        $order = Order::getByCartId($cartId);
        $order->total_paid_tax_excl = $totalPrice->toPrecision(2);
        $order->total_paid_tax_incl = $totalPrice->toPrecision(2);
        $order->total_paid = $totalPrice->toPrecision(2);
        $order->reference = $orderReference;
        $order->update();
    }
}
