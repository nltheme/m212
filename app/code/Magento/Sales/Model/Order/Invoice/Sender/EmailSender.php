<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Sales\Model\Order\Invoice\Sender;

use Magento\Sales\Model\Order\Email\Sender;
use Magento\Sales\Model\Order\Invoice\SenderInterface;

/**
 * Email notification sender for Invoice.
 */
class EmailSender extends Sender implements SenderInterface
{
    /**
     * @var \Magento\Payment\Helper\Data
     */
    private $paymentHelper;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Invoice
     */
    private $invoiceResource;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $globalConfig;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @param \Magento\Sales\Model\Order\Email\Container\Template $templateContainer
     * @param \Magento\Sales\Model\Order\Email\Container\InvoiceIdentity $identityContainer
     * @param \Magento\Sales\Model\Order\Email\SenderBuilderFactory $senderBuilderFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Sales\Model\ResourceModel\Order\Invoice $invoiceResource
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $globalConfig
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Magento\Sales\Model\Order\Email\Container\Template $templateContainer,
        \Magento\Sales\Model\Order\Email\Container\InvoiceIdentity $identityContainer,
        \Magento\Sales\Model\Order\Email\SenderBuilderFactory $senderBuilderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Sales\Model\ResourceModel\Order\Invoice $invoiceResource,
        \Magento\Framework\App\Config\ScopeConfigInterface $globalConfig,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        parent::__construct(
            $templateContainer,
            $identityContainer,
            $senderBuilderFactory,
            $logger,
            $addressRenderer
        );

        $this->paymentHelper = $paymentHelper;
        $this->invoiceResource = $invoiceResource;
        $this->globalConfig = $globalConfig;
        $this->eventManager = $eventManager;
    }

    /**
     * Sends order invoice email to the customer.
     *
     * Email will be sent immediately in two cases:
     *
     * - if asynchronous email sending is disabled in global settings
     * - if $forceSyncMode parameter is set to TRUE
     *
     * Otherwise, email will be sent later during running of
     * corresponding cron job.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @param \Magento\Sales\Api\Data\InvoiceCommentCreationInterface|null $comment
     * @param bool $forceSyncMode
     *
     * @return bool
     */
    public function send(
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Magento\Sales\Api\Data\InvoiceInterface $invoice,
        \Magento\Sales\Api\Data\InvoiceCommentCreationInterface $comment = null,
        $forceSyncMode = false
    ) {
        $invoice->setSendEmail(true);

        if (!$this->globalConfig->getValue('sales_email/general/async_sending') || $forceSyncMode) {
            $transport = [
                'order' => $order,
                'invoice' => $invoice,
                'comment' => $comment ? $comment->getComment() : '',
                'billing' => $order->getBillingAddress(),
                'payment_html' => $this->getPaymentHtml($order),
                'store' => $order->getStore(),
                'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
                'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
            ];

            $this->eventManager->dispatch(
                'email_invoice_set_template_vars_before',
                ['sender' => $this, 'transport' => $transport]
            );

            $this->templateContainer->setTemplateVars($transport);

            if ($this->checkAndSend($order)) {
                $invoice->setEmailSent(true);

                $this->invoiceResource->saveAttribute($invoice, ['send_email', 'email_sent']);

                return true;
            }
        } else {
            $invoice->setEmailSent(null);

            $this->invoiceResource->saveAttribute($invoice, 'email_sent');
        }

        $this->invoiceResource->saveAttribute($invoice, 'send_email');

        return false;
    }

    /**
     * Returns payment info block as HTML.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     *
     * @return string
     */
    private function getPaymentHtml(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        return $this->paymentHelper->getInfoBlockHtml(
            $order->getPayment(),
            $this->identityContainer->getStore()->getStoreId()
        );
    }
}
