<?php
/**
 * @file
 * Provides \Drupal\xero\Plugin\DataType\Phone.
 */

namespace Drupal\xero\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Xero phone type
 *
 * @DataType(
 *   id = "xero_phone",
 *   label = @Translation("Xero Phone"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\PhoneDefinition"
 * )
 */
class Phone extends Map {

  /**
   * Get the canonical phone number.
   *
   * @return string
   *   The full phone number.
   */
  public function getPhone() {
    return $this->get('PhoneCountryCode')->getValue() . '-' . $this->get('PhoneAreaCode')->getValue() . '-' . $this->get('PhoneNumber')->getValue();
  }
}
