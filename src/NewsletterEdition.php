<?php


namespace Drupal\simplenews_cron;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\simplenews\Spool\SpoolStorage;
use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

class NewsletterEdition
{


  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private ConfigData $config;

  /**
   * @entity_type.manager
   * @var EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entity_type_manager;

  /**
   * Simplenews module storage pool
   * @var SpoolStorage
   */
  private SpoolStorage $spool_storage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private Connection $connection;

  /**
   * @var LoggerInterface
   */
  private LoggerChannelFactoryInterface $logger;

  /**
   * NewsletterEdition constructor.
   * @param ConfigData $config
   * @param EntityTypeManagerInterface $entityTypeManager
   * @param SpoolStorage $spoolStorage
   * @param Connection $connection
   * @param LoggerChannelFactoryInterface $logger
   */
  public function __construct(
    ConfigData $config,
    EntityTypeManagerInterface $entityTypeManager,
    SpoolStorage $spoolStorage,
    Connection $connection,
    LoggerChannelFactoryInterface $logger)
  {
    $this->config = $config;
    $this->entity_type_manager = $entityTypeManager;
    $this->spool_storage = $spoolStorage;
    $this->connection = $connection;
    $this->logger = $logger;

  }

  /**
   * Get list of existing and published newsletters
   * Needed to send them to the newsletter spool
   * @return EntityInterface[]
   */
  private function get_newsletter_nodes(): array
  {
    return $this->entity_type_manager
      ->getListBuilder('node')
      ->getStorage()
      ->loadByProperties([
        'type' => 'simplenews_issue',
        'status' => 1,
      ]);
  }

  /**
   * Adds the newsletter to the send pool
   * Needed for the simplenews module to send the new edition
   * @param $node
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */

  private function send_new_edition($node): void
  {
    // load nodes by id
    $node_storage = $this->entity_type_manager
      ->getStorage('node')->load($node);

    // send nodes to the simplenews storage pool
    $this->spool_storage->addIssue($node_storage);
    /**
     * may need to be saved bellow
     * @TODO
     */
    //$node_storage->save();
  }


  /**
   * Set newsletter edition status on db
   * Needed to change the status of the newsletter back and forth
   * in order to send them
   * @param $nodeId
   * @param $status
   */
  private function set_newsletter_status($nodeId, $status): void
  {
    //get newsletter for update
    $query = $this->connection->update('{node__simplenews_issue}');
    // update the status field
    $query->fields([
      'simplenews_issue_status' => $status,
    ]);
    // where id equals passed id
    $query->condition('entity_id', $nodeId);
    // execute the update
    $query->execute();
  }


  /**
   * Set the status of the nodes
   * Combines the different functions into a single call
   * @param $nodeId
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  private function status_setter($nodeId): void
  {
    // Set the status of the node to 1 in preparation for sending
    $this->set_newsletter_status($nodeId, '1');

    //send news letter for each node
    $this->send_new_edition($nodeId);

    //set status back to 0 for future runs
    $this->set_newsletter_status($nodeId, '0');
  }

  /**
   *  Iterate through all newsletter nodes
   *  Needed to apply the send to each individual newsetter on the site
   */
  private function newsletter_iteration(): void
  {
    //Set run time stamp on config file
    $this->config->set_sent_time(time());

    // Store all the node id of entity type simplenews_issue into an object array
    $nodes = $this->get_newsletter_nodes();

    // Loop through each stored node id object
    foreach ($nodes as $node) {

      // convert record from class object to string by grabbing the id in the object
      $node_id = $node->id();

      //check that a newsletter has been assign to the node
      $field = $node->get('simplenews_issue')->getValue();

      //warning message
      $message = "Node " . $node_id . " has not been assign a newsletter";

      //if field is empty log message - else run code
      if (empty($field)) {
        $this->logger->get('simplenews_cron')->error($message);
      } else {
        // handle possible exceptions
        try {

          $this->status_setter($node_id);

        } catch (InvalidPluginDefinitionException $e) {
          $this->logger->get('simplenews_cron')->error('InvalidPluginDefinitionException '.$e);
        } catch (PluginNotFoundException $e) {
          $this->logger->get('simplenews_cron')->error('PluginNotFoundException '.$e);
        } catch (EntityStorageException $e) {
          $this->logger->get('simplenews_cron')->error('EntityStorageException '.$e);
        }
      }

    }
  }

  /**
   * cron executable function
   * The only callable function to initiate the process on cron run
   */
  public function cron_manager(): void
  {
    //{ test
   // $this->config->set_sent_time('');
   // $this->logger->get('simplenews_cron')->info($this->config->get_sent_time());  }

    if ($this->config->get_sent_time() < strtotime('-1 days') && date('D') === $this->config->get_send_date()) {
      $this->newsletter_iteration();
    } else {
      return;
    }

  }

}
