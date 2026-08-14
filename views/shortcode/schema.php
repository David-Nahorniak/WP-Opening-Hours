<?php
defined( 'ABSPATH' ) || exit;

$attributes = $this->data['attributes'];

$schema = isset( $attributes['schema'] ) ? $attributes['schema'] : array();

/**
 * Variables defined by extraction
 *
 * @var       $schema     array   Associative array containing JSON-LD schema
 */
?>
<script type="application/ld+json">
  <?php echo json_encode($schema, JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>
</script>
