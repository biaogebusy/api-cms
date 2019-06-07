<?php

namespace Drupal\jsonapi_extras\Normalizer;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\jsonapi_extras\ResourceType\ConfigurableResourceType;

/**
 * Decorates the JSON:API ResourceObjectNormalizer.
 *
 * @internal
 */
class ResourceObjectNormalizer extends JsonApiNormalizerDecoratorBase {

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    assert($object instanceof ResourceObject);
    $resource_type = $object->getResourceType();
    $cacheable_normalization = parent::normalize($object, $format, $context);
    assert($cacheable_normalization instanceof CacheableNormalization);
    if (is_subclass_of($resource_type->getDeserializationTargetClass(), ConfigEntityInterface::class)) {
      return new CacheableNormalization(
        $cacheable_normalization,
        static::enhanceConfigFields($cacheable_normalization->getNormalization(), $resource_type)
      );
    }
    return $cacheable_normalization;
  }

  /**
   * Applies field enhancers to a config entity normalization.
   *
   * @param array $normalization
   *   The normalization to be enhanced.
   * @param \Drupal\jsonapi_extras\ResourceType\ConfigurableResourceType $resource_type
   *   The resource type of the normalized resource object.
   *
   * @return array
   *   The enhanced field data.
   */
  protected static function enhanceConfigFields(array $normalization, ConfigurableResourceType $resource_type) {
    if (!empty($normalization['attributes'])) {
      foreach ($normalization['attributes'] as $field_name => $field_value) {
        $enhancer = $resource_type->getFieldEnhancer($field_name);
        if (!$enhancer) {
          continue;
        }
        $normalization['attributes'][$field_name] = $enhancer->undoTransform($field_value);
      }
    }
    return $normalization;
  }

}
