<?php

namespace OpeningHours\Module\CustomPostType\MetaBox;

use OpeningHours\Entity\Set;
use OpeningHours\Module\CustomPostType\Set as SetPostType;
use OpeningHours\Module\AbstractModule;
use OpeningHours\Module\OpeningHours;
use WP_Post;
defined( 'ABSPATH' ) || exit;

/**
 * Abstraction for a Meta Box
 *
 * @author      Jannik Portz
 * @package     OpeningHours\Module\CustomPostType\MetaBox
 */
abstract class AbstractMetaBox extends AbstractModule {
  const WP_ACTION_ADD_META_BOXES = 'add_meta_boxes';
  const WP_ACTION_SAVE_POST = 'save_post';

  const POST_TYPE = SetPostType::CPT_SLUG;

  const PRIORITY_DEFAULT = 'default';
  const PRIORITY_HIGH = 'high';
  const PRIORITY_LOW = 'low';

  const CONTEXT_NORMAL = 'normal';
  const CONTEXT_SIDE = 'side';
  const CONTEXT_ADVANCED = 'advanced';

  /**
   * The meta box id
   * @var       string
   */
  protected $id;

  /**
   * The meta box name / title
   * @var       string
   */
  protected $name;

  /**
   * The meta box context
   * @var       string
   */
  protected $context;

  /**
   * The meta box priority
   * @var       string
   */
  protected $priority;

  public function __construct($id, $name, $context = self::CONTEXT_NORMAL, $priority = self::PRIORITY_DEFAULT) {
    $this->id = $id;
    $this->name = $name;
    $this->context = $context;
    $this->priority = $priority;

    $this->registerHookCallbacks();
  }

  /** Registers Hook Callbacks */
  protected function registerHookCallbacks() {
    add_action(static::WP_ACTION_ADD_META_BOXES, array($this, 'registerMetaBox'), 10, 2);
    add_action(static::WP_ACTION_SAVE_POST, array($this, 'saveDataCallback'), 10, 3);
  }

  /**
   * Callback for saving the meta box data.
   *
   * @param     int      $post_id The current post's id
   * @param     WP_Post  $post    The current post (may be null when fired for foreign post types)
   * @param     bool     $update  Whether an existing post is updated (false if new post is created)
   */
  public function saveDataCallback($post_id, $post, $update) {
    if (!$post instanceof WP_Post || $post->post_type !== static::POST_TYPE) {
      return;
    }

    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
      return;
    }

    if (!current_user_can('edit_post', $post_id)) {
      return;
    }

    if ($this->verifyNonce($post_id) === false) {
      return;
    }

    $this->saveData($post_id, $post, $update);
  }

  /**
   * Verifies WordPress nonce for the given post id
   *
   * @param     int $postId The post id the nonce was generated for
   * @return    bool            Whether the nonce is valid
   */
  protected function verifyNonce($postId = 0) {
    $values = $this->generateNonceValues($postId);
    if (!array_key_exists($values['name'], $_POST)) {
      return false;
    }

    $nonceValue = $_POST[$values['name']];
    return wp_verify_nonce($nonceValue, $values['action']);
  }

  /** Prints the nonce field for the meta box
   *
   * @param     int $postId The post id to bind the nonce to
   */
  public function nonceField($postId = 0) {
    $values = $this->generateNonceValues($postId);
    wp_nonce_field($values['action'], $values['name']);
  }

  /**
   * Generates the nonce name and action for the given post id
   *
   * @param     int $postId The post id to bind the nonce to
   * @return    array           Associative array with the nonce 'name' and 'action'
   */
  public function generateNonceValues($postId = 0) {
    return array(
      'name' => $this->id . '_nonce',
      'action' => $this->id . '_edit_' . $postId
    );
  }

  /**
   * Determines current set and checks if it is a parent set
   * @return    bool
   * @todo      move somewhere else
   */
  public function currentSetIsParent() {
    global $post;
    return !(bool) $post->post_parent;
  }

  /** Registers meta box with add_meta_box */
  public function registerMetaBox() {
    add_meta_box(
      $this->id,
      $this->name,
      array($this, 'renderMetaBox'),
      self::POST_TYPE,
      $this->context,
      $this->priority
    );
  }

  /**
   * Retrieves the Set with the specified id or creates a new empty one
   * @param     string|int  $setId    The id of the set
   * @return    Set                   The Set instance
   */
  protected function getSet($setId) {
    $set = OpeningHours::getInstance()->getSet($setId);
    if ($set instanceof Set) {
      return $set;
    }

    return new Set($setId);
  }

  /**
   * Renders the meta box content
   *
   * @param     WP_Post $post The current post
   */
  abstract public function renderMetaBox(WP_Post $post);

  /**
   * Processes data when post ist updated or saved
   *
   * @param     int     $post_id The current post's id
   * @param     WP_Post $post    The current post
   * @param     bool    $update  Whether an existing post is updated (false if new post is created)
   */
  abstract protected function saveData($post_id, WP_Post $post, $update);
}
