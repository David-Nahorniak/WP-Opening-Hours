<?php

namespace OpeningHours\Module\Widget;

use OpeningHours\Fields\FieldRenderer;
use OpeningHours\Fields\WidgetFieldRenderer;
use OpeningHours\Module\Shortcode\AbstractShortcode as Shortcode;
use WP_Widget;
defined( 'ABSPATH' ) || exit;

/**
 * Abstraction for a Widget
 *
 * @author      Jannik Portz
 * @package     OpeningHours\Module\Widget
 */
abstract class AbstractWidget extends WP_Widget {
  /**
   * String with unique widget identifier
   * @var       string
   */
  protected $widgetId;

  /**
   * The Widget title
   * @var       string
   */
  protected $title;

  /**
   * Widget description for widget admin panel
   * @var       string
   */
  protected $description;

  /**
   * Singleton instance of shortcode
   * @var       Shortcode
   */
  protected $shortcode;

  /**
   * Associative array with:
   *  key:    string with field name
   *  value:  associative array w/ field options
   *
   * @var       array
   */
  protected $fields;

  /**
   * The FieldRenderer used to render the form fields
   * @var       FieldRenderer
   */
  protected $fieldRenderer;

  /**
   * AbstractWidget constructor.
   *
   * @param     string    $id          The widget id
   * @param     string    $title       The widget title
   * @param     string    $description The widget description
   * @param     Shortcode $shortcode   The shortcode singleton instance
   */
  public function __construct($id, $title, $description, Shortcode $shortcode) {
    $this->id = $id;
    $this->title = $title;
    $this->description = $description;
    $this->shortcode = $shortcode;
    $this->fields = array();
    $this->fieldRenderer = new WidgetFieldRenderer($this);
    $this->registerFields();

    parent::__construct($id, $title, array(
      'description' => $description
    ));
  }

  /**
   * Renders a single field from the collection
   *
   * @param     array $field    The field config array
   * @param     array $instance The current widget instance
   *
   * @return    string                The field markup
   */
  public function renderField(array $field, array $instance) {
    $value = array_key_exists($field['name'], $instance) ? $instance[$field['name']] : null;
    return $this->fieldRenderer->getFieldMarkup($field, $value);
  }

  /**
   * Sanitizes widget form input on save.
   *
   * Iterates over the registered fields and sanitizes each value according to its
   * field type, which also secures the widget→shortcode data path.
   *
   * @param     array $new_instance The new widget instance values
   * @param     array $old_instance The previous widget instance values
   * @return    array                       The sanitized widget instance
   */
  public function update($new_instance, $old_instance) {
    $instance = array();

    foreach ($this->fields as $name => $field) {
      $value = array_key_exists($name, $new_instance) ? $new_instance[$name] : null;
      $instance[$name] = $this->sanitizeFieldValue($field, $value, $old_instance);
    }

    return $instance;
  }

  /**
   * Sanitizes a single widget field value according to its field type.
   *
   * @param     array $field       The field configuration array
   * @param     mixed $value       The raw value submitted in the widget form
   * @param     array $old_instance The previous widget instance (used as fallback for selects)
   *
   * @return    mixed                The sanitized value
   */
  protected function sanitizeFieldValue(array $field, $value, array $old_instance) {
    $type = array_key_exists('type', $field) ? $field['type'] : 'text';

    switch ($type) {
      case 'checkbox':
        return !empty($value);

      case 'number':
        return is_numeric($value) ? $value + 0 : '';

      case 'select':
      case 'select-multi':
        $options = $this->resolveFieldOptions($field);

        if ($type === 'select-multi') {
          if (!is_array($value)) {
            return array();
          }

          $sanitized = array();
          foreach ($value as $key) {
            if (is_string($key) && array_key_exists($key, $options)) {
              $sanitized[] = $key;
            }
          }

          return $sanitized;
        }

        if (is_string($value) && array_key_exists($value, $options)) {
          return $value;
        }

        return '';

      case 'textarea':
        return is_string($value) ? sanitize_textarea_field($value) : '';

      case 'text':
      case 'time':
      case 'date':
      case 'email':
      case 'url':
      default:
        return is_string($value) ? sanitize_text_field($value) : '';
    }
  }

  /**
   * Resolves the available options for a field, invoking the options callback if present.
   *
   * @param     array $field The field configuration array
   * @return    array               Associative array of allowed option keys
   */
  protected function resolveFieldOptions(array $field) {
    if (array_key_exists('options_callback', $field) && is_callable($field['options_callback'])) {
      return (array) call_user_func($field['options_callback']);
    }

    return array_key_exists('options', $field) && is_array($field['options']) ? $field['options'] : array();
  }

  /**
   * Widget Function
   * Gets called by WordPress to render widget in front-end
   * Wrapper function for widgetContent()
   *
   * @param     array $args     The widget args including the sidebar args
   * @param     array $instance The current widget instance
   */
  public function widget($args, $instance) {
    $this->widgetContent($args, $instance);
  }

  /**
   * Widget Form Function
   * Gets called by WordPress to render widget form
   *
   * @param     array $instance The current widget instance to populate the fields with
   *
   * @return    void
   */
  public function form($instance) {
    $extended = array();

    ob_start();

    foreach ($this->fields as $field) {
      if (!array_key_exists('extended', $field) || $field['extended'] !== true) {
        echo $this->renderField($field, $instance);
      } else {
        $extended[] = $field;
      }
    }

    if (count($extended) < 1) {
      return;
    }

    echo '<div class="extended-settings">';
    echo '<p><a class="collapse-toggle">' . __('More Settings', 'wp-opening-hours') . '</a></p>';
    echo '<div class="settings-container hidden">';

    foreach ($extended as $field) {
      echo $this->renderField($field, $instance);
    }

    echo '</div>';
    echo '</div>';

    $markup = ob_get_contents();
    ob_end_clean();

    $filter_hook = 'op_widget_' . $this->widgetId . '_form_markup';
    echo apply_filters($filter_hook, $markup, $this);
  }

  /** Registers the Widget class in WordPress. Gets called in \OpeningHours\OpeningHours */
  public static function registerWidget() {
    register_widget(get_called_class());
  }

  /** Adds all fields for this Widget */
  abstract protected function registerFields();

  /**
   * Prints the widget content
   *
   * @param     array $args     The widget args including the sidebar args
   * @param     array $instance The current widget instance
   */
  protected function widgetContent(array $args, array $instance) {
    echo $this->shortcode->renderShortcode(array_merge($args, $instance));
  }

  /**
   * Returns string containing a link to more information on PHP date and time formats
   * @return      string
   */
  public static function getPhpDateFormatInfo() {
    return sprintf(
      '<a href="http://bit.ly/16Wsegh" target="blank">%s</a>',
      __('More about PHP date and time formats.', 'wp-opening-hours')
    );
  }

  /**
   * Getter: Widget Id
   * @return    string
   */
  public function getWidgetId() {
    return $this->widgetId;
  }

  /**
   * Getter: Title
   * @return    string
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Getter: Shortcode
   * @return    Shortcode
   */
  public function getShortcode() {
    return $this->shortcode;
  }

  /**
   * Adds a field to the collection
   *
   * @param     string $name    The field name
   * @param     array  $options The field options
   */
  public function addField($name, array $options) {
    $options['name'] = $name;
    $this->fields[$name] = $options;
  }

  /**
   * Getter: (single) Field
   *
   * @param     string $name The name to search for
   *
   * @return    array               The field options
   */
  public function getField($name) {
    return $this->fields[$name];
  }
}
