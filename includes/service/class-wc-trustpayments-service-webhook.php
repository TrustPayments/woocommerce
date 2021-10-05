<?php
if (!defined('ABSPATH')) {
	exit();
}
/**
 * Trust Payments WooCommerce
 *
 * This WooCommerce plugin enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */
/**
 * This service handles webhooks.
 */
class WC_TrustPayments_Service_Webhook extends WC_TrustPayments_Service_Abstract {

	/**
	 * The webhook listener API service.
	 *
	 * @var \TrustPayments\Sdk\Service\WebhookListenerService
	 */
	private $webhook_listener_service;

	/**
	 * The webhook url API service.
	 *
	 * @var \TrustPayments\Sdk\Service\WebhookUrlService
	 */
	private $webhook_url_service;
	private $webhook_entities = array();

	/**
	 * Constructor to register the webhook entites.
	 */
	public function __construct(){
	    $this->webhook_entities[1487165678181] = new WC_TrustPayments_Webhook_Entity(1487165678181, 'Manual Task',
				array(
				    \TrustPayments\Sdk\Model\ManualTaskState::DONE,
				    \TrustPayments\Sdk\Model\ManualTaskState::EXPIRED,
				    \TrustPayments\Sdk\Model\ManualTaskState::OPEN
				), 'WC_TrustPayments_Webhook_Manual_Task');
	    $this->webhook_entities[1472041857405] = new WC_TrustPayments_Webhook_Entity(1472041857405, 'Payment Method Configuration',
				array(
				    \TrustPayments\Sdk\Model\CreationEntityState::ACTIVE,
				    \TrustPayments\Sdk\Model\CreationEntityState::DELETED,
				    \TrustPayments\Sdk\Model\CreationEntityState::DELETING,
				    \TrustPayments\Sdk\Model\CreationEntityState::INACTIVE
				), 'WC_TrustPayments_Webhook_Method_Configuration', true);
	    $this->webhook_entities[1472041829003] = new WC_TrustPayments_Webhook_Entity(1472041829003, 'Transaction',
				array(
				    \TrustPayments\Sdk\Model\TransactionState::CONFIRMED,
				    \TrustPayments\Sdk\Model\TransactionState::AUTHORIZED,
				    \TrustPayments\Sdk\Model\TransactionState::DECLINE,
				    \TrustPayments\Sdk\Model\TransactionState::FAILED,
					\TrustPayments\Sdk\Model\TransactionState::FULFILL,
				    \TrustPayments\Sdk\Model\TransactionState::VOIDED,
				    \TrustPayments\Sdk\Model\TransactionState::COMPLETED,
				    \TrustPayments\Sdk\Model\TransactionState::PROCESSING
				), 'WC_TrustPayments_Webhook_Transaction');
	    $this->webhook_entities[1472041819799] = new WC_TrustPayments_Webhook_Entity(1472041819799, 'Delivery Indication',
				array(
				    \TrustPayments\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED
				), 'WC_TrustPayments_Webhook_Delivery_Indication');

		$this->webhook_entities[1472041816898] = new WC_TrustPayments_Webhook_Entity(1472041816898, 'Transaction Invoice',
			array(
				\TrustPayments\Sdk\Model\TransactionInvoiceState::NOT_APPLICABLE,
				\TrustPayments\Sdk\Model\TransactionInvoiceState::PAID,
				\TrustPayments\Sdk\Model\TransactionInvoiceState::DERECOGNIZED,
			), 'WC_TrustPayments_Webhook_Transaction_Invoice');

	    $this->webhook_entities[1472041831364] = new WC_TrustPayments_Webhook_Entity(1472041831364, 'Transaction Completion',
				array(
				    \TrustPayments\Sdk\Model\TransactionCompletionState::FAILED,
				    \TrustPayments\Sdk\Model\TransactionCompletionState::SUCCESSFUL
				), 'WC_TrustPayments_Webhook_Transaction_Completion');

	    $this->webhook_entities[1472041867364] = new WC_TrustPayments_Webhook_Entity(1472041867364, 'Transaction Void',
				array(
				    \TrustPayments\Sdk\Model\TransactionVoidState::FAILED,
				    \TrustPayments\Sdk\Model\TransactionVoidState::SUCCESSFUL
				), 'WC_TrustPayments_Webhook_Transaction_Void');

	    $this->webhook_entities[1472041839405] = new WC_TrustPayments_Webhook_Entity(1472041839405, 'Refund',
				array(
				    \TrustPayments\Sdk\Model\RefundState::FAILED,
				    \TrustPayments\Sdk\Model\RefundState::SUCCESSFUL
				), 'WC_TrustPayments_Webhook_Refund');
	    $this->webhook_entities[1472041806455] = new WC_TrustPayments_Webhook_Entity(1472041806455, 'Token',
				array(
				    \TrustPayments\Sdk\Model\CreationEntityState::ACTIVE,
				    \TrustPayments\Sdk\Model\CreationEntityState::DELETED,
				    \TrustPayments\Sdk\Model\CreationEntityState::DELETING,
				    \TrustPayments\Sdk\Model\CreationEntityState::INACTIVE
				), 'WC_TrustPayments_Webhook_Token');
	    $this->webhook_entities[1472041811051] = new WC_TrustPayments_Webhook_Entity(1472041811051, 'Token Version',
				array(
				    \TrustPayments\Sdk\Model\TokenVersionState::ACTIVE,
				    \TrustPayments\Sdk\Model\TokenVersionState::OBSOLETE
				), 'WC_TrustPayments_Webhook_Token_Version');
	}

	/**
	 * Installs the necessary webhooks in Trust Payments.
	 */
	public function install(){
	    $space_id = get_option(WooCommerce_TrustPayments::CK_SPACE_ID);
		if (!empty($space_id)) {
			$webhook_url = $this->get_webhook_url($space_id);
			if ($webhook_url == null) {
				$webhook_url = $this->create_webhook_url($space_id);
			}
			$existing_listeners = $this->get_webhook_listeners($space_id, $webhook_url);
			foreach ($this->webhook_entities as $webhook_entity) {
				/* @var WC_TrustPayments_Webhook_Entity $webhook_entity */
				$exists = false;
				foreach ($existing_listeners as $existing_listener) {
					if ($existing_listener->getEntity() == $webhook_entity->get_id()) {
						$exists = true;
					}
				}
				if (!$exists) {
					$this->create_webhook_listener($webhook_entity, $space_id, $webhook_url);
				}
			}
		}
	}

	/**
	 * @param int|string $id
	 * @return WC_TrustPayments_Webhook_Entity
	 */
	public function get_webhook_entity_for_id($id){
		if (isset($this->webhook_entities[$id])) {
			return $this->webhook_entities[$id];
		}
		return null;
	}

	/**
	 * Create a webhook listener.
	 *
	 * @param WC_TrustPayments_Webhook_Entity     $entity
	 * @param int                                         $space_id
	 * @param \TrustPayments\Sdk\Model\WebhookUrl $webhook_url
	 *
	 * @return \TrustPayments\Sdk\Model\WebhookListenerCreate
	 * @throws \Exception
	 */
	protected function create_webhook_listener(WC_TrustPayments_Webhook_Entity $entity, $space_id, \TrustPayments\Sdk\Model\WebhookUrl $webhook_url){
	    $webhook_listener = new \TrustPayments\Sdk\Model\WebhookListenerCreate();
		$webhook_listener->setEntity($entity->get_id());
		$webhook_listener->setEntityStates($entity->get_states());
		$webhook_listener->setName('Woocommerce ' . $entity->get_name());
		$webhook_listener->setState(\TrustPayments\Sdk\Model\CreationEntityState::ACTIVE);
		$webhook_listener->setUrl($webhook_url->getId());
		$webhook_listener->setNotifyEveryChange($entity->is_notify_every_change());
		return $this->get_webhook_listener_service()->create($space_id, $webhook_listener);
	}

	/**
	 * Returns the existing webhook listeners.
	 *
	 * @param int                                         $space_id
	 * @param \TrustPayments\Sdk\Model\WebhookUrl $webhook_url
	 *
	 * @return \TrustPayments\Sdk\Model\WebhookListener[]
	 * @throws \Exception
	 */
	protected function get_webhook_listeners($space_id, \TrustPayments\Sdk\Model\WebhookUrl $webhook_url){
	    $query = new \TrustPayments\Sdk\Model\EntityQuery();
	    $filter = new \TrustPayments\Sdk\Model\EntityQueryFilter();
	    $filter->setType(\TrustPayments\Sdk\Model\EntityQueryFilterType::_AND);
		$filter->setChildren(
				array(
				    $this->create_entity_filter('state', \TrustPayments\Sdk\Model\CreationEntityState::ACTIVE),
					$this->create_entity_filter('url.id', $webhook_url->getId())
				));
		$query->setFilter($filter);
		return $this->get_webhook_listener_service()->search($space_id, $query);
	}

	/**
	 * Creates a webhook url.
	 *
	 * @param int $space_id
	 *
	 * @return \TrustPayments\Sdk\Model\WebhookUrlCreate
	 * @throws \Exception
	 */
	protected function create_webhook_url($space_id){
	    $webhook_url = new \TrustPayments\Sdk\Model\WebhookUrlCreate();
		$webhook_url->setUrl($this->get_url());
		$webhook_url->setState(\TrustPayments\Sdk\Model\CreationEntityState::ACTIVE);
		$webhook_url->setName('Woocommerce');
		return $this->get_webhook_url_service()->create($space_id, $webhook_url);
	}

	/**
	 * Returns the existing webhook url if there is one.
	 *
	 * @param int $space_id
	 *
	 * @return \TrustPayments\Sdk\Model\WebhookUrl
	 * @throws \Exception
	 */
	protected function get_webhook_url($space_id){
	    $query = new \TrustPayments\Sdk\Model\EntityQuery();
	    $filter = new \TrustPayments\Sdk\Model\EntityQueryFilter();
	    $filter->setType(\TrustPayments\Sdk\Model\EntityQueryFilterType::_AND);
		$filter->setChildren(
				array(
				    $this->create_entity_filter('state', \TrustPayments\Sdk\Model\CreationEntityState::ACTIVE),
					$this->create_entity_filter('url', $this->get_url())
				));
		$query->setFilter($filter);
		$query->setNumberOfEntities(1);
		$result = $this->get_webhook_url_service()->search($space_id, $query);
		if (!empty($result)) {
			return $result[0];
		}
		else {
			return null;
		}
	}

	/**
	 * Returns the webhook endpoint URL.
	 *
	 * @return string
	 */
	protected function get_url(){
		return add_query_arg('wc-api', 'trustpayments_webhook', home_url('/'));
	}

	/**
	 * Returns the webhook listener API service.
	 *
	 * @return \TrustPayments\Sdk\Service\WebhookListenerService
	 * @throws \Exception
	 */
	protected function get_webhook_listener_service(){
		if ($this->webhook_listener_service == null) {
		    $this->webhook_listener_service = new \TrustPayments\Sdk\Service\WebhookListenerService(WC_TrustPayments_Helper::instance()->get_api_client());
		}
		return $this->webhook_listener_service;
	}

	/**
	 * Returns the webhook url API service.
	 *
	 * @return \TrustPayments\Sdk\Service\WebhookUrlService
	 * @throws \Exception
	 */
	protected function get_webhook_url_service(){
		if ($this->webhook_url_service == null) {
		    $this->webhook_url_service = new \TrustPayments\Sdk\Service\WebhookUrlService(WC_TrustPayments_Helper::instance()->get_api_client());
		}
		return $this->webhook_url_service;
	}
}
